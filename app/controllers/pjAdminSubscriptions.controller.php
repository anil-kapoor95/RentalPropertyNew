<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Subscription plans & billing.
 *  Administrator only : Index (company subscriptions), Manage, Plans, Plan, Promos, Promo, Billing,
 *                       Gateways + GatewayOptions (Options > Payments: how companies pay)
 *  Company login (4)  : MyPlan, Pay, CheckoutReturn - always the company's own data only
 */
class pjAdminSubscriptions extends pjAdmin
{
	public function beforeFilter()
	{
		$res = parent::beforeFilter();
		$this->appendCss('subscriptions.css?v=' . @filemtime(PJ_INSTALL_PATH . 'app/web/css/subscriptions.css'));
		$this->appendJs('pjAdminSubscriptions.js?v=' . @filemtime(PJ_INSTALL_PATH . 'app/web/js/pjAdminSubscriptions.js'));
		return $res;
	}

	/* ------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	private function requireAdmin()
	{
		$this->checkLogin();
		if (!$this->isAdmin() || !pjEntitlement::isReady())
		{
			$this->sendForbidden();
			return false;
		}
		return true;
	}

	private function requireCompanyLogin()
	{
		$this->checkLogin();
		if ((int) $this->getRoleId() !== 4 || !pjAuth::factory('pjAdminSubscriptions', 'pjActionMyPlan')->hasAccess()
			|| !pjEntitlement::isReady() || $this->getCompanyId() <= 0)
		{
			$this->sendForbidden();
			return false;
		}
		return true;
	}

	private function csrfOk()
	{
		$t = $this->_post->toString('csrf_token');
		return !empty($_SESSION['csrf_token']) && is_string($t) && hash_equals($_SESSION['csrf_token'], $t);
	}

	/** Company of a public hash, never the Admin workspace. */
	private function companyFromHash($hash)
	{
		$cid = pjCompanyContext::idFromHash($hash);
		if ($cid <= 0 || pjCompanyContext::isAdminWorkspace($cid))
		{
			return false;
		}
		$c = pjCompanyModel::factory()->find($cid)->getData();
		return !empty($c) ? $c : false;
	}

	private function companies()
	{
		$m = pjCompanyModel::factory()->select('t1.id, t1.name, t1.email, t1.status');
		$aws = pjCompanyContext::adminWorkspaceId(false);
		if ($aws > 0)
		{
			$m->where('t1.id !=', $aws);
		}
		return $m->orderBy('t1.name ASC')->findAll()->getDataPair('id');
	}

	private function back($action, $msg, $extra = '')
	{
		pjUtil::redirect($_SERVER['PHP_SELF'] . '?controller=pjAdminSubscriptions&action=' . $action . $extra . '&msg=' . $msg);
	}

	private function transactions($company_id = 0, $limit = 200)
	{
		$m = pjBillingTransactionModel::factory()
			->select(sprintf('t1.*, (SELECT p.`name` FROM `%s` p WHERE p.`id` = t1.plan_id) AS `plan_name`, (SELECT c.`name` FROM `%s` c WHERE c.`id` = t1.company_id) AS `company_name`',
				pjPlanModel::factory()->getTable(), pjCompanyModel::factory()->getTable()));
		if ($company_id > 0)
		{
			$m->where('t1.company_id', (int) $company_id);
		}
		return $m->orderBy('t1.id DESC')->limit($limit)->findAll()->getData();
	}

	/* ------------------------------------------------------------------
	 * Administrator: company subscriptions
	 * ------------------------------------------------------------------ */

	public function pjActionIndex()
	{
		if (!$this->requireAdmin()) { return; }
		$rows = array();
		foreach ($this->companies() as $cid => $c)
		{
			$s = pjEntitlement::state($cid);
			$rows[] = array('company' => $c, 'hash' => pjCompanyContext::hashOf($cid), 'state' => $s, 'usage' => pjEntitlement::usage($cid));
		}
		$this->set('rows', $rows);
		$this->set('unassigned_mode', pjEntitlement::setting('unassigned_mode'));
	}

