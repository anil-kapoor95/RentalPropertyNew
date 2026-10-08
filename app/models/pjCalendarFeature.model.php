<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjCalendarFeatureModel extends pjAppModel
{
	protected $primaryKey = null;

	protected $table = 'calendars_features';

	protected $schema = array(
		array('name' => 'calendar_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'feature_id', 'type' => 'int', 'default' => ':NULL')
	);

	public static function factory($attr=array())
	{
		return new pjCalendarFeatureModel($attr);
	}
}
?>
