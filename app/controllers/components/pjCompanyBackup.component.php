<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Backup of ONE company: its database rows (SQL) and its uploaded files (ZIP).
 *
 * The SQL file first removes the company's current rows and then re-inserts the
 * backed-up rows, so importing it restores that company only and leaves every
 * other company untouched.
 */
class pjCompanyBackup
{
	private $company_id;
	private $model;
	private $handle;

	public function __construct($company_id)
	{
		$this->company_id = (int) $company_id;
		$this->model = pjCompanyModel::factory();
	}

	/** Folder that holds this company's backups (created and protected on demand). */
	public static function folder($company_id)
	{
		$base = PJ_WEB_PATH . 'backup/';
		self::protect($base);
		// Folder named by the company's public hash (the numeric id is never exposed).
		$dir = $base . 'company-' . pjCompanyContext::hashOf($company_id) . '/';
		$old = $base . 'company-' . (int) $company_id . '/';
		if (!is_dir($dir) && is_dir($old))
		{
			@rename($old, $dir);
		}
		if (!is_dir($dir))
		{
			@mkdir($dir, 0755, true);
		}
		self::protect($dir);
		return $dir;
	}

	/** Block direct web access to backup files (downloads go through the admin). */
	public static function protect($dir)
	{
		if (is_dir($dir) && !is_file($dir . '.htaccess'))
		{
			@file_put_contents($dir . '.htaccess', "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order Allow,Deny\n    Deny from all\n</IfModule>\n");
		}
		if (is_dir($dir) && !is_file($dir . 'index.php'))
		{
			@file_put_contents($dir . 'index.php', '<?php header("HTTP/1.1 403 Forbidden"); exit; ?>');
		}
	}

	/* ------------------------------------------------------------------
	 * Database
	 * ------------------------------------------------------------------ */

	private function query($sql)
	{
		return $this->model->reset()->prepare($sql)->exec()->getData();
	}

	private function columns($table)
	{
		static $cache = array();
		if (!isset($cache[$table]))
		{
			$cache[$table] = array();
			foreach ($this->query(sprintf("SHOW COLUMNS FROM `%s`", $table)) as $c)
			{
				$cache[$table][$c['Field']] = preg_match('/blob|binary/i', $c['Type']) === 1;
			}
		}
		return $cache[$table];
	}

	private function tableExists($table)
	{
		static $tables = null;
		if ($tables === null)
		{
			$tables = array();
			foreach ($this->query("SHOW TABLES") as $row)
			{
				$tables[] = current($row);
			}
		}
		return in_array($table, $tables, true);
	}

	private function write($str)
	{
		fwrite($this->handle, $str);
	}

	/** Dump rows of the model's table matching $where as REPLACE INTO statements. */
	private function dump($model, $where)
	{
		$table = $model->getTable();
		if (!$this->tableExists($table))
		{
			return 0;
		}
		$cols = $this->columns($table);
		$select = array();
		foreach ($cols as $name => $is_blob)
		{
			$select[] = $is_blob ? sprintf("HEX(t1.`%1\$s`) AS `%1\$s`", $name) : sprintf("t1.`%s`", $name);
		}
		$rows = $model->reset()->select(join(', ', $select))->where($where)->findAll()->getData();
		foreach ($rows as $row)
		{
			$values = array();
			foreach ($row as $name => $val)
			{
				if ($val === null)
				{
					$values[] = 'NULL';
				} elseif (!empty($cols[$name])) {
					$values[] = $val === '' ? "''" : "UNHEX('" . preg_replace('/[^0-9A-Fa-f]/', '', $val) . "')";
				} else {
					$values[] = "'" . $this->model->escapeStr($val) . "'";
				}
			}
			$this->write(sprintf("REPLACE INTO `%s` (`%s`) VALUES (%s);\n", $table, join('`, `', array_keys($row)), join(', ', $values)));
		}
		return count($rows);
	}

	private function del($table, $where)
	{
		if ($this->tableExists($table))
		{
			$this->write(sprintf("DELETE FROM `%s` WHERE %s;\n", $table, $where));
		}
	}