	public function pjActionManage()
	{
		if (!$this->requireAdmin()) { return; }
		$company = $this->companyFromHash(self::isPost() ? $this->_post->toString('company') : $this->_get->toString('id'));
		if (!$company)
		{
			$this->back('pjActionIndex', 'subErrCompany');
		}
		$cid = (int) $company['id'];
		$hash = pjCompanyContext::hashOf($cid);
		if (self::isPost())
		{
			if (!$this->csrfOk())
			{
				$this->back('pjActionManage', 'subErrCsrf', '&id=' . $hash);
			}
			$uid = $this->getUserId();
			$note = trim((string) $this->_post->toString('note'));
			$ok = false;
			switch ($this->_post->toString('do'))
			{
				case 'assign':
					$ok = pjEntitlement::assignPlan($cid, $this->_post->toInt('plan_id'), $this->_post->toString('status'), $this->_post->toString('period_end'), $uid, $note);
					break;
				case 'status':
					$ok = pjEntitlement::setStatus($cid, $this->_post->toString('status'), $uid, $note);
					break;
				case 'extend':
					$ok = pjEntitlement::extend($cid, $this->_post->toString('period_end'), $uid, $note);
					break;
				case 'schedule':
					$ok = pjEntitlement::scheduleChange($cid, $this->_post->toInt('plan_id'), $uid);
					break;
				case 'cancel_end':
					$ok = pjEntitlement::cancelAtPeriodEnd($cid, $this->_post->toInt('flag') === 1, $uid);
					break;
				case 'remove':
					// back to "no plan" (the history and payments are kept)
					$ok = pjEntitlement::removeSubscription($cid, $uid, $note);
					break;
				case 'confirm_txn':
					// A pending bank transfer / cash payment / payment request of this company has arrived.
					$txn = pjBillingTransactionModel::factory()->where('t1.uuid', (string) $this->_post->toString('txn'))
						->where('t1.company_id', $cid)->where('t1.status', 'pending')->limit(1)->findAll()->getDataIndex(0);
					$ref = trim((string) $this->_post->toString('reference'));
					if (!empty($txn) && !pjSubscriptionBilling::isOnline($txn['provider']) && $ref !== '')
					{
						if ((int) pjBillingTransactionModel::factory()->where('t1.provider', $txn['provider'])->where('t1.provider_ref', $ref)->findCount()->getData() > 0)
						{
							$this->back('pjActionManage', 'subErrDuplicateRef', '&id=' . $hash);
						}
						$ok = pjEntitlement::applyPayment($txn['id'], $ref, $txn['amount'], $txn['currency'], $uid) === 'applied';
					}
					break;
				case 'cancel_txn':
					$txn = pjBillingTransactionModel::factory()->where('t1.uuid', (string) $this->_post->toString('txn'))
						->where('t1.company_id', $cid)->where('t1.status', 'pending')->limit(1)->findAll()->getDataIndex(0);
					$ok = !empty($txn) && pjEntitlement::failPayment($txn['id'], 'cancelled', 'Cancelled by the administrator');
					break;
				case 'payment':
					// Payment received outside the system (bank transfer...): explicit administrator action.
					$plan = pjEntitlement::plan($this->_post->toInt('plan_id'));
					$amount = (float) str_replace(',', '.', (string) $this->_post->toString('amount'));
					$ref = trim((string) $this->_post->toString('reference'));
					if ($plan && $amount > 0 && $ref !== '')
					{
						if ((int) pjBillingTransactionModel::factory()->where('t1.provider', 'manual')->where('t1.provider_ref', $ref)->findCount()->getData() > 0)
						{
							$this->back('pjActionManage', 'subErrDuplicateRef', '&id=' . $hash);
						}
						$sub = pjEntitlement::subscription($cid);
						$kind = ($sub && (int) $sub['plan_id'] === (int) $plan['id']) ? 'renewal' : 'new';
						$plan['price'] = $amount; // the amount actually received
						$txn = pjEntitlement::createTransaction($cid, $plan, $kind, 'manual', $uid);
						$ok = $txn && pjEntitlement::applyPayment($txn['id'], $ref, $amount, $plan['currency'], $uid) === 'applied';
					}
					break;
			}
			$this->back('pjActionManage', $ok ? 'subSaved' : 'subErrSave', '&id=' . $hash);
		}
		$this->set('company', $company);
		$this->set('hash', $hash);
		$this->set('state', pjEntitlement::state($cid));
		$this->set('usage', pjEntitlement::usage($cid));
		$this->set('plans', pjEntitlement::plans());
		$this->set('transactions', $this->transactions($cid, 50));
		$this->set('events', pjSubscriptionEventModel::factory()->where('t1.company_id', $cid)->orderBy('t1.id DESC')->limit(50)->findAll()->getData());
	}

