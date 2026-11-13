<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * One place to write money amounts in the back office (dashboards, subscriptions, billing, e-mails).
 *
 * Everything comes from the saved settings, nothing is hard-coded:
 * - the currency symbol and its decimal places: System Options currency table (plugin_base_currencies: code, sign,
 *   decimals); a currency without a symbol there is written with its code (e.g. "AED");
 * - thousands separator, whether decimals are shown, and the symbol position: the options of the company / platform
 *   that is shown (o_price_format 1-6 and o_currency_place, the same settings pjCurrency::formatPrice uses).
 *
 * Only the display changes; amounts are never rounded for calculations or storage.
 */
class pjMoney
{
	private static $currencies = null;

	/** code => array(sign (plain text, HTML entities decoded), decimals) from the currency table. */
	private static function currencies()
	{
		if (self::$currencies === null)
		{
			self::$currencies = array();
			if (class_exists('pjBaseCurrencyDataModel'))
			{
				foreach (pjBaseCurrencyDataModel::factory()->findAll()->getData() as $row)
				{
					$code = strtoupper(trim((string) $row['code']));
					if ($code === '') { continue; }
					self::$currencies[$code] = array(
						'sign' => trim(html_entity_decode((string) $row['sign'], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
						'decimals' => isset($row['decimals']) && $row['decimals'] !== null && $row['decimals'] !== '' ? max(0, min(4, (int) $row['decimals'])) : 2,
					);
				}
			}
		}
		return self::$currencies;
	}

	/** Symbol of a currency (plain text), or its code when the currency table has none. */
	public static function symbol($code)
	{
		$code = strtoupper(trim((string) $code));
		$all = self::currencies();
		return isset($all[$code]) && $all[$code]['sign'] !== '' ? $all[$code]['sign'] : $code;
	}

	/** Decimal places of a currency (ISO 4217 minor units from the currency table; 2 when unknown). */
	public static function decimals($code)
	{
		$code = strtoupper(trim((string) $code));
		$all = self::currencies();
		return isset($all[$code]) ? $all[$code]['decimals'] : 2;
	}

	/** Options to format with: the given array, else the options of the current request (company or platform). */
	private static function options($options)
	{
		if (is_array($options))
		{
			return $options;
		}
		$reg = pjRegistry::getInstance();
		return $reg->is('options') ? (array) $reg->get('options') : array();
	}

	/**
	 * Format settings: array(decimals, thousands separator, symbol place front|back).
	 * o_price_format: 1: 1000, 2: 1000.00, 3: 1,000, 4: 1,000.00, 5: 1 000, 6: 1 000.00 (the "decimals" formats show
	 * the currency's own number of decimal places, e.g. 0 for JPY, 3 for KWD).
	 */
	public static function style($code = null, $options = null)
	{
		$o = self::options($options);
		$format = isset($o['o_price_format']) ? (int) $o['o_price_format'] : 4;
		$format = $format >= 1 && $format <= 6 ? $format : 4;
		$with_decimals = $format % 2 === 0;
		$thousands = $format <= 2 ? '' : ($format <= 4 ? ',' : ' ');
		$place = isset($o['o_currency_place']) && $o['o_currency_place'] === 'front' ? 'front' : 'back';
		$code = $code === null || $code === '' ? (isset($o['o_currency']) ? $o['o_currency'] : '') : $code;
		return array($with_decimals ? self::decimals($code) : 0, $thousands, $place);
	}

	/** The number only, e.g. "13,230.00" (no symbol). */
	public static function number($amount, $code = null, $options = null)
	{
		list($decimals, $thousands) = self::style($code, $options);
		return number_format((float) $amount, $decimals, '.', $thousands);
	}

	/**
	 * Amount with the currency symbol, e.g. "₹ 13,230.00" or "13,230.00 €" (plain text: escape it for HTML).
	 * $code: currency of the amount (default: the currency of the options).
	 */
	public static function format($amount, $code = null, $options = null)
	{
		if ($amount === null || $amount === '')
		{
			return '';
		}
		$o = self::options($options);
		$code = $code === null || $code === '' ? (isset($o['o_currency']) ? $o['o_currency'] : '') : $code;
		list($decimals, $thousands, $place) = self::style($code, $o);
		$value = (float) $amount;
		$number = number_format(abs($value), $decimals, '.', $thousands);
		$sign = self::symbol($code);
		$text = $sign === '' ? $number : ($place === 'front' ? $sign . ' ' . $number : $number . ' ' . $sign);
		return ($value < 0 && round(abs($value), $decimals) > 0 ? '-' : '') . $text;
	}

	/**
	 * Pieces of an amount for custom layouts (e.g. a small symbol next to a big number):
	 * array(symbol, number, place front|back). $decimals overrides the decimals of the format.
	 */
	public static function parts($amount, $code = null, $options = null, $decimals = null)
	{
		$o = self::options($options);
		$code = $code === null || $code === '' ? (isset($o['o_currency']) ? $o['o_currency'] : '') : $code;
		list($d, $thousands, $place) = self::style($code, $o);
		$d = $decimals === null ? $d : (int) $decimals;
		return array(self::symbol($code), number_format((float) $amount, $d, '.', $thousands), $place);
	}

	/** Amounts in several currencies (currency => amount), e.g. "₹ 18,493.00 + € 120.00"; $empty when none. */
	public static function formatMany($by_currency, $options = null, $empty = null)
	{
		$parts = array();
		foreach ((array) $by_currency as $code => $amount)
		{
			$parts[] = self::format($amount, $code, $options);
		}
		if (empty($parts))
		{
			return $empty !== null ? $empty : self::format(0, null, $options);
		}
		return implode(' + ', $parts);
	}
}
?>
