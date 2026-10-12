<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjBaseLocaleModel extends pjBaseAppModel
{
    protected $primaryKey = 'id';
    
    protected $table = 'plugin_base_locale';
    
    protected $schema = array(
        array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
        array('name' => 'language_iso', 'type' => 'varchar', 'default' => ':NULL'),
        array('name' => 'name', 'type' => 'varchar', 'default' => ':NULL'),
        array('name' => 'flag', 'type' => 'varchar', 'default' => ':NULL'),
        array('name' => 'dir', 'type' => 'enum', 'default' => 'ltr'),
        array('name' => 'sort', 'type' => 'int', 'default' => ':NULL'),
        array('name' => 'is_default', 'type' => 'tinyint', 'default' => ':NULL')
    );
    
    public static function factory($attr=array())
    {
        return new static($attr);
    }

    /* Languages per company: reads return only the languages of the current
       company, with that company's order and default (see pjCompanyContext).
       Writes (insert/modify/erase) always go to the system language table. */

    private $cUnscoped = false;

    private $cCount = null;

    /** Read the system language list, ignoring the company. */
    public function unscoped()
    {
        $this->cUnscoped = true;
        return $this;
    }

    private function cScopeId()
    {
        if ($this->cUnscoped || !class_exists('pjCompanyContext') || !class_exists('pjCompanyLocaleModel'))
        {
            return 0;
        }
        return (int) pjCompanyContext::localeScopeId();
    }

    public function beforeFind()
    {
        $cid = $this->cScopeId();
        if ($cid > 0)
        {
            $this->from(pjCompanyContext::localeFromSql($cid), false);
        }
        return parent::beforeFind();
    }

    public function findCount()
    {
        if ($this->cScopeId() > 0)
        {
            // The core count query cannot use a sub-select as table.
            $this->cCount = null;
            $n = count(parent::findAll()->getData());
            $this->cCount = $n;
            return $this;
        }
        return parent::findCount();
    }

    public function getData()
    {
        if ($this->cCount !== null)
        {
            $n = $this->cCount;
            $this->cCount = null;
            return $n;
        }
        return parent::getData();
    }
}
?>