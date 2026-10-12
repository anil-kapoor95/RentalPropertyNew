<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Cron jobs per company: the job list and the server cron are shared,
 * but every company has its own on/off switch, next run, last run and result.
 */
class pjCompanyCronModel extends pjAppModel
{
	protected $primaryKey = null;

	protected $table = 'companies_cron_jobs';

	protected $schema = array(
		array('name' => 'company_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'cron_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'is_active', 'type' => 'tinyint', 'default' => '1'),
		array('name' => 'next_run', 'type' => 'datetime', 'default' => ':NULL'),
		array('name' => 'last_run', 'type' => 'datetime', 'default' => ':NULL'),
		array('name' => 'status', 'type' => 'text', 'default' => ':NULL')
	);

	public static function factory($attr=array())
	{
		return new pjCompanyCronModel($attr);
	}
}
?>
