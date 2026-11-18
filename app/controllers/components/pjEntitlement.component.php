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
	public static $baseFeatures = array('website', 'properties', 'availability', 'social_sharing', 'email_notifications', 'payment_gateway');

	/** Features that are in the catalogue but not built yet: shown as "Coming soon", never sold as working. */
	public static $comingSoon = array('customers', 'analytics', 'calendar_notes', 'ai_chatbot');

	/** Numeric limits (null = unlimited). */
	public static $limits = array('max_properties', 'max_users', 'max_payment_gateways', 'ai_monthly_messages');

	public static $statuses = array('trial', 'active', 'past_due', 'cancelled', 'expired');

	/** Payment methods every company may use (also without a plan); the others need "payment_gateway_choice". */
	public static $freePaymentMethods = array('paypal', 'cash');

	/** Offline payment methods do not count as "payment gateways". */
	public static $offlineMethods = array('cash', 'bank');

	private static $settings = null;
	private static $stateCache = array();
	private static $ready = null;

	/* ------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------ */

	/**
	 * Dual pricing: every plan has a USD price (international, the base / reporting currency of the dashboards)
	 * and an INR price (India). A company pays in one of them; every payment keeps a snapshot of both prices, the
	 * exchange rate and the USD value at the time of the checkout, so history is never recalculated.
	 */
	const BASE_CURRENCY = 'USD';

	public static $currencies = array('USD', 'INR');

	public static $defaults = array(
		'currency' => 'USD', 'grace_days' => '7',
		// INR per 1 USD (Subscriptions > Plans > Currency & exchange rate): suggests INR prices, values INR payments in USD
		'inr_rate' => '88.00',
		// billing country -> pricing currency (ISO codes, "IN:INR,NP:INR"); every other country pays in fallback_currency
		'country_currencies' => 'IN:INR', 'fallback_currency' => 'USD',
		// gateways per currency for subscription payments (JSON), edited in Options > Payments
		'gateway_map' => '{"INR":["razorpay"],"USD":["razorpay","stripe"]}',
		// A company without a plan has no full access (basic features only, nothing new).
		// expired_mode: 'suspend' = the website takes no bookings and the back office is read-only (My plan stays
		// open to pay); 'read_only' = back office read-only, website open; 'basic' = only the basic features.
		'unassigned_mode' => 'restricted', 'expired_mode' => 'suspend', 'self_trial' => '0',
		// Plan every new company gets (Subscriptions > Plans: "Plan for new companies"), '' = none
		'default_plan_id' => '',
		// reminder e-mails (pjSubscriptionMail): days before the trial end / the renewal date
		'reminder_trial_days' => '3', 'reminder_renewal_days' => '7',
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
		$num = function ($v) { return ($v === null || $v === '') ? null : (float) $v; };
		if (array_key_exists('price_usd', $row))
		{
			$usd = $num($row['price_usd']);
			$inr = isset($row['price_inr']) ? $num($row['price_inr']) : null;
		} else {
			// the dual-currency update has not run yet: the single price of the plan
			$legacy = $num($row['price']);
			$legacy_cur = !empty($row['currency']) ? strtoupper($row['currency']) : self::BASE_CURRENCY;
			$usd = $legacy_cur === 'USD' ? $legacy : null;
			$inr = $legacy_cur === 'INR' ? $legacy : null;
			if ($usd === null && $inr !== null)
			{
				$usd = round($inr / self::rate(), 2);
			}
		}
		$row['inr_auto'] = isset($row['inr_auto']) ? (int) $row['inr_auto'] : 0;
		$row['prices'] = array('USD' => $usd, 'INR' => $inr);
		// "price" / "currency" of a plan are its USD (base) price: limits, free / purchasable checks and dashboards
		$row['price'] = $usd;
		$row['currency'] = self::BASE_CURRENCY;
		return $row;
	}

	/* ------------------------------------------------------------------
	 * Dual currency (USD / INR)
	 * ------------------------------------------------------------------ */

	/** INR per 1 USD from the settings (never 0). */
	public static function rate()
	{
		$r = (float) str_replace(',', '.', (string) self::setting('inr_rate'));
		return $r > 0 ? $r : 88.0;
	}

	/** Suggested INR price of a USD price at the current rate (whole rupees). */
	public static function suggestInr($usd)
	{
		return $usd === null || $usd === '' ? null : (float) round((float) $usd * self::rate());
	}

	/** USD value of an amount at the given rate (INR per USD); null for other currencies. */
	public static function toUsd($amount, $currency, $rate = null)
	{
		$currency = strtoupper((string) $currency);
		if ($currency === 'USD')
		{
			return round((float) $amount, 2);
		}
		if ($currency === 'INR')
		{
			$rate = $rate === null ? self::rate() : (float) $rate;
			return $rate > 0 ? round((float) $amount / $rate, 2) : null;
		}
		return null;
	}

	public static function isCurrency($currency)
	{
		return in_array(strtoupper((string) $currency), self::$currencies, true);
	}

	/** Price of a plan in a currency (null: not offered in it). A free plan is free in every currency. */
	public static function priceIn($plan, $currency)
	{
		$currency = strtoupper((string) $currency);
		if (empty($plan) || !isset($plan['prices']) || !self::isCurrency($currency))
		{
			return null;
		}
		if ($plan['prices']['USD'] !== null && $plan['prices']['USD'] <= 0)
		{
			return 0.0;
		}
		return $plan['prices'][$currency];
	}

	/** Currencies a plan can be bought in (price > 0), base currency first. */
	public static function currenciesOf($plan)
	{
		$out = array();
		foreach (self::$currencies as $c)
		{
			$p = self::priceIn($plan, $c);
			if ($p !== null && $p > 0)
			{
				$out[] = $c;
			}
		}
		return $out;
	}

	/** The plan as charged in $currency (price / currency replaced), or false when it has no price in it. */
	public static function planInCurrency($plan, $currency)
	{
		$price = self::priceIn($plan, $currency);
		if (empty($plan) || $price === null)
		{
			return false;
		}
		$plan['price'] = $price;
		$plan['currency'] = strtoupper((string) $currency);
		return $plan;
	}

	/** Country -> currency mapping of the settings: array('IN' => 'INR', ...). */
	public static function countryCurrencies()
	{
		$out = array();
		foreach (preg_split('/[\s,;]+/', strtoupper((string) self::setting('country_currencies'))) as $pair)
		{
			if (preg_match('/^([A-Z]{2})[:=]([A-Z]{3})$/', $pair, $m) && self::isCurrency($m[2]))
			{
				$out[$m[1]] = $m[2];
			}
		}
		return $out;
	}

	/** Currency for countries without their own (USD unless the settings say otherwise). */
	public static function fallbackCurrency()
	{
		$c = strtoupper((string) self::setting('fallback_currency'));
		return self::isCurrency($c) ? $c : self::BASE_CURRENCY;
	}

	/** Pricing currency of a country (ISO alpha-2): the settings' country mapping (India: INR), else the fallback (USD). */
	public static function currencyForCountry($country)
	{
		$country = strtoupper(trim((string) $country));
		$map = self::countryCurrencies();
		return preg_match('/^[A-Z]{2}$/', $country) && isset($map[$country]) ? $map[$country] : self::fallbackCurrency();
	}

	/** A real country code (in the countries of Display > Countries)? */
	public static function isCountry($country)
	{
		$country = strtoupper(trim((string) $country));
		if (!preg_match('/^[A-Z]{2}$/', $country))
		{
			return false;
		}
		return !class_exists('pjBaseCountryModel')
			|| (int) pjBaseCountryModel::factory()->where('t1.alpha_2', $country)->findCount()->getData() > 0;
	}

	/** Billing country of a company (ISO alpha-2 from its profile), '' when not set. */
	public static function billingCountry($company_id)
	{
		$c = pjCompanyModel::factory()->find((int) $company_id)->getData();
		if (!empty($c['country_id']) && class_exists('pjBaseCountryModel'))
		{
			$country = pjBaseCountryModel::factory()->find((int) $c['country_id'])->getData();
			if (!empty($country['alpha_2']))
			{
				return strtoupper($country['alpha_2']);
			}
		}
		return '';
	}

	/** Pricing currency of the company's billing country (country mapping of the settings, else the fallback). */
	public static function countryCurrency($company_id)
	{
		$map = self::countryCurrencies();
		$country = self::billingCountry($company_id);
		return $country !== '' && isset($map[$country]) ? $map[$country] : self::fallbackCurrency();
	}

	/**
	 * The company's currency is fixed once it has a subscription billing currency or a paid payment: renewals,
	 * upgrades and downgrades stay in it (never converted silently). Only the administrator changes it
	 * (Subscriptions > company > Billing currency, logged).
	 */
	public static function currencyLocked($company_id)
	{
		$sub = self::subscription($company_id);
		if ($sub && !empty($sub['billing_currency']))
		{
			return true;
		}
		return (int) $company_id > 0 && self::isReady() && (int) pjBillingTransactionModel::factory()->where('t1.company_id', (int) $company_id)
			->where('t1.status', 'paid')->where('t1.amount >', 0)->findCount()->getData() > 0;
	}

	/**
	 * Billing currency of a company: the currency of its subscription (set by its last payment or the administrator),
	 * else of its last paid payment, else of its country.
	 */
	public static function billingCurrency($company_id)
	{
		$sub = self::subscription($company_id);
		if ($sub && !empty($sub['billing_currency']) && self::isCurrency($sub['billing_currency']))
		{
			return strtoupper($sub['billing_currency']);
		}
		if ((int) $company_id > 0 && self::isReady())
		{
			$paid = pjBillingTransactionModel::factory()->select('t1.currency')->where('t1.company_id', (int) $company_id)->where('t1.status', 'paid')
				->where('t1.amount >', 0)->orderBy('t1.id DESC')->limit(1)->findAll()->getDataIndex(0);
			if (!empty($paid) && self::isCurrency($paid['currency']))
			{
				return strtoupper($paid['currency']);
			}
		}
		return (int) $company_id > 0 ? self::countryCurrency($company_id) : self::BASE_CURRENCY;
	}

	/**
	 * Currency a purchase is charged in. Once the company's currency is fixed (currencyLocked) every purchase stays
	 * in it; before that (first purchase) the company may pick, the default being the currency of its billing
	 * country. A plan without a price in that currency falls back to the fallback currency (shown explicitly).
	 * $country: billing country chosen on the website (ISO alpha-2): before the currency is fixed it decides the
	 * currency (India INR, other countries USD), ahead of $requested.
	 * Returns false when the plan has no price in any currency.
	 */
	public static function checkoutCurrency($company_id, $plan, $kind, $requested = '', $country = '')
	{
		$avail = self::currenciesOf($plan);
		if (empty($avail))
		{
			return false;
		}
		$billing = self::billingCurrency($company_id);
		if (self::currencyLocked($company_id) && in_array($billing, $avail, true))
		{
			return $billing;
		}
		$requested = self::isCountry($country) ? self::currencyForCountry($country) : strtoupper((string) $requested);
		if (!self::currencyLocked($company_id) && in_array($requested, $avail, true))
		{
			return $requested;
		}
		if (in_array($billing, $avail, true))
		{
			return $billing;
		}
		return in_array(self::fallbackCurrency(), $avail, true) ? self::fallbackCurrency() : $avail[0];
	}

	/**
	 * The plan as charged to this company: its price in $currency, except a renewal of the same plan in the same
	 * currency, which keeps the recurring amount agreed at the last payment (price changes apply to new purchases).
	 */
	public static function chargedPlan($company_id, $plan, $kind, $currency)
	{
		$charged = self::planInCurrency($plan, $currency);
		if (!$charged)
		{
			return false;
		}
		$sub = self::subscription($company_id);
		if ($kind === 'renewal' && $sub && (int) $sub['plan_id'] === (int) $plan['id'] && strtoupper((string) $sub['billing_currency']) === $charged['currency']
			&& isset($sub['price_amount']) && $sub['price_amount'] !== null && (float) $sub['price_amount'] > 0)
		{
			$charged['price'] = (float) $sub['price_amount'];
			$charged['price_locked'] = true;
		}
		return $charged;
	}

	/** Id of the exchange rate snapshot payments are valued with now (the last one saved), 0 when none. */
	public static function rateSnapshotId()
	{
		if (!class_exists('pjExchangeRateSnapshotModel'))
		{
			return 0;
		}
		$row = pjExchangeRateSnapshotModel::factory()->where('t1.base_currency', 'USD')->where('t1.quote_currency', 'INR')
			->orderBy('t1.id DESC')->limit(1)->findAll()->getDataIndex(0);
		return !empty($row) ? (int) $row['id'] : 0;
	}

	/** Administrator saves the exchange rate: setting + a new snapshot (audit: rate, source, time, user). */
	public static function saveRate($rate, $source, $user_id)
	{
		self::saveSetting('inr_rate', number_format((float) $rate, 4, '.', ''));
		pjExchangeRateSnapshotModel::factory()->setAttributes(array('base_currency' => 'USD', 'quote_currency' => 'INR',
			'rate' => number_format((float) $rate, 6, '.', ''), 'source' => substr((string) $source, 0, 40),
			'user_id' => $user_id ? (int) $user_id : ':NULL', 'captured_at' => date('Y-m-d H:i:s')))->insert();
	}

	/** Administrator sets the billing currency of a company (its next payments; history is not touched). */
	public static function setBillingCurrency($company_id, $currency, $user_id)
	{
		$currency = strtoupper((string) $currency);
		$sub = self::subscription($company_id);
		if (!$sub || !self::isCurrency($currency))
		{
			return false;
		}
		$old = !empty($sub['billing_currency']) ? $sub['billing_currency'] : '-';
		$data = array('billing_currency' => $currency);
		if ($old !== $currency)
		{
			// the agreed recurring amount was in the old currency: the next payment uses the plan price in the new one
			$data['price_amount'] = ':NULL';
			$data['price_usd'] = ':NULL';
			$data['exchange_rate'] = ':NULL';
		}
		self::saveSubscription($company_id, $data);
		self::log($company_id, 'currency', sprintf('Billing currency %s -> %s', $old, $currency), $user_id);
		return true;
	}

	/** A plan can be bought online only when it is active, public and has a price > 0. */
	public static function isPurchasable($plan)
	{
		return !empty($plan) && $plan['status'] === 'T' && (int) $plan['is_public'] === 1 && $plan['price'] !== null && $plan['price'] > 0;
	}

	/** A free plan (price 0): a company may switch to it itself, no payment involved. */
	public static function isFree($plan)
	{
		return !empty($plan) && $plan['status'] === 'T' && $plan['price'] !== null && $plan['price'] <= 0;
	}

	public static function isComingSoon($feature)
	{
		return in_array($feature, self::$comingSoon, true);
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
				$sub = self::applyDueChange($sub);
				$plan = self::plan($sub['plan_id']);
				list($status, $entitled, $grace_end) = self::effectiveStatus($sub);
				// trial over and not paid (before the hourly job has moved it): already the free plan's rights
				if ($status === 'expired' && ($free = self::trialFallbackPlan($sub)))
				{
					$plan = $free;
					$status = 'active';
					$entitled = true;
					$grace_end = null;
				}
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

	/** The company's back office is read-only (expired / cancelled with "suspend" or "read_only" mode): it can look, and pay on My plan. */
	public static function backOfficeReadOnly($company_id)
	{
		if ((int) $company_id <= 0)
		{
			return false;
		}
		$s = self::state($company_id);
		return $s['source'] === 'plan' && !$s['entitled'] && in_array(self::setting('expired_mode'), array('suspend', 'read_only'), true);
	}

	/**
	 * Back-office guard (called from every controller's beforeFilter): an expired company in read-only mode may
	 * look at everything but change nothing, except paying / changing its plan (My plan), its own profile and
	 * logging out. Administrators are never limited. Stops the request with a JSON 402 (Ajax) or a redirect.
	 */
	public static function readOnlyGuard($controller)
	{
		if (!self::isReady() || !class_exists('pjCompanyContext') || !is_object($controller) || !$controller->isLoged() || $controller->isAdmin() || (int) $controller->getRoleId() === 6)
		{
			return;
		}
		// (the framework moves $_GET / $_POST into its own input objects)
		$get = is_object($controller->_get) ? $controller->_get : null;
		$ctl = $get ? (string) $get->toString('controller') : (isset($_GET['controller']) ? (string) $_GET['controller'] : '');
		$action = $get ? (string) $get->toString('action') : (isset($_GET['action']) ? (string) $_GET['action'] : '');
		if (in_array($ctl, array('pjAdminSubscriptions', 'pjFront', 'pjJabbApi', 'pjCron', 'pjInstaller', 'pjListings'), true)
			|| in_array($action, array('pjActionLogin', 'pjActionLogout', 'pjActionProfile', 'pjActionForgot', 'pjActionSwitch'), true))
		{
			return;
		}
		$is_post = isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
		$is_xhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
		$mutating = $is_post || preg_match('/^pjAction(Delete|Save|Set|Copy|Import|Upload|Remove|Sort|Send|Change|Clone|Restore|Empty|Toggle|Create|Add)/', $action);
		if (!$mutating)
		{
			return;
		}
		// read-only Ajax lists are POSTed too (grids): those keep working
		if ($is_post && $is_xhr && preg_match('/^pjAction(Get|Check|Load|Find|Search|Preview|Export)/', $action))
		{
			return;
		}
		$cid = (int) pjCompanyContext::activeId($controller->getRoleId(), $controller->getUserId());
		if (!self::backOfficeReadOnly($cid))
		{
			return;
		}
		$text = __('subReadOnly', true);
		if ($is_xhr)
		{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('status' => 'ERR', 'code' => 402, 'text' => $text));
			exit;
		}
		header('Location: ' . $_SERVER['PHP_SELF'] . '?controller=pjAdminSubscriptions&action=pjActionMyPlan&msg=subReadOnly');
		exit;
	}

	/* ------------------------------------------------------------------
	 * Usage
	 * ------------------------------------------------------------------ */

	public static function usage($company_id)
	{
		$company_id = (int) $company_id;
		return array(
			// switched-off properties do not count against the plan's limit
			'properties' => (int) pjCalendarModel::factory()->where('t1.company_id', $company_id)->where('t1.status', 'T')->findCount()->getData(),
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

	/** May the company switch on this payment method (PayPal and cash always; the rest with "payment_gateway_choice")? */
	public static function allowsPaymentMethod($company_id, $method)
	{
		if (!self::isReady() || in_array((string) $method, self::$freePaymentMethods, true))
		{
			return true;
		}
		return self::allows($company_id, 'payment_gateway_choice');
	}

	/** Does this switched-on method count against "max_payment_gateways"? Not cash / bank, not PayPal (always free)
	 * and not a gateway the plan no longer allows (it cannot take payments, the company can only switch it off). */
	public static function countsAsGateway($company_id, $method)
	{
		$method = (string) $method;
		return !in_array($method, self::$offlineMethods, true)
			&& !in_array($method, self::$freePaymentMethods, true)
			&& self::allowsPaymentMethod($company_id, $method);
	}

	/** Switched-on gateways of a property that count against the plan's limit. */
	public static function countedGateways($company_id, $calendar_id)
	{
		$out = array();
		foreach (self::activeGateways($calendar_id) as $m)
		{
			if (self::countsAsGateway($company_id, $m)) { $out[] = $m; }
		}
		return $out;
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

	/** The plan new companies get (Subscriptions > Plans), or false. */
	public static function defaultPlan()
	{
		$id = (int) self::setting('default_plan_id');
		$plan = $id > 0 ? self::plan($id) : false;
		return $plan && $plan['status'] === 'T' ? $plan : false;
	}

	/**
	 * A new company gets the plan for new companies: active, without an end date (the company changes it on
	 * My plan, the administrator on Subscriptions). Nothing happens when no such plan is set or it has one already.
	 */
	public static function assignDefaultPlan($company_id, $user_id = null)
	{
		$plan = self::isReady() ? self::defaultPlan() : false;
		if (!$plan || (int) $company_id <= 0 || self::subscription($company_id))
		{
			return false;
		}
		$now = date('Y-m-d H:i:s');
		self::saveSubscription($company_id, array('plan_id' => (int) $plan['id'], 'status' => 'active', 'started_at' => $now,
			'current_period_start' => $now, 'current_period_end' => ':NULL', 'trial_ends_at' => ':NULL',
			'cancel_at_period_end' => 0, 'cancelled_at' => ':NULL', 'pending_plan_id' => ':NULL'));
		self::log($company_id, 'assigned', sprintf('Plan %s assigned (plan for new companies)', $plan['name']), $user_id);
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

	/**
	 * A plan change scheduled for the end of the period (downgrade) is applied once that end has passed:
	 * a free plan starts at once without an end date; a paid plan starts with its first period due
	 * (past_due during the grace days, then expired if not paid). Cancellation at period end wins.
	 * Returns the (updated) subscription row.
	 */
	public static function applyDueChange($sub, $now = null)
	{
		if (empty($sub) || empty($sub['pending_plan_id']) || (int) $sub['cancel_at_period_end'] === 1)
		{
			return $sub;
		}
		$now = $now === null ? time() : (int) $now;
		$end_str = $sub['status'] === 'trial' && !empty($sub['trial_ends_at']) ? $sub['trial_ends_at'] : $sub['current_period_end'];
		if (empty($end_str) || $now <= strtotime($end_str) || !in_array($sub['status'], array('trial', 'active', 'past_due', 'expired'), true))
		{
			return $sub;
		}
		$plan = self::plan($sub['pending_plan_id']);
		if (!$plan || $plan['status'] !== 'T')
		{
			self::saveSubscription($sub['company_id'], array('pending_plan_id' => ':NULL'));
			self::log($sub['company_id'], 'scheduled', 'Scheduled plan change dropped: the plan is no longer available');
			return self::subscription($sub['company_id']);
		}
		$old = self::plan($sub['plan_id']);
		$data = array('plan_id' => (int) $plan['id'], 'status' => 'active', 'trial_ends_at' => ':NULL', 'pending_plan_id' => ':NULL',
			'cancel_at_period_end' => 0, 'cancelled_at' => ':NULL');
		if (self::isFree($plan))
		{
			$data['current_period_start'] = date('Y-m-d H:i:s', $now);
			$data['current_period_end'] = ':NULL';
		} else {
			// the first period of the new plan is due now: grace days to pay it
			$data['current_period_start'] = $end_str;
			$data['current_period_end'] = $end_str;
		}
		self::saveSubscription($sub['company_id'], $data);
		self::log($sub['company_id'], 'plan_changed', sprintf('Scheduled change applied: %s -> %s', $old ? $old['name'] : '?', $plan['name']));
		return self::subscription($sub['company_id']);
	}

	/**
	 * Company switches to a free plan itself. While it has paid time left the change waits for the end of
	 * the period (no refund); otherwise (no plan, trial, expired, free plan) it starts now.
	 * Returns 'scheduled' | 'switched' | false.
	 */
	public static function switchToFree($company_id, $plan, $user_id)
	{
		if (!self::isFree($plan) || (int) $plan['is_public'] !== 1)
		{
			return false;
		}
		$s = self::state($company_id);
		$sub = $s['subscription'];
		if ($sub && (int) $sub['plan_id'] === (int) $plan['id'] && $s['entitled'])
		{
			return false; // already on it
		}
		$paid_left = $sub && $s['entitled'] && $s['plan'] && !self::isFree($s['plan']) && $s['status'] !== 'trial'
			&& !empty($sub['current_period_end']) && strtotime($sub['current_period_end']) > time();
		if ($paid_left)
		{
			self::scheduleChange($company_id, $plan['id'], $user_id);
			return 'scheduled';
		}
		$now = date('Y-m-d H:i:s');
		$data = array('plan_id' => (int) $plan['id'], 'status' => 'active', 'current_period_start' => $now, 'current_period_end' => ':NULL',
			'trial_ends_at' => ':NULL', 'cancel_at_period_end' => 0, 'cancelled_at' => ':NULL', 'pending_plan_id' => ':NULL');
		if (!$sub || empty($sub['started_at']))
		{
			$data['started_at'] = $now;
		}
		self::saveSubscription($company_id, $data);
		self::log($company_id, 'assigned', sprintf('Plan %s chosen by the company (free plan)', $plan['name']), $user_id);
		return 'switched';
	}

	/**
	 * Unused paid time of the current plan, in seconds of the new plan (credit for an upgrade / plan change):
	 * value left = old price x remaining share of its period; converted at the new plan's price.
	 */
	public static function changeCreditSeconds($sub, $new_plan, $now = null)
	{
		$now = $now === null ? time() : (int) $now;
		if (empty($sub) || empty($new_plan) || (int) $sub['plan_id'] === (int) $new_plan['id']
			|| !in_array($sub['status'], array('active', 'past_due'), true) || empty($sub['current_period_end']) || empty($sub['current_period_start']))
		{
			return 0;
		}
		$old = self::plan($sub['plan_id']);
		$end = strtotime($sub['current_period_end']);
		$start = strtotime($sub['current_period_start']);
		// worked out in USD (the base price of both plans; the payment by its USD value at the time it was made)
		$new_usd = self::priceIn($new_plan, self::BASE_CURRENCY);
		if (!$old || self::isFree($old) || $old['price'] === null || $end <= $now || $end <= $start || $new_usd === null || $new_usd <= 0)
		{
			return 0;
		}
		// only time that was actually paid for (not a trial or a period the administrator assigned), at the amount paid
		$paid = pjBillingTransactionModel::factory()->where('t1.company_id', (int) $sub['company_id'])->where('t1.plan_id', (int) $old['id'])
			->where('t1.status', 'paid')->where('t1.period_end >', date('Y-m-d H:i:s', $now))->where('t1.amount >', 0)
			->orderBy('t1.period_end DESC')->limit(1)->findAll()->getDataIndex(0);
		if (empty($paid))
		{
			return 0;
		}
		$paid_usd = isset($paid['amount_usd']) && $paid['amount_usd'] !== null && $paid['amount_usd'] !== ''
			? (float) $paid['amount_usd'] : (strtoupper((string) $paid['currency']) === 'USD' ? (float) $paid['amount'] : 0);
		if ($paid_usd <= 0)
		{
			return 0;
		}
		$old_len = max(86400, strtotime(self::addPeriod($sub['current_period_start'], $old)) - $start);
		$value_left = min($paid_usd, (float) $old['price']) * min(1, ($end - $now) / $old_len);
		$new_len = strtotime(self::addPeriod(date('Y-m-d H:i:s', $now), $new_plan)) - $now;
		return (int) floor($value_left / $new_usd * $new_len);
	}

	/**
	 * Can the company start this plan's free trial itself (website plans window / Pricing page)? A paid plan with
	 * trial days, once per company, for a company that never paid for a plan and is not on a paid plan or a trial now.
	 */
	public static function trialAvailable($company_id, $plan)
	{
		$company_id = (int) $company_id;
		if ($company_id <= 0 || !self::isReady() || !$plan || $plan['status'] !== 'T' || (int) $plan['is_public'] !== 1
			|| (int) $plan['trial_days'] <= 0 || self::isFree($plan) || $plan['price'] === null)
		{
			return false;
		}
		$sub = self::subscription($company_id);
		if ($sub && (int) $sub['used_trial'] === 1)
		{
			return false;
		}
		if ((int) pjBillingTransactionModel::factory()->where('t1.company_id', $company_id)->where('t1.status', 'paid')
			->where('t1.amount >', 0)->findCount()->getData() > 0)
		{
			return false;
		}
		$s = self::state($company_id);
		// on a paid plan (or a trial) now: nothing to try
		if ($s['source'] === 'plan' && $s['entitled'] && !empty($s['plan']) && (!self::isFree($s['plan']) || $s['status'] === 'trial'))
		{
			return false;
		}
		return true;
	}

	/** The company starts a plan's free trial on the website (rules: trialAvailable). No payment. */
	public static function startWebsiteTrial($company_id, $plan, $user_id)
	{
		if (!self::trialAvailable($company_id, $plan))
		{
			return false;
		}
		return self::assignPlan((int) $company_id, (int) $plan['id'], 'trial', '', $user_id, 'Free trial started on the website');
	}

	/**
	 * A trial that ended without payment goes back to the free plan for new companies (when that plan is free):
	 * the company keeps its free website instead of being suspended. Returns the free plan, or false.
	 */
	public static function trialFallbackPlan($sub)
	{
		if (empty($sub) || $sub['status'] !== 'trial')
		{
			return false;
		}
		$plan = self::defaultPlan();
		return $plan && self::isFree($plan) && (int) $plan['id'] !== (int) $sub['plan_id'] ? $plan : false;
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
		// Paying for the plan of a running trial: the trial days are kept, the paid period starts when the trial ends.
		if ($sub && $sub['status'] === 'trial' && (int) $sub['plan_id'] === (int) $plan['id'] && !empty($sub['trial_ends_at'])
			&& strtotime($sub['trial_ends_at']) > time())
		{
			$start = $sub['trial_ends_at'];
		}
		$end = self::addPeriod($start, $plan);
		// Plan change with paid time left on the old plan: that time is not lost, it is credited as extra time.
		$credit = $start === $now ? self::changeCreditSeconds($sub, $plan) : 0;
		if ($credit > 0)
		{
			$end = date('Y-m-d H:i:s', strtotime($end) + $credit);
		}

		$m->reset()->set('id', $txn['id'])->modify(array(
			'status' => 'paid', 'paid_at' => $now, 'provider_ref' => $provider_ref !== '' ? $provider_ref : ':NULL',
			'period_start' => $start, 'period_end' => $end, 'modified' => $now,
		));
		$data = array('plan_id' => (int) $plan['id'], 'status' => 'active', 'current_period_start' => $start,
			'current_period_end' => $end, 'trial_ends_at' => ':NULL', 'cancel_at_period_end' => 0, 'cancelled_at' => ':NULL');
		// the company is billed in the currency it paid in (renewals keep it); prices / rate of this payment kept
		if (self::isCurrency($txn['currency']))
		{
			$cur = strtoupper($txn['currency']);
			$list = $cur === 'INR' ? (isset($txn['price_inr']) ? $txn['price_inr'] : null) : (isset($txn['price_usd']) ? $txn['price_usd'] : null);
			$data['billing_currency'] = $cur;
			// recurring amount agreed with this payment (before a one-off discount / coupon): renewals keep it.
			// A payment recorded by the administrator uses the plan price in that currency.
			$agreed = $txn['list_amount'] !== null && $txn['list_amount'] !== '' ? $txn['list_amount'] : $txn['amount'];
			$data['price_amount'] = $txn['provider'] === 'manual' && $list !== null && $list !== '' ? $list : $agreed;
			if (!empty($txn['billing_country']))
			{
				$data['billing_country'] = $txn['billing_country'];
			}
			$data['price_usd'] = isset($txn['price_usd']) && $txn['price_usd'] !== null && $txn['price_usd'] !== '' ? $txn['price_usd'] : ':NULL';
			$data['exchange_rate'] = isset($txn['exchange_rate']) && $txn['exchange_rate'] !== null && $txn['exchange_rate'] !== '' ? $txn['exchange_rate'] : ':NULL';
		}
		if (!$sub || empty($sub['started_at']))
		{
			$data['started_at'] = $now;
		}
		// a payment is an explicit choice of this plan: an earlier scheduled change (downgrade) is dropped
		if ($sub && !empty($sub['pending_plan_id']))
		{
			$data['pending_plan_id'] = ':NULL';
		}
		self::saveSubscription($cid, $data);
		$m->commit();
		self::log($cid, 'payment', sprintf('Payment %s %s (%s, %s) - plan %s active until %s%s', $txn['amount'], $txn['currency'], $txn['provider'], $provider_ref, $plan['name'], $end,
			$credit > 0 ? sprintf(' (incl. %.1f days credited from the previous plan)', $credit / 86400) : ''), $user_id);
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

	/**
	 * New pending transaction. The amount comes from the plan, never from the request; $quote
	 * (pjSubscriptionPromos::quote, worked out on the server) takes the discount / coupon off.
	 * $plan is the plan as charged (planInCurrency): its price / currency are the charged ones. A snapshot of
	 * both plan prices, the exchange rate and the USD value of the amount is stored with the payment.
	 */
	public static function createTransaction($company_id, $plan, $kind, $provider, $user_id, $quote = null, $extra = array())
	{
		if (!self::isCurrency($plan['currency']))
		{
			return false;
		}
		$uuid = bin2hex(function_exists('random_bytes') ? random_bytes(16) : md5(uniqid(mt_rand(), true), true));
		$data = array(
			'uuid' => $uuid, 'company_id' => (int) $company_id, 'plan_id' => (int) $plan['id'], 'kind' => $kind,
			'amount' => number_format((float) $plan['price'], 2, '.', ''), 'currency' => $plan['currency'],
			'status' => 'pending', 'provider' => $provider, 'created_by' => $user_id ? (int) $user_id : ':NULL',
			'created' => date('Y-m-d H:i:s'),
		);
		if (!empty($quote) && !empty($quote['promo_ids']))
		{
			$data['amount'] = number_format((float) $quote['amount'], 2, '.', '');
			$data['list_amount'] = number_format((float) $quote['list'], 2, '.', '');
			$data['discount_amount'] = number_format((float) $quote['discount'], 2, '.', '');
			$data['promo_ids'] = implode(',', $quote['promo_ids']);
			$data['coupon_code'] = $quote['coupon_code'] !== '' ? $quote['coupon_code'] : ':NULL';
		}
		$rate = self::rate();
		$fmt = function ($v) { return $v === null || $v === '' ? ':NULL' : number_format((float) $v, 2, '.', ''); };
		$data['price_usd'] = $fmt(isset($plan['prices']) ? self::priceIn($plan, 'USD') : null);
		$data['price_inr'] = $fmt(isset($plan['prices']) ? self::priceIn($plan, 'INR') : null);
		$data['exchange_rate'] = number_format($rate, 6, '.', '');
		$data['amount_usd'] = $fmt(self::toUsd($data['amount'], $plan['currency'], $rate));
		$snap = self::rateSnapshotId();
		$data['rate_snapshot_id'] = $snap > 0 ? $snap : ':NULL';
		// billing country chosen at the checkout (website country selector), else the company's country
		$country = !empty($extra['billing_country']) && self::isCountry($extra['billing_country'])
			? strtoupper($extra['billing_country']) : self::billingCountry($company_id);
		$data['billing_country'] = $country !== '' ? $country : ':NULL';
		// a payment started on the StayOwned website returns there after the gateway
		if (!empty($extra['return_url']))
		{
			$data['return_url'] = (string) $extra['return_url'];
			$data['channel'] = 'web';
		}
		$id = pjBillingTransactionModel::factory()->setAttributes($data)->insert()->getInsertId();
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
		// scheduled plan changes (downgrades) whose period has ended
		foreach (pjCompanySubscriptionModel::factory()->where('t1.pending_plan_id IS NOT NULL')->findAll()->getData() as $sub)
		{
			$after = self::applyDueChange($sub);
			if ((int) $after['plan_id'] !== (int) $sub['plan_id'])
			{
				$changed++;
			}
		}
		foreach (pjCompanySubscriptionModel::factory()->whereIn('t1.status', array('trial', 'active', 'past_due'))->findAll()->getData() as $sub)
		{
			list($status) = self::effectiveStatus($sub);
			// trial over and not paid: back to the free plan (keeps the free website), not suspended
			if ($status === 'expired' && ($free = self::trialFallbackPlan($sub)))
			{
				$now = date('Y-m-d H:i:s');
				self::saveSubscription($sub['company_id'], array('plan_id' => (int) $free['id'], 'status' => 'active',
					'current_period_start' => $now, 'current_period_end' => ':NULL', 'trial_ends_at' => ':NULL',
					'cancel_at_period_end' => 0, 'cancelled_at' => ':NULL', 'pending_plan_id' => ':NULL'));
				self::log($sub['company_id'], 'status', sprintf('Trial ended without payment: back to %s (automatic)', $free['name']));
				$changed++;
				continue;
			}
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
