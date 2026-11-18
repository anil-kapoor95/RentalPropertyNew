<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Exchange rate snapshots (audit trail): every rate the administrator saves, with its source and time.
 * Payments keep the id of the snapshot they were valued with; snapshots are never changed or deleted.
 */
class pjExchangeRateSnapshotModel extends pjAppModel
{
	protected $primaryKey = 'id';

	protected $table = 'exchange_rate_snapshots';

	protected $schema = array(
		array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'base_currency', 'type' => 'varchar', 'default' => 'USD'),
		array('name' => 'quote_currency', 'type' => 'varchar', 'default' => 'INR'),
		array('name' => 'rate', 'type' => 'decimal', 'default' => ':NULL'),
		array('name' => 'source', 'type' => 'varchar', 'default' => 'manual'),
		array('name' => 'user_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'captured_at', 'type' => 'datetime', 'default' => ':NOW()')
	);

	public static function factory($attr=array())
	{
		return new pjExchangeRateSnapshotModel($attr);
	}
}
?>
