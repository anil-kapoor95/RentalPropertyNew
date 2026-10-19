<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Subscription plans: the ONE place that decides what a company may do.
 *
 *   pjEntitlement::allows($company_id, 'coupons')        feature switch
 *   pjEntitlement::limit($company_id, 'max_properties')  null = unlimited
 *   pjEntitlement::canAddProperty($company_id)           limit check incl. usage
 *   pjEntitlement::state($company_id)                    plan, effective status, features, limits
 *
 * Rules
 * - The Admin workspace (and "no company") is never limited.
 * - A company without a subscription follows the "unassigned_mode" setting
 *   ('full' = unchanged behaviour for existing companies, 'restricted' = no paid features).
 * - Entitled statuses: trial (until trial end), active (until period end),
 *   past_due (period end + grace days). Afterwards the company is expired / cancelled:
 *   only the basic features stay (existing data keeps working, nothing new is unlocked).
 * - Status is evaluated from the dates at every request, so expiry is exact even
 *   before the cron job writes the new status.
 */
class pjEntitlement
{
	/** Feature catalogue (key => label key). */
	public static $features = array(
		'website'                => 'sub_f_website',
		'properties'             => 'sub_f_properties',
		'availability'           => 'sub_f_availability',
		'ical_sync'              => 'sub_f_ical_sync',
		'social_sharing'         => 'sub_f_social_sharing',
		'email_notifications'    => 'sub_f_email_notifications',
		'payment_gateway'        => 'sub_f_payment_gateway',
		'custom_domain'          => 'sub_f_custom_domain',
		'themes'                 => 'sub_f_themes',
		'payment_gateway_choice' => 'sub_f_payment_gateway_choice',
		'coupons'                => 'sub_f_coupons',
		'booking_policies'       => 'sub_f_booking_policies',
		'customers'              => 'sub_f_customers',
		'analytics'              => 'sub_f_analytics',
		'seo'                    => 'sub_f_seo',
		'calendar_notes'         => 'sub_f_calendar_notes',
		'ai_chatbot'             => 'sub_f_ai_chatbot',
	);

	/** Features kept by an expired / cancelled / restricted company (existing data keeps working). */
	public static $baseFeatures = array('website', 'properties', 'availability', 'ical_sync', 'social_sharing', 'email_notifications', 'payment_gateway');

	/** Numeric limits (null = unlimited). */
	public static $limits = array('max_properties', 'max_users', 'max_payment_gateways', 'ai_monthly_messages');

	public static $statuses = array('trial', 'active', 'past_due', 'cancelled', 'expired');

	/** Offline payment methods do not count as "payment gateways". */
	public static $offlineMethods = array('cash', 'bank');

	private static $settings = null;
	private static $stateCache = array();
	private static $ready = null;

	/* ------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------ */

	public static $defaults = array(
		'currency' => 'USD', 'grace_days' => '7',
		'unassigned_mode' => 'full', 'expired_mode' => 'read_only', 'self_trial' => '0',
	);

	/** The update that creates the subscription tables has run. */
	public static function isReady()
	{
		if (self::$ready === null)
		{
			self::$ready = false;
			// (pjModel::exec refuses statements with quotes: list the tables and look for ours)
			$m = pjSubscriptionSettingModel::factory();
			$table = $m->getTable();
			foreach ($m->prepare("SHOW TABLES")->exec()->getData() as $row)
			{
				if (in_array($table, array_values($row), true))
				{
					self::$ready = true;
					break;
				}
			}
		}
		return self::$ready;
	}

	public static function settings()
	{
		if (self::$settings === null)
		{
			self::$settings = self::$defaults;
			if (self::isReady())
			{
				foreach (pjSubscriptionSettingModel::factory()->findAll()->getData() as $row)
				{
					self::$settings[$row['key']] = (string) $row['value'];
				}
			}
		}
		return self::$settings;
	}

	public static function setting($key)
	{
		$s = self::settings();
		return isset($s[$key]) ? $s[$key] : null;
	}

	public static function saveSetting($key, $value)
	{
		if (!array_key_exists($key, self::$defaults))
		{
			return false;
		}
		$m = pjSubscriptionSettingModel::factory();
		if ((int) $m->where('t1.key', $key)->findCount()->getData() > 0)
		{
			$m->reset()->where('`key`', $key)->limit(1)->modifyAll(array('value' => (string) $value));
		} else {
			$m->reset()->setAttributes(array('key' => $key, 'value' => (string) $value))->insert();
		}
		self::$settings = null;
		return true;
	}

