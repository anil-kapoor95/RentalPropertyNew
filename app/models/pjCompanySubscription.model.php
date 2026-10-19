<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Current subscription of a company (one row per company).
 */
class pjCompanySubscriptionModel extends pjAppModel
{
	protected $primaryKey = 'id';

	protected $table = 'company_subscriptions';

	protected $schema = array(
		array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'company_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'plan_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'status', 'type' => 'enum', 'default' => 'trial'),
		array('name' => 'started_at', 'type' => 'datetime', 'default' => ':NULL'),
		array('name' => 'trial_ends_at', 'type' => 'datetime', 'default' => ':NULL'),
		array('name' => 'current_period_start', 'type' => 'datetime', 'default' => ':NULL'),
		array('name' => 'current_period_end', 'type' => 'datetime', 'default' => ':NULL'),
		array('name' => 'cancel_at_period_end', 'type' => 'tinyint', 'default' => '0'),
		array('name' => 'cancelled_at', 'type' => 'datetime', 'default' => ':NULL'),
		array('name' => 'pending_plan_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'used_trial', 'type' => 'tinyint', 'default' => '0'),
		array('name' => 'notes', 'type' => 'text', 'default' => ':NULL'),
		array('name' => 'created', 'type' => 'datetime', 'default' => ':NOW()'),
		array('name' => 'modified', 'type' => 'datetime', 'default' => ':NULL')
	);

	public static function factory($attr=array())
	{
		return new pjCompanySubscriptionModel($attr);
	}
}
?>