	/**
	 * Write the company's SQL backup to $file. Returns true on success.
	 */
	public function exportSql($file)
	{
		$X = $this->company_id;
		if ($X <= 0 || ($this->handle = @fopen($file, 'w')) === false)
		{
			return false;
		}

		$mods = array(
			'companies' => pjCompanyModel::factory(),
			'co_options' => pjCompanyOptionModel::factory(),
			'base_opt' => pjBaseOptionModel::factory(),
			'calendars' => pjCalendarModel::factory(),
			'multi_lang' => pjMultiLangModel::factory(),
			'options' => pjOptionModel::factory(),
			'res' => pjReservationModel::factory(),
			'res_extras' => pjReservationExtraModel::factory(),
			'prices' => pjPriceModel::factory(),
			'period' => pjPeriodModel::factory(),
			'period_price' => pjPeriodPriceModel::factory(),
			'limits' => pjLimitModel::factory(),
			'feeds' => pjFeedModel::factory(),
			'ical_urls' => pjIcalUrlsModel::factory(),
			'photos' => pjCalendarPhotoModel::factory(),
			'password' => pjPasswordModel::factory(),
			'cal_extras' => pjCalendarExtraModel::factory(),
			'cal_feat' => pjCalendarFeatureModel::factory(),
			'cal_disc' => pjCalendarDiscountModel::factory(),
			'cal_vouch' => pjCalendarVoucherModel::factory(),
			'extras' => pjExtraModel::factory(),
			'features' => pjFeatureModel::factory(),
			'discounts' => pjDiscountModel::factory(),
			'vouchers' => pjVoucherModel::factory(),
			'notif' => pjNotificationModel::factory(),
			'users' => pjAuthUserModel::factory(),
			'co_countries' => pjCompanyCountryModel::factory(),
			'countries' => pjBaseCountryModel::factory(),
		);
		$t = array(
			'companies'   => pjCompanyModel::factory()->getTable(),
			'co_options'  => pjCompanyOptionModel::factory()->getTable(),
			'base_opt'    => pjBaseOptionModel::factory()->getTable(),
			'calendars'   => pjCalendarModel::factory()->getTable(),
			'multi_lang'  => pjMultiLangModel::factory()->getTable(),
			'options'     => pjOptionModel::factory()->getTable(),
			'res'         => pjReservationModel::factory()->getTable(),
			'res_extras'  => pjReservationExtraModel::factory()->getTable(),
			'prices'      => pjPriceModel::factory()->getTable(),
			'period'      => pjPeriodModel::factory()->getTable(),
			'period_price'=> pjPeriodPriceModel::factory()->getTable(),
			'limits'      => pjLimitModel::factory()->getTable(),
			'feeds'       => pjFeedModel::factory()->getTable(),
			'ical_urls'   => pjIcalUrlsModel::factory()->getTable(),
			'photos'      => pjCalendarPhotoModel::factory()->getTable(),
			'password'    => pjPasswordModel::factory()->getTable(),
			'cal_extras'  => pjCalendarExtraModel::factory()->getTable(),
			'cal_feat'    => pjCalendarFeatureModel::factory()->getTable(),
			'cal_disc'    => pjCalendarDiscountModel::factory()->getTable(),
			'cal_vouch'   => pjCalendarVoucherModel::factory()->getTable(),
			'extras'      => pjExtraModel::factory()->getTable(),
			'features'    => pjFeatureModel::factory()->getTable(),
			'discounts'   => pjDiscountModel::factory()->getTable(),
			'vouchers'    => pjVoucherModel::factory()->getTable(),
			'notif'       => pjNotificationModel::factory()->getTable(),
			'users'       => pjAuthUserModel::factory()->getTable(),
		);
		$pay_models = array();
		if (class_exists('pjPaymentOptionModel')) { $pay_models[] = pjPaymentOptionModel::factory(); }
		if (class_exists('pjMollieOptionModel')) { $pay_models[] = pjMollieOptionModel::factory(); }

		$fid = pjCompanyContext::optionsForeignId($X);
		$company = pjCompanyModel::factory()->find($X)->getData();
		$name = !empty($company) ? $company['name'] : '';

		// Reusable sub-selects
		$CAL   = sprintf("(SELECT `id` FROM `%s` WHERE `company_id` = %u)", $t['calendars'], $X);
		$RES   = sprintf("(SELECT `id` FROM `%s` WHERE `calendar_id` IN %s)", $t['res'], $CAL);
		$PER   = sprintf("(SELECT `id` FROM `%s` WHERE `foreign_id` IN %s)", $t['period'], $CAL);
		$NOTIF = sprintf("(SELECT `id` FROM `%s` WHERE `company_id` = %u OR `foreign_id` IN %s)", $t['notif'], $X, $CAL);
		$own   = function ($table) use ($t, $X) { return sprintf("(SELECT `id` FROM `%s` WHERE `company_id` = %u)", $t[$table], $X); };

		// Snapshot of the ids so the DELETE part does not depend on the data at restore time
		$this->write("-- Company backup: " . str_replace(array("\r", "\n"), ' ', $name) . " (company id " . $X . ")\n");
		$this->write("-- Created: " . date('Y-m-d H:i:s') . "\n");
		$this->write("-- Importing this file replaces ONLY this company's data. Other companies are not touched.\n");
		$this->write("-- Users are restored with REPLACE (never deleted).\n\n");
		$this->write("SET NAMES utf8;\nSET FOREIGN_KEY_CHECKS = 0;\nSTART TRANSACTION;\n\n");

		// ---- 1. remove the company's current data (children first) ----
		$this->write("-- Remove current data of this company\n");
		$this->del($t['multi_lang'], sprintf("(`model` IN ('pjCalendar','pjOption','pjPayment') AND `foreign_id` IN %s)", $CAL));
		foreach (array('pjExtra' => 'extras', 'pjFeature' => 'features', 'pjDiscount' => 'discounts', 'pjVoucher' => 'vouchers') as $ml => $tb)
		{
			$this->del($t['multi_lang'], sprintf("(`model` = '%s' AND `foreign_id` IN %s)", $ml, $own($tb)));
		}
		$this->del($t['multi_lang'], sprintf("(`model` = 'pjNotification' AND `foreign_id` IN %s)", $NOTIF));
		$this->del($t['res_extras'], sprintf("`reservation_id` IN %s", $RES));
		$this->del($t['period_price'], sprintf("`period_id` IN %s", $PER));
		$this->del($t['res'], sprintf("`calendar_id` IN %s", $CAL));
		$this->del($t['options'], sprintf("`foreign_id` IN %s", $CAL));
		$this->del($t['prices'], sprintf("`foreign_id` IN %s", $CAL));
		$this->del($t['period'], sprintf("`foreign_id` IN %s", $CAL));
		foreach (array('limits', 'feeds', 'ical_urls', 'photos', 'password', 'cal_extras', 'cal_feat', 'cal_disc', 'cal_vouch') as $tb)
		{
			$this->del($t[$tb], sprintf("`calendar_id` IN %s", $CAL));
		}
		foreach ($pay_models as $pm)
		{
			$this->del($pm->getTable(), sprintf("`foreign_id` IN %s", $CAL));
		}
		$this->del($t['notif'], sprintf("`company_id` = %u OR `foreign_id` IN %s", $X, $CAL));
		foreach (array('extras', 'features', 'discounts', 'vouchers') as $tb)
		{
			$this->del($t[$tb], sprintf("`company_id` = %u", $X));
		}
		$this->del($t['co_options'], sprintf("`company_id` = %u", $X));
		$this->del($t['multi_lang'], sprintf("(`model` = 'pjBaseField' AND `field` = '%s')", pjCompanyContext::labelField($X)));
		$this->del($t['multi_lang'], sprintf("(`model` = 'pjBaseOption' AND `foreign_id` = %u)", $fid));
		$this->del($t['multi_lang'], sprintf("(`model` = 'pjBaseCountry' AND `foreign_id` IN (SELECT `id` FROM `%s` WHERE `company_id` = %u))", $mods['countries']->getTable(), $X));
		$this->del($mods['co_countries']->getTable(), sprintf("`company_id` = %u", $X));
		$this->del($mods['countries']->getTable(), sprintf("`company_id` = %u", $X));
		$this->del($t['base_opt'], sprintf("`foreign_id` = %u", $fid));
		$this->del($t['calendars'], sprintf("`company_id` = %u", $X));
		$this->del($t['companies'], sprintf("`id` = %u", $X));

		// ---- 2. data ----
		$this->write("\n-- Data\n");
		$this->dump($mods['companies'], sprintf("`id` = %u", $X));
		$this->dump($mods['co_options'], sprintf("`company_id` = %u", $X));
		$this->dump($mods['countries'], sprintf("`company_id` = %u", $X));
		$this->dump($mods['co_countries'], sprintf("`company_id` = %u", $X));
		$this->dump($mods['multi_lang'], sprintf("(`model` = 'pjBaseField' AND `field` = '%s')", pjCompanyContext::labelField($X)));
		$this->dump($mods['multi_lang'], sprintf("(`model` = 'pjBaseOption' AND `foreign_id` = %u)", $fid));
		$this->dump($mods['multi_lang'], sprintf("(`model` = 'pjBaseCountry' AND `foreign_id` IN (SELECT `id` FROM `%s` WHERE `company_id` = %u))", $mods['countries']->getTable(), $X));
		$this->dump($mods['base_opt'], sprintf("`foreign_id` = %u", $fid));
		$this->dump($mods['calendars'], sprintf("`company_id` = %u", $X));
		$this->dump($mods['options'], sprintf("`foreign_id` IN %s", $CAL));
		$this->dump($mods['prices'], sprintf("`foreign_id` IN %s", $CAL));
		$this->dump($mods['period'], sprintf("`foreign_id` IN %s", $CAL));
		$this->dump($mods['period_price'], sprintf("`period_id` IN %s", $PER));
		foreach (array('limits', 'feeds', 'ical_urls', 'photos', 'password', 'cal_extras', 'cal_feat', 'cal_disc', 'cal_vouch') as $tb)
		{
			$this->dump($mods[$tb], sprintf("`calendar_id` IN %s", $CAL));
		}
		foreach ($pay_models as $pm)
		{
			$this->dump($pm, sprintf("`foreign_id` IN %s", $CAL));
		}
		$this->dump($mods['res'], sprintf("`calendar_id` IN %s", $CAL));
		$this->dump($mods['res_extras'], sprintf("`reservation_id` IN %s", $RES));
		foreach (array('extras', 'features', 'discounts', 'vouchers') as $tb)
		{
			$this->dump($mods[$tb], sprintf("`company_id` = %u", $X));
		}
		$this->dump($mods['notif'], sprintf("`company_id` = %u OR `foreign_id` IN %s", $X, $CAL));
		$this->dump($mods['multi_lang'], sprintf("(`model` IN ('pjCalendar','pjOption','pjPayment') AND `foreign_id` IN %s)", $CAL));
		foreach (array('pjExtra' => 'extras', 'pjFeature' => 'features', 'pjDiscount' => 'discounts', 'pjVoucher' => 'vouchers') as $ml => $tb)
		{
			$this->dump($mods['multi_lang'], sprintf("(`model` = '%s' AND `foreign_id` IN %s)", $ml, $own($tb)));
		}
		$this->dump($mods['multi_lang'], sprintf("(`model` = 'pjNotification' AND `foreign_id` IN %s)", $NOTIF));
		// The company's login account and the owners of its properties (never the super admin)
		$this->dump($mods['users'], sprintf("`id` <> 1 AND (`company_id` = %u OR `id` IN (SELECT `user_id` FROM `%s` WHERE `id` = %u) OR `id` IN (SELECT `user_id` FROM `%s` WHERE `company_id` = %u))", $X, $t['companies'], $X, $t['calendars'], $X));

		$this->write("\nCOMMIT;\nSET FOREIGN_KEY_CHECKS = 1;\n");
		fclose($this->handle);
		return true;
	}

