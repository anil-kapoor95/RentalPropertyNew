<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjAdmin extends pjAppController
{
	public $defaultUser = 'admin_user';
	
	public $requireLogin = true;
	
	public function __construct($requireLogin=null)
	{
		$this->setLayout('pjActionAdmin');
		
		if (!is_null($requireLogin) && is_bool($requireLogin))
		{
		    $this->requireLogin = $requireLogin;
		}
		
		if ($this->requireLogin)
		{
			$_get = pjRegistry::getInstance()->get('_get');
		    if (!$this->isLoged() && !in_array(@$_get->toString('action'), array('pjActionLogin', 'pjActionForgot', 'pjActionReset', 'pjActionValidate', 'pjActionExportFeed')))
		    {
		        if (!$this->isXHR())
		        {
		            pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjBase&action=pjActionLogin");
		        } else {
		            header('HTTP/1.1 401 Unauthorized');
		            exit;
		        }
		    }
		}
		
		// The page an Ajax call was made from (Referer). It is only trusted inside the
		// same controller: an action without a permission row of its own inherits the
		// permission of a page of the SAME section, never of an unrelated page
		// (a forged Referer to e.g. the dashboard must not unlock other sections).
		$ref_controller = $ref_action = null;
		if ($this->isXHR() && isset($_SERVER['HTTP_REFERER'])) {
		    $http_refer_arr = @parse_url($_SERVER['HTTP_REFERER']);
		    if (is_array($http_refer_arr) && isset($http_refer_arr['query'])) {
		        parse_str($http_refer_arr['query'], $arr);
		        if (isset($arr['controller'], $arr['action']) && is_string($arr['controller']) && is_string($arr['action'])) {
		            $ref_controller = $arr['controller'];
		            $ref_action = $arr['action'];
		        }
		    }
		}
		$ref_inherits_arr = array();
		// ($_GET values are already cleared by pjInput here, so read the query string)
		$query_string_arr = array();
		parse_str(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '', $query_string_arr);
		$req_controller = isset($query_string_arr['controller']) && is_string($query_string_arr['controller']) ? $query_string_arr['controller'] : '';
		$req_action = isset($query_string_arr['action']) && is_string($query_string_arr['action']) ? $query_string_arr['action'] : '';
		if ($ref_controller !== null && $req_controller !== '' && $req_action !== '' && $ref_controller === $req_controller) {
		    $cnt = pjAuthPermissionModel::factory()->where('`key`', $req_controller . '_' . $req_action)->findCount()->getData();
		    if ($cnt <= 0) {
		        $ref_inherits_arr[$req_controller . '::' . $req_action] = $ref_controller . '::' . $ref_action;
		    }
		}
		
		$inherits_arr = array(
		    'pjBasePermissions::pjActionResetPermission' => 'pjBasePermissions::pjActionUserPermission',
		    
		    'pjAdminOptions::pjActionNotificationsGetMetaData' => 'pjAdminOptions::pjActionNotifications',
		    'pjAdminOptions::pjActionNotificationsGetContent' => 'pjAdminOptions::pjActionNotifications',
		    'pjAdminOptions::pjActionNotificationsSetContent' => 'pjAdminOptions::pjActionNotifications',
		
			'pjAdminCalendars::pjActionCheckAssignCalendar' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionDeleteAllPrices' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionBeforeSavePrices' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionSavePrices' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionDeleteSeasonPrices' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionDeletePeriods' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionSavePeriods' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionCopy' => 'pjAdminCalendars::pjActionUpdate',		
			'pjAdminCalendars::pjActionGetFeed' => 'pjAdminCalendars::pjActionUpdate',
	        'pjAdminCalendars::pjActionViewFeed' => 'pjAdminCalendars::pjActionUpdate',
	        'pjAdminCalendars::pjActionRefreshFeed' => 'pjAdminCalendars::pjActionUpdate',
	        'pjAdminCalendars::pjActionUpdateFeed' => 'pjAdminCalendars::pjActionUpdate',
	        'pjAdminCalendars::pjActionDeleteFeed' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionExport' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionGetReservation' => 'pjAdminCalendars::pjActionUpdate',
		    
		    'pjAdminReservations::pjActionGetBooking' => 'pjAdminReservations::pjActionIndex',
		    'pjAdminReservations::pjActionGetReservation' => 'pjAdminReservations::pjActionIndex',
		    'pjAdminReservations::pjActionSaveReservation' => 'pjAdminReservations::pjActionUpdate',
			'pjAdminReservations::pjActionGetSchedule' => 'pjAdminReservations::pjActionSchedule',
			'pjAdminReservations::pjActionEmailConfirmation' => 'pjAdminReservations::pjActionUpdate',
			'pjAdminReservations::pjActionEmailCancellation' => 'pjAdminReservations::pjActionUpdate',		    
		    		    
		    'pjAdmin::pjActionRedirect' => 'pjAdminReservations::pjActionUpdate',
		    'pjAdmin::pjActionSetLocale' => 'pjAdmin::pjActionIndex',
		    'pjAdmin::pjActionVerifyAPIKey' => 'pjBaseOptions::pjActionApiKeys',

			'pjAdminCalendars::pjActionGetCalendar' => 'pjAdminCalendars::pjActionIndex',
			'pjAdminCalendars::pjActionGetCal' => 'pjAdminCalendars::pjActionIndex',
			'pjAdminCalendars::pjActionCheckRefId' => 'pjAdminCalendars::pjActionIndex',
			'pjAdminCalendars::pjActionSaveCalendar' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionSaveFeed' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionSaveIcalUrls' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionGetPassword' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionDeletePassword' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionDeletePasswordBulk' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionDeletePeriod' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionDeletePropertyImage' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionPaymentOptions' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionNotificationsGetMetaData' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionNotificationsGetContent' => 'pjAdminCalendars::pjActionUpdate',
			'pjAdminCalendars::pjActionNotificationsSetContent' => 'pjAdminCalendars::pjActionUpdate',

			'pjAdminFeatures::pjActionGetFeature' => 'pjAdminFeatures::pjActionIndex',
			'pjAdminFeatures::pjActionSaveFeature' => 'pjAdminFeatures::pjActionUpdate',
			'pjAdminOptions::pjActionSaveThemeColors' => 'pjAdminOptions::pjActionColors',
			'pjAdminVouchers::pjActionCheckCode' => 'pjAdminVouchers::pjActionIndex',
		
			'pjAdminExtras::pjActionGetExtra' => 'pjAdminExtras::pjActionIndex',
			'pjAdminExtras::pjActionSaveExtra' => 'pjAdminExtras::pjActionUpdate',
		
			'pjAdminDiscounts::pjActionGetDiscount' => 'pjAdminDiscounts::pjActionIndex',
			'pjAdminDiscounts::pjActionSaveDiscount' => 'pjAdminDiscounts::pjActionUpdate',
		
			'pjAdminCompanies::pjActionSwitch' => 'pjAdmin::pjActionIndex',
			'pjAdminCompanies::pjActionGetCompany' => 'pjAdminCompanies::pjActionIndex',
			'pjAdminCompanies::pjActionSaveCompany' => 'pjAdminCompanies::pjActionUpdate',

			'pjAdminVouchers::pjActionGetVoucher' => 'pjAdminVouchers::pjActionIndex',
			'pjAdminVouchers::pjActionSaveVoucher' => 'pjAdminVouchers::pjActionUpdate',
		);
		// Helpers of the add AND the edit form: the permission of the form they were called from.
		$form_helpers = array('pjActionCalcPrice', 'pjActionGetAdults', 'pjActionGetChildren', 'pjActionGetExtras', 'pjActionGetPMs', 'pjActionGetBookingFields', 'pjActionCheckUnique', 'pjActionCheckDates');
		foreach ($form_helpers as $helper) {
		    $form = $ref_controller === 'pjAdminReservations' && $ref_action === 'pjActionCreate' ? 'pjActionCreate' : 'pjActionUpdate';
		    $inherits_arr['pjAdminReservations::' . $helper] = 'pjAdminReservations::' . $form;
		}
		if (isset($_REQUEST['controller']) && $_REQUEST['controller'] == 'pjAdminOptions' && isset($_REQUEST['next_action'])
		    && is_string($_REQUEST['next_action']) && preg_match('/^pjAction[A-Za-z]+$/', $_REQUEST['next_action'])) {
		    $inherits_arr['pjAdminOptions::pjActionUpdate'] = 'pjAdminOptions::'.$_REQUEST['next_action'];
		}
		$inherits_arr = array_merge($ref_inherits_arr, $inherits_arr);
		pjRegistry::getInstance()->set('inherits', $inherits_arr);
	}
	
	public function beforeFilter()
	{
	    parent::beforeFilter();
	    
	    if (!pjAuth::factory()->hasAccess())
	    {
	        if (!$this->isXHR())
	        {
	            $this->sendForbidden();
	            return false;
	        } else {
	            self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.'));
	        }
	    }
	    
	    // First-run setup wizard: redirect to the welcome page until a demo choice is made.
	    if (class_exists('pjAdminSetup') && $this->_get->toString('controller') !== 'pjAdminSetup' && !$this->isXHR())
	    {
	        $wizardDone = pjOptionModel::factory()->reset()->where('`foreign_id`', 0)->where('`key`', 'o_setup_wizard_completed')->where('`value`', '1')->findCount()->getData();
	        if ((int) $wizardDone === 0)
	        {
	            pjUtil::redirect($_SERVER['PHP_SELF'] . '?controller=pjAdminSetup&action=pjActionWelcome');
	        }
	    }
	    
	    if (self::isPost())
	    {
	    	pjAppController::validateCsrfToken();
	    }

	    return true;
	}


	
	public function afterFilter()
	{
	    parent::afterFilter();
	    $this->appendJs('index.php?controller=pjBase&action=pjActionMessages', PJ_INSTALL_URL, true);
	}
	
	public function beforeRender()
	{
		
	}
	
	public function setLocalesData()
	{
	    $locale_arr = pjLocaleModel::factory()
	    ->select('t1.*, t2.file')
	    ->join('pjBaseLocaleLanguage', 't2.iso=t1.language_iso', 'left')
	    ->where('t2.file IS NOT NULL')
	    ->orderBy('t1.sort ASC')->findAll()->getData();
	    
	    $lp_arr = array();
	    foreach ($locale_arr as $item)
	    {
	        $lp_arr[$item['id']."_"] = $item['file'];
	    }
	    $this->set('lp_arr', $locale_arr);
	    $this->set('locale_str', pjAppController::jsonEncode($lp_arr));
	    $this->set('is_flag_ready', $this->requestAction(array('controller' => 'pjBaseLocale', 'action' => 'pjActionIsFlagReady'), array('return')));
	}
	
	public function pjActionVerifyAPIKey()
	{
	    $this->setAjax(true);
	    
	    if ($this->isXHR())
	    {
	        if (!self::isPost())
	        {
	            self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'HTTP method is not allowed.'));
	        }
	        
	        $option_key = $this->_post->toString('key');
	        if (!array_key_exists($option_key, $this->option_arr))
	        {
	            self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Option cannot be found.'));
	        }
	        
	        $option_value = $this->_post->toString('value');
	        if(empty($option_value))
	        {
	            self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'API key is empty.'));
	        }
	        
	        $html = '';
	        $isValid = false;
	        switch ($option_key)
	        {
	            case 'o_google_maps_api_key':
	                $address = preg_replace('/\s+/', '+', $this->option_arr['o_timezone']);
	                $api_key_str = $option_value;
	                $gfile = "https://maps.googleapis.com/maps/api/geocode/json?key=".$api_key_str."&address=".$address;
	                $Http = new pjHttp();
	                $response = $Http->request($gfile)->getResponse();
	                $geoObj = pjAppController::jsonDecode($response);
	                $geoArr = (array) $geoObj;
	                if ($geoArr['status'] == 'OK')
	                {
	                    $html = '<img src="' . $url . '" class="img-responsive" />';
	                    $isValid = true;
	                }
	                break;
	            default:
	                // API key for an unknown service. We can't verify it so we assume it's correct.
	                $isValid = true;
	        }
	        
	        if ($isValid)
	        {
	            self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Key is correct!', 'html' => $html));
	        }
	        else
	        {
	            self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Key is not correct!', 'html' => $html));
	        }
	    }
	    exit;
	}

	public function pjActionIndex()
	{
		$this->checkLogin();
	    if (!pjAuth::factory()->hasAccess())
	    {
	        $this->sendForbidden();
	        return;
	    }
	
		$pjCalendarModel = pjCalendarModel::factory();
		$pjReservationModel = pjReservationModel::factory();
		$pjAuthUserModel = pjAuthUserModel::factory();
		$pjCalendarModel = pjCalendarModel::factory();
		$OptionModel = pjOptionModel::factory();
		
		$calendar_option_arr = array();
		$pjCalendarModel->where('t1.company_id', $this->getCompanyId());
		if ($this->isRestrictedUser())
		{
			$pjCalendarModel->where('t1.user_id', $this->getUserId());
		}
		$calendar_arr = $pjCalendarModel->findAll()->getData();
		foreach ($calendar_arr as $val) {
			$calendar_option_arr[$val['id']] = $OptionModel->reset()->getPairs($val['id']);
		}
		
		$first_date_of_month = date('Y-m-01');
	    $last_date_of_month = date('Y-m-t');
	    $cnt_bookings_today = $total_amount_today = $cnt_bookings_this_month = $total_amount_this_month = 0;
	    
		$pjReservationModel
			->join('pjCalendar', 't2.id=t1.calendar_id', 'left')
			->where('DATE(t1.created) BETWEEN "'.$first_date_of_month.'" AND "'.$last_date_of_month.'"')
			->whereIn('t1.status', array('Confirmed','Pending'));
		$pjReservationModel->where('t2.company_id', $this->getCompanyId());
		if ($this->isRestrictedUser())
		{
			$pjReservationModel->where('t2.user_id', $this->getUserId());
		}
		$bookings_today = $pjReservationModel->orderBy('t1.created DESC')->findAll()->getData();	
		foreach ($bookings_today as $val) {
			$total = $val['amount'] + $val['extra_price'] - $val['discount_amount'] - $val['promo_amount'] + $val['tax'] + $val['tourist_tax'];
			if (date('Y-m-d', strtotime($val['created'])) == date('Y-m-d')) {
				$cnt_bookings_today += 1;
				$total_amount_today += $total;
			}
			$cnt_bookings_this_month += 1;
			$total_amount_this_month += $total;
		}	
		$this->set('cnt_bookings_today', $cnt_bookings_today)
			->set('total_amount_today', $total_amount_today)
			->set('cnt_bookings_this_month', $cnt_bookings_this_month)
			->set('total_amount_this_month', $total_amount_this_month)
			->set('cnt_users', $pjAuthUserModel->findCount()->getData())
			->set('cnt_calendars', count($calendar_arr))
			->set('calendar_option_arr', $calendar_option_arr);
			
		$pjReservationModel->reset()
			->select('t1.id, t1.uuid, t1.c_name, t1.created, t1.date_from, t1.date_to, t1.status, IF(t1.price_based_on="nights", DATEDIFF(t1.date_to, t1.date_from), DATEDIFF(t1.date_to, t1.date_from) + 1) AS nights, t3.content AS `calendar_name`')
			->join('pjCalendar', 't2.id=t1.calendar_id', 'left')
			->join('pjMultiLang', "t3.model='pjCalendar' AND t3.foreign_id=t1.calendar_id AND t3.field='name' AND t3.locale='".$this->getLocaleId()."'", 'left')
			->limit(5)
			->orderBy('t1.created DESC');
		$pjReservationModel->where('t2.company_id', $this->getCompanyId());
		if ($this->isRestrictedUser())
		{
			$pjReservationModel->where('t2.user_id', $this->getUserId());
		}
		$latest_reservation_arr = $pjReservationModel->findAll()->getData();		
		$this->set('latest_reservation_arr', $latest_reservation_arr);
		
		$pjReservationModel->reset()
			->select('t1.*, IF(t1.price_based_on="nights", DATEDIFF(t1.date_to, t1.date_from), DATEDIFF(t1.date_to, t1.date_from) + 1) AS nights, t3.content AS `calendar_name`')
			->join('pjCalendar', 't2.id=t1.calendar_id', 'left')
			->join('pjMultiLang', "t3.model='pjCalendar' AND t3.foreign_id=t1.calendar_id AND t3.field='name' AND t3.locale='".$this->getLocaleId()."'", 'left')
			->whereIn('t1.status', array('Confirmed','Pending'))
			->where('t1.date_from', date('Y-m-d'))
			->orderBy('t1.created DESC');
		$pjReservationModel->where('t2.company_id', $this->getCompanyId());
		if ($this->isRestrictedUser())
		{
			$pjReservationModel->where('t2.user_id', $this->getUserId());
		}
		$arriving_today_arr = $pjReservationModel->findAll()->getData();		
		$this->set('arriving_today_arr', $arriving_today_arr);
		
		$pjReservationModel->reset()
			->select('t1.*, IF(t1.price_based_on="nights", DATEDIFF(t1.date_to, t1.date_from), DATEDIFF(t1.date_to, t1.date_from) + 1) AS nights, t3.content AS `calendar_name`')
			->join('pjCalendar', 't2.id=t1.calendar_id', 'left')
			->join('pjMultiLang', "t3.model='pjCalendar' AND t3.foreign_id=t1.calendar_id AND t3.field='name' AND t3.locale='".$this->getLocaleId()."'", 'left')
			->whereIn('t1.status', array('Confirmed','Pending'))
			->where('t1.date_to', date('Y-m-d'))
			->orderBy('t1.created DESC');
		$pjReservationModel->where('t2.company_id', $this->getCompanyId());
		if ($this->isRestrictedUser())
		{
			$pjReservationModel->where('t2.user_id', $this->getUserId());
		}
		$leaving_today_arr = $pjReservationModel->findAll()->getData();		
		$this->set('leaving_today_arr', $leaving_today_arr);
					
		$this->appendJs('pjAdmin.js');
	}
	
	public function pjActionRedirect()
	{
		if ($this->_get->toInt('calendar_id') > 0)
		{
			if ($this->isCompanyCalendar($this->_get->toInt('calendar_id')))
			{
				$this->setCalendarId($this->_get->toInt('calendar_id'));
			}
		}
		
		$qs = NULL;
		if ($this->_get->check('nextParams'))
		{
			$nextParams = $this->_get->toString('nextParams');
			parse_str($nextParams, $params);
			if (!empty($params))
			{
				$qs = http_build_query($params);
				$qs = "&" . $qs;
			}
		}
		if ($this->_get->check('nextTab'))
		{
			$qs .= "&tab=" . $this->_get->toString('nextTab');
		}
		pjUtil::redirect(sprintf("%sindex.php?controller=%s&action=%s%s", PJ_INSTALL_URL, $this->_get->toString('nextController'), $this->_get->toString('nextAction'), $qs));
		exit;
	}
	
	public function pjActionSetLocale()
	{
		if ($this->_get->toInt('id') > 0)
		{
			$this->setLocaleId($this->_get->toInt('id'));
			$this->loadSetFields(true);
		}
		pjUtil::redirect(sprintf("%sindex.php?controller=pjAdmin&action=pjActionIndex", PJ_INSTALL_URL));
	}
}
?>