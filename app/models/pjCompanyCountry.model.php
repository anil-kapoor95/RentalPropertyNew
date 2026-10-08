<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Active / inactive status of a country for one company (overrides the shared status).
 */
class pjCompanyCountryModel extends pjAppModel
{
	protected $primaryKey = null;

	protected $table = 'companies_countries';

	protected $schema = array(
		array('name' => 'company_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'country_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'status', 'type' => 'enum', 'default' => 'T')
	);

	public static function factory($attr=array())
	{
		return new pjCompanyCountryModel($attr);
	}
}
?>