	/* ------------------------------------------------------------------
	 * Administrator: plans
	 * ------------------------------------------------------------------ */

	public function pjActionPlans()
	{
		if (!$this->requireAdmin()) { return; }
		$counts = pjCompanySubscriptionModel::factory()->select('t1.plan_id, COUNT(*) AS `cnt`')->groupBy('t1.plan_id')->findAll()->getDataPair('plan_id', 'cnt');
		$this->set('plans', pjEntitlement::plans());
		$this->set('counts', $counts);
	}

	public function pjActionPlan()
	{
		if (!$this->requireAdmin()) { return; }
		$id = self::isPost() ? $this->_post->toInt('id') : $this->_get->toInt('id');
		$plan = $id > 0 ? pjEntitlement::plan($id) : false;
		if ($id > 0 && !$plan)
		{
			$this->back('pjActionPlans', 'subErrSave');
		}
		if (self::isPost())
		{
			if (!$this->csrfOk())
			{
				$this->back('pjActionPlans', 'subErrCsrf');
			}
			if ($this->_post->toString('do') === 'delete' && $plan)
			{
				$used = (int) pjCompanySubscriptionModel::factory()->where(sprintf('(t1.plan_id = %1$u OR t1.pending_plan_id = %1$u)', $plan['id']))->findCount()->getData()
					+ (int) pjBillingTransactionModel::factory()->where('t1.plan_id', $plan['id'])->findCount()->getData();
				if ($used > 0)
				{
					$this->back('pjActionPlans', 'subErrPlanUsed');
				}
				pjPlanModel::factory()->set('id', $plan['id'])->erase();
				$this->back('pjActionPlans', 'subSaved');
			}
			$name = trim((string) $this->_post->toString('name'));
			$code = strtolower(trim((string) $this->_post->toString('code')));
			if ($name === '' || !preg_match('/^[a-z0-9_-]{2,32}$/', $code))
			{
				$this->back('pjActionPlan', 'subErrPlanFields', $plan ? '&id=' . $plan['id'] : '');
			}
			$dup = pjPlanModel::factory()->where('t1.code', $code);
			if ($plan) { $dup->where('t1.id !=', $plan['id']); }
			if ((int) $dup->findCount()->getData() > 0)
			{
				$this->back('pjActionPlan', 'subErrPlanCode', $plan ? '&id=' . $plan['id'] : '');
			}
			$num = function ($v) { $v = trim((string) $v); return $v === '' ? ':NULL' : max(0, (int) $v); };
			$price = trim(str_replace(',', '.', (string) $this->_post->toString('price')));
			$features = array_values(array_intersect(array_keys(pjEntitlement::$features), (array) $this->_post->toArray('features')));
			$currency = strtoupper(trim((string) $this->_post->toString('currency')));
			$data = array(
				'code' => $code, 'name' => $name,
				'description' => trim((string) $this->_post->toString('description')) !== '' ? trim((string) $this->_post->toString('description')) : ':NULL',
				'price' => $price === '' ? ':NULL' : number_format(max(0, (float) $price), 2, '.', ''),
				'currency' => preg_match('/^[A-Z]{3}$/', $currency) ? $currency : ':NULL',
				'billing_interval' => $this->_post->toString('billing_interval') === 'year' ? 'year' : 'month',
				'interval_count' => max(1, min(12, $this->_post->toInt('interval_count'))),
				'trial_days' => max(0, min(365, $this->_post->toInt('trial_days'))),
				'max_properties' => $num($this->_post->toString('max_properties')),
				'max_users' => $num($this->_post->toString('max_users')),
				'max_payment_gateways' => $num($this->_post->toString('max_payment_gateways')),
				'ai_monthly_messages' => $num($this->_post->toString('ai_monthly_messages')),
				'features' => implode(',', $features),
				'is_public' => $this->_post->check('is_public') ? 1 : 0,
				'status' => $this->_post->check('status') ? 'T' : 'F',
				'sort' => max(0, $this->_post->toInt('sort')),
				'modified' => date('Y-m-d H:i:s'),
			);
			if ($plan)
			{
				pjPlanModel::factory()->set('id', $plan['id'])->modify($data);
			} else {
				$data['created'] = date('Y-m-d H:i:s');
				pjPlanModel::factory()->setAttributes($data)->insert();
			}
			pjEntitlement::clearCache();
			$this->back('pjActionPlans', 'subSaved');
		}
		$this->set('plan', $plan);
	}

