<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjFeatureModel extends pjAppModel
{
	protected $primaryKey = 'id';

	protected $table = 'features';

	protected $schema = array(
		array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'company_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'category_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'status', 'type' => 'enum', 'default' => 'T')
	);

	public $i18n = array('name');

	public static function factory($attr=array())
	{
		return new pjFeatureModel($attr);
	}
}
?>
