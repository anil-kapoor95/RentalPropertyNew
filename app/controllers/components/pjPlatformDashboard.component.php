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
		$key = in_array($key, array('7', '30', '90', 'month', 'custom'), true) ? $key : '30';
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

	/** Paid revenue in the range: currency => total (refunded / failed / open payments are not counted). */
	public static function revenue($from, $to)
	{
		$out = array();
		if (!class_exists('pjEntitlement') || !pjEntitlement::isReady())
		{
			return $out;
		}
		foreach (pjBillingTransactionModel::factory()->select('t1.currency, SUM(t1.amount) AS `total`')->where('t1.status', 'paid')
			->where(self::between('t1.paid_at', $from, $to))->groupBy('t1.currency')->findAll()->getData() as $row)
		{
			$out[strtoupper((string) $row['currency'])] = round((float) $row['total'], 2);
		}
		ksort($out);
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
		if (self::existedAt($sub, $time))
		{
			$r = pjEntitlement::effectiveStatus($sub, $time);
			return array($r[0], $r[1]);
		}
		if (self::$events === null)
		{
			self::$events = array();
			foreach (pjSubscriptionEventModel::factory()->select('t1.company_id, t1.type, t1.message, t1.created')
				->whereIn('t1.type', array('assigned', 'payment', 'extended', 'status', 'removed'))
				->orderBy('t1.created ASC, t1.id ASC')->findAll()->getData() as $e)
			{
				self::$events[(int) $e['company_id']][] = $e;
			}
		}
		$state = null; // array(status, end)
		$cid = (int) $sub['company_id'];
		foreach (isset(self::$events[$cid]) ? self::$events[$cid] : array() as $e)
		{
			if (strtotime($e['created']) > $time) { break; }
			$msg = (string) $e['message'];
			switch ($e['type'])
			{
				case 'assigned':
					if (preg_match('/assigned \((trial|active)\) until (\d{4}-\d{2}-\d{2}[ \d:]*)/', $msg, $m)) {
						$state = array($m[1], trim($m[2]));
					} else {
						// plan for new companies / free plan chosen: no end date
						$state = array('active', null);
					}
					break;
				case 'payment':
					if (preg_match('/active until (\d{4}-\d{2}-\d{2}[ \d:]*)/', $msg, $m)) { $state = array('active', trim($m[1])); }
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
				return array($r[0], $r[1]);
			}
			return null;
		}
		$row = array('status' => $state[0], 'current_period_end' => $state[1], 'trial_ends_at' => $state[0] === 'trial' ? $state[1] : null, 'cancel_at_period_end' => 0);
		$r = pjEntitlement::effectiveStatus($row, $time);
		return array($r[0], $r[1]);
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
				'amount' => pjMoney::format($t['amount'], $t['currency']),
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
			'price' => $plan && $plan['price'] !== null && (float) $plan['price'] > 0 ? pjMoney::format($plan['price'], $plan['currency']) : '',
			'interval' => $plan ? array((int) max(1, (int) $plan['interval_count']), $plan['billing_interval'] === 'year' ? 'year' : 'month') : null,
			'last_payment' => !empty($paid) ? array('date' => $paid['paid_at'], 'amount' => pjMoney::format($paid['amount'], $paid['currency'])) : null,
			'usage' => pjEntitlement::usage($company_id),
			'limits' => $s['limits'],
			'grace_ends_at' => $s['grace_ends_at'],
		);
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