	/* ------------------------------------------------------------------
	 * Files
	 * ------------------------------------------------------------------ */

	/** Uploaded files that belong to the company. */
	public function companyFiles()
	{
		$X = $this->company_id;
		$files = array();
		$cal_ids = pjCalendarModel::factory()->where('t1.company_id', $X)->findAll()->getDataPair(null, 'id');
		foreach ($cal_ids as $cid)
		{
			$cid = (int) $cid;
			foreach (array(PJ_UPLOAD_PATH . $cid . '_*', PJ_UPLOAD_PATH . 'calendar_photos/' . $cid . '_*', 'app/web/calendars/' . $cid . '_*') as $pattern)
			{
				foreach ((array) glob($pattern) as $f)
				{
					if ($f && is_file($f)) { $files[$f] = true; }
				}
			}
		}
		if (!empty($cal_ids))
		{
			foreach (pjCalendarPhotoModel::factory()->whereIn('t1.calendar_id', $cal_ids)->findAll()->getData() as $ph)
			{
				foreach (array('small_path', 'large_path') as $k)
				{
					if (!empty($ph[$k]) && is_file($ph[$k])) { $files[$ph[$k]] = true; }
				}
			}
			foreach (pjCalendarModel::factory()->select('t1.ical_url')->whereIn('t1.id', $cal_ids)->findAll()->getData() as $c)
			{
				if (!empty($c['ical_url']) && strpos($c['ical_url'], PJ_UPLOAD_PATH) === 0 && is_file($c['ical_url'])) { $files[$c['ical_url']] = true; }
			}
		}
		$company = pjCompanyModel::factory()->find($X)->getData();
		if (!empty($company['logo']) && is_file($company['logo']))
		{
			$files[$company['logo']] = true;
		}
		return array_keys($files);
	}

	public function exportFiles($zip_file)
	{
		$zip = new pjZipStream();
		$zip->setZipFile($zip_file);
		foreach ($this->companyFiles() as $f)
		{
			$data = @file_get_contents($f);
			if ($data === false)
			{
				return false;
			}
			$zip->addFile($data, $f);
		}
		$zip->finalize();
		return true;
	}
}
?>
