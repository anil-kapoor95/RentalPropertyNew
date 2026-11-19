<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Money amounts are written with pjMoney (currency symbol, decimals and separators from the saved settings).
 *
 * Data of the Super Admin dashboard (pjAdmin::pjActionIndex for role 1, view pjAdmin/pjActionPlatform.php).
 *
 * Platform-level information only: companies, subscriptions, plans, plan coupons, platform billing and the health
 * of the platform services. No bookings, guests, properties or other operational data of a company.
 * Every number comes from the database; where something cannot be measured the result says so ('unknown').
 */
class pjPlatformDashboard
{
	/**
	 * Date range of the filter: array(from Y-m-d, to Y-m-d, key, days, error).
	 * Presets end today; a custom range must be valid dates, start on or before its end and not start in the future
	 * (an end in the future is cut to today). An invalid custom range falls back to the last 30 days with error = true.
	 */
	public static function range($key, $from, $to)
	{
		$today = date('Y-m-d');
		$key = in_array($key, array('7', '30', '90', '12m', 'month', 'custom'), true) ? $key : '30';
		$error = false;
		if ($key === 'custom')
		{
			$valid = function ($d) {
				if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $d, $m)) { return ''; }
				return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $d : '';
			};
			$f = $valid($from);
			$t = $valid($to);
			if ($t !== '' && $t > $today)
			{
				$t = $today;
			}
			if ($f !== '' && $t !== '' && $f <= $t)
			{
				return array($f, $t, 'custom', self::days($f, $t), false);
			}
			$key = '30';
			$error = true;
		}
		if ($key === 'month')
		{
			return array(date('Y-m-01'), $today, 'month', (int) date('j'), $error);
		}
		if ($key === '12m')
		{
			// the current month and the 11 before it
			$f = date('Y-m-01', strtotime(date('Y-m-01') . ' -11 months'));
			return array($f, $today, '12m', self::days($f, $today), $error);
		}
		$days = (int) $key;
		return array(date('Y-m-d', strtotime($today . ' -' . ($days - 1) . ' days')), $today, $key, $days, $error);
	}

	/** Number of days from..to, both included. */
	public static function days($from, $to)
	{
		return (int) round((strtotime($to . ' 12:00:00') - strtotime($from . ' 12:00:00')) / 86400) + 1;
	}

	/** End of the period as a time (end of the "to" day, never later than now). */
	public static function asOf($to)
	{
		return min(time(), strtotime($to . ' 23:59:59'));
	}

	public static function betweenSql($col, $from, $to)
	{
		return self::between($col, $from, $to);
	}

	private static function between($col, $from, $to)
	{
		return sprintf("%s BETWEEN '%s 00:00:00' AND '%s 23:59:59'", $col, $from, $to);
	}

	/** The period of the same length just before from..to: array(from, to). */
	public static function previous($from, $to)
	{
		$days = self::days($from, $to);
		return array(date('Y-m-d', strtotime($from . ' -' . $days . ' days')), date('Y-m-d', strtotime($from . ' -1 day')));
	}

	/**
	 * Change of $cur against $prev for the card badges: array(up|down|flat|new, percent),
	 * or null when there is nothing to compare (both zero).
	 */
	public static function change($cur, $prev)
	{
		$cur = (float) $cur;
		$prev = (float) $prev;
		if ($prev <= 0)
		{
			return $cur > 0 ? array('new', null) : null;
		}
		$pct = (int) round(($cur - $prev) * 100 / $prev);
		return array($pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat'), $pct);
	}

	/* ------------------------------------------------------------------ billing overview */

	/**
	 * Platform billing in the range: revenue per currency (and of the previous period), paid / open / failed
	 * payments, companies past due at the end of the period, and the renewals / trial endings of the next 30 days
	 * from today (a look ahead, so not tied to the period; the first $limit listed).
	 */
	public static function billing($from, $to, $limit = 4)
	{
		$out = array('ready' => false, 'revenue' => array(), 'revenue_prev' => array(), 'paid' => 0, 'pending' => 0, 'failed' => 0,
			'past_due' => 0, 'renewals' => 0, 'renewals_list' => array(), 'renewals_amount' => array(),
			'trials_ending' => 0, 'trials_amount' => array());
		if (!class_exists('pjEntitlement') || !pjEntitlement::isReady())
		{
			return $out;
		}
		$out['ready'] = true;
		$out['revenue'] = self::revenue($from, $to);
		list($pf, $pt) = self::previous($from, $to);
		$out['revenue_prev'] = self::revenue($pf, $pt);
		$out['paid'] = (int) pjBillingTransactionModel::factory()->where('t1.status', 'paid')->where(self::between('t1.paid_at', $from, $to))->findCount()->getData();
		// payments started in the period that are still open, or failed / were cancelled
		foreach (pjBillingTransactionModel::factory()->select('t1.status, COUNT(*) AS `cnt`')->whereIn('t1.status', array('pending', 'failed', 'cancelled'))
			->where(self::between('t1.created', $from, $to))->groupBy('t1.status')->findAll()->getDataPair('status', 'cnt') as $st => $cnt)
		{
			if ($st === 'pending') { $out['pending'] += (int) $cnt; } else { $out['failed'] += (int) $cnt; }
		}
		$names = pjCompanyModel::factory()->findAll()->getDataPair('id', 'name');
		$plans = array();
		foreach (pjEntitlement::plans() as $p) { $plans[(int) $p['id']] = $p; }
		$as_of = self::asOf($to);
		$now = time();
		$until = strtotime('+30 days');
		foreach (pjCompanySubscriptionModel::factory()->findAll()->getData() as $sub)
		{
			if (!isset($names[(int) $sub['company_id']])) { continue; }
			$sub = pjEntitlement::applyDueChange($sub);
			$then = self::statusAt($sub, $as_of);
			if ($then !== null && $then[0] === 'past_due') { $out['past_due']++; }
			list($st) = pjEntitlement::effectiveStatus($sub);
			$end = !empty($sub['current_period_end']) ? strtotime($sub['current_period_end']) : 0;
			if (!in_array($st, array('active', 'trial'), true) || (int) $sub['cancel_at_period_end'] === 1 || $end < $now || $end > $until)
			{
				continue;
			}
			$plan = isset($plans[(int) $sub['plan_id']]) ? $plans[(int) $sub['plan_id']] : null;
			$price = $plan && $plan['price'] !== null ? (float) $plan['price'] : 0;
			// a trial that ends is not a renewal: the company pays only if it buys the plan
			$bucket = $st === 'trial' ? 'trials' : 'renewals';
			if ($bucket === 'trials') { $out['trials_ending']++; } else { $out['renewals']++; }
			if ($price > 0)
			{
				$cur = strtoupper((string) $plan['currency']);
				$k = $bucket . '_amount';
				$out[$k][$cur] = (isset($out[$k][$cur]) ? $out[$k][$cur] : 0) + $price;
			}
			$out['renewals_list'][] = array('company' => (string) $names[(int) $sub['company_id']], 'plan' => $plan ? (string) $plan['name'] : '',
				'date' => date('Y-m-d H:i:s', $end), 'status' => $st, 'amount' => $price > 0 ? pjMoney::format($price, $plan['currency']) : '');
		}
		usort($out['renewals_list'], function ($a, $b) { return strcmp($a['date'], $b['date']); });
		$out['renewals_list'] = array_slice($out['renewals_list'], 0, $limit);
		return $out;
	}

	/**
	 * Paid revenue in the range, in USD: array('USD' => total) (refunded / failed / open payments are not counted).
	 * Every payment is counted at the USD value stored with it at checkout (amount_usd: INR payments at the exchange
	 * rate of that day), so a later rate change never changes past revenue.
	 */
	public static function revenue($from, $to)
	{
		$out = array();
		if (!class_exists('pjEntitlement') || !pjEntitlement::isReady())
		{
			return $out;
		}
		$row = pjBillingTransactionModel::factory()->select('SUM(t1.amount_usd) AS `total`, COUNT(*) AS `cnt`')->where('t1.status', 'paid')
			->where('t1.amount_usd IS NOT NULL')->where(self::between('t1.paid_at', $from, $to))->findAll()->getDataIndex(0);
		if (!empty($row) && (int) $row['cnt'] > 0)
		{
			$out[pjEntitlement::BASE_CURRENCY] = round((float) $row['total'], 2);
		}
		return $out;
	}

	/** The subscription row had started by $time (rows started later describe a later period). */
	private static function existedAt($sub, $time)
	{
		$start = !empty($sub['started_at']) ? $sub['started_at'] : (!empty($sub['created']) ? $sub['created'] : null);
		return $start === null || strtotime($start) <= $time;
	}

	private static $events = null;

	/**
	 * Subscription status of a company at $time: array(status, entitled) or null (no subscription then).
	 * The current subscription row answers for its own period; for an earlier time (the row was replaced later,
	 * e.g. a new plan or trial assigned after $time) the status is rebuilt from the subscription event log.
	 */
	public static function statusAt($sub, $time)
	{
		$r = self::stateAt($sub, $time);
		return $r === null ? null : array($r[0], $r[1]);
	}

	private static $planIdsByName = null;

	/**
	 * Like statusAt, with the plan in force: array(status, entitled, plan_id) or null. For an earlier time the plan
	 * is read from the log ("Plan X assigned ...", "... plan X active until ...").
	 */
	public static function stateAt($sub, $time)
	{
		if (self::existedAt($sub, $time))
		{
			$r = pjEntitlement::effectiveStatus($sub, $time);
			return array($r[0], $r[1], (int) $sub['plan_id']);
		}
		if (self::$events === null)
		{
			self::$events = array();
			foreach (pjSubscriptionEventModel::factory()->select('t1.company_id, t1.type, t1.message, t1.created')
				->whereIn('t1.type', array('assigned', 'payment', 'extended', 'status', 'removed', 'plan_changed'))
				->orderBy('t1.created ASC, t1.id ASC')->findAll()->getData() as $e)
			{
				self::$events[(int) $e['company_id']][] = $e;
			}
		}
		if (self::$planIdsByName === null)
		{
			self::$planIdsByName = array();
			foreach (pjEntitlement::plans() as $p) { self::$planIdsByName[mb_strtolower(trim((string) $p['name']))] = (int) $p['id']; }
		}
		$byName = function ($name) {
			$k = mb_strtolower(trim((string) $name));
			return isset(self::$planIdsByName[$k]) ? self::$planIdsByName[$k] : 0;
		};
		$state = null; // array(status, end, plan_id)
		$cid = (int) $sub['company_id'];
		foreach (isset(self::$events[$cid]) ? self::$events[$cid] : array() as $e)
		{
			if (strtotime($e['created']) > $time) { break; }
			$msg = (string) $e['message'];
			switch ($e['type'])
			{
				case 'assigned':
					$plan = preg_match('/^Plan (.+?) (?:assigned|chosen)/', $msg, $pm) ? $byName($pm[1]) : 0;
					if (preg_match('/assigned \((trial|active)\) until (\d{4}-\d{2}-\d{2}[ \d:]*)/', $msg, $m)) {
						$state = array($m[1], trim($m[2]), $plan);
					} else {
						// plan for new companies / free plan chosen: no end date
						$state = array('active', null, $plan);
					}
					break;
				case 'payment':
					if (preg_match('/plan (.+?) active until (\d{4}-\d{2}-\d{2}[ \d:]*)/', $msg, $m)) { $state = array('active', trim($m[2]), $byName($m[1])); }
					break;
				case 'plan_changed':
					if ($state !== null && preg_match('/-> (.+)$/', $msg, $m)) { $state[2] = $byName($m[1]); }
					break;
				case 'extended':
					if ($state !== null && preg_match('/set to (\d{4}-\d{2}-\d{2}[ \d:]*)/', $msg, $m)) { $state[1] = trim($m[1]); }
					break;
				case 'status':
					if ($state !== null && preg_match('/-> (trial|active|past_due|cancelled|expired)/', $msg, $m)) { $state[0] = $m[1]; }
					break;
				case 'removed':
					$state = null;
					break;
			}
		}
		if ($state === null)
		{
			// nothing logged before $time: the row existed then (created) but its dates are later; count it as it is now
			if (!empty($sub['created']) && strtotime($sub['created']) <= $time)
			{
				$r = pjEntitlement::effectiveStatus($sub, $time);
				return array($r[0], $r[1], (int) $sub['plan_id']);
			}
			return null;
		}
		$row = array('status' => $state[0], 'current_period_end' => $state[1], 'trial_ends_at' => $state[0] === 'trial' ? $state[1] : null, 'cancel_at_period_end' => 0);
		$r = pjEntitlement::effectiveStatus($row, $time);
		return array($r[0], $r[1], $state[2] > 0 ? $state[2] : (int) $sub['plan_id']);
	}
	/**
	 * Latest billing transactions (all companies, or one company): newest first; with $from / $to only those of the
	 * period (by payment date, or the start date for unpaid ones).
	 * Each: date, company, plan, kind, amount, status, method, coupon.
	 */
	public static function transactions($company_id = 0, $limit = 5, $from = null, $to = null)
	{
		if (!class_exists('pjEntitlement') || !pjEntitlement::isReady())
		{
			return array();
		}
		$m = pjBillingTransactionModel::factory()->orderBy('COALESCE(t1.paid_at, t1.created) DESC, t1.id DESC')->limit((int) $limit);
		if ((int) $company_id > 0)
		{
			$m->where('t1.company_id', (int) $company_id);
		}
		if ($from !== null && $to !== null)
		{
			$m->where(self::between('COALESCE(t1.paid_at, t1.created)', $from, $to));
		}
		$rows = $m->findAll()->getData();
		if (empty($rows))
		{
			return array();
		}
		$names = pjCompanyModel::factory()->findAll()->getDataPair('id', 'name');
		$plans = array();
		foreach (pjEntitlement::plans() as $p) { $plans[(int) $p['id']] = $p['name']; }
		$out = array();
		foreach ($rows as $t)
		{
			$out[] = array(
				'date' => !empty($t['paid_at']) ? $t['paid_at'] : $t['created'],
				'company' => isset($names[(int) $t['company_id']]) ? (string) $names[(int) $t['company_id']] : '',
				'plan' => isset($plans[(int) $t['plan_id']]) ? (string) $plans[(int) $t['plan_id']] : '',
				'kind' => (string) $t['kind'],
				// the amount charged, in its currency; the USD value stored with it beside (dashboards count in USD)
				'amount' => pjMoney::format($t['amount'], $t['currency']),
				'amount_usd' => isset($t['amount_usd']) && $t['amount_usd'] !== null && strtoupper((string) $t['currency']) !== 'USD' ? pjMoney::format($t['amount_usd'], 'USD') : '',
				'status' => (string) $t['status'],
				'method' => class_exists('pjSubscriptionBilling') ? (string) pjSubscriptionBilling::methodName($t['provider']) : (string) $t['provider'],
				'coupon' => isset($t['coupon_code']) ? (string) $t['coupon_code'] : '',
			);
		}
		return $out;
	}

	/**
	 * Billing of one company (company dashboards): plan, status, next billing date, last payment, amounts.
	 * Null when subscriptions are not set up.
	 */
	public static function companyBilling($company_id)
	{
		if (!class_exists('pjEntitlement') || !pjEntitlement::isReady() || (int) $company_id <= 0)
		{
			return null;
		}
		$s = pjEntitlement::state($company_id);
		$sub = $s['subscription'];
		$plan = $s['plan'];
		$last = pjBillingTransactionModel::factory()->where('t1.company_id', (int) $company_id)->whereIn('t1.status', array('paid', 'pending', 'failed', 'cancelled'))
			->orderBy('t1.created DESC')->limit(1)->findAll()->getDataIndex(0);
		$paid = pjBillingTransactionModel::factory()->where('t1.company_id', (int) $company_id)->where('t1.status', 'paid')
			->orderBy('t1.paid_at DESC')->limit(1)->findAll()->getDataIndex(0);
		$pending = !empty($sub['pending_plan_id']) ? pjEntitlement::plan($sub['pending_plan_id']) : false;
		$is_free = $plan && pjEntitlement::isFree($plan);
		$billing_currency = pjEntitlement::billingCurrency($company_id);
		$billed = $plan ? (pjEntitlement::planInCurrency($plan, $billing_currency) ?: $plan) : false;
		// payment status: what the company has to know about paying
		if (!$sub)
		{
			$pay = 'none';
		} elseif (in_array($s['status'], array('past_due'), true)) {
			$pay = 'overdue';
		} elseif (in_array($s['status'], array('expired', 'cancelled'), true)) {
			$pay = 'ended';
		} elseif ($s['status'] === 'trial') {
			$pay = 'trial';
		} elseif ($is_free) {
			$pay = 'free';
		} elseif (!empty($last) && in_array($last['status'], array('failed', 'cancelled'), true)) {
			$pay = 'failed';
		} elseif (!empty($last) && $last['status'] === 'pending') {
			$pay = 'pending';
		} else {
			$pay = 'paid';
		}
		$next = null;
		if ($sub && !empty($sub['current_period_end']) && in_array($s['status'], array('active', 'trial', 'past_due'), true) && (int) $sub['cancel_at_period_end'] !== 1)
		{
			$next = $sub['current_period_end'];
		}
		return array(
			'state' => $s,
			'plan' => $plan,
			'subscription' => $sub,
			'status' => $s['status'],
			'source' => $s['source'],
			'entitled' => $s['entitled'],
			'payment' => $pay,
			'next_billing' => $next,
			'ends_at' => $sub && !empty($sub['current_period_end']) ? $sub['current_period_end'] : null,
			'cancel_at_end' => $sub && (int) $sub['cancel_at_period_end'] === 1,
			'pending_plan' => $pending ? $pending['name'] : '',
			// the company sees its plan price in its own billing currency
			'price' => $billed && $billed['price'] !== null && (float) $billed['price'] > 0 ? pjMoney::format($billed['price'], $billed['currency']) : '',
			'billing_currency' => $billing_currency,
			'interval' => $plan ? array((int) max(1, (int) $plan['interval_count']), $plan['billing_interval'] === 'year' ? 'year' : 'month') : null,
			'last_payment' => !empty($paid) ? array('date' => $paid['paid_at'], 'amount' => pjMoney::format($paid['amount'], $paid['currency'])) : null,
			'usage' => pjEntitlement::usage($company_id),
			'limits' => $s['limits'],
			'grace_ends_at' => $s['grace_ends_at'],
		);
	}

	/* ------------------------------------------------------------------ redesigned dashboard: one snapshot per period */

	/**
	 * Steps of the charts for from..to: per day up to 31 days, per week up to 124 days, per month beyond.
	 * Returns array(bucket, steps[] {start, end (Y-m-d), label, tip}).
	 */
	public static function buckets($from, $to)
	{
		$days = self::days($from, $to);
		$bucket = $days <= 31 ? 'day' : ($days <= 124 ? 'week' : 'month');
		$steps = array();
		$t = strtotime($from . ' 12:00:00');
		$end = strtotime($to . ' 12:00:00');
		while ($t <= $end)
		{
			if ($bucket === 'day') {
				$next = strtotime('+1 day', $t);
			} elseif ($bucket === 'week') {
				$next = strtotime('+7 days', $t);
			} else {
				$next = strtotime(date('Y-m-01', $t) . ' 12:00:00 +1 month');
			}
			$steps[] = array('start' => date('Y-m-d', $t), 'end' => date('Y-m-d', min($end, $next - 86400)));
			$t = $next;
		}
		return array($bucket, $steps);
	}

	/** Index of the step a date (Y-m-d H:i:s) falls in, or -1. */
	private static function stepOf($steps, $datetime)
	{
		$d = substr((string) $datetime, 0, 10);
		foreach ($steps as $i => $s)
		{
			if ($d >= $s['start'] && $d <= $s['end']) { return $i; }
		}
		return -1;
	}

	/**
	 * Monthly recurring revenue of one subscription in USD: the recurring amount the company agreed (in its billing
	 * currency) at the exchange rate stored with its last payment; the plan's USD price when nothing is stored.
	 */
	private static function monthlyUsd($plan, $sub = null)
	{
		$base = self::monthly($plan);
		if ($base <= 0 || empty($sub) || empty($sub['price_amount']) || (float) $sub['price_amount'] <= 0 || empty($sub['billing_currency']))
		{
			return $base;
		}
		$usd = class_exists('pjEntitlement') ? pjEntitlement::toUsd($sub['price_amount'], $sub['billing_currency'], !empty($sub['exchange_rate']) ? (float) $sub['exchange_rate'] : null) : null;
		if ($usd === null || $usd <= 0)
		{
			return $base;
		}
		$months = max(1, (int) $plan['interval_count']) * ($plan['billing_interval'] === 'year' ? 12 : 1);
		return round($usd / $months, 2);
	}

	/** Price of a plan per month (yearly plans / 12, plans of several months / months); 0 for free plans. */
	private static function monthly($plan)
	{
		if (empty($plan) || $plan['price'] === null || (float) $plan['price'] <= 0) { return 0.0; }
		$months = max(1, (int) $plan['interval_count']) * ($plan['billing_interval'] === 'year' ? 12 : 1);
		return round((float) $plan['price'] / $months, 2);
	}

	/**
	 * Everything the Super Admin dashboard shows for the period from..to (real data only). Counts of things are taken
	 * at the end of each step / of the period; events (revenue, sign-ups, trials, payments) inside it.
	 * Also the JSON answer of pjAdmin::pjActionPlatformData.
	 */
	public static function snapshot($from, $to)
	{
		$ready = class_exists('pjEntitlement') && pjEntitlement::isReady();
		list($pf, $pt) = self::previous($from, $to);
		list($bucket, $steps) = self::buckets($from, $to);
		$n = count($steps);
		$zero = array_fill(0, $n, 0);
		$now = time();
		$endTs = self::asOf($to);
		$prevEndTs = self::asOf($pt);
		$stepEnd = array();
		$stepStart = array();
		foreach ($steps as $i => $s)
		{
			$stepEnd[$i] = min($now, strtotime($s['end'] . ' 23:59:59'));
			$stepStart[$i] = strtotime($s['start'] . ' 00:00:00') - 1;
		}

		$plans = $ready ? pjEntitlement::plans() : array();
		$companies = pjCompanyModel::factory()->select('t1.id, t1.name, t1.status, t1.created')->findAll()->getData();
		$created = array();
		foreach ($companies as $c) { $created[(int) $c['id']] = !empty($c['created']) ? strtotime($c['created']) : 0; }
		$existsAt = function ($cid, $ts) use ($created) { return isset($created[$cid]) && $created[$cid] <= $ts; };

		// ---- revenue: paid payments in the period (and the period before), per plan and kind, in USD (the value
		// stored with each payment at checkout; INR payments at that day's rate, never recalculated)
		$paidRows = $ready ? pjBillingTransactionModel::factory()->select('t1.company_id, t1.plan_id, t1.kind, t1.amount_usd AS `amount`, t1.amount AS `amount_orig`, t1.currency AS `currency_orig`, t1.paid_at, t1.coupon_code')
			->where('t1.status', 'paid')->where('t1.amount_usd IS NOT NULL')->where(self::between('t1.paid_at', $pf, $to))->findAll()->getData() : array();
		// currency-wise totals (what was actually collected, in the charged currencies) beside the consolidated USD
		$collected = array();
		foreach ($paidRows as $k3 => $t3)
		{
			$paidRows[$k3]['currency'] = pjEntitlement::BASE_CURRENCY;
			if (substr($t3['paid_at'], 0, 10) >= $from && (float) $t3['amount_orig'] > 0)
			{
				$c3 = strtoupper((string) $t3['currency_orig']);
				$collected[$c3] = (isset($collected[$c3]) ? $collected[$c3] : 0) + (float) $t3['amount_orig'];
			}
		}
		ksort($collected);
		$revenue = array();
		$revenuePrev = array();
		foreach ($paidRows as $t)
		{
			$cur = strtoupper((string) $t['currency']);
			$day = substr($t['paid_at'], 0, 10);
			if ($day >= $from) { $revenue[$cur] = (isset($revenue[$cur]) ? $revenue[$cur] : 0) + (float) $t['amount']; }
			elseif ($day <= $pt) { $revenuePrev[$cur] = (isset($revenuePrev[$cur]) ? $revenuePrev[$cur] : 0) + (float) $t['amount']; }
		}
		// the dashboard is kept in USD (plan prices are their USD base price, payments their stored USD value)
		$main = $ready ? pjEntitlement::BASE_CURRENCY : 'USD';
		ksort($revenue);

		// revenue per step: by plan (the four largest plans, then "Other") and by kind, in the main currency
		$byPlan = array();
		$byKind = array('new' => $zero, 'upgrade' => $zero, 'renewal' => $zero);
		$revSeries = $zero;
		$otherCurrency = false;
		foreach ($paidRows as $t)
		{
			if (substr($t['paid_at'], 0, 10) < $from) { continue; }
			if (strtoupper((string) $t['currency']) !== $main) { $otherCurrency = true; continue; }
			$i = self::stepOf($steps, $t['paid_at']);
			if ($i < 0) { continue; }
			$pid = (int) $t['plan_id'];
			if (!isset($byPlan[$pid])) { $byPlan[$pid] = $zero; }
			$byPlan[$pid][$i] += (float) $t['amount'];
			$k = isset($byKind[$t['kind']]) ? $t['kind'] : 'new';
			$byKind[$k][$i] += (float) $t['amount'];
			$revSeries[$i] += (float) $t['amount'];
		}
		uasort($byPlan, function ($a, $b) { return array_sum($b) <=> array_sum($a); });
		$planSeries = array();
		$other = $zero;
		$k = 0;
		foreach ($byPlan as $pid => $vals)
		{
			if ($k < 4) {
				$planSeries[] = array('name' => isset($plans[$pid]) ? $plans[$pid]['name'] : '#' . $pid, 'values' => array_map(function ($v) { return round($v, 2); }, $vals));
			} else {
				foreach ($vals as $i => $v) { $other[$i] += $v; }
			}
			$k++;
		}
		if (array_sum($other) > 0) { $planSeries[] = array('name' => __('dash_other', true), 'values' => $other, 'other' => true); }
		$kindSeries = array();
		foreach (array('new' => 'dash_kind_new', 'upgrade' => 'dash_kind_upgrade', 'renewal' => 'dash_kind_renewal') as $kk => $lbl)
		{
			$kindSeries[] = array('name' => __($lbl, true), 'values' => array_map(function ($v) { return round($v, 2); }, $byKind[$kk]));
		}

		// ---- companies: total at each step end, new and left per step
		$companiesAt = function ($ts) use ($created) { $c = 0; foreach ($created as $t) { if ($t <= $ts) { $c++; } } return $c; };
		$newSeries = $zero;
		foreach ($created as $cid => $ts)
		{
			if ($ts === 0) { continue; }
			$i = self::stepOf($steps, date('Y-m-d', $ts));
			if ($i >= 0) { $newSeries[$i]++; }
		}
		$totalSeries = array();
		foreach ($stepEnd as $i => $ts) { $totalSeries[$i] = $companiesAt($ts); }

		// ---- subscriptions: active / trial / MRR at the end of each step, and companies that lost their plan per step
		$subs = $ready ? pjCompanySubscriptionModel::factory()->findAll()->getData() : array();
		foreach ($subs as $k2 => $sub) { $subs[$k2] = pjEntitlement::applyDueChange($sub); }
		$activeSeries = $zero;
		$mrrSeries = $zero;
		$trialSeries = $zero;
		$leftSeries = $zero;
		$at = function ($ts) use ($subs, $plans, $existsAt, $main) {
			$o = array('active' => 0, 'trial' => 0, 'paying' => 0, 'mrr' => array(), 'ended' => 0, 'plans' => array());
			foreach ($subs as $sub)
			{
				$cid = (int) $sub['company_id'];
				if (!$existsAt($cid, $ts)) { continue; }
				$st = self::stateAt($sub, $ts);
				if ($st === null) { continue; }
				list($status, $entitled, $pid) = $st;
				if (!$entitled) { $o['ended']++; continue; }
				$o['active']++;
				if (!isset($o['plans'][$pid])) { $o['plans'][$pid] = array('count' => 0, 'trial' => 0, 'mrr' => 0.0); }
				$o['plans'][$pid]['count']++;
				if ($status === 'trial') { $o['trial']++; $o['plans'][$pid]['trial']++; continue; }
				$plan = isset($plans[$pid]) ? $plans[$pid] : null;
				$m = self::monthlyUsd($plan, (int) $sub['plan_id'] === (int) $pid ? $sub : null);
				if ($m > 0)
				{
					$o['paying']++;
					$cur = strtoupper((string) $plan['currency']);
					$o['mrr'][$cur] = (isset($o['mrr'][$cur]) ? $o['mrr'][$cur] : 0) + $m;
					$o['plans'][$pid]['mrr'] += $m;
				}
			}
			return $o;
		};
		foreach ($stepEnd as $i => $ts)
		{
			$a = $at($ts);
			$activeSeries[$i] = $a['active'];
			$trialSeries[$i] = $a['trial'];
			$mrrSeries[$i] = round(isset($a['mrr'][$main]) ? $a['mrr'][$main] : 0, 2);
		}
		foreach ($subs as $sub)
		{
			foreach ($steps as $i => $s)
			{
				$before = self::stateAt($sub, $stepStart[$i]);
				$after = self::stateAt($sub, $stepEnd[$i]);
				if ($before !== null && $before[1] && ($after === null || !$after[1])) { $leftSeries[$i]--; }
			}
		}
		$end = $at($endTs);
		$prevEnd = $at($prevEndTs);

		// ---- plan mix at the end of the period
		$companiesEnd = $companiesAt($endTs);
		$withSub = 0;
		foreach ($subs as $sub) { if ($existsAt((int) $sub['company_id'], $endTs) && self::stateAt($sub, $endTs) !== null) { $withSub++; } }
		$mix = array();
		foreach ($end['plans'] as $pid => $v)
		{
			$plan = isset($plans[$pid]) ? $plans[$pid] : null;
			$mix[] = array('name' => $plan ? $plan['name'] : '#' . $pid, 'count' => $v['count'], 'trial' => $v['trial'],
				'free' => !$plan || self::monthly($plan) <= 0, 'mrr' => round($v['mrr'], 2), 'currency' => $plan ? strtoupper((string) $plan['currency']) : $main,
				'mrr_text' => pjMoney::format(round($v['mrr'], 2), $plan ? $plan['currency'] : $main),
				'price' => $plan && $plan['price'] !== null && (float) $plan['price'] > 0 ? pjMoney::format($plan['price'], $plan['currency']) : '', 'sort' => $plan ? (int) $plan['sort'] : 999);
		}
		usort($mix, function ($a, $b) { return $a['free'] === $b['free'] ? $b['count'] <=> $a['count'] : ($a['free'] ? 1 : -1); });

		// ---- trial -> paid: trials started in a period that were followed by a paid payment
		$events = $ready ? pjSubscriptionEventModel::factory()->select('t1.company_id, t1.created')->where('t1.type', 'assigned')
			->where("t1.message LIKE '%(trial)%'")->where(self::between('t1.created', $pf, $to))->findAll()->getData() : array();
		$allPaid = $ready ? pjBillingTransactionModel::factory()->select('t1.company_id, t1.paid_at')->where('t1.status', 'paid')->findAll()->getData() : array();
		$conv = function ($f, $t) use ($events, $allPaid, $steps) {
			$trials = array();
			foreach ($events as $e)
			{
				$d = substr($e['created'], 0, 10);
				if ($d < $f || $d > $t) { continue; }
				$cid = (int) $e['company_id'];
				if (!isset($trials[$cid]) || $e['created'] < $trials[$cid]) { $trials[$cid] = $e['created']; }
			}
			$converted = 0;
			foreach ($trials as $cid => $start)
			{
				foreach ($allPaid as $p) { if ((int) $p['company_id'] === $cid && $p['paid_at'] >= $start) { $converted++; break; } }
			}
			return array(count($trials), $converted);
		};
		list($trialsN, $convertedN) = $conv($from, $to);
		list($trialsP, $convertedP) = $conv($pf, $pt);
		$trialsStarted = $zero;
		foreach ($events as $e)
		{
			if (substr($e['created'], 0, 10) < $from) { continue; }
			$i = self::stepOf($steps, $e['created']);
			if ($i >= 0) { $trialsStarted[$i]++; }
		}

		// ---- funnel: the companies that signed up in the period (one cohort), and how far each of them got by the end
		// of the period. Every step is a part of the step before it: chose a plan (trial or paid) -> paid -> renewed.
		$cohort = array();
		foreach ($created as $cid => $ts) { if ($ts && date('Y-m-d', $ts) >= $from && date('Y-m-d', $ts) <= $to) { $cohort[$cid] = 1; } }
		$withPlan = array();
		$paying = array();
		$renewed = array();
		foreach ($subs as $sub)
		{
			$cid = (int) $sub['company_id'];
			if (isset($cohort[$cid]) && self::stateAt($sub, $endTs) !== null) { $withPlan[$cid] = 1; }
		}
		foreach ($allPaid as $p)
		{
			$cid = (int) $p['company_id'];
			if (isset($cohort[$cid]) && !empty($p['paid_at']) && substr($p['paid_at'], 0, 10) <= $to) { $paying[$cid] = 1; $withPlan[$cid] = 1; }
		}
		foreach ($paidRows as $t)
		{
			$cid = (int) $t['company_id'];
			if ($t['kind'] === 'renewal' && isset($paying[$cid]) && substr($t['paid_at'], 0, 10) <= $to) { $renewed[$cid] = 1; }
		}
		$signups = count($cohort);
		$planN = count($withPlan);

		// ---- payments started in the period
		$pay = array('paid' => 0, 'failed' => 0, 'pending' => 0, 'rate' => null);
		if ($ready)
		{
			foreach (pjBillingTransactionModel::factory()->select('t1.status, COUNT(*) AS `cnt`')->where(self::between('t1.created', $from, $to))
				->groupBy('t1.status')->findAll()->getDataPair('status', 'cnt') as $st => $c)
			{
				if ($st === 'paid') { $pay['paid'] += (int) $c; }
				elseif ($st === 'pending') { $pay['pending'] += (int) $c; }
				elseif (in_array($st, array('failed', 'cancelled'), true)) { $pay['failed'] += (int) $c; }
			}
			$tried = $pay['paid'] + $pay['failed'];
			$pay['rate'] = $tried > 0 ? (int) round($pay['paid'] * 100 / $tried) : null;
		}

		$money = array('code' => $main);
		list($money['decimals'], $money['thousands'], $money['place']) = pjMoney::style($main);
		$money['symbol'] = pjMoney::symbol($main);

		return array(
			'ready' => $ready,
			'money' => $money,
			'bucket' => $bucket,
			'steps' => $steps,
			'kpi' => array(
				'revenue' => array('value' => round(isset($revenue[$main]) ? $revenue[$main] : 0, 2), 'prev' => round(isset($revenuePrev[$main]) ? $revenuePrev[$main] : 0, 2),
					'text' => pjMoney::formatMany($revenue, null, pjMoney::format(0, $main)), 'prev_text' => pjMoney::formatMany($revenuePrev, null, pjMoney::format(0, $main)),
					'multi' => count($revenue) > 1, 'payments' => count(array_filter($paidRows, function ($t) use ($from) { return substr($t['paid_at'], 0, 10) >= $from; })),
					// collected per charged currency, e.g. "₹ 4,498.00 + $ 39.00" (the KPI value is the consolidated USD)
					'by_currency' => array_map(function ($v) { return round($v, 2); }, $collected),
					'collected_text' => count($collected) > 0 ? pjMoney::formatMany($collected) : ''),
				'companies' => array('value' => $companiesEnd, 'new' => array_sum($newSeries), 'left' => -array_sum($leftSeries)),
				'subs' => array('value' => $end['active'], 'prev' => $prevEnd['active'], 'trial' => $end['trial']),
				'mrr' => array('value' => round(isset($end['mrr'][$main]) ? $end['mrr'][$main] : 0, 2), 'prev' => round(isset($prevEnd['mrr'][$main]) ? $prevEnd['mrr'][$main] : 0, 2),
					'text' => pjMoney::formatMany($end['mrr'], null, pjMoney::format(0, $main)), 'paying' => $end['paying']),
				'conversion' => array('trials' => $trialsN, 'converted' => $convertedN, 'rate' => $trialsN > 0 ? (int) round($convertedN * 100 / $trialsN) : null,
					'prev_rate' => $trialsP > 0 ? (int) round($convertedP * 100 / $trialsP) : null),
			),
			'series' => array(
				'revenue' => array_map(function ($v) { return round($v, 2); }, $revSeries),
				'by_plan' => $planSeries,
				'by_kind' => $kindSeries,
				'companies' => $totalSeries,
				'new' => $newSeries,
				'left' => $leftSeries,
				'active' => $activeSeries,
				'trial' => $trialSeries,
				'mrr' => $mrrSeries,
				'trials_started' => $trialsStarted,
			),
			'other_currency' => $otherCurrency,
			'plan_mix' => array('plans' => $mix, 'ended' => $end['ended'], 'no_plan' => max(0, $companiesEnd - $withSub), 'total' => $companiesEnd,
				'mrr_text' => pjMoney::formatMany($end['mrr'], null, pjMoney::format(0, $main))),
			'funnel' => array('signups' => $signups, 'trials' => $planN, 'paying' => count($paying), 'renewed' => count($renewed)),
			'payments' => $pay,
		);
	}

	/* ------------------------------------------------------------------ reporting currency */

	/** Reporting currency of the dashboard: USD (default) or INR. */
	public static function reportCurrency($value)
	{
		return strtoupper(trim((string) $value)) === 'INR' ? 'INR' : 'USD';
	}

	/**
	 * Total of amounts in several currencies (array('USD' => 10, 'INR' => 900)) in the reporting currency, at the
	 * global exchange rate of the settings. A reporting value only: never stored, never charged.
	 */
	public static function toReport($by_currency, $report)
	{
		$rate = class_exists('pjEntitlement') ? pjEntitlement::rate() : 1.0;
		$total = 0.0;
		foreach ((array) $by_currency as $code => $amount)
		{
			$code = strtoupper((string) $code);
			$usd = $code === 'INR' ? (float) $amount / $rate : (float) $amount;
			$total += $report === 'INR' ? $usd * $rate : $usd;
		}
		return round($total, 2);
	}

	/**
	 * The dashboard snapshot (computed in USD: every payment at the USD value stored with it when it was paid) shown
	 * in the reporting currency: with INR every consolidated figure (revenue, MRR, chart series, plan MRR) is
	 * multiplied by the global rate (Subscriptions > Options > Currency & exchange rate). The amounts actually charged
	 * (kpi.revenue.collected_text, latest billing, activity) are not touched: they stay in their own currency.
	 * Adds 'report' {code, converted, rate, rate_text}.
	 */
	public static function inCurrency($snap, $report)
	{
		$base = isset($snap['money']['code']) ? $snap['money']['code'] : 'USD';
		$rate = class_exists('pjEntitlement') ? pjEntitlement::rate() : 1.0;
		$snap['report'] = array('code' => $report, 'converted' => false, 'rate' => $rate,
			'rate_text' => '1 USD = ' . pjMoney::format($rate, 'INR'));
		if ($report === $base || $base !== 'USD' || $report !== 'INR')
		{
			return $snap;
		}
		$f = $rate;
		$m = function ($v) use ($f) { return round((float) $v * $f, 2); };
		$ms = function ($list) use ($m) { return array_map($m, (array) $list); };

		$snap['report']['converted'] = true;
		$money = array('code' => 'INR');
		list($money['decimals'], $money['thousands'], $money['place']) = pjMoney::style('INR');
		$money['symbol'] = pjMoney::symbol('INR');
		$snap['money'] = $money;

		$k =& $snap['kpi'];
		foreach (array('revenue', 'mrr') as $key)
		{
			$k[$key]['value'] = $m($k[$key]['value']);
			$k[$key]['prev'] = $m($k[$key]['prev']);
			$k[$key]['text'] = pjMoney::format($k[$key]['value'], 'INR');
			if (isset($k[$key]['prev_text']))
			{
				$k[$key]['prev_text'] = pjMoney::format($k[$key]['prev'], 'INR');
			}
		}
		$k['revenue']['multi'] = false;
		unset($k);

		$s =& $snap['series'];
		$s['revenue'] = $ms($s['revenue']);
		$s['mrr'] = $ms($s['mrr']);
		foreach (array('by_plan', 'by_kind') as $key)
		{
			foreach ($s[$key] as $i => $row)
			{
				$s[$key][$i]['values'] = $ms($row['values']);
			}
		}
		unset($s);

		foreach ($snap['plan_mix']['plans'] as $i => $p)
		{
			$v = !empty($p['free']) ? 0.0 : self::toReport(array($p['currency'] => $p['mrr']), 'INR');
			$snap['plan_mix']['plans'][$i]['mrr'] = $v;
			$snap['plan_mix']['plans'][$i]['mrr_text'] = pjMoney::format($v, 'INR');
		}
		$snap['plan_mix']['mrr_text'] = $snap['kpi']['mrr']['text'];
		return $snap;
	}

	/* ------------------------------------------------------------------ statistics cards */

	/**
	 * Statistics cards for the period from..to. Counts of things are taken at the end of the period
	 * (companies registered by then, subscriptions in force then, plans / coupons existing and valid then);
	 * events (new companies, purchases, coupon uses, revenue) are counted inside the period and compared with the
	 * period of the same length just before it.
	 */
	public static function stats($from, $to)
	{
		$ready = class_exists('pjEntitlement') && pjEntitlement::isReady();
		$as_of = self::asOf($to);
		$end_sql = date('Y-m-d H:i:s', $as_of);
		list($pf, $pt) = self::previous($from, $to);
		$by_status = pjCompanyModel::factory()->select('t1.status, COUNT(*) AS `cnt`')
			->where(sprintf("(t1.created IS NULL OR t1.created <= '%s')", $end_sql))->groupBy('t1.status')->findAll()->getDataPair('status', 'cnt');
		$out = array(
			'companies_total' => array_sum(array_map('intval', $by_status)),
			'companies_active' => isset($by_status['T']) ? (int) $by_status['T'] : 0,
			'companies_new' => (int) pjCompanyModel::factory()->where(self::between('t1.created', $from, $to))->findCount()->getData(),
			'companies_new_prev' => (int) pjCompanyModel::factory()->where(self::between('t1.created', $pf, $pt))->findCount()->getData(),
			'subs_active' => 0, 'subs_trial' => 0, 'purchases' => 0, 'purchases_prev' => 0,
			'revenue' => array(), 'revenue_prev' => array(),
			'plans_available' => 0, 'plans_total' => 0,
			'coupons_active' => 0, 'coupons_total' => 0, 'coupon_uses' => 0, 'coupon_uses_prev' => 0,
			'ready' => $ready,
		);
		if (!$ready)
		{
			return $out;
		}
		$companies = pjCompanyModel::factory()->where(sprintf("(t1.created IS NULL OR t1.created <= '%s')", $end_sql))->findAll()->getDataPair('id', 'id');
		foreach (pjCompanySubscriptionModel::factory()->findAll()->getData() as $sub)
		{
			if (!isset($companies[(int) $sub['company_id']])) { continue; }
			$then = self::statusAt(pjEntitlement::applyDueChange($sub), $as_of);
			if ($then === null) { continue; }
			list($st, $entitled) = $then;
			if ($entitled)
			{
				$out['subs_active']++;
				if ($st === 'trial') { $out['subs_trial']++; }
			}
		}
		$paid = function ($f, $t, $coupon) {
			$m = pjBillingTransactionModel::factory()->where('t1.status', 'paid')->where(pjPlatformDashboard::betweenSql('t1.paid_at', $f, $t));
			if ($coupon) { $m->where("t1.coupon_code IS NOT NULL AND t1.coupon_code <> ''"); }
			return (int) $m->findCount()->getData();
		};
		$out['purchases'] = $paid($from, $to, false);
		$out['purchases_prev'] = $paid($pf, $pt, false);
		$out['revenue'] = self::revenue($from, $to);
		$out['revenue_prev'] = self::revenue($pf, $pt);
		foreach (pjEntitlement::plans() as $p)
		{
			if (!empty($p['created']) && strtotime($p['created']) > $as_of) { continue; }
			$out['plans_total']++;
			if ($p['status'] === 'T' && (int) $p['is_public'] === 1) { $out['plans_available']++; }
		}
		if (class_exists('pjSubscriptionPromos') && pjSubscriptionPromos::isReady())
		{
			$uses = pjSubscriptionPromos::usesAll();
			foreach (pjSubscriptionPromos::promos('coupon') as $c)
			{
				if (!empty($c['created']) && strtotime($c['created']) > $as_of) { continue; }
				$out['coupons_total']++;
				$used = isset($uses[(int) $c['id']]) ? (int) $uses[(int) $c['id']] : 0;
				// switched on, valid on at least one day of the period, and not used up
				if ($c['status'] === 'T' && (empty($c['date_from']) || $c['date_from'] <= $to) && (empty($c['date_to']) || $c['date_to'] >= $from)
					&& ($c['max_uses'] === null || $c['max_uses'] === '' || $used < (int) $c['max_uses']))
				{
					$out['coupons_active']++;
				}
			}
			$out['coupon_uses'] = $paid($from, $to, true);
			$out['coupon_uses_prev'] = $paid($pf, $pt, true);
		}
		return $out;
	}

	/* ------------------------------------------------------------------ subscription overview (donut) */

	/** Companies by subscription status at the end of the period: status => count (no_plan = without a subscription then). */
	public static function overview($to = null)
	{
		$to = $to === null ? date('Y-m-d') : $to;
		$as_of = self::asOf($to);
		$out = array('active' => 0, 'trial' => 0, 'past_due' => 0, 'cancelled' => 0, 'expired' => 0, 'no_plan' => 0);
		$companies = pjCompanyModel::factory()->where(sprintf("(t1.created IS NULL OR t1.created <= '%s')", date('Y-m-d H:i:s', $as_of)))->findAll()->getDataPair('id', 'id');
		$total = count($companies);
		if (!class_exists('pjEntitlement') || !pjEntitlement::isReady())
		{
			$out['no_plan'] = $total;
			return $out;
		}
		$with = 0;
		foreach (pjCompanySubscriptionModel::factory()->findAll()->getData() as $sub)
		{
			if (!isset($companies[(int) $sub['company_id']])) { continue; }
			$then = self::statusAt(pjEntitlement::applyDueChange($sub), $as_of);
			if ($then === null) { continue; }
			$st = $then[0];
			$out[isset($out[$st]) ? $st : 'expired']++;
			$with++;
		}
		$out['no_plan'] = max(0, $total - $with);
		return $out;
	}

	/* ------------------------------------------------------------------ trends (line chart) */

	/**
	 * Series of the period from..to: per day (up to 92 days), per week (up to 2 years) or per month.
	 * Returns bucket, keys[] (first day of each step), companies[], purchases[], trials[].
	 */
	public static function trends($from, $to)
	{
		$days = self::days($from, $to);
		$bucket = $days <= 92 ? 'day' : ($days <= 731 ? 'week' : 'month');
		// first day of each step, and a SQL expression that maps a date column to it
		$keys = array();
		$t = strtotime($from . ' 12:00:00');
		$end = strtotime($to . ' 12:00:00');
		if ($bucket === 'day')
		{
			for (; $t <= $end; $t = strtotime('+1 day', $t)) { $keys[] = date('Y-m-d', $t); }
			$expr = function ($col) { return "DATE($col)"; };
		} elseif ($bucket === 'week') {
			for (; $t <= $end; $t = strtotime('+7 days', $t)) { $keys[] = date('Y-m-d', $t); }
			$start = $from;
			$expr = function ($col) use ($start) { return sprintf("DATE_ADD('%s', INTERVAL FLOOR(DATEDIFF(DATE(%s), '%s') / 7) * 7 DAY)", $start, $col, $start); };
		} else {
			for ($t = strtotime(date('Y-m-01', $t) . ' 12:00:00'); $t <= $end; $t = strtotime('+1 month', $t)) { $keys[] = date('Y-m-01', $t); }
			$expr = function ($col) { return "DATE_FORMAT($col, '%Y-%m-01')"; };
		}
		$zero = array_fill_keys($keys, 0);
		$fill = function ($rows) use ($zero, $bucket, $keys, $from) {
			$out = $zero;
			foreach ($rows as $k => $c)
			{
				if ($bucket === 'month' && $k < $keys[0]) { $k = $keys[0]; }
				if (isset($out[$k])) { $out[$k] += (int) $c; }
			}
			return array_values($out);
		};
		$series = array('companies' => array_values($zero), 'purchases' => array_values($zero), 'trials' => array_values($zero));
		$series['companies'] = $fill(pjCompanyModel::factory()->select($expr('t1.created') . ' AS `d`, COUNT(*) AS `cnt`')
			->where(self::between('t1.created', $from, $to))->groupBy('`d`')->findAll()->getDataPair('d', 'cnt'));
		if (class_exists('pjEntitlement') && pjEntitlement::isReady())
		{
			$series['purchases'] = $fill(pjBillingTransactionModel::factory()->select($expr('t1.paid_at') . ' AS `d`, COUNT(*) AS `cnt`')->where('t1.status', 'paid')
				->where(self::between('t1.paid_at', $from, $to))->groupBy('`d`')->findAll()->getDataPair('d', 'cnt'));
			// trials started: the plan was assigned with status trial (by the administrator, at sign-up or by the company)
			$series['trials'] = $fill(pjSubscriptionEventModel::factory()->select($expr('t1.created') . ' AS `d`, COUNT(*) AS `cnt`')->where('t1.type', 'assigned')
				->where("t1.message LIKE '%(trial)%'")->where(self::between('t1.created', $from, $to))->groupBy('`d`')->findAll()->getDataPair('d', 'cnt'));
		}
		return array('bucket' => $bucket, 'keys' => $keys, 'companies' => $series['companies'], 'purchases' => $series['purchases'], 'trials' => $series['trials']);
	}

	/* ------------------------------------------------------------------ recent platform activity */

	/** Latest platform events in the range: array of {type, date, company, text}. */
	public static function activity($from, $to, $limit = 12)
	{
		$items = array();
		$names = pjCompanyModel::factory()->findAll()->getDataPair('id', 'name');
		foreach (pjCompanyModel::factory()->where(self::between('t1.created', $from, $to))->orderBy('t1.created DESC')->limit($limit)->findAll()->getData() as $c)
		{
			$items[] = array('type' => 'registration', 'date' => $c['created'], 'company' => (string) $c['name'], 'plan' => '', 'amount' => '', 'code' => '');
		}
		if (class_exists('pjEntitlement') && pjEntitlement::isReady())
		{
			$plans = array();
			foreach (pjEntitlement::plans() as $p) { $plans[(int) $p['id']] = $p['name']; }
			foreach (pjBillingTransactionModel::factory()->where('t1.status', 'paid')->where(self::between('t1.paid_at', $from, $to))
				->orderBy('t1.paid_at DESC')->limit($limit * 2)->findAll()->getData() as $t)
			{
				$company = isset($names[(int) $t['company_id']]) ? (string) $names[(int) $t['company_id']] : '';
				$plan = isset($plans[(int) $t['plan_id']]) ? (string) $plans[(int) $t['plan_id']] : '';
				$amount = pjMoney::format($t['amount'], $t['currency']);
				$items[] = array('type' => $t['kind'] === 'upgrade' ? 'upgrade' : 'purchase', 'date' => $t['paid_at'], 'company' => $company, 'plan' => $plan, 'amount' => $amount, 'code' => '');
				if (!empty($t['coupon_code']))
				{
					$items[] = array('type' => 'coupon', 'date' => $t['paid_at'], 'company' => $company, 'plan' => $plan, 'amount' => '', 'code' => (string) $t['coupon_code']);
				}
			}
			// plan changes without a payment (scheduled downgrade applied, free plan chosen)
			foreach (pjSubscriptionEventModel::factory()->where('t1.type', 'plan_changed')->where(self::between('t1.created', $from, $to))
				->orderBy('t1.created DESC')->limit($limit)->findAll()->getData() as $e)
			{
				$items[] = array('type' => 'change', 'date' => $e['created'], 'company' => isset($names[(int) $e['company_id']]) ? (string) $names[(int) $e['company_id']] : '',
					'plan' => preg_match('/->\s*(.+)$/', (string) $e['message'], $m) ? trim($m[1]) : '', 'amount' => '', 'code' => '');
			}
		}
		usort($items, function ($a, $b) { return strcmp($b['date'], $a['date']); });
		return array_slice($items, 0, $limit);
	}

	/* ------------------------------------------------------------------ platform health */

	/** One check: array(state ok|warning|error|unknown, title key, detail text). */
	private static function check($state, $detail)
	{
		return array('state' => $state, 'detail' => $detail);
	}

	public static function health()
	{
		$out = array();

		// payment gateway for subscription payments (Options > Payments)
		if (!class_exists('pjSubscriptionBilling') || !pjSubscriptionBilling::hasPayments())
		{
			$out['gateway'] = self::check('unknown', __('dash_h_gateway_none_plugin', true));
		} else {
			$methods = pjSubscriptionBilling::activeMethods();
			$online = array();
			$test = array();
			foreach ($methods as $m => $label)
			{
				if (!pjSubscriptionBilling::isOnline($m)) { continue; }
				$online[] = $label;
				$opt = pjPaymentOptionModel::factory()->getOptions(pjSubscriptionBilling::FOREIGN_ID, $m);
				if (!empty($opt['is_test_mode'])) { $test[] = $label; }
			}
			if (empty($methods))
			{
				$out['gateway'] = self::check('warning', __('dash_h_gateway_off', true));
			} elseif (empty($online)) {
				$out['gateway'] = self::check('warning', sprintf(__('dash_h_gateway_offline', true), implode(', ', $methods)));
			} elseif (!empty($test)) {
				$out['gateway'] = self::check('warning', sprintf(__('dash_h_gateway_test', true), implode(', ', $test)));
			} else {
				$out['gateway'] = self::check('ok', sprintf(__('dash_h_gateway_ok', true), implode(', ', $online)));
			}
		}

		// e-mail service: the platform's sending settings, and the result of the last reminder e-mails
		$opt = pjCompanyContext::basePairs(0);
		$send = isset($opt['o_send_email']) ? (string) $opt['o_send_email'] : '';
		if ($send === 'smtp' && trim((string) @$opt['o_smtp_host']) === '')
		{
			$out['email'] = self::check('error', __('dash_h_email_nohost', true));
		} else {
			$label = $send === 'smtp' ? sprintf(__('dash_h_email_smtp', true), (string) $opt['o_smtp_host']) : __('dash_h_email_mail', true);
			$job = pjBaseCronJobModel::factory()->where('t1.controller', 'pjCron')->where('t1.action', 'pjActionSubscriptions')->limit(1)->findAll()->getDataIndex(0);
			if (!empty($job['status']) && preg_match('/(\d+) failed/', (string) $job['status'], $m) && (int) $m[1] > 0)
			{
				$out['email'] = self::check('error', $label . ' ' . sprintf(__('dash_h_email_failed', true), (int) $m[1]));
			} else {
				$out['email'] = self::check('ok', $label . ' ' . __('dash_h_email_untested', true));
			}
		}

		// scheduled tasks (System Options > Cron jobs): active jobs that are overdue or never ran
		$jobs = pjBaseCronJobModel::factory()->where('t1.is_active', 1)->findAll()->getData();
		$late = array();
		$last = 0;
		$unit = array('minute' => 60, 'hour' => 3600, 'day' => 86400, 'week' => 604800, 'month' => 2678400);
		foreach ($jobs as $j)
		{
			$lr = !empty($j['last_run']) ? strtotime($j['last_run']) : 0;
			$last = max($last, $lr);
			$every = max(1, (int) $j['interval']) * (isset($unit[$j['period']]) ? $unit[$j['period']] : 3600);
			if ($lr === 0 || time() - $lr > 2 * $every + 600)
			{
				$late[] = (string) $j['name'];
			}
		}
		if (empty($jobs))
		{
			$out['tasks'] = self::check('unknown', __('dash_h_tasks_none', true));
		} elseif (!empty($late)) {
			$out['tasks'] = self::check('warning', sprintf(__('dash_h_tasks_late', true), count($late), count($jobs)));
			$out['tasks']['items'] = $late;
		} else {
			$out['tasks'] = self::check('ok', sprintf(__('dash_h_tasks_ok', true), count($jobs)));
		}

		// background jobs: this installation has no job queue; its background work is run by the cron runner,
		// so this shows whether the server's cron runner is calling the application at all
		if ($last === 0)
		{
			$out['jobs'] = self::check('error', __('dash_h_jobs_never', true));
		} elseif (time() - $last > 7200) {
			$out['jobs'] = self::check('warning', sprintf(__('dash_h_jobs_stale', true), date('d.m.Y H:i', $last)));
		} else {
			$out['jobs'] = self::check('ok', sprintf(__('dash_h_jobs_ok', true), date('d.m.Y H:i', $last)));
		}
		return $out;
	}

	/** Overall status from the checks: ok | warning | error. */
	public static function overall($health)
	{
		$states = array_map(function ($h) { return $h['state']; }, $health);
		if (in_array('error', $states, true)) { return 'error'; }
		if (in_array('warning', $states, true) || in_array('unknown', $states, true)) { return 'warning'; }
		return 'ok';
	}
}
?>
