<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjRazorpayModel extends pjRazorpayAppModel
{
	protected $primaryKey = 'id';
	
	protected $table = 'plugin_razorpay';
	
	protected $schema = array(
		array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'foreign_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'razorpay_id', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'order_id', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'amount', 'type' => 'decimal', 'default' => ':NULL'),
		array('name' => 'currency', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'description', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'created', 'type' => 'datetime', 'default' => ':NULL'),
	);
	
	public static function factory($attr=array())
	{
		return new pjRazorpayModel($attr);
	}
}
?>