	/* ------------------------------------------------------------------
	 * Plans
	 * ------------------------------------------------------------------ */

	public static function plans($only_active = false)
	{
		if (!self::isReady())
		{
			return array();
		}
		$m = pjPlanModel::factory();
		if ($only_active)
		{
			$m->where('t1.status', 'T');
		}
		$arr = array();
		foreach ($m->orderBy('t1.sort ASC, t1.id ASC')->findAll()->getData() as $row)
		{
			$arr[(int) $row['id']] = self::normalizePlan($row);
		}
		return $arr;
	}

	public static function plan($plan_id)
	{
		$row = pjPlanModel::factory()->find((int) $plan_id)->getData();
		return !empty($row) ? self::normalizePlan($row) : false;
	}

	public static function planByCode($code)
	{
		$row = pjPlanModel::factory()->where('t1.code', (string) $code)->limit(1)->findAll()->getDataIndex(0);
		return !empty($row) ? self::normalizePlan($row) : false;
	}

	private static function normalizePlan($row)
	{
		$row['feature_list'] = array_values(array_intersect(array_keys(self::$features), array_map('trim', explode(',', (string) $row['features']))));
		foreach (self::$limits as $l)
		{
			$row[$l] = ($row[$l] === null || $row[$l] === '') ? null : (int) $row[$l];
		}
		$row['price'] = ($row['price'] === null || $row['price'] === '') ? null : (float) $row['price'];
		$row['currency'] = !empty($row['currency']) ? strtoupper($row['currency']) : self::setting('currency');
		return $row;
	}

	/** A plan can be bought online only when it is active, public and has a price > 0. */
	public static function isPurchasable($plan)
	{
		return !empty($plan) && $plan['status'] === 'T' && (int) $plan['is_public'] === 1 && $plan['price'] !== null && $plan['price'] > 0;
	}

	/** End of one billing period starting at $from (Y-m-d H:i:s). */
	public static function addPeriod($from, $plan)
	{
		$n = max(1, (int) $plan['interval_count']);
		$unit = $plan['billing_interval'] === 'year' ? 'year' : 'month';
		return date('Y-m-d H:i:s', strtotime(sprintf('+%d %s', $n, $unit), strtotime($from)));
	}

	/* ------------------------------------------------------------------
	 * State of a company
	 * ------------------------------------------------------------------ */

	public static function subscription($company_id)
	{
		if (!self::isReady() || (int) $company_id <= 0)
		{
			return false;
		}
		$row = pjCompanySubscriptionModel::factory()->where('t1.company_id', (int) $company_id)->limit(1)->findAll()->getDataIndex(0);
		return !empty($row) ? $row : false;
	}

	/**
	 * Effective status of a subscription row at a given time (dates win over the stored status).
	 * Returns array(status, entitled, grace_ends_at).
	 */
	public static function effectiveStatus($sub, $now = null)
	{
		$now = $now === null ? time() : (int) $now;
		$grace = max(0, (int) self::setting('grace_days')) * 86400;
		$status = $sub['status'];
		$end = !empty($sub['current_period_end']) ? strtotime($sub['current_period_end']) : null;
		$grace_end = $end !== null ? $end + $grace : null;
		switch ($status)
		{
			case 'trial':
				$t = !empty($sub['trial_ends_at']) ? strtotime($sub['trial_ends_at']) : null;
				if ($t === null || $now <= $t) { return array('trial', true, null); }
				return array('expired', false, null);
			case 'active':
				if ($end === null || $now <= $end) { return array('active', true, $grace_end); }
				if ((int) $sub['cancel_at_period_end'] === 1) { return array('cancelled', false, null); }
				if ($now <= $grace_end) { return array('past_due', true, $grace_end); }
				return array('expired', false, $grace_end);
			case 'past_due':
				if ($grace_end === null || $now <= $grace_end) { return array('past_due', true, $grace_end); }
				return array('expired', false, $grace_end);
			case 'cancelled':
				// cancelled by the administrator: ends now; requested by the company: at period end
				if ($end !== null && $now <= $end && (int) $sub['cancel_at_period_end'] === 1) { return array('active', true, null); }
				return array('cancelled', false, null);
			default:
				return array('expired', false, null);
		}
	}

