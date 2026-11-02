<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Sign-up settings (Options > Sign-up settings, administrators only): limits of the sign-up, the life time of
 * its token and the writing tones of the AI texts. Platform options (foreign_id 1, hidden tab 99), read with
 * one query per request; a missing or invalid value uses the default below.
 */
class pjSignupConfig
{
	/** Setting => array(option key, default, min, max). Numbers only. */
	public static $numbers = array(
		'max_photos'         => array('o_signup_max_photos', 20, 1, 100),
		'max_ai_generations' => array('o_signup_max_ai_generations', 30, 0, 500),
		'max_amenities'      => array('o_signup_max_amenities', 100, 1, 500),
		'max_amenity_length' => array('o_signup_max_amenity_length', 80, 10, 255),
		'amenity_category'   => array('o_signup_amenity_category', 2, 1, 99),
		'max_faqs'           => array('o_signup_max_faqs', 8, 1, 30),
		'token_days'         => array('o_signup_token_days', 30, 1, 365),
	);

	/** Writing tones of the AI texts (step 5): tone => array(option key, default instruction). The first one is the default tone. */
	public static $tones = array(
		'warm'    => array('o_signup_tone_warm', 'Warm: friendly and welcoming, as if the host were talking to the guest. Simple, natural words.'),
		'premium' => array('o_signup_tone_premium', 'Premium: polished, refined and understated, like a boutique hotel. Elegant but not flowery, no cliches.'),
		'simple'  => array('o_signup_tone_simple', 'Simple: short, plain and factual sentences that are easy to scan. No marketing words.'),
	);

	private static $values = null;

	/** All sign-up options in one query (cached for the request). */
	private static function values()
	{
		if (self::$values === null)
		{
			self::$values = array();
			$keys = array();
			foreach (self::$numbers as $n) { $keys[] = $n[0]; }
			foreach (self::$tones as $t) { $keys[] = $t[0]; }
			foreach (pjBaseOptionModel::factory()->select('t1.key, t1.value')->where('t1.foreign_id', 1)->whereIn('t1.key', $keys)->findAll()->getData() as $row)
			{
				self::$values[$row['key']] = $row['value'];
			}
		}
		return self::$values;
	}

	/** A number setting (the default when not set or out of range). */
	public static function get($name)
	{
		if (!isset(self::$numbers[$name]))
		{
			return null;
		}
		list($key, $default, $min, $max) = self::$numbers[$name];
		$v = self::values();
		if (!isset($v[$key]) || trim((string) $v[$key]) === '' || !is_numeric($v[$key]))
		{
			return $default;
		}
		$n = (int) $v[$key];
		return $n < $min || $n > $max ? $default : $n;
	}

	/** Instruction of a tone (its default when empty). */
	public static function tone($tone)
	{
		if (!isset(self::$tones[$tone]))
		{
			return null;
		}
		$v = self::values();
		$text = isset($v[self::$tones[$tone][0]]) ? trim((string) $v[self::$tones[$tone][0]]) : '';
		return $text !== '' ? $text : self::$tones[$tone][1];
	}

	/** Tone keys, the default first. */
	public static function toneKeys()
	{
		return array_keys(self::$tones);
	}

	/** Every setting for the settings page: name => array(value, default, min, max) and tone => array(value, default). */
	public static function all()
	{
		$out = array('numbers' => array(), 'tones' => array());
		foreach (self::$numbers as $name => $n)
		{
			$out['numbers'][$name] = array('value' => self::get($name), 'default' => $n[1], 'min' => $n[2], 'max' => $n[3]);
		}
		$v = self::values();
		foreach (self::$tones as $tone => $t)
		{
			$out['tones'][$tone] = array('value' => isset($v[$t[0]]) ? (string) $v[$t[0]] : '', 'default' => $t[1]);
		}
		return $out;
	}

	/** Saves one option (insert when missing). */
	public static function save($key, $value)
	{
		$m = pjBaseOptionModel::factory();
		$value = (string) $value;
		if ((int) $m->where('t1.foreign_id', 1)->where('t1.key', $key)->findCount()->getData() > 0)
		{
			$m->reset()->where('foreign_id', 1)->where('`key`', $key)->limit(1)->modifyAll(array('value' => $value === '' ? ':NULL' : $value));
		} else {
			$m->reset()->setAttributes(array('foreign_id' => 1, 'key' => $key, 'tab_id' => 99, 'value' => $value, 'type' => strpos($key, 'tone') !== false ? 'text' : 'int', 'order' => 1, 'is_visible' => 0))->insert();
		}
		self::$values = null;
	}
}
?>