	/* ------------------------------------------------------------------
	 * Administrator: coupons & discounts on plan prices
	 * ------------------------------------------------------------------ */

	private function promoKind($kind)
	{
		return $kind === 'coupon' ? 'coupon' : 'discount';
	}

	public function pjActionPromos()
	{
		if (!$this->requireAdmin()) { return; }
		$kind = $this->promoKind($this->_get->toString('kind'));
		$plans = array();
		foreach (pjEntitlement::plans() as $p)
		{
			$plans[(int) $p['id']] = $p['name'];
		}
		$this->set('kind', $kind);
		$this->set('promos', pjSubscriptionPromos::promos($kind));
		$this->set('counts', array('discount' => count(pjSubscriptionPromos::promos('discount')), 'coupon' => count(pjSubscriptionPromos::promos('coupon'))));
		$this->set('uses', pjSubscriptionPromos::isReady() ? pjSubscriptionPromos::usesAll() : array());
		$this->set('plans', $plans);
	}

	public function pjActionPromo()
	{
		if (!$this->requireAdmin()) { return; }
		$id = self::isPost() ? $this->_post->toInt('id') : $this->_get->toInt('id');
		$promo = $id > 0 ? pjSubscriptionPromos::find($id) : false;
		$kind = $promo ? $promo['kind'] : $this->promoKind(self::isPost() ? $this->_post->toString('kind') : $this->_get->toString('kind'));
		if (($id > 0 && !$promo) || !pjSubscriptionPromos::isReady())
		{
			$this->back('pjActionPromos', 'subErrSave', '&kind=' . $kind);
		}
		if (self::isPost())
		{
			if (!$this->csrfOk())
			{
				$this->back('pjActionPromos', 'subErrCsrf', '&kind=' . $kind);
			}
			if ($this->_post->toString('do') === 'delete' && $promo)
			{
				pjSubscriptionPromoModel::factory()->set('id', $promo['id'])->erase();
				$this->back('pjActionPromos', 'subSaved', '&kind=' . $kind);
			}
			$again = '&kind=' . $kind . ($promo ? '&id=' . $promo['id'] : '');
			$name = trim((string) $this->_post->toString('name'));
			$type = $this->_post->toString('type') === 'amount' ? 'amount' : 'percent';
			$value = round((float) str_replace(',', '.', (string) $this->_post->toString('value')), 2);
			$code = $kind === 'coupon' ? pjSubscriptionPromos::normalizeCode($this->_post->toString('code')) : '';
			$date = function ($v) { $v = trim((string) $v); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : ':NULL'; };
			$num = function ($v) { $v = trim((string) $v); return $v === '' ? ':NULL' : max(0, (int) $v); };
			if ($name === '' || mb_strlen($name) > 100 || $value <= 0 || ($type === 'percent' && $value > 100)
				|| ($kind === 'coupon' && !pjSubscriptionPromos::isValidCode($code)))
			{
				$this->back('pjActionPromo', 'subErrPromoFields', $again);
			}
			if ($kind === 'coupon')
			{
				$dup = pjSubscriptionPromoModel::factory()->where('t1.code', $code);
				if ($promo) { $dup->where('t1.id !=', $promo['id']); }
				if ((int) $dup->findCount()->getData() > 0)
				{
					$this->back('pjActionPromo', 'subErrPromoCode', $again);
				}
			}
			$plan_ids = array();
			foreach (pjEntitlement::plans() as $p)
			{
				$plan_ids[] = (int) $p['id'];
			}
			$plan_ids = array_values(array_intersect($plan_ids, array_map('intval', (array) $this->_post->toArray('plan_ids'))));
			$kinds = array_values(array_intersect(pjSubscriptionPromos::$kinds, (array) $this->_post->toArray('kinds')));
			$currency = strtoupper(trim((string) $this->_post->toString('currency')));
			if (!preg_match('/^[A-Z]{3}$/', $currency))
			{
				$currency = strtoupper((string) pjEntitlement::setting('currency'));
			}
			$from = $date($this->_post->toString('date_from'));
			$to = $date($this->_post->toString('date_to'));
			if ($from !== ':NULL' && $to !== ':NULL' && $to < $from)
			{
				list($from, $to) = array($to, $from);
			}
			$data = array(
				'kind' => $kind, 'name' => $name, 'code' => $kind === 'coupon' ? $code : ':NULL',
				'type' => $type, 'value' => number_format($value, 2, '.', ''),
				'currency' => $type === 'amount' && $currency !== '' ? $currency : ':NULL',
				'plan_ids' => !empty($plan_ids) ? implode(',', $plan_ids) : ':NULL',
				'kinds' => !empty($kinds) ? implode(',', $kinds) : ':NULL',
				'date_from' => $from, 'date_to' => $to,
				'max_uses' => $num($this->_post->toString('max_uses')),
				'max_per_company' => $num($this->_post->toString('max_per_company')),
				'status' => $this->_post->check('status') ? 'T' : 'F',
				'modified' => date('Y-m-d H:i:s'),
			);
			if ($promo)
			{
				pjSubscriptionPromoModel::factory()->set('id', $promo['id'])->modify($data);
			} else {
				$data['created'] = date('Y-m-d H:i:s');
				pjSubscriptionPromoModel::factory()->setAttributes($data)->insert();
			}
			$this->back('pjActionPromos', 'subSaved', '&kind=' . $kind);
		}
		$this->set('kind', $kind);
		$this->set('promo', $promo);
		$this->set('plans', pjEntitlement::plans());
	}

