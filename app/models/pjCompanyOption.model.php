<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Per-company settings (theme colours, font, palette...).
 * Missing keys fall back to the global System Options.
 */
class pjCompanyOptionModel extends pjAppModel
{
	protected $primaryKey = null;

	protected $table = 'companies_options';

	protected $schema = array(
		array('name' => 'company_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'key', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'value', 'type' => 'text', 'default' => ':NULL')
	);

	public static function factory($attr=array())
	{
		return new pjCompanyOptionModel($attr);
	}

	/**
	 * key => value pairs for a company.
	 */
	public function getPairs($company_id)
	{
		if ((int) $company_id <= 0)
		{
			return array();
		}
		return $this->reset()->where('t1.company_id', (int) $company_id)->findAll()->getDataPair('key', 'value');
	}

	/**
	 * Insert or update a single key.
	 */
	public function saveValue($company_id, $key, $value)
	{
		$company_id = (int) $company_id;
		$exists = $this->reset()->where('company_id', $company_id)->where('`key`', $key)->findCount()->getData();
		if ((int) $exists > 0)
		{
			$this->reset()->where('company_id', $company_id)->where('`key`', $key)->limit(1)->modifyAll(array('value' => $value));
		} else {
			$this->reset()->setAttributes(array('company_id' => $company_id, 'key' => $key, 'value' => $value))->insert();
		}
		return $this;
	}
}
?>