	/**
	 * Everything about one company.
	 * array(source => admin|unassigned|plan, plan, subscription, status, entitled, features[], limits[])
	 */
	public static function state($company_id)
	{
		$company_id = (int) $company_id;
		if (isset(self::$stateCache[$company_id]))
		{
			return self::$stateCache[$company_id];
		}
		$all = array_keys(self::$features);
		$unlimited = array_fill_keys(self::$limits, null);
		$state = array('source' => 'admin', 'plan' => false, 'subscription' => false, 'status' => null,
			'entitled' => true, 'grace_ends_at' => null, 'features' => $all, 'limits' => $unlimited);

		if ($company_id > 0 && self::isReady() && !(class_exists('pjCompanyContext') && pjCompanyContext::isAdminWorkspace($company_id)))
		{
			$sub = self::subscription($company_id);
			if (!$sub)
			{
				$state['source'] = 'unassigned';
				if (self::setting('unassigned_mode') === 'restricted')
				{
					$state['entitled'] = false;
					$state['features'] = self::$baseFeatures;
					$state['limits'] = array('max_properties' => 0, 'max_users' => 0, 'max_payment_gateways' => 0, 'ai_monthly_messages' => 0);
				}
			} else {
				$plan = self::plan($sub['plan_id']);
				list($status, $entitled, $grace_end) = self::effectiveStatus($sub);
				$state['source'] = 'plan';
				$state['plan'] = $plan;
				$state['subscription'] = $sub;
				$state['status'] = $status;
				$state['entitled'] = $entitled && !empty($plan);
				$state['grace_ends_at'] = $grace_end ? date('Y-m-d H:i:s', $grace_end) : null;
				if ($state['entitled'])
				{
					$state['features'] = $plan['feature_list'];
					$state['limits'] = array();
					foreach (self::$limits as $l)
					{
						$state['limits'][$l] = $plan[$l];
					}
				} else {
					// expired / cancelled: existing data keeps working, nothing new
					$state['features'] = self::$baseFeatures;
					$state['limits'] = array('max_properties' => 0, 'max_users' => 0, 'max_payment_gateways' => 0, 'ai_monthly_messages' => 0);
				}
			}
		}
		return self::$stateCache[$company_id] = $state;
	}

	public static function clearCache()
	{
		self::$stateCache = array();
	}

	public static function allows($company_id, $feature)
	{
		$s = self::state($company_id);
		return in_array($feature, $s['features'], true);
	}

	/** null = unlimited */
	public static function limit($company_id, $key)
	{
		$s = self::state($company_id);
		return array_key_exists($key, $s['limits']) ? $s['limits'][$key] : null;
	}

	/** New front-end bookings are refused (expired / cancelled with "suspend" mode). */
	public static function frontSuspended($company_id)
	{
		$s = self::state($company_id);
		return $s['source'] === 'plan' && !$s['entitled'] && self::setting('expired_mode') === 'suspend';
	}

	/* ------------------------------------------------------------------
	 * Usage
	 * ------------------------------------------------------------------ */

	public static function usage($company_id)
	{
		$company_id = (int) $company_id;
		return array(
			'properties' => (int) pjCalendarModel::factory()->where('t1.company_id', $company_id)->findCount()->getData(),
			'users'      => (int) pjAuthUserModel::factory()->where('t1.company_id', $company_id)->where('t1.role_id', 5)->findCount()->getData(),
		);
	}

	public static function canAddProperty($company_id)
	{
		$max = self::limit($company_id, 'max_properties');
		if ($max === null)
		{
			return true;
		}
		$u = self::usage($company_id);
		return $u['properties'] < $max;
	}

	public static function canAddUser($company_id)
	{
		$max = self::limit($company_id, 'max_users');
		if ($max === null)
		{
			return true;
		}
		$u = self::usage($company_id);
		return $u['users'] < $max;
	}

	/** Online payment gateways that are switched on for a property. */
	public static function activeGateways($calendar_id)
	{
		if (!class_exists('pjPaymentOptionModel'))
		{
			return array();
		}
		return array_values(array_diff(
			pjPaymentOptionModel::factory()->where('t1.foreign_id', (int) $calendar_id)->where('t1.is_active', 1)->findAll()->getDataPair(null, 'payment_method'),
			self::$offlineMethods
		));
	}

	/* ------------------------------------------------------------------
	 * Changes (each one is logged)
	 * ------------------------------------------------------------------ */

