<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Platform billing (companies pay the platform for their plan) through the same payment
 * gateways a property uses (plugins/pjPayments + pjStripe, pjPaypal, ...). The administrator
 * switches gateways on in Options > Payments; those settings are stored under a reserved
 * foreign_id, separate from every property, so guest booking payments are not touched.
 *
 * Flow: pending transaction (amount from the plan) -> gateway form -> the gateway notifies
 * payments_webhook.php -> pjFront::pjActionConfirm -> confirmFromGateway() -> the gateway
 * plugin verifies the payment with the provider (amount + currency) -> applyPayment()
 * (once per transaction). Bank transfer / cash: the transaction stays pending until the
 * administrator confirms it. No card data ever reaches this server.
 */
class pjSubscriptionBilling
{
	/** foreign_id of the platform's own gateway settings (never a property id). */
	const FOREIGN_ID = 999999999;

	public static function hasPayments()
	{
		return class_exists('pjPayments') && pjObject::getPlugin('pjPayments') !== NULL;
	}

	/** Same order as a property's Payments tab. */
	public static function sortOrder()
	{
		return array('paypal', 'authorize', 'paypal_express', '2checkout', 'stripe', 'razorpay', 'mollie', 'skrill', 'world_pay', 'braintree', 'bank', 'cash');
	}

	public static function isOnline($method)
	{
		return !in_array($method, array_merge(array('manual'), pjEntitlement::$offlineMethods), true);
	}

	public static function methodName($method)
	{
		$names = __('payment_methods', true);
		if ($method === 'manual')
		{
			return __('sub_provider_manual', true);
		}
		return is_array($names) && !empty($names[$method]) ? $names[$method] : ucfirst(str_replace('_', ' ', (string) $method));
	}

	/** Every method that can be configured: method => name (installed gateway plugins + bank / cash). */
	public static function allMethods()
	{
		if (!self::hasPayments())
		{
			return array();
		}
		$out = array();
		foreach (array_keys(pjPayments::getPaymentMethods()) as $m)
		{
			if (in_array($m, pjEntitlement::$offlineMethods, true) || pjObject::getPlugin(pjPayments::getPluginName($m)) !== NULL)
			{
				$out[$m] = self::methodName($m);
			}
		}
		foreach (pjEntitlement::$offlineMethods as $m)
		{
			$out[$m] = self::methodName($m);
		}
		return pjUtil::sortArrayByArray($out, self::sortOrder());
	}

	/** Methods switched on for subscriptions: method => name. */
	public static function activeMethods()
	{
		if (!self::hasPayments())
		{
			return array();
		}
		$all = self::allMethods();
		$on = pjPaymentOptionModel::factory()->where('t1.foreign_id', self::FOREIGN_ID)->where('t1.is_active', 1)
			->findAll()->getDataPair(null, 'payment_method');
		$out = array();
		foreach ($on as $m)
		{
			if (isset($all[$m]))
			{
				$out[$m] = $all[$m];
			}
		}
		return pjUtil::sortArrayByArray($out, self::sortOrder());
	}

	/**
	 * Currency the online gateway plugins check a payment against: the currency of the main
	 * settings (options of foreign_id 1, as loaded when the gateway calls back).
	 */
	public static function gatewayCurrency()
	{
		$o = array_merge(pjBaseOptionModel::factory()->getPairs(1), pjOptionModel::factory()->getPairs(1));
		return isset($o['o_currency']) ? strtoupper((string) $o['o_currency']) : '';
	}

	public static function returnUrl($txn)
	{
		return PJ_INSTALL_URL . 'index.php?controller=pjAdminSubscriptions&action=pjActionCheckoutReturn&txn=' . $txn['uuid'];
	}

	public static function cancelHash($txn)
	{
		return sha1($txn['uuid'] . strtotime($txn['created']) . PJ_SALT);
	}

	/**
	 * Start (or reuse) a payment of $plan with $method. Returns array('txn' => ..) or array('error' => label key).
	 * Duplicate prevention: an open payment of the same company / plan / kind / method is reused
	 * (online: from the last 30 minutes; bank / cash: while it is still open).
	 */
	public static function checkout($company, $plan, $kind, $method, $user_id)
	{
		if (!pjEntitlement::isPurchasable($plan))
		{
			return array('error' => 'subErrCheckout');
		}
		$active = self::activeMethods();
		if (!isset($active[$method]))
		{
			return array('error' => 'subErrMethod');
		}
		$online = self::isOnline($method);
		if ($online && strtoupper((string) $plan['currency']) !== self::gatewayCurrency())
		{
			return array('error' => 'subErrCurrency');
		}
		$m = pjBillingTransactionModel::factory()
			->where('t1.company_id', (int) $company['id'])->where('t1.plan_id', (int) $plan['id'])
			->where('t1.kind', $kind)->where('t1.status', 'pending')->where('t1.provider', $method);
		if ($online)
		{
			$m->where('t1.created >', date('Y-m-d H:i:s', time() - 1800));
		}
		$open = $m->orderBy('t1.id DESC')->limit(1)->findAll()->getDataIndex(0);
		if (!empty($open) && round((float) $open['amount'], 2) === round((float) $plan['price'], 2))
		{
			return array('txn' => $open);
		}
		$txn = pjEntitlement::createTransaction($company['id'], $plan, $kind, $method, $user_id);
		if (!$txn)
		{
			return array('error' => 'subErrCheckout');
		}
		pjEntitlement::log($company['id'], 'payment_started', sprintf('Payment started: plan %s, %s %s, %s', $plan['name'], $txn['amount'], $txn['currency'], self::methodName($method)), $user_id);
		return array('txn' => $txn);
	}

