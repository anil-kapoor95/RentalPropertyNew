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

	public function pjActionSyncIcalUrls()
	{
		$pjIcalUrlsModel = pjIcalUrlsModel::factory()->reset();
		// Cron job running for one company: only that company's properties.
		if (class_exists('pjCompanyContext') && (int) pjCompanyContext::$cronCompanyId > 0)
		{
			$pjIcalUrlsModel->where(sprintf("t1.calendar_id IN (SELECT `id` FROM `%s` WHERE `company_id` = %u)", pjCalendarModel::factory()->getTable(), (int) pjCompanyContext::$cronCompanyId));
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