	public static function log($company_id, $type, $message, $user_id = null)
	{
		if (!self::isReady())
		{
			return;
		}
		pjSubscriptionEventModel::factory()->setAttributes(array(
			'company_id' => (int) $company_id, 'type' => substr((string) $type, 0, 40),
			'message' => substr((string) $message, 0, 500), 'user_id' => $user_id ? (int) $user_id : ':NULL',
			'created' => date('Y-m-d H:i:s'),
		))->insert();
	}

	/** Create or replace the company's subscription row. */
	public static function saveSubscription($company_id, $data)
	{
		$company_id = (int) $company_id;
		$data['modified'] = date('Y-m-d H:i:s');
		$m = pjCompanySubscriptionModel::factory();
		$sub = self::subscription($company_id);
		if ($sub)
		{
			$m->set('id', $sub['id'])->modify($data);
		} else {
			$data['company_id'] = $company_id;
			$data['created'] = date('Y-m-d H:i:s');
			$m->setAttributes($data)->insert();
		}
		self::clearCache();
		return self::subscription($company_id);
	}

	/**
	 * Administrator assigns a plan (explicit, authorised action; no payment involved).
	 * $status trial|active, $period_end Y-m-d (empty: one billing period / the trial length).
	 */
	public static function assignPlan($company_id, $plan_id, $status, $period_end, $user_id, $note = '')
	{
		$plan = self::plan($plan_id);
		if (!$plan || !in_array($status, array('trial', 'active'), true))
		{
			return false;
		}
		$now = date('Y-m-d H:i:s');
		$data = array('plan_id' => (int) $plan_id, 'status' => $status, 'started_at' => $now,
			'cancel_at_period_end' => 0, 'cancelled_at' => ':NULL', 'pending_plan_id' => ':NULL', 'current_period_start' => $now);
		if ($status === 'trial')
		{
			$end = $period_end ? date('Y-m-d 23:59:59', strtotime($period_end)) : date('Y-m-d H:i:s', strtotime('+' . max(1, (int) $plan['trial_days']) . ' days'));
			$data['trial_ends_at'] = $end;
			$data['current_period_end'] = $end;
			$data['used_trial'] = 1;
		} else {
			$data['trial_ends_at'] = ':NULL';
			$data['current_period_end'] = $period_end ? date('Y-m-d 23:59:59', strtotime($period_end)) : self::addPeriod($now, $plan);
		}
		if ($note !== '')
		{
			$data['notes'] = $note;
		}
		self::saveSubscription($company_id, $data);
		self::log($company_id, 'assigned', sprintf('Plan %s assigned (%s) until %s%s', $plan['name'], $status, $data['current_period_end'], $note !== '' ? ' - ' . $note : ''), $user_id);
		return true;
	}

	/** Administrator changes the status (cancelled / expired end access at once). */
	public static function setStatus($company_id, $status, $user_id, $note = '')
	{
		$sub = self::subscription($company_id);
		if (!$sub || !in_array($status, self::$statuses, true))
		{
			return false;
		}
		$data = array('status' => $status);
		if ($status === 'cancelled')
		{
			$data['cancelled_at'] = date('Y-m-d H:i:s');
			$data['cancel_at_period_end'] = 0;
		}
		if (in_array($status, array('active', 'past_due'), true))
		{
			$data['cancel_at_period_end'] = 0;
			$data['cancelled_at'] = ':NULL';
		}
		self::saveSubscription($company_id, $data);
		self::log($company_id, 'status', sprintf('Status %s -> %s%s', $sub['status'], $status, $note !== '' ? ' - ' . $note : ''), $user_id);
		return true;
	}

	/** Administrator moves the end date (renewal date). */
	public static function extend($company_id, $period_end, $user_id, $note = '')
	{
		$sub = self::subscription($company_id);
		if (!$sub || !$period_end || strtotime($period_end) === false)
		{
			return false;
		}
		$end = date('Y-m-d 23:59:59', strtotime($period_end));
		$data = array('current_period_end' => $end);
		if ($sub['status'] === 'trial')
		{
			$data['trial_ends_at'] = $end;
		} elseif (strtotime($end) > time() && in_array($sub['status'], array('past_due', 'expired'), true)) {
			$data['status'] = 'active';
		}
		self::saveSubscription($company_id, $data);
		self::log($company_id, 'extended', 'End date set to ' . $end . ($note !== '' ? ' - ' . $note : ''), $user_id);
		return true;
	}

