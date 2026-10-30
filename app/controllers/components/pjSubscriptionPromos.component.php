<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * The administrator's discounts and coupons on plan prices (Subscriptions > Coupons & Discounts).
 *  - discount: taken off automatically; when several fit, the biggest one is used
 *  - coupon  : only with its code (My plan), taken off the price after the discount
 * A use is a PAID billing transaction that carries the promo id (pending payments are not counted).
 */
class pjSubscriptionPromos
{
	/** Payment kinds a promo can be limited to (billing_transactions.kind). */
	public static $kinds = array('new', 'upgrade', 'renewal');

	private static $ready = null;

	public static function isReady()
	{
		if (self::$ready === null)
		{
			self::$ready = false;
			$m = pjSubscriptionPromoModel::factory();
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

	public static function normalizeCode($code)
	{
		return strtoupper(trim((string) $code));
	}

	public static function isValidCode($code)
	{
		return (bool) preg_match('/^[A-Z0-9_-]{3,30}$/', (string) $code);
	}

	/** Comma separated ids / words -> array. */
	public static function listOf($csv)
	{
		return array_values(array_filter(array_map('trim', explode(',', (string) $csv)), 'strlen'));
	}

	public static function promos($kind)
	{
		if (!self::isReady())
		{
			return array();
		}
		return pjSubscriptionPromoModel::factory()->where('t1.kind', $kind === 'coupon' ? 'coupon' : 'discount')
			->orderBy('t1.status ASC, t1.id DESC')->findAll()->getData();
	}

	public static function find($id)
	{
		if (!self::isReady() || (int) $id <= 0)
		{
			return false;
		}
		$p = pjSubscriptionPromoModel::factory()->find((int) $id)->getData();
		return !empty($p) ? $p : false;
	}

	public static function findCoupon($code)
	{
		$code = self::normalizeCode($code);
		if (!self::isReady() || !self::isValidCode($code))
		{
			return false;
		}
		$p = pjSubscriptionPromoModel::factory()->where('t1.kind', 'coupon')->where('t1.code', $code)->limit(1)->findAll()->getDataIndex(0);
		return !empty($p) ? $p : false;
	}

	/** Paid payments that used the promo (of one company when $company_id > 0). */
	public static function uses($id, $company_id = 0)
	{
		$m = pjBillingTransactionModel::factory()->where('t1.status', 'paid')
			->where(sprintf("FIND_IN_SET('%u', t1.promo_ids)", (int) $id));
		if ((int) $company_id > 0)
		{
			$m->where('t1.company_id', (int) $company_id);
		}
		return (int) $m->findCount()->getData();
	}

	/** Uses of every promo: id => count. */
	public static function usesAll()
	{
		$out = array();
		foreach (pjBillingTransactionModel::factory()->select('t1.promo_ids')->where('t1.status', 'paid')
			->where("t1.promo_ids IS NOT NULL")->findAll()->getData() as $t)
		{
			foreach (self::listOf($t['promo_ids']) as $id)
			{
				$out[(int) $id] = isset($out[(int) $id]) ? $out[(int) $id] + 1 : 1;
			}
		}
		return $out;
	}

	/** Can the promo be used for this plan, payment kind and company today? */
	public static function applies($p, $plan, $kind, $company_id)
	{
		if (empty($p) || $p['status'] !== 'T' || (float) $p['value'] <= 0)
		{
			return false;
		}
		$today = date('Y-m-d');
		if ((!empty($p['date_from']) && $today < $p['date_from']) || (!empty($p['date_to']) && $today > $p['date_to']))
		{
			return false;
		}
		$plans = self::listOf($p['plan_ids']);
		if (!empty($plans) && !in_array((string) (int) $plan['id'], $plans, true))
		{
			return false;
		}
		$kinds = self::listOf($p['kinds']);
		if (!empty($kinds) && !in_array((string) $kind, $kinds, true))
		{
			return false;
		}
		if ($p['type'] === 'amount' && strtoupper((string) $p['currency']) !== strtoupper((string) $plan['currency']))
		{
			return false;
		}
		if ($p['max_uses'] !== null && self::uses($p['id']) >= (int) $p['max_uses'])
		{
			return false;
		}
		if ($p['max_per_company'] !== null && self::uses($p['id'], $company_id) >= (int) $p['max_per_company'])
		{
			return false;
		}
		return true;
	}

	/** Amount the promo takes off $amount. */
	public static function cut($p, $amount)
	{
		$amount = max(0, (float) $amount);
		$cut = $p['type'] === 'percent' ? $amount * min(100, (float) $p['value']) / 100 : min($amount, (float) $p['value']);
		return round($cut, 2);
	}

	/**
	 * Price of a plan for a company: plan price, the biggest fitting discount, then the coupon (if a code is given).
	 * Returns list, discount, amount, promo_ids, coupon_code, error ('' or 'subErrCoupon').
	 */
	public static function quote($plan, $kind, $company_id, $code = '')
	{
		$list = round((float) $plan['price'], 2);
		$q = array('list' => $list, 'discount' => 0.0, 'amount' => $list, 'promo_ids' => array(), 'coupon_code' => '', 'error' => '');
		if (!self::isReady() || $plan['price'] === null)
		{
			return $q;
		}
		$amount = $list;
		$best = null;
		$best_cut = 0;
		foreach (pjSubscriptionPromoModel::factory()->where('t1.kind', 'discount')->where('t1.status', 'T')->findAll()->getData() as $p)
		{
			if (self::applies($p, $plan, $kind, $company_id) && ($c = self::cut($p, $list)) > $best_cut)
			{
				$best = $p;
				$best_cut = $c;
			}
		}
		if ($best)
		{
			$amount -= $best_cut;
			$q['promo_ids'][] = (int) $best['id'];
		}
		$code = self::normalizeCode($code);
		if ($code !== '')
		{
			$coupon = self::findCoupon($code);
			if (!$coupon || !self::applies($coupon, $plan, $kind, $company_id))
			{
				$q['error'] = 'subErrCoupon';
			} else {
				$amount -= self::cut($coupon, $amount);
				$q['promo_ids'][] = (int) $coupon['id'];
				$q['coupon_code'] = $code;
			}
		}
		$q['amount'] = round(max(0, $amount), 2);
		$q['discount'] = round($list - $q['amount'], 2);
		return $q;
	}
}
?>
