<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjCron extends pjAppController
{
	public function __construct()
	{
		$this->setLayout('pjActionEmpty');
	}

	public function beforeFilter()
	{
		parent::beforeFilter();
		// These jobs are started by the cron runner (pjBaseCron). Opened directly as a
		// URL they run only for a logged-in administrator (the report lists properties).
		if ($this->_get->toString('controller') === 'pjCron' && !$this->isAdmin())
		{
			header('HTTP/1.1 403 Forbidden');
			exit;
		}
		return true;
	}

	/**
	 * Property filter for the company the cron job runs for: > 0 that company,
	 * -1 the system run (properties without a company or of an inactive company,
	 * which no company run covers), 0 every property.
	 */
	private function cronCalendarSql()
	{
		$cid = class_exists('pjCompanyContext') ? (int) pjCompanyContext::$cronCompanyId : 0;
		$cal = pjCalendarModel::factory()->getTable();
		if ($cid > 0)
		{
			return sprintf("t1.calendar_id IN (SELECT `id` FROM `%s` WHERE `company_id` = %u)", $cal, $cid);
		}
		if ($cid === -1)
		{
			return sprintf("t1.calendar_id IN (SELECT `id` FROM `%s` WHERE `company_id` IS NULL OR `company_id` NOT IN (SELECT `id` FROM `%s` WHERE `status` = 'T'))", $cal, pjCompanyModel::factory()->getTable());
		}
		return null;
	}

	public function pjActionSyncIcalUrls()
	{
		$pjIcalUrlsModel = pjIcalUrlsModel::factory()->reset();
		// Cron job running for one company: only that company's properties.
		if (($filter = $this->cronCalendarSql()) !== null)
		{
			$pjIcalUrlsModel->where($filter);
		}
		$rows = $pjIcalUrlsModel->findAll()->getData();

		$calendar_ids = array();
		foreach ($rows as $row)
		{
			if (!empty($row['ical_urls']) && (int) $row['calendar_id'] > 0)
			{
				$calendar_ids[(int) $row['calendar_id']] = true;
			}
		}
		// properties with feeds added in the "iCal feeds" tab
		$pjFeedModel = pjFeedModel::factory()->reset();
		if (($filter = $this->cronCalendarSql()) !== null)
		{
			$pjFeedModel->where($filter);
		}
		foreach ($pjFeedModel->findAll()->getData() as $row)
		{
			if (!empty($row['url']) && (int) $row['calendar_id'] > 0)
			{
				$calendar_ids[(int) $row['calendar_id']] = true;
			}
		}

		$report   = array();
		$report[] = 'iCal sync run at ' . date('Y-m-d H:i:s');
		$report[] = 'Feed URLs found: ' . count($rows) . ' | Calendars to sync: ' . count($calendar_ids);
		$report[] = str_repeat('-', 60);

		foreach (array_keys($calendar_ids) as $calendar_id)
		{
			try
			{
				$result = pjAppController::syncCalendarIcalUrls($calendar_id);
			}
			catch (Exception $e)
			{
				$result = array('status' => 'ERR', 'text' => $e->getMessage());
			}

			$line = 'Calendar ' . $calendar_id . ': ' . (isset($result['status']) ? $result['status'] : '?');
			if (isset($result['text']))
			{
				$line .= ' - ' . $result['text'];
			}
			if (isset($result['stats']))
			{
				$s = $result['stats'];
				$line .= sprintf(' | feeds:%d fetched:%d failed:%d parsed:%d inserted:%d updated:%d ignored:%d removed:%d',
					$s['feeds'], $s['fetched'], $s['failed'], $s['parsed'], $s['inserted'], $s['updated'], $s['ignored'], $s['removed']);
			}
			$report[] = $line;
		}

		// Print the report so it is visible when the cron URL is opened in a browser.
		if (!headers_sent())
		{
			header('Content-Type: text/plain; charset=utf-8');
			header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		}
		// The result shows which properties were synced (per company when run for one company).
		return "Property reservations have been synced successfully." . "\n" . implode("\n", array_filter(array_slice($report, 1), function ($l) { return strpos($l, '---') !== 0; }));
		exit;
	}
}
?>