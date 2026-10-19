<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjAppController extends pjBaseAppController
{
	public $models = array();

	/**
	 * Generate (or return existing) CSRF token for the current session.
	 */
	public static function getCsrfToken()
	{
		if (empty($_SESSION['csrf_token']))
		{
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		return $_SESSION['csrf_token'];
	}

	/**
	 * Validate CSRF token. pjInput nulls raw $_POST/$_GET, so read request objects first.
	 */
	public static function validateCsrfToken()
	{
		$token = '';
		$reg = pjRegistry::getInstance();
		$post = $reg->get('_post');
		$get = $reg->get('_get');
		if (is_object($post) && $post->check('csrf_token')) {
			$token = (string) $post->toString('csrf_token');
		} elseif (is_object($get) && $get->check('csrf_token')) {
			$token = (string) $get->toString('csrf_token');
		} elseif (!empty($_POST['csrf_token'])) {
			$token = $_POST['csrf_token'];
		} elseif (!empty($_GET['csrf_token'])) {
			$token = $_GET['csrf_token'];
		} elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
			$token = $_SERVER['HTTP_X_CSRF_TOKEN'];
		}
		if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $token))
		{
			header('HTTP/1.1 403 Forbidden');
			exit('Invalid CSRF token.');
		}
	}

	public $defaultCalendarId = 'admin_calendar_id';
		
	public function pjActionCheckInstall()
	{
		$this->setLayout('pjActionEmpty');
		
		$result = array('status' => 'OK', 'code' => 200, 'text' => 'Operation succeeded', 'info' => array());
		$folders = array('app/web/upload');
		foreach ($folders as $dir)
		{
			if (!is_writable($dir))
			{
				$result['status'] = 'ERR';
				$result['code'] = 101;
				$result['text'] = 'Permission requirement';
				$result['info'][] = sprintf('Folder \'<span class="bold">%1$s</span>\' is not writable. You need to set write permissions (chmod 777) to directory located at \'<span class="bold">%1$s</span>\'', $dir);
			}
		}
		
		return $result;
	}
	
	public function beforeFilter()
    {
    	parent::beforeFilter();
    	if(!in_array($this->_get->toString('controller'), array('pjFront', 'pjInstaller', 'pjListings', 'pjCron', 'pjJabbApi')))
    	{
    		// Everything in the back-office works inside the active company.
    		$company_id = $this->getCompanyId();
    		$this->set('active_company', pjCompanyContext::activeCompany($this->getRoleId(), $this->getUserId()));

    		$pjCalendarModel = pjCalendarModel::factory()->where('t1.company_id', $company_id);
    		if ($this->isRestrictedUser())
    		{
    			$pjCalendarModel->where('t1.user_id', $this->getUserId());
    		}
	    	$calendars = $pjCalendarModel
				->select('t1.*, t2.content AS `name`')
				->join('pjMultiLang', "t2.model='pjCalendar' AND t2.foreign_id=t1.id AND t2.field='name' AND t2.locale='".$this->getLocaleId()."'", 'left')
				->orderBy('t1.id ASC')
				->findAll()->getDataPair('id');
			$this->set('calendars', $calendars);
			
			// The "current property" must always belong to the active company.
			$current = $this->getCalendarId();
			if ($current === false || !isset($calendars[$current]))
			{
				if (count($calendars) > 0)
				{
					$keys = array_keys($calendars);
					$this->setCalendarId($keys[0]);
				} else {
					unset($_SESSION[$this->defaultCalendarId]);
				}
			}
			
			$base_option_arr = pjCompanyContext::basePairs($company_id);
	        $script_option_arr = pjOptionModel::factory()->getPairs($this->getCalendarId());
	        $this->option_arr = array_merge($base_option_arr, $script_option_arr);
	        $this->set('option_arr', $this->option_arr);
	        
	        pjRegistry::getInstance()->set('options', $this->option_arr);
	        
	        $this->appendJs('pjAdminCore.js');
    	}
    	return true;
    }
	
	public function afterFilter()
	{
		parent::afterFilter();
	    if(!in_array($this->_get->toString('controller'), array('pjFront', 'pjInstaller')))
	    {
	        $this->appendCss('admin.css');
	    }
	}
	
	public function pjActionAfterInstall()
	{
		$this->setLayout('pjActionEmpty');
		
		$result = array('status' => 'OK', 'code' => 200, 'text' => 'Operation succeeded', 'info' => array());
	    $pjAuthRolePermissionModel = pjAuthRolePermissionModel::factory();
	    $pjAuthUserPermissionModel = pjAuthUserPermissionModel::factory();
	    
	    $permissions = pjAuthPermissionModel::factory()->findAll()->getDataPair('key', 'id');
	    
	    $roles = array(1 => 'admin', 2 => 'editor', 3 => 'owner', 4 => 'company');
	    foreach ($roles as $role_id => $role)
	    {
	        if (isset($GLOBALS['CONFIG'], $GLOBALS['CONFIG']["role_permissions_{$role}"])
	        && is_array($GLOBALS['CONFIG']["role_permissions_{$role}"])
	        && !empty($GLOBALS['CONFIG']["role_permissions_{$role}"]))
	        {
	            $pjAuthRolePermissionModel->reset()->where('role_id', $role_id)->eraseAll();
	            
	            foreach ($GLOBALS['CONFIG']["role_permissions_{$role}"] as $role_permission)
	            {
	                if($role_permission == '*')
	                {
	                    // Grant full permissions for the role
	                    foreach($permissions as $key => $permission_id)
	                    {
	                        $pjAuthRolePermissionModel->setAttributes(compact('role_id', 'permission_id'))->insert();
	                    }
	                    break;
	                }
	                else
	                {
	                    $hasAsterix = strpos($role_permission, '*') !== false;
	                    if($hasAsterix)
	                    {
	                        $role_permission = str_replace('*', '', $role_permission);
	                    }
	                    
	                    foreach($permissions as $key => $permission_id)
	                    {
	                        if($role_permission == $key || ($hasAsterix && strpos($key, $role_permission) !== false))
	                        {
	                            $pjAuthRolePermissionModel->setAttributes(compact('role_id', 'permission_id'))->insert();
	                        }
	                    }
	                }
	            }
	        }
	    }
	    pjAuthRoleModel::factory()->setAttributes(array('id' => 3, 'role' => 'Owner', 'is_backend' => 'T', 'is_admin' => 'T'))->insert();
	    if ((int) pjAuthRoleModel::factory()->where('t1.id', 4)->findCount()->getData() === 0)
	    {
	        pjAuthRoleModel::factory()->setAttributes(array('id' => 4, 'role' => 'Company', 'is_backend' => 'T', 'is_admin' => 'T'))->insert();
	    }
	    
	    // The Administrator role has every permission (pjAuth::hasAccess); user #1 gets no
	    // personal copy, so moving that user to another role really limits them.
	    $pjAuthUserPermissionModel->reset()->where('user_id', 1)->eraseAll();
	    
		$first_company = pjCompanyModel::factory()->orderBy('t1.id ASC')->limit(1)->findAll()->getDataIndex(0);
		$company_id = !empty($first_company) ? (int) $first_company['id'] : 0;
		$id = pjCalendarModel::factory()->setAttributes(array(
			'user_id'        => 1,
			'company_id'     => $company_id > 0 ? $company_id : ':NULL',
			'uuid'           => pjUtil::uuid(),
			'bedrooms'       => '1',
			'bathrooms'      => '1',
			'address_state'  => 'California',
			'address_zip'    => '94103',
			'lat'            => '37.7749',
			'lng'            => '-122.4194',
			'show_googlemap' => 'T',
		))->insert()->getInsertId();
		if ($id !== false && (int) $id > 0)
		{
			pjMultiLangModel::factory()->saveMultiLang(array(
				1 => array(
					'name'         => 'Calendar 1',
					'description'  => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
					'address_city' => 'San Francisco'
				)
			), $id, 'pjCalendar');

			$pjOptionModel = pjOptionModel::factory();
			$pjOptionModel->init($id);
			$pjOptionModel->initConfirmation($id, null);

			$data = $data = $pjOptionModel->reset()->getAllPairs($id);
			pjUtil::pjActionGenerateImages($id, $data);
		}
		
		return array('status' => 'OK', 'code' => 200, 'text' => 'Operation succeeded');
	}
    
	public function getCalendarId()
	{
	    if (isset($_SESSION[$this->defaultCalendarId]))
	    {
	        return $_SESSION[$this->defaultCalendarId];
	    }
	    return false;
	}
	
	public function setCalendarId($calendar_id)
	{
	    $_SESSION[$this->defaultCalendarId] = (int) $calendar_id;
	    return $this;
	}
	
    public static function getTokens($booking_arr, $option_arr, $locale_id)
    {
        $payment_methods = __('payment_methods', true);
    	$na = __('lblNA', true, false);
    	$c_name = !empty($booking_arr['c_name']) ? @$booking_arr['c_name'] : $na;
    	$c_email = !empty($booking_arr['c_email']) ? @$booking_arr['c_email'] : $na;
    	$c_phone = !empty($booking_arr['c_phone']) ? @$booking_arr['c_phone'] : $na;
    	$c_adults = $booking_arr['c_adults'] != '' ? @$booking_arr['c_adults'] : $na;
    	$c_children = $booking_arr['c_children'] != '' ? @$booking_arr['c_children'] : $na;
    	$c_notes = !empty($booking_arr['c_notes']) ? @$booking_arr['c_notes'] : $na;
    	$c_address = !empty($booking_arr['c_address']) ? @$booking_arr['c_address'] : $na;
    	$c_city = !empty($booking_arr['c_city']) ? @$booking_arr['c_city'] : $na;
    	$country = !empty($booking_arr['country']) ? @$booking_arr['country'] : $na;
    	$c_state = !empty($booking_arr['c_state']) ? @$booking_arr['c_state'] : $na;
    	$c_zip = !empty($booking_arr['c_zip']) ? @$booking_arr['c_zip'] : $na;
    	$cc_type = !empty($booking_arr['cc_type']) ? @$booking_arr['cc_type'] : $na;
    	$cc_num = !empty($booking_arr['cc_num']) ? @$booking_arr['cc_num'] : $na;
    	$cc_exp_month = @$booking_arr['payment_method'] == 'creditcard' ? (!empty($booking_arr['cc_exp_month']) ? @$booking_arr['cc_exp_month'] : $na) : $na;
    	$cc_exp_year = @$booking_arr['payment_method'] == 'creditcard' ? (!empty($booking_arr['cc_exp_year']) ? @$booking_arr['cc_exp_year'] : $na) : $na;
    	$cc_code = !empty($booking_arr['cc_code']) ? @$booking_arr['cc_code'] : $na;
    	$payment_method = !empty($booking_arr['payment_method']) ? $payment_methods[$booking_arr['payment_method']] : $na;
    	
    	$security = NULL;
    	$tax = pjCurrency::formatPrice($booking_arr['tax']);
    	$tourist_tax = pjCurrency::formatPrice($booking_arr['tourist_tax']);
    	$amount = pjCurrency::formatPrice($booking_arr['amount']);

		if($option_arr['o_to_be_paid'] == 'when_booking') {
			$security = pjCurrency::formatPrice($booking_arr['security']);
		}

		if (!isset($booking_arr['total'])) {
			$total =  $booking_arr['amount'] + $booking_arr['extra_price'] - $booking_arr['discount_amount'] - $booking_arr['promo_amount'] + $booking_arr['tax'] + $booking_arr['tourist_tax'];
		}
		else {
			$total = $booking_arr['total'];
		}
    	$total_price =  pjCurrency::formatPrice($total);
    	$extra_price = pjCurrency::formatPrice($booking_arr['extra_price']);
    	$discount_amount = pjCurrency::formatPrice($booking_arr['discount_amount']);
    	$promo_amount = pjCurrency::formatPrice($booking_arr['promo_amount']);
    	$deposit = pjCurrency::formatPrice($booking_arr['deposit']);
    	
    	$cancelURL = sprintf("%sindex.php?controller=pjFront&action=pjActionCancel&cid=%u&locale=%u&id=%u&hash=%s", PJ_INSTALL_URL, @$booking_arr['calendar_id'], $locale_id, @$booking_arr['id'], sha1(@$booking_arr['id'] . PJ_SALT));
    	$cancelURL = '<a href="'.$cancelURL.'">'.$cancelURL.'</a>';
    	
    	$calendar_name = '';
		if(isset($booking_arr['calendar_id']) && (int) $booking_arr['calendar_id'] > 0)
		{
			$calendar = pjCalendarModel::factory()
			->select("t1.*, t2.content as name")
			->join('pjMultiLang', "t2.foreign_id = t1.id AND t2.model = 'pjCalendar' AND t2.locale = '".$locale_id."' AND t2.field = 'name'", 'left')
			->find($booking_arr['calendar_id'])->getData();
			$calendar_name = $calendar['name'];
		}
		
		$extra_arr = pjExtraModel::factory()
			->select('t1.*, t2.content AS name, t3.qty')
			->join('pjMultiLang', "t2.model='pjExtra' AND t2.foreign_id=t1.id AND t2.field='name' AND t2.locale='".$locale_id."'", 'left outer')
			->join('pjReservationExtra', 't1.id=t3.extra_id', 'left outer')
			->where('t1.status', 'T')
			->where("(t1.id IN (SELECT TCE.extra_id FROM `".pjCalendarExtraModel::factory()->getTable()."` AS `TCE` WHERE `TCE`.calendar_id='".$booking_arr['calendar_id']."') )")
			->where("(t3.reservation_id='".$booking_arr['id']."')")
			->orderBy('`name` ASC')
			->findAll()->getData();
		$extras = '';
		$temp_extra = array();
		$price_types = $option_arr['o_price_based_on'] == 'days' ? __('day_price_types', true) : __('price_types', true);
		foreach($extra_arr as $extra)
		{
			$temp_extra[] = $extra['qty'] . ' x ' . pjSanitize::html($extra['name']) . ' (' . pjCurrency::formatPrice($extra['price']) . ' ' . $price_types[$extra['price_type']] . ')';
		}
		$extras = join("<br/>", $temp_extra);
		
    	$search = array(
    		'{Name}', '{Email}', '{Phone}', '{Adults}', '{Children}',
    		'{Notes}', '{Address}', '{City}', '{Country}', '{State}',
    		'{Zip}', '{CCType}', '{CCNum}', '{CCExpMonth}', '{CCExpYear}',
    		'{CCSec}', '{PaymentMethod}', '{StartDate}', '{EndDate}', '{Deposit}',
    		'{Security}', '{Tax}', '{TouristTax}', '{Price}', '{TotalPrice}', '{ExtraPrice}', '{Discount}', '{Promo}', '{PromoCode}','{CalendarID}', '{ReservationID}',
    		'{ReservationUUID}', '{CancelURL}', '{CalendarName}', '{Extras}');
		$replace = array(
			$c_name, $c_email, $c_phone, $c_adults, $c_children,
			$c_notes, $c_address, $c_city, $country, $c_state,
			$c_zip, $cc_type, $cc_num, $cc_exp_month, $cc_exp_year,
			$cc_code, $payment_method, date(@$option_arr['o_date_format'], strtotime(@$booking_arr['date_from'])), date(@$option_arr['o_date_format'], strtotime(@$booking_arr['date_to'])), @$deposit,
			$security, $tax, $tourist_tax, $amount, @$total_price, @$extra_price, @$discount_amount, @$promo_amount, @$booking_arr['promo_code'], @$booking_arr['calendar_id'], @$booking_arr['id'],
			@$booking_arr['uuid'], $cancelURL, $calendar_name, $extras
		);
		return compact('search', 'replace');
    }
    
	public static function getSubjectMessage($notification, $locale_id)
    {
    	$variant = $notification['variant'] == 'confirmation' ? 'confirm' : $notification['variant'];
        $field = $variant . '_tokens_' . $notification['recipient'];
        $pjMultiLangModel = pjMultiLangModel::factory();
        $lang_message = $pjMultiLangModel
        ->reset()
        ->select('t1.*')
        ->where('t1.foreign_id', $notification['id'])
        ->where('t1.model','pjNotification')
        ->where('t1.locale', $locale_id)
        ->where('t1.field', $field)
        ->limit(0, 1)
        ->findAll()
        ->getData();
        $field = $variant . '_subject_' . $notification['recipient'];
        $lang_subject = $pjMultiLangModel
        ->reset()
        ->select('t1.*')
        ->where('t1.foreign_id',  $notification['id'])
        ->where('t1.model','pjNotification')
        ->where('t1.locale', $locale_id)
        ->where('t1.field', $field)
        ->limit(0, 1)
        ->findAll()
        ->getData();
        return compact('lang_message', 'lang_subject');
    }
    
	public static function getSmsMessage($notification, $locale_id)
    {
    	$variant = $notification['variant'] == 'confirmation' ? 'confirm' : $notification['variant'];
        $field = $variant . '_sms_' . $notification['recipient'];
        $pjMultiLangModel = pjMultiLangModel::factory();
        $lang_message = $pjMultiLangModel
        ->reset()
        ->select('t1.*')
        ->where('t1.foreign_id', $notification['id'])
        ->where('t1.model','pjNotification')
        ->where('t1.locale', $locale_id)
        ->where('t1.field', $field)
        ->limit(0, 1)
        ->findAll()
        ->getData();
        return compact('lang_message');
    }

    public static function jsonDecode($str)
	{
		$Services_JSON = new pjServices_JSON();
		return $Services_JSON->decode($str);
	}
	
	public static function jsonEncode($arr)
	{
		$Services_JSON = new pjServices_JSON();
		return $Services_JSON->encode($arr);
	}
	
	public static function jsonResponse($arr)
	{
		header("Content-Type: application/json; charset=utf-8");
		echo pjAppController::jsonEncode($arr);
		exit;
	}

	public function isEditor()
	{
		return $this->getRoleId() == 2;
	}

	public function isOwner()
	{
		return $this->getRoleId() == 3;
	}

	public function isCompany()
	{
		return $this->getRoleId() == 4;
	}

	/**
	 * Users that may only see their own properties inside the active company
	 * (Owner role). Company users are limited by the company itself.
	 */
	public function isRestrictedUser()
	{
		return $this->isOwner();
	}

	/* ------------------------------------------------------------------
	 * Company (workspace) scoping
	 * ------------------------------------------------------------------ */

	/** Active company id (0 = none available). */
	/**
	 * Subscription plan check for the active company (server side, see pjEntitlement).
	 * Stops the request when the plan does not include $feature.
	 */
	public function requirePlanFeature($feature)
	{
		if (!class_exists('pjEntitlement') || pjEntitlement::allows($this->getCompanyId(), $feature))
		{
			return true;
		}
		$this->planDenied();
	}

	/** Answer for a request the plan does not allow (JSON for Ajax, a page otherwise). */
	public function planDenied()
	{
		if ($this->isXHR())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 402, 'text' => __('subLocked', true)));
		}
		if ((int) $this->getRoleId() === 4)
		{
			pjUtil::redirect($_SERVER['PHP_SELF'] . '?controller=pjAdminSubscriptions&action=pjActionMyPlan&msg=subLocked');
		}
		if ($this->isAdmin())
		{
			pjUtil::redirect($_SERVER['PHP_SELF'] . '?controller=pjAdminSubscriptions&action=pjActionManage&id=' . pjCompanyContext::hashOf($this->getCompanyId()) . '&msg=subLocked');
		}
		pjUtil::redirect($_SERVER['PHP_SELF'] . '?controller=pjAdmin&action=pjActionIndex');
		exit;
	}

	public function getCompanyId()
	{
		return (int) pjCompanyContext::activeId($this->getRoleId(), $this->getUserId());
	}

	/** Active company record or false. */
	public function getCurrentCompany()
	{
		return pjCompanyContext::activeCompany($this->getRoleId(), $this->getUserId());
	}

	/**
	 * SQL condition "<column> belongs to a property of the active company".
	 * Use with any calendar_id / foreign_id column.
	 */
	public function companyCalendarSql($column)
	{
		$sql = sprintf("`company_id` = %u", $this->getCompanyId());
		if ($this->isRestrictedUser())
		{
			$sql .= sprintf(" AND `user_id` = %u", (int) $this->getUserId());
		}
		return sprintf("(%s IN (SELECT `id` FROM `%s` WHERE %s))", $column, pjCalendarModel::factory()->getTable(), $sql);
	}

	/** Property ids of the active company (respecting Owner restriction). */
	public function getCompanyCalendarIds()
	{
		$m = pjCalendarModel::factory()->where('t1.company_id', $this->getCompanyId());
		if ($this->isRestrictedUser())
		{
			$m->where('t1.user_id', $this->getUserId());
		}
		return array_map('intval', $m->findAll()->getDataPair(null, 'id'));
	}

	public function isCompanyCalendar($calendar_id)
	{
		return (int) $calendar_id > 0 && in_array((int) $calendar_id, $this->getCompanyCalendarIds(), true);
	}

	/** Keep only the property ids that belong to the active company. */
	public function filterCompanyCalendarIds($ids)
	{
		$allowed = $this->getCompanyCalendarIds();
		$out = array();
		foreach ((array) $ids as $id)
		{
			if (in_array((int) $id, $allowed, true))
			{
				$out[] = (int) $id;
			}
		}
		return array_values(array_unique($out));
	}

	public function isCompanyReservation($reservation_id)
	{
		$arr = pjReservationModel::factory()->select('t1.calendar_id')->find((int) $reservation_id)->getData();
		return !empty($arr) && $this->isCompanyCalendar($arr['calendar_id']);
	}

	/** Keep only reservation ids whose property belongs to the active company. */
	public function filterCompanyReservationIds($ids)
	{
		$ids = array_values(array_filter(array_map('intval', (array) $ids)));
		if (empty($ids))
		{
			return array();
		}
		return array_map('intval', pjReservationModel::factory()
			->whereIn('t1.id', $ids)
			->where($this->companyCalendarSql('t1.calendar_id'))
			->findAll()->getDataPair(null, 'id'));
	}

	/**
	 * For models that carry their own company_id column (extras, features,
	 * discounts, vouchers): does record $id belong to the active company?
	 */
	public function isCompanyRecord($model, $id)
	{
		return (int) $id > 0 && (int) $model->reset()->where('t1.id', (int) $id)->where('t1.company_id', $this->getCompanyId())->findCount()->getData() === 1;
	}

	public function filterCompanyRecordIds($model, $ids)
	{
		$ids = array_values(array_filter(array_map('intval', (array) $ids)));
		if (empty($ids))
		{
			return array();
		}
		return array_map('intval', $model->reset()->whereIn('t1.id', $ids)->where('t1.company_id', $this->getCompanyId())->findAll()->getDataPair(null, 'id'));
	}

	/** Stop the request: the record belongs to another company. */
	public function denyCompanyAccess($redirect = null)
	{
		if ($this->isXHR() || $redirect === null)
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 403, 'text' => 'This record belongs to another company.'));
		}
		pjUtil::redirect($redirect);
		exit;
	}
	
	public function isPriceReady()
	{
		return $this->isAdmin() || $this->isEditor() || $this->isOwner();
	}
	
	public function isPeriodReady()
	{
		return $this->isAdmin() || $this->isEditor() || $this->isOwner();
	}
	
	public function isInvoiceReady()
	{
		return $this->isAdmin() || $this->isEditor() || $this->isOwner();
	}
	
	public function isCountryReady()
	{
		return $this->isAdmin();
	}
	
	public function isOneAdminReady()
	{
		return $this->isAdmin();
	}

	public function getLocaleId()
	{
		return isset($_SESSION[$this->defaultLocale]) && (int) $_SESSION[$this->defaultLocale] > 0 ? (int) $_SESSION[$this->defaultLocale] : false;
	}
	public function setLocaleId($locale_id)
	{
		$_SESSION[$this->defaultLocale] = (int) $locale_id;
	}
	
	protected function pjActionCheckDt($date_from, $date_to, $calendar_id=NULL, $id=NULL, $backend=false)
	{
		$calendar_id = !empty($calendar_id) ? (int) $calendar_id : $this->getCalendarId();
		
		if ($backend && $calendar_id != $this->getCalendarId())
		{
			$option_arr = pjOptionModel::factory()->getPairs($calendar_id);
		} else {
			$option_arr = $this->option_arr;
		}
		$validate_booking_msg = __('validate_booking_msg', true);
		if ($option_arr['o_price_based_on'] == 'nights' && $date_from == $date_to && $option_arr['o_booking_behavior'] == 1)
		{
			return array('status' => 'ERR', 'code' => 100, 'text' => $validate_booking_msg[1]);
		}
		
		$pjReservationModel = pjReservationModel::factory();
		$pjLimitModel = pjLimitModel::factory();
		
		$info = $pjReservationModel
			->prepare(sprintf("SELECT `date_from`, `date_to` 
				FROM `%1\$s`
				WHERE `calendar_id` = :calendar_id
				%2\$s
				AND `status` != :status
				AND ((`date_from` BETWEEN :date_from AND :date_to)
				OR ( `date_to` BETWEEN :date_from AND :date_to)
				OR ( `date_from` <= :date_from AND `date_to` >= :date_to))",
				$pjReservationModel->getTable(), (!empty($id) ? " AND `id` != :id" : NULL),
				($option_arr['o_price_based_on'] == 'nights' ? '<' : '<='),
				($option_arr['o_price_based_on'] == 'nights' ? '>' : '>=')
			))
			->exec(array(
				'calendar_id' => $calendar_id,
				'status' => 'Cancelled',
				'date_from' => $date_from,
				'date_to' => $date_to,
				'id' => $id
			))
			->getData();

		$limit_info = $pjLimitModel
			->prepare(sprintf("SELECT `date_from`, `date_to`,`blocked_days`
				FROM `%1\$s`
				WHERE `calendar_id` = :calendar_id
				%2\$s
				AND `blocked` = :blocked
				AND ((`date_from` BETWEEN :date_from AND :date_to)
				OR ( `date_to` BETWEEN :date_from AND :date_to)
				OR ( `date_from` <= :date_from AND `date_to` >= :date_to))",
					$pjLimitModel->getTable(), NULL,
					($option_arr['o_price_based_on'] == 'nights' ? '<' : '<='),
					($option_arr['o_price_based_on'] == 'nights' ? '>' : '>=')
			))
			->exec(array(
					'calendar_id' => $calendar_id,
					'blocked' => 'T',
					'date_from' => $date_from,
					'date_to' => $date_to,
			))
			->getData();
		
		foreach ($limit_info as $limit)
		{
			$limit_date_from = $limit['date_from'];
			$limit_date_to = ($option_arr['o_price_based_on'] == 'nights' && $limit['date_from'] == $limit['date_to']) ? date('Y-m-d', strtotime('+1 day',  strtotime($limit['date_to']))) : $limit['date_to'];
			
			if($limit['blocked_days'] != "" && $limit['blocked_days'] != "1|2|3|4|5|6|0")
			{
				$blocked_days = explode("|", $limit['blocked_days']);
			
				$dt_from = strtotime($date_from);
				$dt_to = strtotime($date_to);
				for($i = $dt_from; $i <= $dt_to; $i = strtotime('+1 day', $i))
				{
					$week_day = date('w', $i);
						
					if(in_array($week_day, $blocked_days) && ($i >= strtotime($limit['date_from']) && $i <= strtotime($limit['date_to'])) )
					{
						$limit_date_from = date('Y-m-d', $i);
						$limit_date_to = date('Y-m-d', $i);
						if ($option_arr['o_price_based_on'] == 'nights')
						{
						    $temp_ts =  strtotime('+1 day', $i);
							$week_day_to = date('w', $temp_ts);
							while(in_array($week_day_to, $blocked_days))
							{
							    $temp_ts =  strtotime('+1 day', $temp_ts);
								$week_day_to = date('w', $temp_ts);
							}
							$limit_date_to = date('Y-m-d', $temp_ts);
							$i = $temp_ts;
						}
						$temp = array();
						$temp['price_based_on'] = $option_arr['o_price_based_on'];
						$temp['date_from'] = $limit_date_from;
						$temp['date_to'] = $limit_date_to;
						$temp['status'] = 'Confirmed';
						$temp['from_limit'] = 1;
						$info[] = $temp;
					}
				}
			}else{
				$temp = array();
				$temp['price_based_on'] = $option_arr['o_price_based_on'];
				$temp['date_from'] = $limit_date_from;
				$temp['date_to'] = $limit_date_to;
				$temp['status'] = 'Confirmed';
				$temp['from_limit'] = 1;
				$info[] = $temp;
			}
		}
			
		$morning = array();
		$afternoon = array();
		$av_arr = array();
		$booked_arr = array();
		$nights_mode = false;
		if ($option_arr['o_price_based_on'] == 'nights')
		{ 
			$nights_mode = true;
		}
		if(isset($info) && count($info)  >0)
		{
			foreach ($info as $res)
			{
				$dt_from = strtotime($res['date_from']);
				$dt_to = strtotime($res['date_to']);
				for($i = $dt_from; $i <= $dt_to; $i = strtotime('+1 day', $i))
				{
					if(!empty($res['price_based_on']) && in_array($res['price_based_on'], array('nights', 'days')))
					{
						if($res['price_based_on'] == 'nights')
						{
							$nights_mode = true;
						}
					}
					if (!isset($afternoon[$i])) {
						$afternoon[$i] = 0;
					}
					if (!isset($morning[$i])) {
						$morning[$i] = 0;
					}
					if (!isset($booked_arr[$i])) {
						$booked_arr[$i] = 0;
					}
					if ($i == $dt_from && $nights_mode){
						$afternoon[$i] += 1;
					}elseif ($i == $dt_to && $nights_mode) {
						$morning[$i] += 1;
					}else {
						$booked_arr[$i] += 1;
					}
				}
			}
		}
		$s_from = strtotime($date_from);
		$s_to = strtotime($date_to);	
  		for($z = $s_from; $z <= $s_to; $z = strtotime('+1 day', $z))
		{
			if(isset($booked_arr[$z]) || isset($morning[$z]) || isset($afternoon[$z])) 
			{
				$booked_value = isset($booked_arr[$z]) ? $booked_arr[$z] : 0;
				$monring_value = isset($morning[$z]) ? $morning[$z] : 0;
				$afternoon_value = isset($afternoon[$z]) ? $afternoon[$z] : 0;
				
				$booked_value += min($monring_value,$afternoon_value);
				$morning[$z] -= min($monring_value, $afternoon_value);
				$afternoon[$z] -= min($monring_value, $afternoon_value);
				
				$av_arr[$z] = $booked_value;
				if($morning[$z] >= $afternoon[$z])
				{
					if($z > $s_from && $z <= $s_to)
					{
						$av_arr[$z] = $booked_value + ($morning[$z] );
					}
				}else{
					if($z >= $s_from && $z < $s_to)
					{
						$av_arr[$z] = $booked_value + ($afternoon[$z]);
					}
				}
			}else{
				$av_arr[$z] = 0;
			}
		}
		$cnt = max($av_arr);
		
		if(empty($id))
		{
			if ($cnt < (int) $option_arr['o_bookings_per_day'])
			{
				$result = array('status' => 'OK', 'code' => 200, 'text' => '');
			} else {
				$result = array('status' => 'ERR', 'code' => 100, 'text' => $validate_booking_msg[2]);
			}
		}else{
			if ($cnt < (int) $option_arr['o_bookings_per_day'])
			{
				$result = array('status' => 'OK', 'code' => 200, 'text' => '');
			} else {
				$result = array('status' => 'ERR', 'code' => 100, 'text' => $validate_booking_msg[2]);
			}
		}
		return $result;
	}
	
	/**
	 * Keep only the extras of the property (active ones) and clamp each quantity to the
	 * range the booking forms offer: 1..max_count for multi / count extras, otherwise 1.
	 * Returns array($extras, $qty) in the form the forms post (extra[id]=on, qty[id]=n).
	 */
	public static function sanitizeExtras($calendar_id, $extras, $qty)
	{
		$extras = is_array($extras) ? $extras : array();
		$qty = is_array($qty) ? $qty : array();
		$ids = array_values(array_filter(array_map('intval', array_keys($extras))));
		if (empty($ids) || (int) $calendar_id <= 0)
		{
			return array(array(), array());
		}
		$rows = pjExtraModel::factory()
			->whereIn('t1.id', $ids)
			->where('t1.status', 'T')
			->where(sprintf("t1.id IN (SELECT `extra_id` FROM `%s` WHERE `calendar_id` = %u)", pjCalendarExtraModel::factory()->getTable(), (int) $calendar_id))
			->findAll()->getDataPair('id');
		$out_extras = array();
		$out_qty = array();
		foreach ($rows as $id => $row)
		{
			$max = 1;
			if ($row['multi'] == 'T' || in_array($row['price_type'], array('count', 'count_night'), true))
			{
				$max = max(1, (int) $row['max_count']);
			}
			$q = isset($qty[$id]) ? (int) $qty[$id] : 1;
			$out_extras[(int) $id] = 'on';
			$out_qty[(int) $id] = min($max, max(1, $q));
		}
		return array($out_extras, $out_qty);
	}

	protected function pjActionCalcPrices($calendar_id, $start_dt, $end_dt, $adults, $children, $extras, $extras_qty, $promo_code, $option_arr, $locale_id)
	{
		// never trust posted extras: only the property's own, with a valid quantity
		list($extras, $extras_qty) = self::sanitizeExtras($calendar_id, $extras, $extras_qty);
		$adults = max(0, (int) $adults);
		$children = max(0, (int) $children);
		$tourist_tax = 0;
		$amount = 0;
		$extra_price = 0;
		$sub_total = 0;
		$tax = 0;
		$total = 0;
		$deposit = 0;
		$net = 0;
		$security = 0;
		$promo_valid = 0;
		$promo_amount = 0;
		$promo_type = '';
		$promo_percentage = 0;
		$discount_valid = 0;
		$discount_check = 0;
		$discount_amount = 0;
		$discount_type = '';
		$discount_percentage = 0;
		$discount_name = '';
		$temp_discount_amount;
		
		$nights = 0;
		$early_nights = 0;
		if ($end_dt > $start_dt) {
			$new_start_date = new DateTime(date('Y-m-d', $start_dt));
			$new_end_date = new DateTime(date('Y-m-d', $end_dt));
			$nights = $new_end_date->diff($new_start_date)->format("%a");
		}
		
		$new_start_early_nights = new DateTime(date('Y-m-d', $start_dt));
		$new_end_early_nights = new DateTime(date('Y-m-d', time()));
		$early_nights = $new_end_early_nights->diff($new_start_early_nights)->format("%a");
		
		if ($option_arr['o_price_based_on'] == 'days')
		{
			$nights += 1;
			$early_nights += 1;
		}
		
		$price_arr = array();
		if ($option_arr['o_price_plugin'] == 'price')
		{
			$price_arr = pjPriceModel::factory()->getPrice(
					$calendar_id,
					date("Y-m-d", $start_dt),
					date("Y-m-d", $end_dt),
					$this->option_arr,
					@$adults,
					(int) $option_arr['o_bf_children'] !== 1 ? @$children : 0
			);
		
		} elseif ($option_arr['o_price_plugin'] == 'period') {
			$price_arr = pjPeriodModel::factory()->getPrice(
					$calendar_id,
					date("Y-m-d", $start_dt),
					date("Y-m-d", $end_dt),
					$this->option_arr,
					@$adults,
					(int) $option_arr['o_bf_children'] !== 1 ? @$children : 0
			);
		}
		$people = @$adults + ((int) $option_arr['o_bf_children'] !== 1 ? @$children : 0);
		$tourist_taxt_per_nights = 0;
		if((float)$option_arr['o_tourist_tax'] > 0)
		{
			$tourist_taxt_per_nights = (float)$option_arr['o_tourist_tax'] * $nights;
		}
		if(isset($option_arr['o_max_tax']) && (float)$option_arr['o_max_tax'] > 0 && $tourist_taxt_per_nights > (float)$option_arr['o_max_tax'])
		{
			$tourist_taxt_per_nights = (float)$option_arr['o_max_tax'];
		}
		$tourist_tax = $people * $tourist_taxt_per_nights;
		
		$net = $price_arr['net'];
		$amount = $price_arr['amount'];
		//$tax = $price_arr['tax'];
		
		if(isset($extras) && !empty($extras))
		{
			$extra_arr = pjExtraModel::factory()->whereIn('t1.id', array_keys($extras))->findAll()->getData();
			foreach($extra_arr as $k => $v)
			{
				$qty = $extras_qty[$v['id']];
				switch ($v['price_type']) {
					case 'person':
				 		$extra_price += $v['price'] * $qty * $people;
					break;
					
					case 'night':
						$extra_price += $v['price'] * $qty * $nights;
					break;
					
					case 'person_night':
						$extra_price += $v['price'] * $qty * $people * $nights;
					break;
					case 'count':
						$extra_price += $v['price'] * $qty;
					break;
					case 'count_night':
						$extra_price += $v['price'] * $qty * $nights;
					break;
					case 'one_time':
						$extra_price += $v['price'] * $qty;
					break;
				}
			}
		}
		
		$sub_total = $amount + $extra_price;
		
		// Coupons and discounts are a plan feature of the property's company.
		$coupons_allowed = !class_exists('pjEntitlement') || pjEntitlement::allows(pjCompanyContext::companyOfCalendar($calendar_id), 'coupons');
		if($promo_code != '' && $coupons_allowed)
		{
			// Coupon of the property's company only (codes are unique per company).
			$voucher_company_id = pjCompanyContext::companyOfCalendar($calendar_id);
			$voucher_arr = pjVoucherModel::factory()
				->where('t1.code', $promo_code)
				->where('t1.company_id', $voucher_company_id)
				->where("t1.id IN (SELECT TCV.voucher_id FROM `".pjCalendarVoucherModel::factory()->getTable()."` AS `TCV` WHERE `TCV`.calendar_id='".(int) $calendar_id."') ")
				->findAll()
				->getData();
			if(!empty($voucher_arr))
			{
				$voucher_arr = $voucher_arr[0];
				switch ($voucher_arr['condition']) {
					case 'period':
						$date_from_ts = strtotime($voucher_arr['date_from']);
						$date_to_ts = strtotime($voucher_arr['date_to']);
						//if( ($start_dt >= $date_from_ts && $start_dt <= $date_to_ts) || ($end_dt >= $date_from_ts && $end_dt <= $date_to_ts) || ($date_from_ts >= $start_dt && $date_from_ts <= $end_dt ) || ($date_to_ts >= $start_dt && $date_to_ts <= $end_dt ))
						if ($start_dt >= $date_from_ts && $end_dt <= $date_to_ts)
						{
							$promo_valid = 1;
						}
						break;
		
					case 'made':
						$date_from_ts = strtotime($voucher_arr['date_from']);
						$date_to_ts = strtotime($voucher_arr['date_to']);
						if (time() >= $date_from_ts && time() <= $date_to_ts)
						{
							$promo_valid = 1;
						}
						break;
							
					case 'both':
						$date_from_ts = strtotime($voucher_arr['date_from']);
						$date_to_ts = strtotime($voucher_arr['date_to']);
						//if( (($start_dt >= $date_from_ts && $start_dt <= $date_to_ts) || ($end_dt >= $date_from_ts && $end_dt <= $date_to_ts) || ($date_from_ts >= $start_dt && $date_from_ts <= $end_dt ) || ($date_to_ts >= $start_dt && $date_to_ts <= $end_dt )) && (time() >= $date_from_ts && time() <= $date_to_ts))
						if (($start_dt >= $date_from_ts && $end_dt <= $date_to_ts) && (time() >= $date_from_ts && time() <= $date_to_ts))
						{
							$promo_valid = 1;
						}
						break;
				}
				if($promo_valid == 1)
				{
					// Usage limit counts only this company's bookings: another company may use the same code.
					$cnt_reservations = pjReservationModel::factory()->where('t1.status', 'confirmed')->where('t1.promo_code', $promo_code)
						->where(sprintf("t1.calendar_id IN (SELECT `id` FROM `%s` WHERE `company_id` = %u)", pjCalendarModel::factory()->getTable(), $voucher_company_id))
						->findCount()->getData();
					if($cnt_reservations >= (int) $voucher_arr['used_count'])
					{
						$promo_valid = 0;
					}else{
						$promo_amount = (float) $voucher_arr['discount'];
						$promo_type = $voucher_arr['type'];
						if($voucher_arr['type'] == 'percent')
						{
							$promo_percentage = (float) $voucher_arr['discount'];
							if($voucher_arr['apply_on'] == 'both')
							{
								$promo_amount = ($amount + $extra_price) * (float) $voucher_arr['discount'] / 100;
							}else if($voucher_arr['apply_on'] == 'room'){
								$promo_amount = ($amount) * (float) $voucher_arr['discount'] / 100;
							}else if($voucher_arr['apply_on'] == 'extra'){
								$promo_amount = ($extra_price) * (float) $voucher_arr['discount'] / 100;
							}
						}
					}
				}
			}
		}
		
		
		$discount_arr = pjDiscountModel::factory()
			->select('t1.*, t2.content as name')
			->join('pjMultiLang', "t2.foreign_id = t1.id AND t2.model = 'pjDiscount' AND t2.locale = '".$locale_id."' AND t2.field = 'name'", 'left')
			->where("(t1.id IN (SELECT TCV.discount_id FROM `".pjCalendarDiscountModel::factory()->getTable()."` AS `TCV` WHERE `TCV`.calendar_id='".(int) $calendar_id."'))")
			->where('t1.company_id', pjCompanyContext::companyOfCalendar($calendar_id))
			->findAll()
			->getData();
		if (!$coupons_allowed)
		{
			$discount_arr = array();
		}
		
		foreach($discount_arr as $k => $v)
		{
			$condition = false;
			switch ($v['condition']) {
				case 'period':
					$date_from_ts = strtotime($v['date_from']);
					$date_to_ts = strtotime($v['date_to']);
					//if( ($start_dt >= $date_from_ts && $start_dt <= $date_to_ts) || ($end_dt >= $date_from_ts && $end_dt <= $date_to_ts) || ($date_from_ts >= $start_dt && $date_from_ts <= $end_dt ) || ($date_to_ts >= $start_dt && $date_to_ts <= $end_dt ))
					if ($start_dt >= $date_from_ts && $end_dt <= $date_to_ts)
					{
						$condition = true;
					}
					break;
			
				case 'made':
					$date_from_ts = strtotime($v['date_from']);
					$date_to_ts = strtotime($v['date_to']);
					if (time() >= $date_from_ts && time() <= $date_to_ts)
					{
						$condition = true;
					}
					break;
						
				case 'both':
					$date_from_ts = strtotime($v['date_from']);
					$date_to_ts = strtotime($v['date_to']);
					//if( (($start_dt >= $date_from_ts && $start_dt <= $date_to_ts) || ($end_dt >= $date_from_ts && $end_dt <= $date_to_ts) || ($date_from_ts >= $start_dt && $date_from_ts <= $end_dt ) || ($date_to_ts >= $start_dt && $date_to_ts <= $end_dt )) && (time() >= $date_from_ts && time() <= $date_to_ts))
					if (($start_dt >= $date_from_ts && $end_dt <= $date_to_ts) && (time() >= $date_from_ts && time() <= $date_to_ts))
					{
						$condition = true;
					}
					break;
			}
			if($condition == true)
			{
				switch ($v['options']) {
					case 'early':
						if($early_nights >= (int) $v['early_days'])
						{
							$discount_check = 1;
						}
						break;
				
					case 'persons':
						if($people >= (int) $v['min_persons'] && $people <= (int) $v['max_persons'])
						{
							$discount_check = 1;
						}
						break;
							
					case 'family':
						if((int) $option_arr['o_bf_children'] !== 1 && (int) @$children > 0)
						{
							$discount_check = 1;
						}
						break;
					case 'duration':
						if($nights >= (int) $v['min_duration'] && $nights <= (int) $v['max_duration'])
						{
							$discount_check = 1;
						}	
						break;
				}
				if($discount_check == 1)
				{
					$temp_discount_amount = (float) $v['discount'];
					if($v['type'] == 'percent')
					{
						$temp_discount_amount = ($amount + $extra_price) * (float) $v['discount'] / 100;
					}
					if($temp_discount_amount >= $discount_amount)
					{
						$discount_amount = $temp_discount_amount;
						$discount_name = pjSanitize::html($v['name']);
						$discount_type = $v['type'];
						if($v['type'] == 'percent')
						{
							$discount_percentage = (float) $v['discount'];
						}
					}
					$discount_valid = 1;
					$discount_check = 0;
				}
			}
		}

		$amount_after_discount = $sub_total - $promo_amount - $discount_amount;
		if ($amount_after_discount < 0) {
			$amount_after_discount = 0;
		}
		if ($amount_after_discount > 0 && isset($option_arr['o_tax']) && (float) $option_arr['o_tax'] > 0)
		{
			$tax = ($amount_after_discount * (float) $option_arr['o_tax']) / 100;
		}
		
		if (isset($option_arr['o_security']) && (float) $option_arr['o_security'] > 0)
		{
			$security = (float) $option_arr['o_security'];
		}
		$date = new DateTime();
		$date->modify('+'.$option_arr['o_require_all_within'].' day');
		if (isset($option_arr['o_require_all_within']) && (int) $option_arr['o_require_all_within'] > 0 && $date->getTimestamp() >= $start_dt)
		{
		    $deposit = $amount_after_discount + $tax + $tourist_tax + $security;
		} elseif (isset($option_arr['o_deposit']) && (float) $option_arr['o_deposit'] > 0) {
				
			switch ($option_arr['o_deposit_type'])
			{
				case 'percent':
					$deposit = (($amount_after_discount + $tax + $tourist_tax) * (float) $option_arr['o_deposit']) / 100 + $security;
					break;
				case 'amount':
					$deposit = (float) $option_arr['o_deposit'] + $security;
					break;
			}
		}
		$total = $amount_after_discount + $tax + $tourist_tax;
		if($option_arr['o_to_be_paid'] == 'on_arrival' && $deposit > 0)
		{
			$deposit = $deposit - $security;
		}
		$unformat_deposit = $deposit;
		$unformat_total = $total;
		
		$result = compact('nights', 'amount', 'extra_price', 'amount_after_discount', 'deposit', 'sub_total', 'tax', 'tourist_tax', 'total', 'security', 'net', 'extras', 'promo_valid', 'promo_code', 'promo_amount', 'promo_type', 'promo_percentage', 'discount_valid', 'discount_amount', 'discount_type', 'discount_percentage', 'discount_name', 'unformat_deposit', 'unformat_total');
		
		return $result;
	}
	static public function getFromEmail($option_arr)
	{
		$email = $option_arr['o_email_address'];
		if($email == '')
		{
			$arr = pjAuthUserModel::factory()
				->findAll()
				->orderBy("t1.id ASC")
				->limit(1)
				->getData();
			$email = !empty($arr) ? $arr[0]['email'] : null;
		}
		return $email;
	}
	static public function getOwnerEmail($calendar_id)
	{
		$arr = pjCalendarModel::factory()->select("t1.*, t2.email")->join("pjAuthUser", "t1.user_id=t2.id", 'left')->find($calendar_id)->getData();
		if(!empty($arr))
		{
			if(!empty($arr['email']))
			{
				return $arr['email'];
			}else{
				return null;
			}
		}else{
			return null;
		}
	}

	/**
	 * Backward-compatible entry point kept for existing callers: given an
	 * `ical_urls` row id, sync the WHOLE calendar that row belongs to.
	 *
	 * @param int $feed_id
	 * @return array
	 */
	/**
	 * Every import URL of a property: the "iCal feeds" tab (feeds table) and the
	 * iCal URL list (ical_urls table). They are always synced together because the
	 * importer removes imported bookings that no longer appear in any of the feeds.
	 */
	public static function calendarFeedList($calendar_id)
	{
		$feeds = array();
		$rows = pjIcalUrlsModel::factory()->reset()->where('calendar_id', (int) $calendar_id)->findAll()->getData();
		foreach ($rows as $row)
		{
			$url = isset($row['ical_urls']) ? trim($row['ical_urls']) : '';
			if ($url !== '')
			{
				$feeds[] = array('url' => $url, 'provider_id' => (int) $row['provider_id']);
			}
		}
		$rows = pjFeedModel::factory()->reset()->where('calendar_id', (int) $calendar_id)->findAll()->getData();
		foreach ($rows as $row)
		{
			$url = isset($row['url']) ? trim($row['url']) : '';
			if ($url !== '')
			{
				$feeds[] = array('url' => $url, 'provider_id' => max(1, (int) $row['provider_id']));
			}
		}
		return $feeds;
	}

	/**
	 * Sync the property a row of the "iCal feeds" tab (feeds table) belongs to.
	 * (Called by the iCal feeds tab when a feed is added, changed or refreshed.)
	 */
	public static function syncFeeds($feed_id)
	{
		$feed = pjFeedModel::factory()->reset()->find((int) $feed_id)->getData();
		if (empty($feed) || empty($feed['calendar_id']))
		{
			return array('status' => 'ERR', 'code' => 404, 'text' => 'Feed not found.');
		}
		return self::syncCalendarIcalUrls((int) $feed['calendar_id']);
	}

	public static function syncIcalUrls($feed_id)
	{
		$feed = pjIcalUrlsModel::factory()->find((int) $feed_id)->getData();
		if (empty($feed) || empty($feed['calendar_id']))
		{
			return array('status' => 'ERR', 'code' => 404, 'text' => 'iCal URL not found.');
		}
		return self::syncCalendarIcalUrls((int) $feed['calendar_id']);
	}

	/**
	 * Import / refresh reservations for a calendar from ALL of its configured
	 * iCal feed URLs (every row in `ical_urls` for that calendar) in one pass.
	 * Processing a calendar's feeds together lets removals be reconciled safely,
	 * so one feed can never delete another feed's bookings.
	 *
	 * @param int $calendar_id
	 * @return array
	 */
	public static function syncCalendarIcalUrls($calendar_id)
	{
		$calendar_id = (int) $calendar_id;
		if ($calendar_id <= 0)
		{
			return array('status' => 'ERR', 'code' => 400, 'text' => 'Invalid calendar.');
		}

		$feeds = self::calendarFeedList($calendar_id);

		if (empty($feeds))
		{
			return array('status' => 'ERR', 'code' => 404, 'text' => 'No iCal URLs for this calendar.');
		}

		return self::importCalendarIcal($calendar_id, $feeds);
	}

	/**
	 * Core importer: pulls events from every feed of ONE calendar and upserts
	 * them into the reservations table by their stable uuid, so repeated runs
	 * never duplicate. Bookings that have disappeared from every feed are removed
	 * - but only imported rows (provider_id > 0) are ever touched, so native
	 * bookings made in the script are left alone. The delete step is skipped when
	 * any feed failed to load, so a temporary outage can't wipe good data.
	 *
	 * @param int   $calendar_id
	 * @param array $feeds  list of array('url' => string, 'provider_id' => int)
	 * @return array
	 */
	protected static function importCalendarIcal($calendar_id, array $feeds)
	{
		$calendar_id = (int) $calendar_id;
		if ($calendar_id <= 0 || empty($feeds))
		{
			return array('status' => 'ERR', 'code' => 400, 'text' => 'Nothing to synchronise.');
		}

		$option_arr     = pjOptionModel::factory()->getPairs($calendar_id);
		$price_based_on = isset($option_arr['o_price_based_on']) ? $option_arr['o_price_based_on'] : 'nights';

		if (!class_exists('iCalEasyReader'))
		{
			include (PJ_COMPONENTS_PATH . 'iCalEasyReader.php');
		}

		$events       = array(); // uuid => booking (active, current/future)
		$seen_uuids   = array(); // every non-cancelled uuid across all feeds
		$fetched_any  = false;
		$fetch_failed = false;
		$stats = array('feeds' => count($feeds), 'fetched' => 0, 'failed' => 0, 'parsed' => 0, 'inserted' => 0, 'updated' => 0, 'ignored' => 0, 'removed' => 0);

		foreach ($feeds as $feed)
		{
			$url         = isset($feed['url']) ? trim($feed['url']) : '';
			$provider_id = isset($feed['provider_id']) ? (int) $feed['provider_id'] : 0;
			if ($url === '')
			{
				continue;
			}

			$contents = self::fetchIcalContents($url);
			if ($contents === false || stripos($contents, 'BEGIN:VCALENDAR') === false)
			{
				$fetch_failed = true;
				$stats['failed']++;
				continue;
			}
			$fetched_any = true;
			$stats['fetched']++;

			$ical  = new iCalEasyReader();
			$lines = $ical->load($contents);
			if (empty($lines) || empty($lines['VEVENT']))
			{
				continue;
			}

			foreach ($lines['VEVENT'] as $item)
			{
				$booking = self::normaliseIcalEvent($item, $calendar_id, $provider_id, $price_based_on);
				if ($booking === false)
				{
					continue;
				}
				$stats['parsed']++;
				if ($booking['status'] !== 'Cancelled')
				{
					$seen_uuids[$booking['uuid']] = true;
				}
				if ($booking['status'] !== 'Cancelled' && $booking['date_to'] >= date('Y-m-d'))
				{
					$events[$booking['uuid']] = $booking; // last one wins => dedup
				}
			}
		}

		if (!$fetched_any)
		{
			return array('status' => 'ERR', 'code' => 502, 'text' => 'No feed could be reached.', 'stats' => $stats);
		}

		$existing = array();
		$existing_rows = pjReservationModel::factory()
			->reset()
			->select('t1.id, t1.uuid')
			->where('calendar_id', $calendar_id)
			->where('provider_id >', 0)
			->findAll()
			->getData();
		foreach ($existing_rows as $row)
		{
			if (!empty($row['uuid']))
			{
				$existing[$row['uuid']] = (int) $row['id'];
			}
		}

		foreach ($events as $uuid => $booking)
		{
			if (isset($existing[$uuid]))
			{
				$booking['modified'] = date('Y-m-d H:i:s');
				pjReservationModel::factory()->set('id', $existing[$uuid])->modify($booking);
				$stats['updated']++;
			}
			else
			{
				$saved = pjReservationModel::factory()->reset()->setAttributes($booking)->insert();
				if ($saved->getAffectedRows() === 1)
				{
					$stats['inserted']++;
				}
				else
				{
					$stats['ignored']++; // uuid already exists in the table (e.g. a native booking) - INSERT IGNORE skipped it
				}
			}
		}

		if (!$fetch_failed)
		{
			$stale_ids = array();
			foreach ($existing as $uuid => $id)
			{
				if (!isset($seen_uuids[$uuid]))
				{
					$stale_ids[] = $id;
				}
			}
			if (!empty($stale_ids))
			{
				$stats['removed'] = count($stale_ids);
				pjReservationModel::factory()
					->reset()
					->where('calendar_id', $calendar_id)
					->where('provider_id >', 0)
					->whereIn('id', $stale_ids)
					->eraseAll();
			}
		}

		self::rebuildCalendarIcalFile($calendar_id);

		return array('status' => 'OK', 'code' => 200, 'stats' => $stats);
	}

	/**
	 * Core, idempotent iCal importer shared by syncFeeds() and syncIcalUrls().
	 *
	 * It is safe to run any number of times (cron, manual refresh, on save):
	 *   - Every event is matched by a STABLE uuid, so re-running never creates
	 *     duplicate reservations. Events that carry no UID get a deterministic
	 *     one derived from their dates, so they stay stable across runs.
	 *   - Existing imported reservations that disappear from the feed, or that
	 *     turn "Cancelled", are removed so the property calendar stays in sync.
	 *   - Native (site) bookings are never touched: they always have provider_id 0
	 *     while imported bookings carry the feed's provider_id (> 0).
	 *   - If NONE of the URLs can be fetched (network error, provider down, ...)
	 *     the database is left untouched so a temporary outage never wipes data.
	 *
	 * @param int   $calendar_id
	 * @param int   $provider_id
	 * @param array $urls
	 * @return array
	 */
	protected static function importIcalReservations($calendar_id, $provider_id, array $urls, $reconcile = true)
	{
		$calendar_id = (int) $calendar_id;
		$provider_id = (int) $provider_id;

		if ($calendar_id <= 0 || empty($urls))
		{
			return array('status' => 'ERR', 'code' => 400, 'text' => 'Nothing to synchronise.');
		}

		$option_arr     = pjOptionModel::factory()->getPairs($calendar_id);
		$price_based_on = isset($option_arr['o_price_based_on']) ? $option_arr['o_price_based_on'] : 'nights';

		if (!class_exists('iCalEasyReader'))
		{
			include (PJ_COMPONENTS_PATH . 'iCalEasyReader.php');
		}

		$events       = array(); // uuid => normalised booking row (active only)
		$seen_uuids   = array(); // every non-cancelled uuid present in the feed(s)
		$fetched_any  = false;   // at least one URL returned usable data
		$fetch_failed = false;   // at least one URL could not be read

		foreach ($urls as $url)
		{
			$contents = self::fetchIcalContents($url);
			if ($contents === false || stripos($contents, 'BEGIN:VCALENDAR') === false)
			{
				$fetch_failed = true;
				continue;
			}
			$fetched_any = true;

			$ical  = new iCalEasyReader();
			$lines = $ical->load($contents);
			if (empty($lines) || empty($lines['VEVENT']))
			{
				continue;
			}

			foreach ($lines['VEVENT'] as $item)
			{
				$booking = self::normaliseIcalEvent($item, $calendar_id, $provider_id, $price_based_on);
				if ($booking === false)
				{
					continue; // event without usable start/end dates
				}

				// Remember every live uuid so we know which rows to keep.
				if ($booking['status'] !== 'Cancelled')
				{
					$seen_uuids[$booking['uuid']] = true;
				}

				// Only current / future, non-cancelled events become reservations.
				if ($booking['status'] !== 'Cancelled' && $booking['date_to'] >= date('Y-m-d'))
				{
					$events[$booking['uuid']] = $booking; // last one wins => dedup
				}
			}
		}

		// Could not read a single URL: do not touch anything.
		if (!$fetched_any)
		{
			return array('status' => 'ERR', 'code' => 502, 'text' => 'Feed could not be reached.');
		}

		// Existing imported reservations for this property/provider, keyed by uuid.
		$existing = array();
		$existing_rows = pjReservationModel::factory()
			->reset()
			->select('t1.id, t1.uuid')
			->where('calendar_id', $calendar_id)
			->where('provider_id', $provider_id)
			->findAll()
			->getData();
		foreach ($existing_rows as $row)
		{
			if (!empty($row['uuid']))
			{
				$existing[$row['uuid']] = (int) $row['id'];
			}
		}

		// Insert brand-new events, update the ones we already have.
		foreach ($events as $uuid => $booking)
		{
			if (isset($existing[$uuid]))
			{
				$booking['modified'] = date('Y-m-d H:i:s');
				pjReservationModel::factory()->set('id', $existing[$uuid])->modify($booking);
			}
			else
			{
				pjReservationModel::factory()->reset()->setAttributes($booking)->insert();
			}
		}

		// Remove imported reservations that are no longer in the feed (cancelled
		// or deleted upstream). Skipped when a URL failed, so a temporary outage
		// can never delete otherwise valid bookings.
		if (!$fetch_failed && $reconcile)
		{
			$stale_ids = array();
			foreach ($existing as $uuid => $id)
			{
				if (!isset($seen_uuids[$uuid]))
				{
					$stale_ids[] = $id;
				}
			}
			if (!empty($stale_ids))
			{
				pjReservationModel::factory()
					->reset()
					->where('calendar_id', $calendar_id)
					->where('provider_id', $provider_id)
					->whereIn('id', $stale_ids)
					->eraseAll();
			}
		}

		// Keep the property's own outgoing .ics feed in sync, so imported bookings
		// also block the dates on the other channels that subscribe to us.
		self::rebuildCalendarIcalFile($calendar_id);

		return array('status' => 'OK', 'code' => 200);
	}

	/**
	 * Convert a single parsed VEVENT into a normalised reservation row, or FALSE
	 * when the event does not carry usable start/end dates.
	 *
	 * @param array  $item
	 * @param int    $calendar_id
	 * @param int    $provider_id
	 * @param string $price_based_on
	 * @return array|false
	 */
	protected static function normaliseIcalEvent($item, $calendar_id, $provider_id, $price_based_on)
	{
		$date_from = self::extractIcalDate(isset($item['DTSTART']) ? $item['DTSTART'] : null);
		$date_to   = self::extractIcalDate(isset($item['DTEND'])   ? $item['DTEND']   : null);

		if ($date_from === null || $date_to === null)
		{
			return false;
		}

		$summary = '';
		if (isset($item['SUMMARY']))
		{
			$summary = is_array($item['SUMMARY'])
				? (isset($item['SUMMARY']['value']) ? $item['SUMMARY']['value'] : '')
				: $item['SUMMARY'];
		}

		$status = 'Confirmed';
		if ($summary !== '')
		{
			if (in_array($provider_id, array(2)))
			{
				$status = preg_match('/cancelled/i', $summary) ? 'Cancelled' : 'Pending';
				$status = preg_match('/Reserved|Blocked/i', $summary) ? 'Confirmed' : $status;
			}
			if (in_array($provider_id, array(3, 4)))
			{
				$status = preg_match('/Reserved|Blocked/i', $summary) ? 'Confirmed' : 'Pending';
				$status = preg_match('/cancelled/i', $summary) ? 'Cancelled' : $status;
			}
		}

		$c_name = $summary;
		if (strpos($summary, '-') !== false)
		{
			$parts = explode('-', $summary);
			$c_name = (isset($parts[1]) && trim($parts[1]) !== '') ? trim($parts[1]) : trim($summary);
		}

		// Stable identifier: use the feed UID when present, otherwise derive a
		// deterministic one so repeated imports of the same event never duplicate.
		$uuid = '';
		if (isset($item['UID']))
		{
			$uuid = is_array($item['UID'])
				? (isset($item['UID']['value']) ? trim($item['UID']['value']) : '')
				: trim($item['UID']);
		}
		if ($uuid === '')
		{
			$uuid = 'rpbc-' . md5($calendar_id . '|' . $provider_id . '|' . $date_from . '|' . $date_to . '|' . $summary);
		}
		$uuid = substr($uuid, 0, 255);

		return array(
			'calendar_id'    => $calendar_id,
			'provider_id'    => $provider_id,
			'uuid'           => $uuid,
			'date_from'      => $date_from,
			'date_to'        => $date_to,
			'price_based_on' => $price_based_on,
			'c_name'         => $c_name,
			'status'         => $status,
		);
	}

	/**
	 * Extract a Y-m-d date from a DTSTART/DTEND value, handling both the plain
	 * string form ("20240115" or "20240115T090000Z") and the parametrised array
	 * form produced by the reader (array('value' => '20240115', ...)).
	 *
	 * @param mixed $value
	 * @return string|null
	 */
	protected static function extractIcalDate($value)
	{
		if (is_array($value))
		{
			$value = isset($value['value']) ? $value['value'] : '';
		}
		$value = trim((string) $value);
		if (strlen($value) < 8)
		{
			return null;
		}
		$y = substr($value, 0, 4);
		$m = substr($value, 4, 2);
		$d = substr($value, 6, 2);
		if (!ctype_digit($y . $m . $d) || !checkdate((int) $m, (int) $d, (int) $y))
		{
			return null;
		}
		return $y . '-' . $m . '-' . $d;
	}

	/**
	 * Fetch the contents of a remote iCal URL robustly: sends a browser
	 * User-Agent (some channels reject the default PHP agent), follows redirects
	 * and applies a timeout. Returns the body as a string, or FALSE on failure.
	 *
	 * @param string $url
	 * @return string|false
	 */
	/**
	 * Is $url a feed address the server may fetch? Only http(s) on the default ports
	 * (or the script's own address) and never a private, loopback, link-local or
	 * reserved address - an attacker must not be able to make the server read its
	 * own network (SSRF). On success $pin holds "host:port:ip" for CURLOPT_RESOLVE so
	 * the checked address is the one actually used.
	 */
	public static function isSafeFeedUrl($url, &$pin = null)
	{
		$pin = null;
		$parts = @parse_url($url);
		if (empty($parts['scheme']) || empty($parts['host']) || !in_array(strtolower($parts['scheme']), array('http', 'https'), true) || isset($parts['user']) || isset($parts['pass']))
		{
			return false;
		}
		$host = strtolower(trim($parts['host'], '[]'));
		$port = isset($parts['port']) ? (int) $parts['port'] : (strtolower($parts['scheme']) === 'https' ? 443 : 80);

		// the script's own address (a feed exported by this installation) is allowed
		$own = defined('PJ_INSTALL_URL') ? @parse_url(PJ_INSTALL_URL) : array();
		if (!empty($own['host']) && strtolower($own['host']) === $host)
		{
			$own_port = isset($own['port']) ? (int) $own['port'] : (isset($own['scheme']) && strtolower($own['scheme']) === 'https' ? 443 : 80);
			return $own_port === $port;
		}
		if (!in_array($port, array(80, 443, 8080, 8443), true))
		{
			return false;
		}
		$ips = array();
		if (filter_var($host, FILTER_VALIDATE_IP))
		{
			$ips[] = $host;
		} else {
			$records = @dns_get_record($host, DNS_A + DNS_AAAA);
			foreach ((array) $records as $r)
			{
				if (!empty($r['ip'])) { $ips[] = $r['ip']; }
				if (!empty($r['ipv6'])) { $ips[] = $r['ipv6']; }
			}
			if (empty($ips))
			{
				$v4 = @gethostbynamel($host);
				$ips = is_array($v4) ? $v4 : array();
			}
		}
		if (empty($ips))
		{
			return false;
		}
		foreach ($ips as $ip)
		{
			if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
			{
				return false;
			}
		}
		// connect over IPv4 when the host has one (not every server has IPv6 connectivity)
		usort($ips, function ($a, $b) { return (strpos($a, ':') !== false) - (strpos($b, ':') !== false); });
		$pin = $host . ':' . $port . ':' . (strpos($ips[0], ':') !== false ? '[' . $ips[0] . ']' : $ips[0]);
		return true;
	}

	public static function fetchIcalContents($url)
	{
		$url = trim((string) $url);
		if ($url === '')
		{
			return false;
		}

		// A local .ics file is accepted only from the script's upload folder (handy for
		// testing); any other path on the server is refused.
		if (!preg_match('#^https?://#i', $url))
		{
			$file = realpath($url);
			$base = realpath(PJ_INSTALL_PATH . PJ_UPLOAD_PATH);
			if ($file !== false && $base !== false && strpos($file, $base . DIRECTORY_SEPARATOR) === 0 && is_file($file))
			{
				$body = @file_get_contents($file);
				return ($body === false || $body === '') ? false : $body;
			}
			return false;
		}

		$agent = 'Mozilla/5.0 (compatible; RentalPropertyBookingCalendar/1.0; iCal sync)';

		if (function_exists('curl_init'))
		{
			// Redirects are followed by hand so that every hop is checked.
			for ($hop = 0; $hop <= 5; $hop++)
			{
				$pin = null;
				if (!self::isSafeFeedUrl($url, $pin))
				{
					return false;
				}
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
				if (defined('CURLOPT_PROTOCOLS'))
				{
					curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
				}
				if ($pin !== null && defined('CURLOPT_RESOLVE'))
				{
					curl_setopt($ch, CURLOPT_RESOLVE, array($pin));
				}
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_USERAGENT, $agent);
				$body = curl_exec($ch);
				$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$next = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
				curl_close($ch);
				if ($code >= 300 && $code < 400 && $next !== '')
				{
					$url = $next;
					continue;
				}
				if ($body !== false && $body !== '' && $code >= 200 && $code < 300)
				{
					return $body;
				}
				return false;
			}
			return false;
		}

		if (!self::isSafeFeedUrl($url))
		{
			return false;
		}
		$context = stream_context_create(array(
			'http' => array(
				'method'          => 'GET',
				'timeout'         => 30,
				'follow_location' => 0,
				'header'          => 'User-Agent: ' . $agent . "\r\n",
			),
			'ssl' => array(
				'verify_peer'      => false,
				'verify_peer_name' => false,
			),
		));

		$body = @file_get_contents($url, false, $context);
		return ($body === false || $body === '') ? false : $body;
	}

	/**
	 * Rebuild the property's outgoing .ics file from the reservations currently in
	 * the database (all non-cancelled bookings from today onwards, native and
	 * imported alike). Writing the file from scratch keeps it duplicate-free.
	 *
	 * @param int $calendar_id
	 * @return void
	 */
	protected static function rebuildCalendarIcalFile($calendar_id)
	{
		$calendar_id = (int) $calendar_id;
		$calendar = pjCalendarModel::factory()->find($calendar_id)->getData();
		if (empty($calendar))
		{
			return;
		}

		$dir = PJ_UPLOAD_PATH . 'calendar_icals';
		if (!is_dir($dir))
		{
			@mkdir($dir, 0777, true);
		}
		$filename = 'listing_' . $calendar_id . '.ics';
		$path     = $dir . '/' . $filename;

		// Make sure the calendar knows where its feed lives.
		if (empty($calendar['ical_url']))
		{
			pjCalendarModel::factory()->reset()->where('id', $calendar_id)->limit(1)
				->modifyAll(array('ical_url' => PJ_UPLOAD_PATH . 'calendar_icals/' . $filename));
		}

		$reservations = pjReservationModel::factory()
			->reset()
			->where('calendar_id', $calendar_id)
			->where('status !=', 'Cancelled')
			->where('date_to >=', date('Y-m-d'))
			->orderBy('date_from ASC')
			->findAll()
			->getData();

		$eol   = "\r\n";
		$out   = 'BEGIN:VCALENDAR' . $eol
			   . 'VERSION:2.0' . $eol
			   . 'PRODID:-//Rental Property//NONSGML v1.0//EN' . $eol
			   . 'CALSCALE:GREGORIAN' . $eol;
		$stamp = gmdate('Ymd\THis\Z');

		foreach ($reservations as $res)
		{
			if (empty($res['date_from']) || empty($res['date_to']))
			{
				continue;
			}
			$dtstart = date('Ymd', strtotime($res['date_from']));
			$dtend   = date('Ymd', strtotime($res['date_to'] . ' +1 days'));
			$uid     = !empty($res['uuid']) ? $res['uuid'] : ('rpbc-' . $calendar_id . '-' . $res['id']);
			$summary = '#' . $calendar_id . ' - ' . (isset($res['c_name']) ? $res['c_name'] : 'Reservation');
			$summary = str_replace(array("\r", "\n"), ' ', $summary);
			$status  = isset($res['status']) ? $res['status'] : '';

			$out .= 'BEGIN:VEVENT' . $eol
				  . 'UID:' . $uid . $eol
				  . 'DTSTAMP:' . $stamp . $eol
				  . 'DTSTART;VALUE=DATE:' . $dtstart . $eol
				  . 'DTEND;VALUE=DATE:' . $dtend . $eol
				  . 'SUMMARY:' . $summary . $eol
				  . 'DESCRIPTION:Status: ' . $status . $eol
				  . 'END:VEVENT' . $eol;
		}
		$out .= 'END:VCALENDAR';

		$fh = @fopen($path, 'w');
		if ($fh)
		{
			@fwrite($fh, $out);
			@fclose($fh);
		}
	}

	/**
	 * Add or update a single reservation's event inside its calendar .ics feed,
	 * matching by UID so repeated calls never duplicate it. A cancelled booking is
	 * removed instead of written, which frees the dates on every channel that
	 * subscribes to this feed. Call this on booking create and on any update.
	 *
	 * @param array $data reservation row (needs calendar_id, uuid, date_from, date_to, status)
	 * @return void
	 */
	public static function saveReservationIcal($data)
	{
		$cal_id = (int) (isset($data['calendar_id']) ? $data['calendar_id'] : 0);
		$uid    = isset($data['uuid']) ? trim((string) $data['uuid']) : '';
		if ($cal_id <= 0 || $uid === '') {
			return;
		}

		$eol  = "\r\n";
		$path = self::ensureCalendarIcalPath($cal_id);
		if ($path === false) {
			return;
		}
		$ics = self::loadCalendarIcal($path, $eol);

		// Drop any existing event for this booking first.
		$ics = self::removeIcalEventByUid($ics, $uid, $eol);

		$status    = isset($data['status']) ? (string) $data['status'] : '';
		$date_from = isset($data['date_from']) ? str_replace('/', '-', (string) $data['date_from']) : '';
		$date_to   = isset($data['date_to'])   ? str_replace('/', '-', (string) $data['date_to'])   : '';

		// Re-add only while the booking is active and has usable dates.
		if (strcasecmp($status, 'Cancelled') !== 0 && $date_from !== '' && $date_to !== '') {
			$c_name = isset($data['c_name']) ? $data['c_name'] : '';
			$bid    = isset($data['id']) ? $data['id'] : (isset($data['reservation_id']) ? $data['reservation_id'] : '');
			$event  = self::buildReservationIcalEvent($cal_id, $uid, $date_from, $date_to, $c_name, $status, $bid, $eol);

			$pos = strripos($ics, 'END:VCALENDAR');
			$ics = ($pos === false)
				? rtrim($ics, "\r\n") . $eol . $event . 'END:VCALENDAR'
				: substr($ics, 0, $pos) . $event . substr($ics, $pos);
		}

		self::writeCalendarIcal($path, $ics);
	}

	/**
	 * Remove a single reservation's event from its calendar .ics feed. Call this
	 * when a booking is deleted.
	 *
	 * @param int    $calendar_id
	 * @param string $uuid
	 * @return void
	 */
	public static function removeReservationIcal($calendar_id, $uuid)
	{
		$cal_id = (int) $calendar_id;
		$uid    = trim((string) $uuid);
		if ($cal_id <= 0 || $uid === '') {
			return;
		}

		$eol  = "\r\n";
		$path = self::ensureCalendarIcalPath($cal_id);
		if ($path === false) {
			return;
		}
		$ics = self::loadCalendarIcal($path, $eol);
		$ics = self::removeIcalEventByUid($ics, $uid, $eol);
		self::writeCalendarIcal($path, $ics);
	}

	/**
	 * Ensure a calendar has a physical .ics feed file on disk. Creates the upload
	 * folder, stores the calendar's feed URL, and writes an empty (event-less)
	 * VCALENDAR skeleton when the file is missing or empty. Safe to call on
	 * calendar creation or on any page that needs the feed to exist. Returns the
	 * absolute path, or FALSE when the calendar does not exist.
	 *
	 * @param int $calendar_id
	 * @return string|false
	 */
	public static function ensureCalendarIcalFile($calendar_id)
	{
		$path = self::ensureCalendarIcalPath($calendar_id);
		if ($path === false)
		{
			return false;
		}
		if (!is_file($path) || @filesize($path) === 0)
		{
			// loadCalendarIcal() returns a fresh skeleton when the file is absent.
			self::writeCalendarIcal($path, self::loadCalendarIcal($path));
		}
		return $path;
	}

	/**
	 * Resolve (and lazily create) the .ics path for a calendar: ensures the upload
	 * folder exists and the calendar row stores its feed URL. Returns the absolute
	 * path, or FALSE when the calendar does not exist.
	 *
	 * @param int $calendar_id
	 * @return string|false
	 */
	protected static function ensureCalendarIcalPath($calendar_id)
	{
		$calendar_id = (int) $calendar_id;
		$calendar = pjCalendarModel::factory()->find($calendar_id)->getData();
		if (empty($calendar)) {
			return false;
		}

		$dir = PJ_UPLOAD_PATH . 'calendar_icals';
		if (!is_dir($dir)) {
			@mkdir($dir, 0777, true);
		}
		$filename = 'listing_' . $calendar_id . '.ics';

		if (empty($calendar['ical_url'])) {
			pjCalendarModel::factory()->reset()->where('id', $calendar_id)->limit(1)
				->modifyAll(array('ical_url' => PJ_UPLOAD_PATH . 'calendar_icals/' . $filename));
		}

		return $dir . '/' . $filename;
	}

	/**
	 * Load a calendar .ics file, returning a fresh VCALENDAR skeleton when the
	 * file is missing or unreadable.
	 *
	 * @param string $path
	 * @param string $eol
	 * @return string
	 */
	protected static function loadCalendarIcal($path, $eol = "\r\n")
	{
		$ics = @file_get_contents($path);
		if ($ics === false || stripos($ics, 'BEGIN:VCALENDAR') === false) {
			$ics = 'BEGIN:VCALENDAR' . $eol
				 . 'VERSION:2.0' . $eol
				 . 'PRODID:-//Rental Property//NONSGML v1.0//EN' . $eol
				 . 'CALSCALE:GREGORIAN' . $eol
				 . 'END:VCALENDAR';
		}
		return $ics;
	}

	/**
	 * Write calendar .ics contents to disk.
	 *
	 * @param string $path
	 * @param string $ics
	 * @return void
	 */
	protected static function writeCalendarIcal($path, $ics)
	{
		$fh = @fopen($path, 'w');
		if ($fh) {
			@fwrite($fh, $ics);
			@fclose($fh);
		}
	}

	/**
	 * Build one all-day VEVENT block for a reservation.
	 *
	 * @return string
	 */
	protected static function buildReservationIcalEvent($cal_id, $uid, $date_from, $date_to, $c_name, $status, $booking_id, $eol = "\r\n")
	{
		$dtstart = date('Ymd', strtotime($date_from));
		$dtend   = date('Ymd', strtotime($date_to . ' +1 days'));
		$stamp   = gmdate('Ymd\THis\Z');
		$summary = str_replace(array("\r", "\n"), ' ', '#' . $cal_id . ' - ' . $c_name);
		$description = 'Guest: ' . $c_name . '\\n'
					 . 'Status: ' . $status . '\\n'
					 . 'Check-in: ' . $date_from . '\\n'
					 . 'Check-out: ' . $date_to . '\\n'
					 . 'Booking ID: #' . $booking_id;

		return 'BEGIN:VEVENT' . $eol
			 . 'UID:' . $uid . $eol
			 . 'DTSTAMP:' . $stamp . $eol
			 . 'DTSTART;VALUE=DATE:' . $dtstart . $eol
			 . 'DTEND;VALUE=DATE:' . $dtend . $eol
			 . 'SUMMARY:' . $summary . $eol
			 . 'DESCRIPTION:' . $description . $eol
			 . 'END:VEVENT' . $eol;
	}

	/**
	 * Remove every VEVENT block carrying the given UID from a VCALENDAR string.
	 *
	 * @param string $ics
	 * @param string $uid
	 * @param string $eol
	 * @return string
	 */
	protected static function removeIcalEventByUid($ics, $uid, $eol = "\r\n")
	{
		$uid = trim($uid);
		if ($ics === '' || $uid === '') {
			return $ics;
		}

		$start = stripos($ics, 'BEGIN:VEVENT');
		if ($start === false) {
			return $ics;
		}

		$header    = substr($ics, 0, $start);
		$footerPos = strripos($ics, 'END:VCALENDAR');
		$footer    = ($footerPos !== false) ? substr($ics, $footerPos) : '';
		$middle    = ($footerPos !== false)
			? substr($ics, $start, $footerPos - $start)
			: substr($ics, $start);

		$blocks = preg_split('/(?=BEGIN:VEVENT)/i', $middle, -1, PREG_SPLIT_NO_EMPTY);
		$kept = '';
		foreach ($blocks as $block) {
			if (preg_match('/[\r\n]UID:([^\r\n]*)/i', $eol . $block, $m) && trim($m[1]) === $uid) {
				continue;
			}
			$kept .= rtrim($block, "\r\n") . $eol;
		}

		return rtrim($header, "\r\n") . $eol . $kept . $footer;
	}

}
?>