	/* ------------------------------------------------------------------
	 * Administrator: billing history
	 * ------------------------------------------------------------------ */

	public function pjActionBilling()
	{
		if (!$this->requireAdmin()) { return; }
		$this->set('transactions', $this->transactions(0, 500));
	}

	/* ------------------------------------------------------------------
	 * Administrator: Options > Payments - gateways companies pay their plan with.
	 * Same gateways and settings forms as a property's Payments tab, stored under
	 * pjSubscriptionBilling::FOREIGN_ID (never a property).
	 * ------------------------------------------------------------------ */

	public function pjActionGateways()
	{
		if (!$this->requireAdmin()) { return; }
		$this->set('all_methods', pjSubscriptionBilling::allMethods());
		$this->set('active_methods', pjSubscriptionBilling::activeMethods());
		$currency = pjSubscriptionBilling::gatewayCurrency();
		$this->set('gateway_currency', $currency);
		$other = array();
		foreach (pjEntitlement::plans() as $p)
		{
			if ($p['price'] !== null && strtoupper((string) $p['currency']) !== $currency)
			{
				$other[] = $p['name'] . ' (' . $p['currency'] . ')';
			}
		}
		$this->set('currency_mismatch', $other);
	}

	public function pjActionGatewayOptions()
	{
		$this->setAjax(true);
		if (!$this->requireAdmin()) { return; }
		if (!pjSubscriptionBilling::hasPayments())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Payments plugin is not installed.'));
		}
		$fid = pjSubscriptionBilling::FOREIGN_ID;
		$method = (string) (self::isPost() ? $this->_post->toString('payment_method') : $this->_get->toString('payment_method'));
		$all = pjSubscriptionBilling::allMethods();
		if (!isset($all[$method]))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Unknown payment method.'));
		}
		if (self::isPost())
		{
			if (!$this->csrfOk())
			{
				self::jsonResponse(array('status' => 'ERR', 'code' => 403, 'text' => __('subErrCsrf', true)));
			}
			if (!in_array($method, pjEntitlement::$offlineMethods, true) && $this->_post->check('plugin_payment_options'))
			{
				// only the settings of the chosen gateway are accepted
				$data = (array) $this->_post->toArray('plugin_payment_options');
				$data = isset($data[$method]) && is_array($data[$method]) ? array($method => $data[$method]) : array();
				if (!empty($data))
				{
					$this->requestAction(array('controller' => 'pjPayments', 'action' => 'pjActionSaveOptions',
						'params' => array('foreign_id' => $fid, 'data' => $data)), array('return'));
				}
			}
			if (in_array($method, pjEntitlement::$offlineMethods, true))
			{
				$m = pjPaymentOptionModel::factory();
				$is_active = $this->_post->toInt('is_active') === 1 ? 1 : 0;
				if ((int) $m->reset()->where('foreign_id', $fid)->where('payment_method', $method)->findCount()->getData() === 0)
				{
					$m->reset()->setAttributes(array('foreign_id' => $fid, 'payment_method' => $method, 'is_active' => $is_active, 'type' => 'offline'))->insert();
				} else {
					$m->reset()->where('foreign_id', $fid)->where('payment_method', $method)->limit(1)->modifyAll(array('is_active' => $is_active));
				}
			}
			if ($this->_post->check('i18n'))
			{
				pjMultiLangModel::factory()->updateMultiLang($this->_post->toI18n('i18n'), $fid, 'pjPayment', 'data');
			}
			if ($method === 'bank' && $this->_post->check('i18n_options'))
			{
				pjMultiLangModel::factory()->updateMultiLang($this->_post->toI18n('i18n_options'), $fid, 'pjOption', 'data');
			}
			self::jsonResponse(array('status' => 'OK', 'text' => __('subSaved', true)));
		}
		$this->set('payment_method', $method);
		$this->set('foreign_id', $fid);
		$this->set('is_active', array_key_exists($method, pjSubscriptionBilling::activeMethods()));
		$this->set('i18n', pjMultiLangModel::factory()->getMultiLang($fid, 'pjPayment'));
		$this->set('i18n_options', pjMultiLangModel::factory()->getMultiLang($fid, 'pjOption'));
		$this->setLocalesData();
	}

	/* ------------------------------------------------------------------
	 * Company login: My plan
	 * ------------------------------------------------------------------ */

	public function pjActionMyPlan()
	{
		if (!$this->requireCompanyLogin()) { return; }
		$cid = $this->getCompanyId();
		$company = pjCompanyModel::factory()->find($cid)->getData();
		$state = pjEntitlement::state($cid);
		if (self::isPost())
		{
			if (!$this->csrfOk())
			{
				$this->back('pjActionMyPlan', 'subErrCsrf');
			}
			$uid = $this->getUserId();
			$plan = pjEntitlement::plan($this->_post->toInt('plan_id'));
			if ($plan && ($plan['status'] !== 'T' || (int) $plan['is_public'] !== 1))
			{
				$plan = false; // hidden / inactive plans are assigned by the administrator only
			}
			$sub = $state['subscription'];
			$cur = $state['plan'];
			switch ($this->_post->toString('do'))
			{
				case 'trial':
					$this->back('pjActionMyPlan', $plan && pjEntitlement::startTrial($cid, $plan['id'], $uid) ? 'subTrialStarted' : 'subErrTrial');
					break;
				case 'pay':
					// new plan / upgrade / renewal: always through a verified payment
					if (!$plan)
					{
						$this->back('pjActionMyPlan', 'subErrPlan');
					}
					$kind = $this->payKind($plan, $cur);
					// the administrator's discount / coupon (Subscriptions > Coupons & Discounts), worked out here
					$quote = pjEntitlement::isPurchasable($plan) ? pjSubscriptionPromos::quote($plan, $kind, $cid, $this->_post->toString('coupon_code')) : null;
					if ($quote && $quote['error'] !== '')
					{
						$this->back('pjActionMyPlan', $quote['error']);
					}
					if ($quote && !empty($quote['promo_ids']) && $quote['amount'] <= 0)
					{
						// nothing left to pay: the plan starts now
						$txn = pjEntitlement::createTransaction($cid, $plan, $kind, 'promo', $uid, $quote);
						$res = $txn ? pjEntitlement::applyPayment($txn['id'], $txn['uuid'], 0, $txn['currency'], $uid) : '';
						$this->back('pjActionMyPlan', $res === 'applied' ? 'subPaymentOk' : 'subErrCheckout');
					}
					if (pjEntitlement::isPurchasable($plan) && count(pjSubscriptionBilling::activeMethods()) === 0)
					{
						// no payment method switched on yet: leave a payment request for the administrator
						$txn = pjEntitlement::createTransaction($cid, $plan, $kind, 'manual', $uid, $quote);
						pjEntitlement::log($cid, 'payment_requested', 'Payment requested for plan ' . $plan['name'] . ($txn ? ' (' . $txn['amount'] . ' ' . $txn['currency'] . ')' : ''), $uid);
						$this->back('pjActionMyPlan', 'subRequestSent');
					}
					$res = pjSubscriptionBilling::checkout($company, $plan, $kind, (string) $this->_post->toString('method'), $uid, $quote);
					if (isset($res['txn']))
					{
						pjUtil::redirect($_SERVER['PHP_SELF'] . '?controller=pjAdminSubscriptions&action=pjActionPay&txn=' . $res['txn']['uuid']);
					}
					$this->back('pjActionMyPlan', $res['error']);
					break;
				case 'downgrade':
					// takes effect at the end of the paid period (no refund, no charge now)
					if ($plan && $sub && $cur && $state['entitled'] && (int) $plan['sort'] < (int) $cur['sort'])
					{
						pjEntitlement::scheduleChange($cid, $plan['id'], $uid);
						$this->back('pjActionMyPlan', 'subDowngradeScheduled');
					}
					$this->back('pjActionMyPlan', 'subErrPlan');
					break;
				case 'undo_downgrade':
					if ($sub) { pjEntitlement::scheduleChange($cid, null, $uid); }
					$this->back('pjActionMyPlan', 'subSaved');
					break;
				case 'cancel':
					if ($sub && $state['entitled']) { pjEntitlement::cancelAtPeriodEnd($cid, true, $uid); }
					$this->back('pjActionMyPlan', 'subCancelScheduled');
					break;
				case 'undo_cancel':
					if ($sub) { pjEntitlement::cancelAtPeriodEnd($cid, false, $uid); }
					$this->back('pjActionMyPlan', 'subSaved');
					break;
			}
			$this->back('pjActionMyPlan', 'subErrSave');
		}
		$this->set('company', $company);
		$this->set('state', $state);
		$this->set('usage', pjEntitlement::usage($cid));
		$plans = pjEntitlement::plans(true);
		$quotes = array();
		foreach ($plans as $p)
		{
			if (pjEntitlement::isPurchasable($p))
			{
				$quotes[(int) $p['id']] = pjSubscriptionPromos::quote($p, $this->payKind($p, $state['plan']), $cid);
			}
		}
		$this->set('plans', $plans);
		$this->set('quotes', $quotes);
		$this->set('transactions', $this->transactions($cid, 50));
		$this->set('methods', pjSubscriptionBilling::activeMethods());
		$this->set('self_trial', pjEntitlement::setting('self_trial') === '1');
	}

	/** What buying the plan is for the company: renewal of its plan, upgrade or a new plan. */
	private function payKind($plan, $cur)
	{
		return ($cur && (int) $cur['id'] === (int) $plan['id']) ? 'renewal' : (($cur && (int) $plan['sort'] > (int) $cur['sort']) ? 'upgrade' : 'new');
	}

	/** My plan (Ajax): the price of a plan with the discount and the coupon code typed in the payment popup. */
	public function pjActionCheckPromo()
	{
		$this->setAjax(true);
		if (!$this->requireCompanyLogin()) { exit; }
		$cid = $this->getCompanyId();
		$plan = pjEntitlement::plan($this->_get->toInt('plan_id'));
		if (!$plan || $plan['status'] !== 'T' || (int) $plan['is_public'] !== 1 || !pjEntitlement::isPurchasable($plan))
		{
			self::jsonResponse(array('status' => 'ERR', 'text' => __('subErrPlan', true)));
		}
		$state = pjEntitlement::state($cid);
		$q = pjSubscriptionPromos::quote($plan, $this->payKind($plan, $state['plan']), $cid, $this->_get->toString('code'));
		$money = function ($v) use ($plan) { return number_format((float) $v, 2) . ' ' . $plan['currency']; };
		self::jsonResponse(array(
			'status' => $q['error'] === '' ? 'OK' : 'ERR',
			'text' => $q['error'] !== '' ? __($q['error'], true) : ($q['coupon_code'] !== '' ? __('sub_coupon_ok', true) : ''),
			'list' => $money($q['list']),
			'discount' => $q['discount'] > 0 ? '- ' . $money($q['discount']) : '',
			'amount' => $money($q['amount']),
		));
	}

	/** Company's own pending payment: gateway form, or bank / cash instructions. */
	public function pjActionPay()
	{
		if (!$this->requireCompanyLogin()) { return; }
		$cid = $this->getCompanyId();
		$txn = pjBillingTransactionModel::factory()->where('t1.uuid', (string) $this->_get->toString('txn'))
			->where('t1.company_id', $cid)->limit(1)->findAll()->getDataIndex(0);
		if (empty($txn))
		{
			$this->back('pjActionMyPlan', 'subErrPlan');
		}
		if ($txn['status'] !== 'pending')
		{
			$this->back('pjActionMyPlan', $txn['status'] === 'paid' ? 'subPaymentOk' : 'subPaymentCancelled');
		}
		$company = pjCompanyModel::factory()->find($cid)->getData();
		$params = false;
		if (pjSubscriptionBilling::isOnline($txn['provider']))
		{
			$active = pjSubscriptionBilling::activeMethods();
			$params = isset($active[$txn['provider']]) ? pjSubscriptionBilling::formParams($txn, $company, $this->getLocaleId()) : false;
			if (!$params)
			{
				pjEntitlement::failPayment($txn['id'], 'cancelled', 'Payment method no longer available');
				$this->back('pjActionMyPlan', 'subErrMethod');
			}
		}
		$this->set('txn', $txn);
		$this->set('plan', pjEntitlement::plan($txn['plan_id']));
		$this->set('params', $params);
		$this->set('bank_account', $txn['provider'] === 'bank' ? pjSubscriptionBilling::bankAccount($this->getLocaleId()) : '');
	}

	/**
	 * Back from the payment page. Only shows the result: the payment is applied when the
	 * gateway confirms it (pjSubscriptionBilling::confirmFromGateway), never from this URL.
	 */
	public function pjActionCheckoutReturn()
	{
		if (!$this->requireCompanyLogin()) { return; }
		$txn = pjBillingTransactionModel::factory()->where('t1.uuid', (string) $this->_get->toString('txn'))
			->where('t1.company_id', $this->getCompanyId())->limit(1)->findAll()->getDataIndex(0);
		if (empty($txn))
		{
			$this->back('pjActionMyPlan', 'subErrPlan');
		}
		$map = array('paid' => 'subPaymentOk', 'cancelled' => 'subPaymentCancelled', 'failed' => 'subPaymentFailed');
		$this->back('pjActionMyPlan', isset($map[$txn['status']]) ? $map[$txn['status']] : 'subPaymentPending');
	}
}
?>
