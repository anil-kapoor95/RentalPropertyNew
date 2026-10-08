<?php
if (!defined("ROOT_PATH")) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

class pjIcalUrlsModel extends pjAppModel
{
    protected $primaryKey = 'id';

    protected $table = 'ical_urls';

    protected $schema = array(
        array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
        array('name' => 'calendar_id', 'type' => 'int', 'default' => ':NULL'),
        array('name' => 'provider_id', 'type' => 'int', 'default' => ':NULL'),
        array('name' => 'ical_urls', 'type' => 'text', 'default' => ':NULL')
    );

    public static function factory($attr = array())
    {
        return new self($attr);
    }
}
?>