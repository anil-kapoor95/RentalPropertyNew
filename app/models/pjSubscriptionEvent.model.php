<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Audit log of subscription changes.
 */
class pjSubscriptionEventModel extends pjAppModel
{
	protected $primaryKey = 'id';

	protected $table = 'subscription_events';

	protected $schema = array(
		array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'company_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'type', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'message', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'user_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'created', 'type' => 'datetime', 'default' => ':NOW()')
	);

	public static function factory($attr=array())
	{
		return new pjSubscriptionEventModel($attr);
	}
}
?>
