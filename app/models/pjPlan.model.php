<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Subscription plans (platform level, managed by the administrator).
 */
class pjPlanModel extends pjAppModel
{
	protected $primaryKey = 'id';

	protected $table = 'plans';

	protected $schema = array(
		array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'code', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'name', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'description', 'type' => 'text', 'default' => ':NULL'),
		array('name' => 'price', 'type' => 'decimal', 'default' => ':NULL'),
		array('name' => 'currency', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'billing_interval', 'type' => 'enum', 'default' => 'month'),
		array('name' => 'interval_count', 'type' => 'tinyint', 'default' => '1'),
		array('name' => 'trial_days', 'type' => 'smallint', 'default' => '0'),
		array('name' => 'max_properties', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'max_users', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'max_payment_gateways', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'ai_monthly_messages', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'features', 'type' => 'text', 'default' => ':NULL'),
		array('name' => 'is_public', 'type' => 'tinyint', 'default' => '1'),
		array('name' => 'status', 'type' => 'enum', 'default' => 'T'),
		array('name' => 'sort', 'type' => 'smallint', 'default' => '0'),
		array('name' => 'created', 'type' => 'datetime', 'default' => ':NOW()'),
		array('name' => 'modified', 'type' => 'datetime', 'default' => ':NULL')
	);

	public static function factory($attr=array())
	{
		return new pjPlanModel($attr);
	}
}
?>
