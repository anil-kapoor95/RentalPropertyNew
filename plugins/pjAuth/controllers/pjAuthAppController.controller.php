<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjAuthAppController extends pjPlugin
{
	public function __construct()
	{
		$this->setLayout('pjActionEmpty');
	}
	
	/** foreign_id of the option texts (pjBaseOption i18n) in use. */
	protected $optionsFid = 1;

	/** Put a company's system options (login & protection, captcha, email...) on top of the shared ones. */
	protected function applyCompanyOptions($company_id)
	{
		if (class_exists('pjCompanyContext') && (int) $company_id > 0)
		{
			static $pairs = array();
			$this->optionsFid = pjCompanyContext::optionsForeignId($company_id);
			if (!isset($pairs[$this->optionsFid]))
			{
				// only the company's General options; login & protection, captcha and email are platform settings
				$pairs[$this->optionsFid] = pjCompanyContext::companyPairs($this->optionsFid);
			}
			$this->option_arr = array_merge((array) $this->option_arr, $pairs[$this->optionsFid]);
		}
		return $this;
	}

	/** Before login: use the company of the account with this e-mail. */
	protected function applyCompanyOptionsForEmail($email)
	{
		if (class_exists('pjCompanyContext') && !empty($email))
		{
			$this->applyCompanyOptions(pjCompanyContext::companyOfEmail($email));
		}
		return $this;
	}

	public function beforeFilter()
	{
	    $this->models['Option'] = pjBaseOptionModel::factory();
	    $this->option_arr = $this->models['Option']->getPairs($this->getForeignId());
	    // Logged in: the rules of the user's active company apply.
	    if (class_exists('pjCompanyContext') && $this->isLoged())
	    {
	        $this->applyCompanyOptions(pjCompanyContext::activeId($this->getRoleId(), $this->getUserId()));
	    }
	    $this->set('option_arr', $this->option_arr);
	    pjRegistry::getInstance()->set('options', $this->option_arr);
	    if (isset($this->option_arr['o_timezone']))
	    {
	        pjTimezone::factory()->setAllTimezones($this->option_arr['o_timezone']);
	    }
	    pjCurrency::factory()->setCurrencyData();
	    
	    if (!$this->session->has($this->defaultLocale))
	    {
	        $locale_arr = pjLocaleModel::factory()->where('is_default', 1)->limit(1)->findAll()->getData();
	        if (count($locale_arr) === 1)
	        {
	            $this->setLocaleId($locale_arr[0]['id']);
	        }
	    }
	    
	    return true;
	}
	
	public function afterFilter()
	{
		
	}
	
	public static function getConst($const)
	{
		$registry = pjRegistry::getInstance();
		$store = $registry->get('pjAuth');
		return isset($store[$const]) ? $store[$const] : NULL;
	}
	
	public function isAuthReady()
	{
		$reflector = new ReflectionClass('pjPlugin');
		try {
			//Try to find out 'isAuthReady' into parent class
			$ReflectionMethod = $reflector->getMethod('isAuthReady');
			return $ReflectionMethod->invoke(new pjPlugin(), 'isAuthReady');
		} catch (ReflectionException $e) {
			//echo $e->getMessage();
			//If failed to find it out, denied access, or not :)
			return false;
		}
	}
}
?>