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

	/**
	 * Currency the gateway plugin verifies a subscription payment in (set only while a subscription
	 * payment is being confirmed; read by pjBaseAppController when the plugin loads its options).
	 */
	public static $currencyOverride = '';

	/**
	 * Default gateways per currency for subscription payments: INR only through Razorpay, USD through Razorpay
	 * or Stripe. The administrator changes them in Options > Payments (setting gateway_map). Enforced on the
	 * server (checkout and payment page), not only in the popup.
	 */
	public static $currencyMethods = array(
		'INR' => array('razorpay'),
		'USD' => array('razorpay', 'stripe'),
	);

	/**
	 * Currencies each gateway can take for a subscription payment. A gateway is never offered (or accepted) for a
	 * currency it does not take, whatever the settings say: PayPal / Authorize.Net and the others do not take INR.
	 */
	public static $currencySupport = array(
		'razorpay'       => array('INR', 'USD'),
		'stripe'         => array('USD'), // INR payments: Razorpay only
		'paypal'         => array('USD'),
		'paypal_express' => array('USD'),
		'authorize'      => array('USD'),
		'2checkout'      => array('USD'),
		'mollie'         => array('USD'),
		'skrill'         => array('USD'),
		'braintree'      => array('USD'),
		'world_pay'      => array('USD'),
	);

	/** Can the gateway take a payment in $currency? */
	public static function supports($method, $currency)
	{
		$currency = strtoupper((string) $currency);
		return isset(self::$currencySupport[$method]) && in_array($currency, self::$currencySupport[$method], true);
	}

	/**
	 * Gateways the administrator can pick for a currency: switched on in Options > Payments, online, and able to take
	 * that currency (method => name).
	 */
	public static function gatewayOptions($currency)
	{
		$out = array();
		foreach (self::activeMethods() as $m => $name)
		{
			if (self::isOnline($m) && self::supports($m, $currency))
			{
				$out[$m] = $name;
			}
		}
		return $out;
	}

	/** Switched-on online gateways that cannot take $currency (method => name), to explain why they are not listed. */
	public static function unsupportedActive($currency)
	{
		$out = array();
		foreach (self::activeMethods() as $m => $name)
		{
			if (self::isOnline($m) && !self::supports($m, $currency))
			{
				$out[$m] = $name;
			}
		}
		return $out;
	}

	/** Gateways per currency from the settings (the defaults above when not set): currency => methods. */
	public static function currencyMethods()
	{
		$map = json_decode((string) pjEntitlement::setting('gateway_map'), true);
		$out = array();
		foreach (pjEntitlement::$currencies as $cur)
		{
			$out[$cur] = is_array($map) && isset($map[$cur]) && is_array($map[$cur])
				? array_values(array_filter($map[$cur], 'is_string'))
				: (isset(self::$currencyMethods[$cur]) ? self::$currencyMethods[$cur] : array());
		}
		return $out;
	}

	/** Gateways allowed for a currency (whether switched on or not). */
	public static function allowedFor($currency)
	{
		$currency = strtoupper((string) $currency);
		$map = self::currencyMethods();
		// only gateways that can take the currency, whatever is saved
		return isset($map[$currency]) ? array_values(array_filter($map[$currency], function ($m) use ($currency) {
			return pjSubscriptionBilling::supports($m, $currency);
		})) : array();
	}

	/** Switched-on gateways that can take a payment in $currency: method => name. */
	public static function methodsFor($currency)
	{
		return array_intersect_key(self::activeMethods(), array_flip(self::allowedFor($currency)));
	}

	/** Every switched-on gateway with the currencies it takes: method => array(currencies). */
	public static function methodCurrencies()
	{
		$out = array();
		foreach (self::activeMethods() as $m => $name)
		{
			foreach (pjEntitlement::$currencies as $cur)
			{
				if (in_array($m, self::allowedFor($cur), true))
				{
					$out[$m][] = $cur;
				}
			}
		}
		return $out;
	}

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
		if ($method === 'promo')
		{
			return __('sub_provider_promo', true);
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
	 * Currencies subscription payments are taken in: each plan is charged and verified in its own currency
	 * (see $currencyOverride), so this is the list of the priced plans' currencies.
	 */
	public static function planCurrencies()
	{
		$out = array();
		foreach (pjEntitlement::plans() as $p)
		{
			foreach (pjEntitlement::currenciesOf($p) as $c)
			{
				$out[$c] = true;
			}
		}
		return array_keys($out);
	}

	/**
	 * Where the browser goes back to after the gateway: the StayOwned website page saved with a payment started there
	 * (return_url), otherwise My plan in the back office.
	 */
	public static function returnUrl($txn)
	{
		if (!empty($txn['return_url']) && preg_match('~^https?://~i', $txn['return_url']))
		{
			return $txn['return_url'] . (strpos($txn['return_url'], '?') === false ? '?' : '&') . 'txn=' . $txn['uuid'];
		}
		return PJ_INSTALL_URL . 'index.php?controller=pjAdminSubscriptions&action=pjActionCheckoutReturn&txn=' . $txn['uuid'];
	}

	/** Signature of the public payment page of a subscription payment started on the website. */
	/**
	 * The same number for a stored date/time string in every request, whatever time zone the request runs in.
	 * Payments and bookings made through the API are written in the company's time zone, while the payment page,
	 * the gateway callbacks and the back office run in another one: strtotime() alone gives a different number there
	 * and every link signed with it fails ("This plan cannot be chosen.").
	 */
	public static function stamp($datetime)
	{
		$t = strtotime((string) $datetime . ' UTC');
		return $t === false ? 0 : $t;
	}

	public static function payHash($txn)
	{
		return hash_hmac('sha256', $txn['uuid'] . '|' . self::stamp($txn['created']), PJ_SALT);
	}

	/** Public payment page (gateway form) of a payment started on the website. */
	public static function payUrl($txn)
	{
		return PJ_INSTALL_URL . 'index.php?controller=pjFront&action=pjActionPaySubscription&txn=' . $txn['uuid'] . '&hash=' . self::payHash($txn);
	}

	/**
	 * Buy a plan: the one purchase process for My plan (back office) and the StayOwned website.
	 * Same rules everywhere: currency (billing country / locked billing currency), the agreed renewal price,
	 * discount + coupon, the gateways of that currency, free plans, 100 % coupons and payment requests.
	 *   $plan: plan row (pjEntitlement::plan), $method: gateway, $coupon: code typed, $return_url: website page
	 *   to come back to (null: My plan).
	 * Returns array(result, txn, error):
	 *   result 'checkout' (pay txn at the gateway), 'applied' (nothing to pay, plan started), 'requested'
	 *   (no gateway for the currency: payment request left), 'free_switched', 'free_scheduled', or 'error' (+ error label key).
	 */
	public static function purchase($company, $plan, $requested_currency, $method, $coupon, $user_id, $return_url = null, $country = '')
	{
		$out = array('result' => 'error', 'txn' => null, 'error' => 'subErrPlan', 'kind' => null, 'currency' => null);
		$cid = (int) $company['id'];
		if (!$plan || $plan['status'] !== 'T' || (int) $plan['is_public'] !== 1)
		{
			return $out; // hidden / inactive plans are assigned by the administrator only
		}
		$state = pjEntitlement::state($cid);
		$cur = $state['plan'];
		$kind = self::payKind($plan, $cur);
		$out['kind'] = $kind;
		if (pjEntitlement::isFree($plan))
		{
			// a free plan needs no payment: now, or at the end of the paid period
			$res = pjEntitlement::switchToFree($cid, $plan, $user_id);
			$out['result'] = $res === 'switched' ? 'free_switched' : ($res === 'scheduled' ? 'free_scheduled' : 'error');
			$out['error'] = $res ? '' : 'subErrPlan';
			return $out;
		}
		if (!pjEntitlement::isPurchasable($plan))
		{
			$out['error'] = 'subErrCheckout';
			return $out;
		}
		$currency = pjEntitlement::checkoutCurrency($cid, $plan, $kind, $requested_currency, $country);
		$charged = $currency ? pjEntitlement::chargedPlan($cid, $plan, $kind, $currency) : false;
		if (!$charged)
		{
			$out['error'] = 'subErrCurrency';
			return $out;
		}
		$out['currency'] = $currency;
		$quote = pjSubscriptionPromos::quote($charged, $kind, $cid, (string) $coupon);
		if ($quote['error'] !== '')
		{
			$out['error'] = $quote['error'];
			return $out;
		}
		$extra = $return_url ? array('return_url' => substr($return_url, 0, 255), 'channel' => 'web') : array();
		// the billing country chosen on the website is kept with the payment while the account's currency isn't
		// fixed (it decided the currency); afterwards the company's own country is kept
		if ($country !== '' && pjEntitlement::isCountry($country) && !pjEntitlement::currencyLocked($cid)
			&& pjEntitlement::currencyForCountry($country) === $currency)
		{
			$extra['billing_country'] = strtoupper($country);
		}
		if (!empty($quote['promo_ids']) && $quote['amount'] <= 0)
		{
			// nothing left to pay: the plan starts now
			$txn = pjEntitlement::createTransaction($cid, $charged, $kind, 'promo', $user_id, $quote, $extra);
			$res = $txn ? pjEntitlement::applyPayment($txn['id'], $txn['uuid'], 0, $txn['currency'], $user_id) : '';
			$out['txn'] = $txn ? pjBillingTransactionModel::factory()->find($txn['id'])->getData() : null;
			$out['result'] = $res === 'applied' ? 'applied' : 'error';
			$out['error'] = $res === 'applied' ? '' : 'subErrCheckout';
			return $out;
		}
		if (count(self::methodsFor($currency)) === 0)
		{
			// no gateway for this currency switched on yet: leave a payment request for the administrator
			$txn = pjEntitlement::createTransaction($cid, $charged, $kind, 'manual', $user_id, $quote, $extra);
			pjEntitlement::log($cid, 'payment_requested', 'Payment requested for plan ' . $plan['name'] . ($txn ? ' (' . $txn['amount'] . ' ' . $txn['currency'] . ')' : ''), $user_id);
			$out['txn'] = $txn;
			$out['result'] = $txn ? 'requested' : 'error';
			$out['error'] = $txn ? '' : 'subErrCheckout';
			return $out;
		}
		$res = self::checkout($company, $charged, $kind, (string) $method, $user_id, $quote, $extra);
		if (isset($res['txn']))
		{
			$out['txn'] = $res['txn'];
			$out['result'] = 'checkout';
			$out['error'] = '';
			return $out;
		}
		$out['error'] = $res['error'];
		return $out;
	}

	/** What buying the plan is for the company: renewal of its plan, upgrade or a new plan. */
	public static function payKind($plan, $cur)
	{
		return ($cur && (int) $cur['id'] === (int) $plan['id']) ? 'renewal' : (($cur && (int) $plan['sort'] > (int) $cur['sort']) ? 'upgrade' : 'new');
	}

	public static function cancelHash($txn)
	{
		return sha1($txn['uuid'] . self::stamp($txn['created']) . PJ_SALT);
	}

	/**
	 * Start (or reuse) a payment of $plan with $method. $plan is the plan as charged (pjEntitlement::planInCurrency):
	 * its currency decides which gateways may take it. Returns array('txn' => ..) or array('error' => label key).
	 * Duplicate prevention: an open payment of the same company / plan / kind / method is reused
	 * (online: from the last 30 minutes; bank / cash: while it is still open).
	 */
	public static function checkout($company, $plan, $kind, $method, $user_id, $quote = null, $extra = array())
	{
		if (!pjEntitlement::isPurchasable($plan))
		{
			return array('error' => 'subErrCheckout');
		}
		$active = self::methodsFor($plan['currency']);
		if (!isset($active[$method]))
		{
			return array('error' => 'subErrMethod');
		}
		$online = self::isOnline($method);
		if ($online && !preg_match('/^[A-Z]{3}$/', strtoupper((string) $plan['currency'])))
		{
			return array('error' => 'subErrCurrency');
		}
		$m = pjBillingTransactionModel::factory()
			->where('t1.company_id', (int) $company['id'])->where('t1.plan_id', (int) $plan['id'])
			->where('t1.kind', $kind)->where('t1.status', 'pending')->where('t1.provider', $method)
			->where('t1.currency', strtoupper((string) $plan['currency']));
		if ($online)
		{
			$m->where('t1.created >', date('Y-m-d H:i:s', time() - 1800));
		}
		$open = $m->orderBy('t1.id DESC')->limit(1)->findAll()->getDataIndex(0);
		$amount = $quote ? (float) $quote['amount'] : (float) $plan['price'];
		$coupon = $quote ? (string) $quote['coupon_code'] : '';
		$return = isset($extra['return_url']) ? (string) $extra['return_url'] : '';
		if (!empty($open) && round((float) $open['amount'], 2) === round($amount, 2) && (string) $open['coupon_code'] === $coupon
			&& (string) (isset($open['return_url']) ? $open['return_url'] : '') === $return)
		{
			return array('txn' => $open);
		}
		$txn = pjEntitlement::createTransaction($company['id'], $plan, $kind, $method, $user_id, $quote, $extra);
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
		$back = self::returnUrl($txn);
		$params = $pjPlugin::getFormParams(array('payment_method' => $txn['provider']), array(
			'locale_id' => $locale_id,
			'return_url' => $back,
			'cancel_url' => $back,
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
		if (is_array($params))
		{
			// The success / failure pages typed in a gateway's settings are for bookings (and may not even be a full
			// URL, e.g. "www.phpjabbers.com"): a plan payment always comes back to its own page, which shows the result
			// and offers to pay again.
			$params['success_url'] = $back;
			$params['failure_url'] = $back;
		}
		return $params;
	}

	/**
	 * Order data a gateway plugin must use for a plan payment (PayPal creates its order from this, not from the
	 * browser): array(amount, currency, description) for an open payment of $method, false for a plan payment
	 * that can't be paid any more (paid, cancelled, other gateway), null when $uuid is not a plan payment
	 * (a booking: the plugin keeps its own behaviour).
	 */
	public static function gatewayOrder($uuid, $method)
	{
		if (!is_string($uuid) || !preg_match('/^[a-f0-9]{32}$/', $uuid) || !class_exists('pjEntitlement') || !pjEntitlement::isReady())
		{
			return null;
		}
		$txn = pjBillingTransactionModel::factory()->where('t1.uuid', $uuid)->limit(1)->findAll()->getDataIndex(0);
		if (empty($txn))
		{
			return null;
		}
		if ($txn['status'] !== 'pending' || $txn['provider'] !== $method || !isset(self::methodsFor($txn['currency'])[$method]))
		{
			return false;
		}
		$plan = pjEntitlement::plan($txn['plan_id']);
		return array(
			'amount' => number_format((float) $txn['amount'], 2, '.', ''),
			'currency' => strtoupper((string) $txn['currency']),
			'description' => trim(($plan ? $plan['name'] . ' plan' : 'Plan') . ' (' . $txn['uuid'] . ')'),
		);
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
		// the plugin verifies amount AND currency with the provider: in the currency of this transaction
		self::$currencyOverride = strtoupper((string) $txn['currency']);
		$response = $controller->requestAction(array('controller' => $pjPlugin, 'action' => 'pjActionConfirm', 'params' => array(
			'request' => $request,
			'payment_method' => $method,
			'foreign_id' => self::FOREIGN_ID,
			'amount' => $txn['amount'],
			// the currency the gateway must have been paid in (the global o_currency is the company's / property's)
			'currency_code' => strtoupper((string) $txn['currency']),
			'txn_id' => (string) $txn['provider_ref'],
			'order_id' => $txn['id'],
			'uuid' => $txn['uuid'],
			'cancel_hash' => self::cancelHash($txn),
			'key' => md5(@$controller->option_arr['private_key'] . PJ_SALT),
		)), array('return'));
		self::$currencyOverride = '';
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
