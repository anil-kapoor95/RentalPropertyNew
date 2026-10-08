<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjCalendarPhotoModel extends pjAppModel
{
	protected $primaryKey = 'id';

	protected $table = 'calendar_photos';

	protected $schema = array(
		array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'calendar_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'hash', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'file_name', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'mime_type', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'small_path', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'large_path', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'sort', 'type' => 'int', 'default' => '0'),
		array('name' => 'created', 'type' => 'datetime', 'default' => ':NOW()')
	);

	public static function factory($attr=array())
	{
		return new pjCalendarPhotoModel($attr);
	}

	/**
	 * All photos of a calendar, in display order.
	 *
	 * @param int $calendar_id
	 * @return array
	 */
	public function getByCalendar($calendar_id)
	{
		return $this->reset()
			->where('calendar_id', (int) $calendar_id)
			->orderBy('t1.sort ASC, t1.id ASC')
			->findAll()
			->getData();
	}

	/**
	 * Next sort value for a calendar, so new photos go to the end.
	 *
	 * @param int $calendar_id
	 * @return int
	 */
	public function getNextSort($calendar_id)
	{
		$arr = $this->reset()
			->where('calendar_id', (int) $calendar_id)
			->orderBy('t1.sort DESC')
			->limit(1)
			->findAll()
			->getData();

		return empty($arr) ? 1 : ((int) $arr[0]['sort'] + 1);
	}
}
?>
