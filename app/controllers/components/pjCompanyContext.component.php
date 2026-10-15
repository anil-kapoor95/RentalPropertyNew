<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Active company ("workspace") for the back-office.
 *
 * - Administrator (role 1, incl. super admin user #1): any company and can switch.
 * - Everyone else is locked to ONE company and cannot switch:
 *     Company (role 4): the company linked to the user.
 *     Owner (role 3) / Editor (role 2): the company that holds their properties.
 *
 * The choice is kept in the session and validated on every request.
 */
class pjCompanyContext
{
	const SESSION_KEY = 'admin_company_id';

	const ROLE_ADMIN = 1;
	const ROLE_EDITOR = 2;
	const ROLE_OWNER = 3;
	const ROLE_COMPANY = 4;
	const ROLE_COMPANY_USER = 5; // users a company creates for itself (Users menu of the company login)

	private static $cache = array();

	/**
	 * Companies the user may work with: array(id => row), ordered by name.
	 */
	public static function allowedCompanies($role_id, $user_id)
	{
		$role_id = (int) $role_id;
		$user_id = (int) $user_id;
		$ck = $role_id . ':' . $user_id;
		if (isset(self::$cache[$ck]))
		{
			return self::$cache[$ck];
		}
		$arr = array();
		if ($user_id > 0)
		{
			$model = pjCompanyModel::factory()->select('t1.id, t1.name, t1.logo, t1.status, t1.user_id');
			if (self::isGlobalUser($role_id, $user_id))
			{
				// administrators and admin-side users (no company): every company
				$arr = $model->orderBy('t1.name ASC')->findAll()->getDataPair('id');
			} else {
				if ($role_id === self::ROLE_COMPANY) {
					// the company whose login this is, or the company the user is assigned to
					$model->where(sprintf("(t1.user_id = %2\$u OR t1.id IN (SELECT `company_id` FROM `%1\$s` WHERE `id` = %2\$u AND `company_id` IS NOT NULL))",
						pjAuthUserModel::factory()->getTable(), $user_id));
				} elseif ($role_id === self::ROLE_COMPANY_USER) {
					// a company user works only in the company that created him
					$model->where(sprintf("t1.id IN (SELECT `company_id` FROM `%1\$s` WHERE `id` = %2\$u AND `company_id` IS NOT NULL)",
						pjAuthUserModel::factory()->getTable(), $user_id));
				} elseif (in_array($role_id, array(self::ROLE_OWNER, self::ROLE_EDITOR), true)) {
					// The company assigned to the user; otherwise the company that holds their properties.
					$model->where(sprintf("(t1.id IN (SELECT `company_id` FROM `%1\$s` WHERE `id` = %3\$u AND `company_id` IS NOT NULL) OR (t1.id IN (SELECT `company_id` FROM `%2\$s` WHERE `user_id` = %3\$u AND `company_id` IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM `%1\$s` WHERE `id` = %3\$u AND `company_id` IS NOT NULL)))",
						pjAuthUserModel::factory()->getTable(), pjCalendarModel::factory()->getTable(), $user_id));
				} else {
					$model->where('t1.id', 0);
				}
				// Non-admins are locked to a single company.
				$arr = $model->orderBy('t1.id ASC')->limit(1)->findAll()->getDataPair('id');
			}
		}
		self::$cache[$ck] = $arr;
		return $arr;
	}

	/**
	 * Active company id for this user (validated). 0 when the user has no company.
	 */
	public static function activeId($role_id, $user_id)
	{
		$allowed = self::allowedCompanies($role_id, $user_id);
		$current = isset($_SESSION[self::SESSION_KEY]) ? (int) $_SESSION[self::SESSION_KEY] : 0;
		if ($current > 0 && isset($allowed[$current]))
		{
			return $current;
		}
		// Prefer the first active company, otherwise the first one.
		$pick = 0;
		foreach ($allowed as $id => $row)
		{
			if ($row['status'] == 'T') { $pick = (int) $id; break; }
		}
		if ($pick === 0 && !empty($allowed))
		{
			$keys = array_keys($allowed);
			$pick = (int) $keys[0];
		}
		if ($pick > 0)
		{
			$_SESSION[self::SESSION_KEY] = $pick;
		} else {
			unset($_SESSION[self::SESSION_KEY]);
		}
		return $pick;
	}

	public static function activeCompany($role_id, $user_id)
	{
		$id = self::activeId($role_id, $user_id);
		$allowed = self::allowedCompanies($role_id, $user_id);
		return $id > 0 && isset($allowed[$id]) ? $allowed[$id] : false;
	}

	/**
	 * Only administrators may work across companies. Decided by the role alone:
	 * user #1 moved to another role is treated like any other user of that role.
	 */
	public static function isAdmin($role_id, $user_id)
	{
		return (int) $role_id === self::ROLE_ADMIN;
	}

	/**
	 * Users that belong to the admin side, not to a company: administrators, and users
	 * of the other admin roles (not Company / Company user) that have no company.
	 * They work across all companies (company switcher), each with its own role permissions.
	 */
	public static function isGlobalUser($role_id, $user_id)
	{
		$role_id = (int) $role_id;
		if (self::isAdmin($role_id, $user_id))
		{
			return true;
		}
		if (in_array($role_id, array(self::ROLE_COMPANY, self::ROLE_COMPANY_USER), true) || (int) $user_id <= 0)
		{
			return false;
		}
		static $cache = array();
		if (!isset($cache[(int) $user_id]))
		{
			$row = pjAuthUserModel::factory()->select('t1.company_id')->find((int) $user_id)->getData();
			$cache[(int) $user_id] = !empty($row) && empty($row['company_id']);
		}
		return $cache[(int) $user_id];
	}

	public static function setActive($company_id, $role_id, $user_id)
	{
		if (!self::isGlobalUser($role_id, $user_id))
		{
			return false;
		}
		$allowed = self::allowedCompanies($role_id, $user_id);
		if (isset($allowed[(int) $company_id]))
		{
			$_SESSION[self::SESSION_KEY] = (int) $company_id;
			return true;
		}
		return false;
	}

	public static function canSwitch($role_id, $user_id)
	{
		return self::isGlobalUser($role_id, $user_id) && count(self::allowedCompanies($role_id, $user_id)) > 1;
	}

	public static function clearCache()
	{
		self::$cache = array();
	}

	/* ------------------------------------------------------------------
	 * Public company reference (hash) - the numeric id is never shown
	 * ------------------------------------------------------------------ */

	/** Random, non-guessable reference of a company (created on first use). */
	public static function hashOf($company_id)
	{
		$company_id = (int) $company_id;
		if ($company_id <= 0)
		{
			return '';
		}
		static $cache = array();
		if (isset($cache[$company_id]))
		{
			return $cache[$company_id];
		}
		$m = pjCompanyModel::factory();
		$row = $m->select('t1.hash')->find($company_id)->getData();
		if (empty($row))
		{
			return '';
		}
		$hash = isset($row['hash']) ? (string) $row['hash'] : '';
		if (!preg_match('/^[a-f0-9]{32}$/', $hash))
		{
			$hash = bin2hex(function_exists('random_bytes') ? random_bytes(16) : md5(uniqid(mt_rand(), true), true));
			$m->reset()->where('id', $company_id)->limit(1)->modifyAll(array('hash' => $hash));
		}
		return $cache[$company_id] = $hash;
	}

	/** Company id for a public hash, or 0. */
	public static function idFromHash($hash)
	{
		$hash = strtolower(trim((string) $hash));
		if (!preg_match('/^[a-f0-9]{32}$/', $hash))
		{
			return 0;
		}
		$row = pjCompanyModel::factory()->select('t1.id')->where('t1.hash', $hash)->limit(1)->findAll()->getDataIndex(0);
		return !empty($row) ? (int) $row['id'] : 0;
	}

	/* ------------------------------------------------------------------
	 * Users per company
	 * ------------------------------------------------------------------ */

	/**
	 * SQL condition for the users of a company. With $with_admins the administrators
	 * (global, no company) are included - e.g. as possible property owners; the Users
	 * menu of a non-administrator never lists them.
	 */
	public static function userScopeSql($company_id, $alias = 't1', $with_admins = true)
	{
		if (!$with_admins)
		{
			return sprintf("(%1\$s.`company_id` = %2\$u AND %1\$s.`role_id` <> %3\$u)", $alias, (int) $company_id, self::ROLE_ADMIN);
		}
		// company users + administrators + admin-side users (no company, not a company role)
		return sprintf("(%1\$s.`company_id` = %2\$u OR %1\$s.`role_id` = %3\$u OR (%1\$s.`company_id` IS NULL AND %1\$s.`role_id` NOT IN (%4\$u, %5\$u)))",
			$alias, (int) $company_id, self::ROLE_ADMIN, self::ROLE_COMPANY, self::ROLE_COMPANY_USER);
	}

	public static function filterUserIds($ids, $company_id)
	{
		$ids = array_values(array_filter(array_map('intval', (array) $ids)));
		if (empty($ids))
		{
			return array();
		}
		return array_map('intval', pjAuthUserModel::factory()->whereIn('t1.id', $ids)->where(self::userScopeSql($company_id, 't1', false))->findAll()->getDataPair(null, 'id'));
	}

	/** Users a non-administrator may manage: their company's users, never an administrator. */
	public static function isCompanyUser($user_id, $company_id)
	{
		return count(self::filterUserIds(array($user_id), $company_id)) === 1;
	}

	/** company_id to store for a user with the given role (administrators have none). */
	public static function companyForRole($role_id, $company_id)
	{
		return (int) $role_id === self::ROLE_ADMIN || (int) $company_id <= 0 ? ':NULL' : (int) $company_id;
	}

	/* ------------------------------------------------------------------
	 * System options per company
	 * ------------------------------------------------------------------ */

	/** Company option rows live in plugin_base_options with foreign_id = OFFSET + company id. */
	const OPTIONS_OFFSET = 1000;

	/**
	 * Option tabs that are per company: 1 General, 2 Visual & Branding, 3 Email,
	 * 4-7 Login & Protection, 8 Captcha & Spam, 10 API keys (+ SMS keys).
	 * Backup on/off (9), cron and internal keys stay global.
	 */
	public static $companyOptionTabs = array(1, 2, 3, 4, 5, 6, 7, 8, 10);

	/**
	 * Keys that drive the admin LOGIN SCREEN itself (shown before anybody is identified),
	 * so they always use the shared value.
	 */
	/**
	 * Secret system options that are NOT copied into a new company's settings (the
	 * company would see the platform's password / key). While the company keeps the
	 * platform's server / site key (the value of the paired option), the platform's
	 * secret is used for it at run time; with its own server it must enter its own.
	 */
	public static $secretOptionKeys = array(
		'o_smtp_user' => 'o_smtp_host',
		'o_smtp_pass' => 'o_smtp_host',
		'o_captcha_secret_key_front' => 'o_captcha_site_key_front',
	);

	public static $globalOptionKeys = array(
		'o_captcha_type', 'o_captcha_mode', 'o_captcha_length', 'o_captcha_background',
		'o_captcha_site_key', 'o_captcha_secret_key', 'o_captcha_location', 'o_secure_login_use_captcha'
	);

	public static function isCompanyOptionKey($key, $tab_id)
	{
		if (in_array((string) $key, self::$globalOptionKeys, true))
		{
			return false;
		}
		return in_array((int) $tab_id, self::$companyOptionTabs, true) || strpos((string) $key, 'plugin_sms_') === 0;
	}

	/** foreign_id to store a given option key in, for the active company fid. */
	public static function fidForKey($key, $company_fid)
	{
		return in_array((string) $key, self::$globalOptionKeys, true) ? 1 : (int) $company_fid;
	}

	/**
	 * Option rows of the given tabs as the company sees them: its own rows, plus the
	 * shared rows for the login-screen keys.
	 */
	public static function optionRows($tabs, $company_fid)
	{
		$m = pjBaseOptionModel::factory();
		$rows = $m->reset()->where('t1.foreign_id', (int) $company_fid)->whereIn('t1.tab_id', (array) $tabs)->findAll()->getData();
		if ((int) $company_fid !== 1)
		{
			$global = $m->reset()->where('t1.foreign_id', 1)->whereIn('t1.tab_id', (array) $tabs)->whereIn('t1.key', self::$globalOptionKeys)->findAll()->getData();
			$rows = array_merge($rows, $global);
		}
		usort($rows, function ($a, $b) {
			if ((int) $a['tab_id'] !== (int) $b['tab_id']) { return (int) $a['tab_id'] - (int) $b['tab_id']; }
			if ((int) $a['order'] !== (int) $b['order']) { return (int) $a['order'] - (int) $b['order']; }
			return strcmp($a['key'], $b['key']);
		});
		return $rows;
	}

	/** Company of a user (0 for administrators / unknown). */
	public static function companyOfUser($user_id)
	{
		$row = pjAuthUserModel::factory()->select('t1.company_id, t1.role_id')->find((int) $user_id)->getData();
		if (empty($row) || (int) $row['role_id'] === self::ROLE_ADMIN)
		{
			return 0;
		}
		if (!empty($row['company_id']))
		{
			return (int) $row['company_id'];
		}
		$c = pjCompanyModel::factory()->select('t1.id')->where('t1.user_id', (int) $user_id)->limit(1)->findAll()->getDataIndex(0);
		return !empty($c) ? (int) $c['id'] : 0;
	}

	/** Company of the back-office user with this e-mail (0 if none). */
	public static function companyOfEmail($email)
	{
		$row = pjAuthUserModel::factory()->select('t1.id')->where('t1.email', (string) $email)->limit(1)->findAll()->getDataIndex(0);
		return !empty($row) ? self::companyOfUser($row['id']) : 0;
	}

	/* ------------------------------------------------------------------
	 * Labels per company (stored as multi_lang rows field = title_c<id>)
	 * ------------------------------------------------------------------ */

	public static function labelField($company_id)
	{
		return 'title_c' . (int) $company_id;
	}

	/** key => text overrides of a company for a locale. */
	public static function labelOverrides($company_id, $locale_id)
	{
		if ((int) $company_id <= 0)
		{
			return array();
		}
		return pjMultiLangModel::factory()
			->select('t1.content, t2.key')
			->join('pjBaseField', 't2.id=t1.foreign_id', 'inner')
			->where('t1.locale', (int) $locale_id)
			->where('t1.model', 'pjBaseField')
			->where('t1.field', self::labelField($company_id))
			->findAll()->getDataPair('key', 'content');
	}

	/* ------------------------------------------------------------------
	 * Countries per company
	 * ------------------------------------------------------------------ */

	/**
	 * SQL: country t1 is visible (shared or the company's own) and active for the company
	 * (company status overrides the shared status).
	 */
	public static function countryActiveSql($company_id, $alias = 't1')
	{
		$company_id = (int) $company_id;
		$cc = pjCompanyCountryModel::factory()->getTable();
		return sprintf("((%1\$s.`company_id` IS NULL OR %1\$s.`company_id` = %2\$u) AND COALESCE((SELECT cc.`status` FROM `%3\$s` cc WHERE cc.`company_id` = %2\$u AND cc.`country_id` = %1\$s.`id` LIMIT 1), %1\$s.`status`) = 'T')", $alias, $company_id, $cc);
	}

	/** SQL expression: the company's status of country t1. */
	public static function countryStatusExpr($company_id, $alias = 't1')
	{
		$cc = pjCompanyCountryModel::factory()->getTable();
		return sprintf("COALESCE((SELECT cc.`status` FROM `%3\$s` cc WHERE cc.`company_id` = %2\$u AND cc.`country_id` = %1\$s.`id` LIMIT 1), %1\$s.`status`)", $alias, (int) $company_id, $cc);
	}

	public static function setCountryStatus($company_id, $country_id, $status)
	{
		$m = pjCompanyCountryModel::factory();
		$m->where('company_id', (int) $company_id)->where('country_id', (int) $country_id)->eraseAll();
		$m->reset()->setAttributes(array('company_id' => (int) $company_id, 'country_id' => (int) $country_id, 'status' => $status === 'T' ? 'T' : 'F'))->insert();
	}

	/**
	 * foreign_id for the company's system options. Creates the company's copy
	 * (from the global values) the first time, and adds keys that are new since.
	 */
	public static function optionsForeignId($company_id)
	{
		$company_id = (int) $company_id;
		if ($company_id <= 0)
		{
			return 1;
		}
		$fid = self::OPTIONS_OFFSET + $company_id;
		static $done = array();
		if (isset($done[$fid]))
		{
			return $fid;
		}
		$done[$fid] = true;

		$m = pjBaseOptionModel::factory();
		$have = $m->reset()->where('t1.foreign_id', $fid)->findAll()->getDataPair('key', 'key');
		$global = $m->reset()->where('t1.foreign_id', 1)->findAll()->getData();
		foreach ($global as $row)
		{
			if (!self::isCompanyOptionKey($row['key'], $row['tab_id']) || isset($have[$row['key']]))
			{
				continue;
			}
			$row['foreign_id'] = $fid;
			if (isset(self::$secretOptionKeys[$row['key']]))
			{
				$row['value'] = '';
			}
			$m->reset()->setAttributes($row)->insert();
		}
		// Translatable option texts (login / captcha messages)
		$ml = pjMultiLangModel::factory();
		if ((int) $ml->reset()->where('t1.model', 'pjBaseOption')->where('t1.foreign_id', $fid)->findCount()->getData() === 0)
		{
			foreach ($ml->reset()->where('t1.model', 'pjBaseOption')->where('t1.foreign_id', 1)->findAll()->getData() as $r)
			{
				$ml->reset()->setAttributes(array('foreign_id' => $fid, 'model' => 'pjBaseOption', 'locale' => $r['locale'], 'field' => $r['field'], 'content' => $r['content'], 'source' => $r['source']))->insert();
			}
		}
		return $fid;
	}

	/** Global system options with the company's own values on top. */
	public static function basePairs($company_id)
	{
		$m = pjBaseOptionModel::factory();
		$pairs = $m->getPairs(1);
		if ((int) $company_id > 0)
		{
			$global = $pairs;
			$pairs = array_merge($pairs, $m->reset()->getPairs(self::optionsForeignId($company_id)));
			foreach (self::$secretOptionKeys as $secret => $pair)
			{
				if (isset($pairs[$secret], $global[$secret], $pairs[$pair], $global[$pair]) && $pairs[$secret] === ''
					&& (string) $pairs[$pair] === (string) $global[$pair])
				{
					$pairs[$secret] = $global[$secret];
				}
			}
		}
		return $pairs;
	}

	/* ------------------------------------------------------------------
	 * Provisioning for a company (idempotent)
	 * ------------------------------------------------------------------ */

	/**
	 * Make sure the company has its own copy of the general (admin) notifications.
	 * Copies the global templates (company_id = 0, is_general = 1) incl. their texts.
	 */
	public static function ensureNotifications($company_id)
	{
		$company_id = (int) $company_id;
		if ($company_id <= 0)
		{
			return;
		}
		$pjNotificationModel = pjNotificationModel::factory();
		$templates = $pjNotificationModel->reset()
			->where('t1.company_id', 0)->where('t1.foreign_id', 0)->where('t1.is_general', 1)
			->orderBy('t1.id ASC')->findAll()->getData();
		if (empty($templates))
		{
			return;
		}
		$existing = $pjNotificationModel->reset()
			->where('t1.company_id', $company_id)->where('t1.foreign_id', 0)->where('t1.is_general', 1)
			->findAll()->getData();
		$have = array();
		foreach ($existing as $e)
		{
			$have[$e['recipient'] . '|' . $e['transport'] . '|' . $e['variant']] = true;
		}
		$pjMultiLangModel = pjMultiLangModel::factory();
		foreach ($templates as $t)
		{
			if (isset($have[$t['recipient'] . '|' . $t['transport'] . '|' . $t['variant']]))
			{
				continue;
			}
			$new_id = $pjNotificationModel->reset()->setAttributes(array(
				'foreign_id' => 0,
				'company_id' => $company_id,
				'recipient' => $t['recipient'],
				'transport' => $t['transport'],
				'variant' => $t['variant'],
				'is_active' => $t['is_active'],
				'is_general' => 1
			))->insert()->getInsertId();
			if ($new_id !== false && (int) $new_id > 0)
			{
				$rows = $pjMultiLangModel->reset()->where('t1.model', 'pjNotification')->where('t1.foreign_id', $t['id'])->findAll()->getData();
				foreach ($rows as $r)
				{
					$pjMultiLangModel->reset()->setAttributes(array(
						'foreign_id' => $new_id,
						'model' => 'pjNotification',
						'locale' => $r['locale'],
						'field' => $r['field'],
						'content' => $r['content'],
						'source' => isset($r['source']) ? $r['source'] : 'data'
					))->insert();
				}
			}
		}
	}

	/**
	 * General notification row for a company (falls back to the global template).
	 */
	public static function getGeneralNotification($company_id, $recipient, $transport, $variant)
	{
		$company_id = (int) $company_id;
		if ($company_id > 0)
		{
			self::ensureNotifications($company_id);
		}
		$m = pjNotificationModel::factory();
		$row = $m->reset()->where('t1.company_id', $company_id)->where('t1.foreign_id', 0)->where('t1.is_general', 1)
			->where('t1.recipient', $recipient)->where('t1.transport', $transport)->where('t1.variant', $variant)
			->limit(1)->findAll()->getDataIndex(0);
		if (empty($row) && $company_id > 0)
		{
			$row = $m->reset()->where('t1.company_id', 0)->where('t1.foreign_id', 0)->where('t1.is_general', 1)
				->where('t1.recipient', $recipient)->where('t1.transport', $transport)->where('t1.variant', $variant)
				->limit(1)->findAll()->getDataIndex(0);
		}
		return $row;
	}

	/**
	 * Give a new company its own copy of the amenity list (features) of the
	 * oldest company, so properties can be tagged straight away.
	 */
	public static function seedFeatures($company_id)
	{
		$company_id = (int) $company_id;
		if ($company_id <= 0)
		{
			return;
		}
		$pjFeatureModel = pjFeatureModel::factory();
		if ((int) $pjFeatureModel->reset()->where('t1.company_id', $company_id)->findCount()->getData() > 0)
		{
			return;
		}
		$source = pjCompanyModel::factory()->where('t1.id !=', $company_id)->orderBy('t1.id ASC')->limit(1)->findAll()->getDataIndex(0);
		if (empty($source))
		{
			return;
		}
		$features = $pjFeatureModel->reset()->where('t1.company_id', $source['id'])->orderBy('t1.id ASC')->findAll()->getData();
		$pjMultiLangModel = pjMultiLangModel::factory();
		foreach ($features as $f)
		{
			$new_id = $pjFeatureModel->reset()->setAttributes(array(
				'company_id' => $company_id,
				'category_id' => $f['category_id'],
				'status' => $f['status']
			))->insert()->getInsertId();
			if ($new_id !== false && (int) $new_id > 0)
			{
				$rows = $pjMultiLangModel->reset()->where('t1.model', 'pjFeature')->where('t1.foreign_id', $f['id'])->findAll()->getData();
				foreach ($rows as $r)
				{
					$pjMultiLangModel->reset()->setAttributes(array(
						'foreign_id' => $new_id,
						'model' => 'pjFeature',
						'locale' => $r['locale'],
						'field' => $r['field'],
						'content' => $r['content'],
						'source' => isset($r['source']) ? $r['source'] : 'data'
					))->insert();
				}
			}
		}
	}

	/**
	 * Company id of a property (calendar), or 0.
	 */
	public static function companyOfCalendar($calendar_id)
	{
		if ((int) $calendar_id <= 0)
		{
			return 0;
		}
		$arr = pjCalendarModel::factory()->select('t1.company_id')->find((int) $calendar_id)->getData();
		return !empty($arr) ? (int) $arr['company_id'] : 0;
	}

	/* ------------------------------------------------------------------
	 * Languages per company
	 * ------------------------------------------------------------------ */

	/** Company whose languages are used in this request (NULL = not decided yet). */
	private static $localeScope = null;

	private static $localesReady = array();

	/** Front-end pages tell which company they show; the back-office uses the active company. */
	public static function setLocaleScope($company_id)
	{
		self::$localeScope = (int) $company_id;
	}

	/** Creates the language table if the update SQL has not been run yet (once per session). */
	private static function ensureLocaleTable()
	{
		if (!empty($_SESSION['pj_companies_locales_ok']))
		{
			return;
		}
		$m = pjCompanyLocaleModel::factory();
		$m->prepare(sprintf("CREATE TABLE IF NOT EXISTS `%s` (`company_id` int(10) unsigned NOT NULL, `locale_id` int(10) unsigned NOT NULL, `sort` int(10) unsigned DEFAULT NULL, `is_default` tinyint(1) unsigned NOT NULL DEFAULT 0, PRIMARY KEY (`company_id`, `locale_id`), KEY `locale_id` (`locale_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8", $m->getTable()))->exec();
		$_SESSION['pj_companies_locales_ok'] = 1;
	}

	/** 0 = no company (installer, login screen, cron): the system language list is used. */
	public static function localeScopeId()
	{
		self::ensureLocaleTable();
		if (self::$localeScope !== null)
		{
			$cid = self::$localeScope;
		} elseif (isset($_SESSION['admin_user']['id'], $_SESSION['admin_user']['role_id'])) {
			$cid = (int) self::activeId($_SESSION['admin_user']['role_id'], $_SESSION['admin_user']['id']);
		} else {
			$cid = 0;
		}
		if ($cid > 0)
		{
			self::ensureLocales($cid);
		}
		return $cid;
	}

	/**
	 * First use after the update: every company that has no language list yet
	 * gets all system languages (same order and default as before languages
	 * became per company). New companies get their list when they are created
	 * (seedNewCompanyLocales).
	 */
	public static function ensureLocales($company_id)
	{
		$company_id = (int) $company_id;
		if ($company_id <= 0 || isset(self::$localesReady[$company_id]))
		{
			return;
		}
		self::$localesReady[$company_id] = true;
		$m = pjCompanyLocaleModel::factory();
		if ((int) $m->where('t1.company_id', $company_id)->findCount()->getData() > 0)
		{
			return;
		}
		$missing = pjCompanyModel::factory()
			->where(sprintf("t1.id NOT IN (SELECT `company_id` FROM `%s`)", $m->getTable()))
			->findAll()->getDataPair(null, 'id');
		if (!in_array($company_id, array_map('intval', $missing), true))
		{
			$missing[] = $company_id;
		}
		$locales = pjBaseLocaleModel::factory()->unscoped()->orderBy('t1.sort ASC')->findAll()->getData();
		foreach ($missing as $cid)
		{
			self::seedLocales((int) $cid, $locales);
		}
	}

	/** A new company starts with the system default language only. */
	public static function seedNewCompanyLocales($company_id)
	{
		$default = pjBaseLocaleModel::factory()->unscoped()->orderBy('t1.is_default DESC, t1.sort ASC')->limit(1)->findAll()->getData();
		self::seedLocales((int) $company_id, $default);
		self::$localesReady[(int) $company_id] = true;
	}

	private static function seedLocales($company_id, $locales)
	{
		if ($company_id <= 0 || empty($locales))
		{
			return;
		}
		$m = pjCompanyLocaleModel::factory();
		if ((int) $m->where('t1.company_id', $company_id)->findCount()->getData() > 0)
		{
			return;
		}
		$has_default = false;
		foreach (array_values($locales) as $i => $l)
		{
			$is_default = ((int) $l['is_default'] === 1 && !$has_default) ? 1 : 0;
			$has_default = $has_default || $is_default === 1;
			$m->reset()->setAttributes(array('company_id' => $company_id, 'locale_id' => $l['id'], 'sort' => $i + 1, 'is_default' => $is_default))->insert();
		}
		if (!$has_default)
		{
			$first = array_values($locales);
			$m->reset()->where('company_id', $company_id)->where('locale_id', $first[0]['id'])->limit(1)->modifyAll(array('is_default' => 1));
		}
	}

	/** FROM clause for pjBaseLocaleModel: the system languages the company uses, with its order and default. */
	public static function localeFromSql($company_id)
	{
		return sprintf("(SELECT l.`id`, l.`language_iso`, l.`name`, l.`flag`, l.`dir`, cl.`sort`, cl.`is_default` FROM `%s` AS l INNER JOIN `%s` AS cl ON cl.`locale_id` = l.`id` AND cl.`company_id` = %u)",
			pjBaseLocaleModel::factory()->getTable(), pjCompanyLocaleModel::factory()->getTable(), (int) $company_id);
	}

	/** Default language id of a company (0 if none). */
	public static function companyDefaultLocale($company_id)
	{
		self::ensureLocales($company_id);
		$row = pjCompanyLocaleModel::factory()->where('t1.company_id', (int) $company_id)->orderBy('t1.is_default DESC, t1.sort ASC')->limit(1)->findAll()->getDataIndex(0);
		return !empty($row) ? (int) $row['locale_id'] : 0;
	}

	/** Language ids of a company. */
	public static function companyLocaleIds($company_id)
	{
		self::ensureLocales($company_id);
		return array_map('intval', pjCompanyLocaleModel::factory()->where('t1.company_id', (int) $company_id)->findAll()->getDataPair(null, 'locale_id'));
	}

	/** How many companies use a system language. */
	public static function localeUsage($locale_id)
	{
		return (int) pjCompanyLocaleModel::factory()->where('t1.locale_id', (int) $locale_id)->findCount()->getData();
	}

	/* ------------------------------------------------------------------
	 * Cron jobs per company
	 * ------------------------------------------------------------------ */

	/** Company a cron job is running for (0 = all companies / system). */
	public static $cronCompanyId = 0;

	/** Creates the table if the update SQL has not been run yet (once per request). */
	public static function ensureCronTable()
	{
		static $done = false;
		if ($done)
		{
			return;
		}
		$done = true;
		$m = pjCompanyCronModel::factory();
		$m->prepare(sprintf("CREATE TABLE IF NOT EXISTS `%s` (`company_id` int(10) unsigned NOT NULL, `cron_id` int(10) unsigned NOT NULL, `is_active` tinyint(1) unsigned NOT NULL DEFAULT 1, `next_run` datetime DEFAULT NULL, `last_run` datetime DEFAULT NULL, `status` text, PRIMARY KEY (`company_id`, `cron_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8", $m->getTable()))->exec();
	}

	/**
	 * The company's row for every cron job (created on first use; on/off
	 * starts like the system switch of the job).
	 * @return array cron_id => row
	 */
	public static function companyCronRows($company_id)
	{
		self::ensureCronTable();
		$company_id = (int) $company_id;
		$m = pjCompanyCronModel::factory();
		$rows = $m->where('t1.company_id', $company_id)->findAll()->getDataPair('cron_id');
		foreach (pjBaseCronJobModel::factory()->findAll()->getData() as $job)
		{
			if (!isset($rows[$job['id']]))
			{
				$m->reset()->setAttributes(array('company_id' => $company_id, 'cron_id' => $job['id'], 'is_active' => (int) $job['is_active'] ? 1 : 0))->insert();
				$rows[$job['id']] = array('company_id' => $company_id, 'cron_id' => $job['id'], 'is_active' => (int) $job['is_active'] ? 1 : 0, 'next_run' => null, 'last_run' => null, 'status' => null);
			}
		}
		return $rows;
	}

	/* ------------------------------------------------------------------
	 * SMS log per company (companies_sms: which company sent an SMS log row)
	 * ------------------------------------------------------------------ */

	public static function smsTable()
	{
		return str_replace('companies_cron_jobs', 'companies_sms', pjCompanyCronModel::factory()->getTable());
	}

	/** Creates the table if the update SQL has not been run yet (once per request). */
	public static function ensureSmsTable()
	{
		static $done = false;
		if ($done)
		{
			return;
		}
		$done = true;
		pjCompanyCronModel::factory()->prepare(sprintf("CREATE TABLE IF NOT EXISTS `%s` (`sms_id` int(10) unsigned NOT NULL, `company_id` int(10) unsigned NOT NULL, PRIMARY KEY (`sms_id`), KEY `company_id` (`company_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8", self::smsTable()))->exec();
	}

	/** Company an SMS is sent for right now (options in use, cron run or the user's company). */
	public static function currentSmsCompany()
	{
		if ((int) self::$cronCompanyId > 0)
		{
			return (int) self::$cronCompanyId;
		}
		$reg = pjRegistry::getInstance();
		$fid = $reg->is('options_foreign_id') ? (int) $reg->get('options_foreign_id') : 0;
		if ($fid > self::OPTIONS_OFFSET)
		{
			return $fid - self::OPTIONS_OFFSET;
		}
		if (isset($_SESSION['admin_user']['id'], $_SESSION['admin_user']['role_id']))
		{
			return (int) self::activeId($_SESSION['admin_user']['role_id'], $_SESSION['admin_user']['id']);
		}
		return 0;
	}

	public static function tagSms($sms_id, $company_id)
	{
		if ((int) $sms_id <= 0 || (int) $company_id <= 0)
		{
			return;
		}
		self::ensureSmsTable();
		pjCompanyCronModel::factory()->prepare(sprintf("INSERT IGNORE INTO `%s` (`sms_id`, `company_id`) VALUES (%u, %u)", self::smsTable(), (int) $sms_id, (int) $company_id))->exec();
	}

	/** SQL: SMS log rows a user of $company_id may see ($with_untagged: also rows of no company). */
	public static function smsScopeSql($company_id, $with_untagged = false)
	{
		self::ensureSmsTable();
		$sql = sprintf("t1.id IN (SELECT `sms_id` FROM `%s` WHERE `company_id` = %u)", self::smsTable(), (int) $company_id);
		if ($with_untagged)
		{
			$sql = sprintf("(%s OR t1.id NOT IN (SELECT `sms_id` FROM `%s`))", $sql, self::smsTable());
		}
		return $sql;
	}

	public static function saveCronRow($company_id, $cron_id, $data)
	{
		self::companyCronRows($company_id);
		pjCompanyCronModel::factory()->where('company_id', (int) $company_id)->where('cron_id', (int) $cron_id)->limit(1)->modifyAll($data);
	}
}


?>
