<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjCompanyModel extends pjAppModel
{
	protected $primaryKey = 'id';

	protected $table = 'companies';

	protected $schema = array(
		array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'user_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'hash', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'name', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'legal_name', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'registration_no', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'tax_no', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'contact_person', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'email', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'phone', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'website', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'address_1', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'address_2', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'city', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'state', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'zip', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'country_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'logo', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'description', 'type' => 'text', 'default' => ':NULL'),
		array('name' => 'commission', 'type' => 'decimal', 'default' => '0.00'),
		array('name' => 'notes', 'type' => 'text', 'default' => ':NULL'),
		array('name' => 'status', 'type' => 'enum', 'default' => 'T'),
		array('name' => 'created', 'type' => 'datetime', 'default' => ':NOW()'),
		array('name' => 'modified', 'type' => 'datetime', 'default' => ':NULL')
	);

	/**
	 * Fields a Company user may change on their own profile.
	 * Everything else (status, login account, commission, notes) is admin-only.
	 */
	public static $profileFields = array(
		'name', 'legal_name', 'registration_no', 'tax_no', 'contact_person',
		'email', 'phone', 'website', 'address_1', 'address_2', 'city',
		'state', 'zip', 'country_id', 'description'
	);

	public static function factory($attr=array())
	{
		return new pjCompanyModel($attr);
	}
}
?>