	/** Plan change at the end of the period (downgrade). null clears it. */
	public static function scheduleChange($company_id, $plan_id, $user_id)
	{
		$sub = self::subscription($company_id);
		if (!$sub)
		{
			return false;
		}
		$plan = $plan_id ? self::plan($plan_id) : null;
		self::saveSubscription($company_id, array('pending_plan_id' => $plan ? (int) $plan['id'] : ':NULL'));
		self::log($company_id, 'scheduled', $plan ? 'At the end of the period the plan changes to ' . $plan['name'] : 'Scheduled plan change removed', $user_id);
		return true;
	}

	/** Cancel at the end of the paid period (or undo). */
	public static function cancelAtPeriodEnd($company_id, $flag, $user_id)
	{
		$sub = self::subscription($company_id);
		if (!$sub)
		{
			return false;
		}
		self::saveSubscription($company_id, array('cancel_at_period_end' => $flag ? 1 : 0));
		self::log($company_id, $flag ? 'cancel_requested' : 'cancel_undone', $flag ? 'Cancels at the end of the period (' . $sub['current_period_end'] . ')' : 'Cancellation withdrawn', $user_id);
		return true;
	}

	/** Administrator removes the subscription: the company is "without a plan" again (history kept). */
	public static function removeSubscription($company_id, $user_id, $note = '')
	{
		$sub = self::subscription($company_id);
		if (!$sub)
		{
			return false;
		}
		pjCompanySubscriptionModel::factory()->set('id', $sub['id'])->erase();
		self::clearCache();
		self::log($company_id, 'removed', 'Subscription removed' . ($note !== '' ? ' - ' . $note : ''), $user_id);
		return true;
	}

	/** Company starts the free trial itself (only when allowed in the settings, once per company). */
	public static function startTrial($company_id, $plan_id, $user_id)
	{
		$plan = self::plan($plan_id);
		$sub = self::subscription($company_id);
		if (self::setting('self_trial') !== '1' || !$plan || $plan['status'] !== 'T' || (int) $plan['trial_days'] <= 0 || ($sub && (int) $sub['used_trial'] === 1))
		{
			return false;
		}
		return self::assignPlan($company_id, $plan_id, 'trial', '', $user_id, 'Trial started by the company');
	}

	/* ------------------------------------------------------------------
	 * Payments
	 * ------------------------------------------------------------------ */

	/**
	 * Apply a PAID transaction to the subscription. Idempotent: a transaction is applied
	 * once (row lock + status check inside one database transaction).
	 * Returns 'applied' | 'duplicate' | error text.
	 */
	public static function applyPayment($txn_id, $provider_ref, $amount, $currency, $user_id = null)
	{
		$m = pjBillingTransactionModel::factory();
		$m->begin();
		$locked = $m->reset()->prepare(sprintf("SELECT `id`, `status` FROM `%s` WHERE `id` = %u FOR UPDATE", $m->getTable(), (int) $txn_id))->exec()->getData();
		$txn = pjBillingTransactionModel::factory()->find((int) $txn_id)->getData();
		if (empty($locked) || empty($txn))
		{
			$m->rollback();
			return 'Transaction not found';
		}
		if ($txn['status'] === 'paid')
		{
			$m->rollback();
			return 'duplicate';
		}
		if ($txn['status'] !== 'pending')
		{
			$m->rollback();
			return 'Transaction is ' . $txn['status'];
		}
		if (round((float) $amount, 2) !== round((float) $txn['amount'], 2) || strtoupper((string) $currency) !== strtoupper($txn['currency']))
		{
			$m->rollback();
			return sprintf('Amount mismatch (%s %s, expected %s %s)', $amount, $currency, $txn['amount'], $txn['currency']);
		}
		$plan = self::plan($txn['plan_id']);
		if (!$plan)
		{
			$m->rollback();
			return 'Plan not found';
		}
		$cid = (int) $txn['company_id'];
		$sub = self::subscription($cid);
		$now = date('Y-m-d H:i:s');
		// Renewal of the same plan continues the paid period; anything else starts now.
		$start = $now;
		if ($sub && $txn['kind'] === 'renewal' && (int) $sub['plan_id'] === (int) $plan['id'] && !empty($sub['current_period_end'])
			&& in_array($sub['status'], array('active', 'past_due'), true) && strtotime($sub['current_period_end']) > time())
		{
			$start = $sub['current_period_end'];
		}
		$end = self::addPeriod($start, $plan);

		$m->reset()->set('id', $txn['id'])->modify(array(
			'status' => 'paid', 'paid_at' => $now, 'provider_ref' => $provider_ref !== '' ? $provider_ref : ':NULL',
			'period_start' => $start, 'period_end' => $end, 'modified' => $now,
		));
		$data = array('plan_id' => (int) $plan['id'], 'status' => 'active', 'current_period_start' => $start,
			'current_period_end' => $end, 'trial_ends_at' => ':NULL', 'cancel_at_period_end' => 0, 'cancelled_at' => ':NULL');
		if (!$sub || empty($sub['started_at']))
		{
			$data['started_at'] = $now;
		}
		if ($sub && (int) $sub['pending_plan_id'] === (int) $plan['id'])
		{
			$data['pending_plan_id'] = ':NULL';
		}
		self::saveSubscription($cid, $data);
		$m->commit();
		self::log($cid, 'payment', sprintf('Payment %s %s (%s, %s) - plan %s active until %s', $txn['amount'], $txn['currency'], $txn['provider'], $provider_ref, $plan['name'], $end), $user_id);
		return 'applied';
	}

