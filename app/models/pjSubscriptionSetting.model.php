<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Platform billing settings (key / value).
 */
class pjSubscriptionSettingModel extends pjAppModel
{
	protected $primaryKey = 'key';

	protected $table = 'subscription_settings';

	protected $schema = array(
		array('name' => 'key', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'value', 'type' => 'text', 'default' => ':NULL')
	);

	public static function factory($attr=array())
	{
		return new pjSubscriptionSettingModel($attr);
	}
}
?>
