<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Provider webhook events already received (each event is processed once).
 */
class pjBillingWebhookEventModel extends pjAppModel
{
	protected $primaryKey = 'id';

	protected $table = 'billing_webhook_events';

	protected $schema = array(
		array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'provider', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'event_id', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'event_type', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'result', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'created', 'type' => 'datetime', 'default' => ':NOW()')
	);

	public static function factory($attr=array())
	{
		return new pjBillingWebhookEventModel($attr);
	}
}
?>