	/** Parameters for the gateway plugin's payment form (pjActionForm). */
	public static function formParams($txn, $company, $locale_id)
	{
		$pjPlugin = pjPayments::getPluginName($txn['provider']);
		if (pjObject::getPlugin($pjPlugin) === NULL || !method_exists($pjPlugin, 'getFormParams'))
		{
			return false;
		}
		return $pjPlugin::getFormParams(array('payment_method' => $txn['provider']), array(
			'locale_id' => $locale_id,
			'return_url' => self::returnUrl($txn),
			'id' => $txn['id'],
			'foreign_id' => self::FOREIGN_ID,
			'uuid' => $txn['uuid'],
			'name' => (string) $company['name'],
			'email' => (string) @$company['email'],
			'phone' => (string) @$company['phone'],
			'amount' => $txn['amount'],
			'cancel_hash' => self::cancelHash($txn),
			'currency_code' => $txn['currency'],
		));
	}

	/** Bank account text the administrator entered for subscriptions (Options > Payments > Bank). */
	public static function bankAccount($locale_id)
	{
		$row = pjMultiLangModel::factory()->select('t1.content')->where('t1.model', 'pjOption')->where('t1.foreign_id', self::FOREIGN_ID)
			->where('t1.field', 'o_bank_account')->where('t1.locale', (int) $locale_id)->limit(1)->findAll()->getDataIndex(0);
		return $row ? (string) $row['content'] : '';
	}

	/**
	 * Called by pjFront::pjActionConfirm (payments_webhook.php) for every gateway callback.
	 * Returns false when $uuid is not a subscription payment (then it is a booking), otherwise
	 * the URL the browser goes back to. The payment itself is verified by the gateway plugin
	 * with the provider (amount and currency); the URL / browser is never trusted.
	 */
	public static function confirmFromGateway($controller, $pjPlugin, $uuid, $request)
	{
		if (!class_exists('pjEntitlement') || !pjEntitlement::isReady() || !is_string($uuid) || !preg_match('/^[a-f0-9]{32}$/', $uuid))
		{
			return false;
		}
		$txn = pjBillingTransactionModel::factory()->where('t1.uuid', $uuid)->limit(1)->findAll()->getDataIndex(0);
		if (empty($txn))
		{
			return false;
		}
		$back = self::returnUrl($txn);
		$method = isset($request['payment_method']) ? (string) $request['payment_method'] : '';
		if ($txn['provider'] !== $method || pjPayments::getPluginName($method) !== $pjPlugin || $txn['status'] !== 'pending')
		{
			return $back; // other gateway, or already handled (a payment is never applied twice)
		}
		$response = $controller->requestAction(array('controller' => $pjPlugin, 'action' => 'pjActionConfirm', 'params' => array(
			'request' => $request,
			'payment_method' => $method,
			'foreign_id' => self::FOREIGN_ID,
			'amount' => $txn['amount'],
			'txn_id' => (string) $txn['provider_ref'],
			'order_id' => $txn['id'],
			'uuid' => $txn['uuid'],
			'cancel_hash' => self::cancelHash($txn),
			'key' => md5(@$controller->option_arr['private_key'] . PJ_SALT),
		)), array('return'));
		$status = is_array($response) && isset($response['status']) ? $response['status'] : 'FAIL';
		if ($status === 'OK')
		{
			$ref = !empty($response['txn_id']) && is_string($response['txn_id']) ? $response['txn_id'] : $method . ':' . $txn['uuid'];
			pjEntitlement::applyPayment($txn['id'], substr($ref, 0, 255), $txn['amount'], $txn['currency']);
		} elseif ($status === 'CANCEL') {
			pjEntitlement::failPayment($txn['id'], 'cancelled', 'Cancelled at ' . self::methodName($method));
		}
		// anything else: not confirmed (yet) - stays pending, the gateway may call again
		return $back;
	}
}
?>
