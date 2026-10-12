<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Languages of a company: which of the system languages (plugin_base_locale)
 * the company uses, in which order, and which one is its default.
 */
class pjCompanyLocaleModel extends pjAppModel
{
	protected $primaryKey = null;

	protected $table = 'companies_locales';

	protected $schema = array(
		array('name' => 'company_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'locale_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'sort', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'is_default', 'type' => 'tinyint', 'default' => '0')
	);

	public static function factory($attr=array())
	{
		return new pjCompanyLocaleModel($attr);
	}
}
?>
