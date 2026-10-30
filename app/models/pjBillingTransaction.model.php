<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Billing history: payments of companies to the platform. Never holds card data.
 */
class pjBillingTransactionModel extends pjAppModel
{
	protected $primaryKey = 'id';

	protected $table = 'billing_transactions';

	protected $schema = array(
		array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'uuid', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'company_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'plan_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'kind', 'type' => 'enum', 'default' => 'new'),
		array('name' => 'amount', 'type' => 'decimal', 'default' => ':NULL'),
		array('name' => 'list_amount', 'type' => 'decimal', 'default' => ':NULL'),
		array('name' => 'discount_amount', 'type' => 'decimal', 'default' => ':NULL'),
		array('name' => 'promo_ids', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'coupon_code', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'currency', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'status', 'type' => 'enum', 'default' => 'pending'),
		array('name' => 'provider', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'provider_session', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'provider_ref', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'checkout_url', 'type' => 'text', 'default' => ':NULL'),
		array('name' => 'period_start', 'type' => 'datetime', 'default' => ':NULL'),
		array('name' => 'period_end', 'type' => 'datetime', 'default' => ':NULL'),
		array('name' => 'failure_reason', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'created_by', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'created', 'type' => 'datetime', 'default' => ':NOW()'),
		array('name' => 'paid_at', 'type' => 'datetime', 'default' => ':NULL'),
		array('name' => 'modified', 'type' => 'datetime', 'default' => ':NULL')
	);

	public static function factory($attr=array())
	{
		return new pjBillingTransactionModel($attr);
	}
}
?>