	/** Mark a pending transaction failed / cancelled (the subscription itself is not changed). */
	public static function failPayment($txn_id, $status, $reason)
	{
		$m = pjBillingTransactionModel::factory();
		$txn = $m->find((int) $txn_id)->getData();
		if (empty($txn) || $txn['status'] !== 'pending' || !in_array($status, array('failed', 'cancelled'), true))
		{
			return false;
		}
		$m->reset()->set('id', $txn['id'])->modify(array('status' => $status, 'failure_reason' => substr((string) $reason, 0, 255), 'modified' => date('Y-m-d H:i:s')));
		self::log($txn['company_id'], 'payment_' . $status, sprintf('Payment %s %s %s: %s', $txn['amount'], $txn['currency'], $status, $reason));
		return true;
	}

	/** New pending transaction (amount always from the plan, never from the request). */
	public static function createTransaction($company_id, $plan, $kind, $provider, $user_id)
	{
		$uuid = bin2hex(function_exists('random_bytes') ? random_bytes(16) : md5(uniqid(mt_rand(), true), true));
		$id = pjBillingTransactionModel::factory()->setAttributes(array(
			'uuid' => $uuid, 'company_id' => (int) $company_id, 'plan_id' => (int) $plan['id'], 'kind' => $kind,
			'amount' => number_format((float) $plan['price'], 2, '.', ''), 'currency' => $plan['currency'],
			'status' => 'pending', 'provider' => $provider, 'created_by' => $user_id ? (int) $user_id : ':NULL',
			'created' => date('Y-m-d H:i:s'),
		))->insert()->getInsertId();
		return ($id !== false && (int) $id > 0) ? pjBillingTransactionModel::factory()->find($id)->getData() : false;
	}

	/* ------------------------------------------------------------------
	 * Cron: write the time-based status changes
	 * ------------------------------------------------------------------ */

	public static function runTransitions()
	{
		if (!self::isReady())
		{
			return 'Subscription tables are missing.';
		}
		$changed = 0;
		foreach (pjCompanySubscriptionModel::factory()->whereIn('t1.status', array('trial', 'active', 'past_due'))->findAll()->getData() as $sub)
		{
			list($status) = self::effectiveStatus($sub);
			if ($status !== $sub['status'])
			{
				$data = array('status' => $status, 'modified' => date('Y-m-d H:i:s'));
				if ($status === 'cancelled')
				{
					$data['cancelled_at'] = date('Y-m-d H:i:s');
				}
				pjCompanySubscriptionModel::factory()->set('id', $sub['id'])->modify($data);
				self::log($sub['company_id'], 'status', sprintf('Status %s -> %s (automatic)', $sub['status'], $status));
				$changed++;
			}
		}
		// online checkouts left unpaid for 2 days are closed (bank transfer / cash / manual
		// requests stay open: the administrator confirms or they are paid later)
		$old = pjBillingTransactionModel::factory()->where('t1.status', 'pending')->where('t1.created <', date('Y-m-d H:i:s', time() - 172800))
			->whereNotIn('t1.provider', array_merge(array('manual'), self::$offlineMethods))->findAll()->getData();
		foreach ($old as $txn)
		{
			self::failPayment($txn['id'], 'cancelled', 'Not paid within 2 days');
		}
		self::clearCache();
		return sprintf('Subscriptions checked: %d status change(s), %d unpaid checkout(s) closed.', $changed, count($old));
	}
}
?>
