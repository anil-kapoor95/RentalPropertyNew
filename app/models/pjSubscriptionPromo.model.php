<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * The administrator's discounts (kind = discount, automatic) and coupons (kind = coupon, with a code) on plan prices.
 */
class pjSubscriptionPromoModel extends pjAppModel
{
	protected $primaryKey = 'id';

	protected $table = 'subscription_promos';

	protected $schema = array(
		array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'kind', 'type' => 'enum', 'default' => 'discount'),
		array('name' => 'name', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'code', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'type', 'type' => 'enum', 'default' => 'percent'),
		array('name' => 'value', 'type' => 'decimal', 'default' => '0.00'),
		array('name' => 'currency', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'plan_ids', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'kinds', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'date_from', 'type' => 'date', 'default' => ':NULL'),
		array('name' => 'date_to', 'type' => 'date', 'default' => ':NULL'),
		array('name' => 'max_uses', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'max_per_company', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'status', 'type' => 'enum', 'default' => 'T'),
		array('name' => 'created', 'type' => 'datetime', 'default' => ':NULL'),
		array('name' => 'modified', 'type' => 'datetime', 'default' => ':NULL')
	);

	public static function factory($attr=array())
	{
		return new pjSubscriptionPromoModel($attr);
	}
}
?>
