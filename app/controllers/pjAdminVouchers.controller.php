<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjAdminVouchers extends pjAdmin
{
	/** Coupons & discounts are a subscription plan feature (every action, incl. Ajax). */
	public function beforeFilter()
	{
		$res = parent::beforeFilter();
		if ($res === false)
		{
			return false;
		}
		$this->requirePlanFeature('coupons');
		return $res;
	}

	/** Properties of the active company for the form (name in the admin language, otherwise any saved language). */
	private function couponCalendars()
	{
		return pjCalendarModel::factory()->where('t1.company_id', $this->getCompanyId())
			->select(sprintf("t1.*, COALESCE(NULLIF(t2.content, ''), (SELECT `ml`.`content` FROM `%s` AS `ml` WHERE `ml`.`model` = 'pjCalendar' AND `ml`.`foreign_id` = t1.id AND `ml`.`field` = 'name' AND `ml`.`content` <> '' ORDER BY `ml`.`locale` LIMIT 1)) AS name", pjMultiLangModel::factory()->getTable()))
			->join('pjMultiLang', "t2.model='pjCalendar' AND t2.foreign_id=t1.id AND t2.field='name' AND t2.locale='".$this->getLocaleId()."'", 'left outer')
			->orderBy('name ASC')
			->findAll()
			->getData();
	}

	public function pjActionCheckCode()
	{
		$this->setAjax(true);
		
		if ($this->isXHR())
		{
			if (!$this->_get->check('code'))
	        {
	            echo 'false';
	            exit;
	        }
	        $code = $this->_get->toString('code');
	        if(empty($code))
	        {
	            echo 'false';
	            exit;
	        }
	        $pjVoucherModel = pjVoucherModel::factory()->where('t1.code', $code)->where('t1.company_id', $this->getCompanyId());
	    	if ($this->_get->check('id') && $this->_get->toInt('id') > 0)
	        {
	        	$pjVoucherModel->where('t1.id !=', $this->_get->toInt('id'));
	        }
	       echo $pjVoucherModel->findCount()->getData() == 0 ? 'true' : 'false';
		}
		exit;
	}
	
	/** Coupon codes are unique inside a company (checked on the server, not only in the form). */
	private function isCodeFree($code, $except_id = 0)
	{
		$m = pjVoucherModel::factory()->where('t1.code', (string) $code)->where('t1.company_id', $this->getCompanyId());
		if ((int) $except_id > 0)
		{
			$m->where('t1.id !=', (int) $except_id);
		}
		return trim((string) $code) !== '' && (int) $m->findCount()->getData() === 0;
	}

	/* ------------------------------------------------------------------
	 * Page form (same layout as the Super Admin's Coupons & Discounts). The Ajax popup below stays for
	 * older callers.
	 * ------------------------------------------------------------------ */

	/** Coupon row from the page form, or an error label key. */
	private function couponFromPost()
	{
		$apply_on = $this->_post->toString('apply_on');
		$condition = $this->_post->toString('condition');
		$data = array(
			'code' => trim((string) $this->_post->toString('code')),
			'discount' => (float) str_replace(',', '.', (string) $this->_post->toString('discount')),
			'type' => $this->_post->toString('type') === 'amount' ? 'amount' : 'percent',
			'condition' => in_array($condition, array('period', 'made', 'both'), true) ? $condition : 'period',
			'apply_on' => in_array($apply_on, array('room', 'extra', 'both'), true) ? $apply_on : 'room',
			'used_count' => max(1, $this->_post->toInt('used_count')),
			'date_from' => $this->cdDate($this->_post->toString('date_from')),
			'date_to' => $this->cdDate($this->_post->toString('date_to')),
		);
		if ($data['code'] === '' || strlen($data['code']) > 100)
		{
			return 'cdErrCode';
		}
		if ($data['discount'] <= 0 || ($data['type'] === 'percent' && $data['discount'] > 100)
			|| $data['date_from'] === '' || $data['date_to'] === '' || $data['date_from'] > $data['date_to']
			|| count($this->filterCompanyCalendarIds($this->_post->toArray('calendar_id'))) === 0)
		{
			return 'cdErrFields';
		}
		$data['discount'] = number_format($data['discount'], 2, '.', '');
		return $data;
	}

	/** Links of a coupon to the properties this user manages (links to other owners' properties stay). */
	private function saveCouponCalendars($id, $ids)
	{
		$m = pjCalendarVoucherModel::factory();
		$managed = $this->getCompanyCalendarIds();
		if (!empty($managed))
		{
			$m->where('voucher_id', (int) $id)->whereIn('calendar_id', $managed)->eraseAll();
		}
		$ids = $this->filterCompanyCalendarIds($ids);
		if (!empty($ids))
		{
			$m->reset();
			foreach ($ids as $calendar_id)
			{
				$m->addBatchRow(array((int) $id, (int) $calendar_id));
			}
			$m->setBatchFields(array('voucher_id', 'calendar_id'))->insertBatch();
		}
	}

	/** Add / edit a coupon on its own page (GET form, POST save or delete). */
	private function couponPage($id)
	{
		$this->checkLogin();
		$id = (int) $id;
		if ($id > 0 && !$this->isCompanyRecord(pjVoucherModel::factory(), $id))
		{
			$this->denyCompanyAccess();
		}
		$back = '&id=' . $id;
		if (self::isPost())
		{
			if ($this->_post->toString('do') === 'delete')
			{
				if ($id <= 0 || !pjAuth::factory('pjAdminVouchers', 'pjActionDeleteVoucher')->hasAccess())
				{
					$this->sendForbidden();
					return;
				}
				pjVoucherModel::factory()->set('id', $id)->erase();
				pjCalendarVoucherModel::factory()->where('voucher_id', $id)->eraseAll();
				$this->cdBack('pjAdminVouchers', 'pjActionIndex', 'cdDeleted');
			}
			$data = $this->couponFromPost();
			$action = $id > 0 ? 'pjActionUpdate' : 'pjActionCreate';
			if (!is_array($data))
			{
				$this->cdBack('pjAdminVouchers', $action, $data, $back);
			}
			if (!$this->isCodeFree($data['code'], $id))
			{
				$this->cdBack('pjAdminVouchers', $action, 'cdErrCode', $back);
			}
			$data['company_id'] = $this->getCompanyId();
			if ($id > 0)
			{
				pjVoucherModel::factory()->where('id', $id)->limit(1)->modifyAll($data);
			} else {
				$id = (int) pjVoucherModel::factory($data)->insert()->getInsertId();
				if ($id <= 0)
				{
					$this->cdBack('pjAdminVouchers', $action, 'cdErrSave');
				}
			}
			$this->saveCouponCalendars($id, $this->_post->toArray('calendar_id'));
			$this->cdBack('pjAdminVouchers', 'pjActionIndex', 'cdSaved');
		}
		$this->setTemplate('pjAdminVouchers', 'pjActionForm');
		$this->set('arr', $id > 0 ? pjVoucherModel::factory()->find($id)->getData() : false);
		$this->set('calendar_names', $this->cdCalendarNames());
		$this->set('calendar_id_arr', $id > 0 ? array_map('intval', pjCalendarVoucherModel::factory()->where('voucher_id', $id)->findAll()->getDataPair(null, 'calendar_id')) : array());
		$this->set('has_delete', pjAuth::factory('pjAdminVouchers', 'pjActionDeleteVoucher')->hasAccess());
		$this->set('cd', $this->cdPageData('coupon'));
		$this->appendCss('subscriptions.css?v=' . @filemtime(PJ_INSTALL_PATH . 'app/web/css/subscriptions.css'));
		$this->appendJs('pjAdminCoupons.js?v=' . @filemtime(PJ_INSTALL_PATH . 'app/web/js/pjAdminCoupons.js'));
	}

	public function pjActionCreate()
	{
		if (!$this->isXHR())
		{
			$this->couponPage(0);
			return;
		}
		$this->setAjax(true);
		if ($this->isXHR()) {
			if ($this->_post->check('voucher_create'))
			{
				$data = array();			
				$data['code'] = $this->_post->toString('code');
				$data['used_count'] = $this->_post->toInt('used_count');
				$data['discount'] = $this->_post->toString('discount');
				$data['type'] = $this->_post->toString('type');
				$data['condition'] = $this->_post->toString('condition');
				$data['apply_on'] = $this->_post->toString('apply_on');
				$data['date_from'] = pjDateTime::formatDate($this->_post->toString('date_from'), $this->option_arr['o_date_format']);
				$data['date_to'] = pjDateTime::formatDate($this->_post->toString('date_to'), $this->option_arr['o_date_format']);
				$data['company_id'] = $this->getCompanyId();
				if (!$this->isCodeFree($data['code']))
				{
					pjAppController::jsonResponse(array('status' => 'ERR', 'code' => 105, 'text' => 'This coupon code is already used.'));
				}
				$id = pjVoucherModel::factory($data)->insert()->getInsertId();
				if ($id !== false && (int) $id > 0)
				{
					$calendar_id_arr = $this->filterCompanyCalendarIds($this->_post->toArray('calendar_id'));
					if(count($calendar_id_arr) > 0)
	            	{
	            		$pjCalendarVoucherModel = pjCalendarVoucherModel::factory();
		                foreach ($calendar_id_arr as $calendar_id)
	    	            {
	    	                $pjCalendarVoucherModel->addBatchRow(array($id, $calendar_id));
	    	            }
	    	            $pjCalendarVoucherModel->setBatchFields(array('voucher_id', 'calendar_id'))->insertBatch();
					}
				}
				pjAppController::jsonResponse(array('status' => 'OK'));
			} else {				
				$this->set('calendar_arr', $this->couponCalendars());
			}
		}
	}
	
	public function pjActionDeleteVoucher()
	{
		$this->setAjax(true);
	
		if (!$this->isXHR())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Missing headers.'));
		}
		
		if (!self::isGet() && !$this->_get->check('id') && $this->_get->toInt('id') < 0)
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'HTTP method not allowed.'));
		}
		if (!$this->isCompanyRecord(pjVoucherModel::factory(), $this->_get->toInt('id')))
		{
			$this->denyCompanyAccess();
		}
		if (pjVoucherModel::factory()->set('id', $this->_get->toInt('id'))->erase()->getAffectedRows() == 1)
		{
			pjCalendarVoucherModel::factory()->where('voucher_id', $this->_get->toInt('id'))->eraseAll();
			$response = array('status' => 'OK');
		} else {
			$response = array('status' => 'ERR');
		}
		
		self::jsonResponse($response);
	}
	
	public function pjActionDeleteVoucherBulk()
	{
		$this->setAjax(true);
	
		if (!$this->isXHR())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Missing headers.'));
		}
		
		if (!self::isPost())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'HTTP method not allowed.'));
		}

		if (!$this->_post->has('record') || !($record = $this->_post->toArray('record')))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Missing, empty or invalid data.'));
		}
		
		$record = $this->filterCompanyRecordIds(pjVoucherModel::factory(), $record);
		if (!empty($record) && pjVoucherModel::factory()->whereIn('id', $record)->eraseAll()->getAffectedRows() > 0)
		{
			pjCalendarVoucherModel::factory()->whereIn('voucher_id', $record)->eraseAll();
			self::jsonResponse(array('status' => 'OK'));
		}
		
		self::jsonResponse(array('status' => 'ERR'));
	}
	
	public function pjActionExportVoucher()
	{
		$this->checkLogin();
		
		if ($record = $this->_post->toArray('record'))
		{
			$record = $this->filterCompanyRecordIds(pjVoucherModel::factory(), $record);
			$arr = empty($record) ? array() : pjVoucherModel::factory()->whereIn('id', $record)->findAll()->getData();
			$csv = new pjCSV();
			$csv
				->setHeader(true)
				->setName("Vouchers-".time().".csv")
				->process($arr)
				->download();
		}
		exit;
	}
	
	public function pjActionGetVoucher()
	{
		$this->setAjax(true);
	
		if ($this->isXHR())
		{
			$pjVoucherModel = pjVoucherModel::factory()->where('t1.company_id', $this->getCompanyId());
			
			if ($q = $this->_get->toString('q'))
			{
				$q = str_replace(array('_', '%'), array('\_', '\%'), $pjVoucherModel->escapeStr($q));
				$pjVoucherModel->where('t1.code LIKE', "%$q%");
			}
	
			if ($this->_get->check('calendar_id') && $this->_get->toInt('calendar_id') > 0)
			{
				$pjVoucherModel->where("(t1.id IN(SELECT `TCV`.voucher_id FROM `".pjCalendarVoucherModel::factory()->getTable()."` AS `TCV` WHERE `TCV`.calendar_id='".$this->_get->toInt('calendar_id')."') )");
			}
	
			$column = 'code';
			$direction = 'ASC';
			$allowed_columns = array('code', 'discount', 'date_from', 'date_to', 'apply_on', 'used_count');
			if ($this->_get->toString('column') && in_array($this->_get->toString('column'), $allowed_columns) && in_array(strtoupper($this->_get->toString('direction')), array('ASC', 'DESC')))
			{
				$column = $this->_get->toString('column');
				$direction = strtoupper($this->_get->toString('direction'));
			}
	
			$total = $pjVoucherModel->findCount()->getData();
			$rowCount = $this->_get->toInt('rowCount') ? $this->_get->toInt('rowCount') : 10;
			$pages = ceil($total / $rowCount);
			$page = $this->_get->toInt('page') ? $this->_get->toInt('page') : 1;
			$offset = ((int) $page - 1) * $rowCount;
			if ($page > $pages)
			{
				$page = $pages;
			}
			
			$data = $pjVoucherModel->select('t1.*')->orderBy("$column $direction")->limit($rowCount, $offset)->findAll()->getData();
			$apply_on = pjUtil::sortArrayByArray(__('apply_on'), array('room','extra','both'));
			foreach($data as $k => $v)
			{
				$v['code'] = pjSanitize::clean($v['code']);
				if($v['type'] == 'percent')
				{
					$v['discount'] = $v['discount'] . '%';
				}else{
					$v['discount'] = pjCurrency::formatPrice($v['discount']);
				}
				$v['date_from'] = date($this->option_arr['o_date_format'], strtotime($v['date_from']));
				$v['date_to'] = date($this->option_arr['o_date_format'], strtotime($v['date_to']));
				$v['apply_on'] = @$apply_on[$v['apply_on']];
				$data[$k] = $v;
			}	
			pjAppController::jsonResponse(compact('data', 'total', 'pages', 'page', 'rowCount', 'column', 'direction'));
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
	    
	    $this->set('has_update', pjAuth::factory('pjAdminVouchers', 'pjActionUpdate')->hasAccess());
	    $this->set('has_create', pjAuth::factory('pjAdminVouchers', 'pjActionCreate')->hasAccess());
	    $this->set('has_export', pjAuth::factory('pjAdminVouchers', 'pjActionExportVoucher')->hasAccess());
	    $this->set('has_delete', pjAuth::factory('pjAdminVouchers', 'pjActionDeleteVoucher')->hasAccess());
	    $this->set('has_delete_bulk', pjAuth::factory('pjAdminVouchers', 'pjActionDeleteVoucherBulk')->hasAccess());

	    // the list (same layout as the Super Admin's Coupons & Discounts)
	    $cid = $this->getCompanyId();
	    $rows = pjVoucherModel::factory()->where('t1.company_id', $cid)->orderBy('t1.date_to DESC, t1.code ASC')->findAll()->getData();
	    $links = array();
	    if (!empty($rows))
	    {
	    	foreach (pjCalendarVoucherModel::factory()->whereIn('voucher_id', array_map(function ($r) { return (int) $r['id']; }, $rows))->findAll()->getData() as $l)
	    	{
	    		$links[(int) $l['voucher_id']][] = (int) $l['calendar_id'];
	    	}
	    }
	    // times each code was used: confirmed bookings of this company's properties (the same count the booking check uses)
	    $uses = pjReservationModel::factory()->select('t1.promo_code, COUNT(*) AS `cnt`')->where('t1.status', 'Confirmed')->where("t1.promo_code <> ''")
	    	->where(sprintf("t1.calendar_id IN (SELECT `id` FROM `%s` WHERE `company_id` = %u)", pjCalendarModel::factory()->getTable(), $cid))
	    	->groupBy('t1.promo_code')->findAll()->getDataPair('promo_code', 'cnt');
	    $uses_ci = array();
	    foreach ($uses as $code => $cnt)
	    {
	    	$uses_ci[strtolower((string) $code)] = (isset($uses_ci[strtolower((string) $code)]) ? $uses_ci[strtolower((string) $code)] : 0) + (int) $cnt;
	    }
	    $this->set('rows', $rows);
	    $this->set('links', $links);
	    $this->set('uses', $uses_ci);
	    $this->set('calendar_names', $this->cdCalendarNames());
	    $this->set('cd', $this->cdPageData('coupon'));
	    $this->appendCss('subscriptions.css?v=' . @filemtime(PJ_INSTALL_PATH . 'app/web/css/subscriptions.css'));
	    $this->appendJs('pjAdminCoupons.js?v=' . @filemtime(PJ_INSTALL_PATH . 'app/web/js/pjAdminCoupons.js'));
	}
	
	public function pjActionSaveVoucher()
	{
		$this->setAjax(true);
	
		if (!$this->isXHR())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Missing headers.'));
		}
		
		if (!self::isPost())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'HTTP method not allowed.'));
		}
		
		$params = array(
				'id' => $this->_get->toInt('id'),
				'column' => $this->_post->toString('column'),
				'value' => $this->_post->toString('value'),
		);
		if (!(isset($params['id'], $params['column'], $params['value'])
				&& pjValidation::pjActionNumeric($params['id'])
				&& pjValidation::pjActionNotEmpty($params['column'])))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Missing, empty or invalid parameters.'));
		}
		
		if (!$this->isCompanyRecord(pjVoucherModel::factory(), $params['id']))
		{
			$this->denyCompanyAccess();
		}
		if (!in_array($params['column'], array('code'), true))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 104, 'text' => 'Invalid column.'));
		}
		if ($params['value'] === '' || !$this->isCodeFree($params['value'], $params['id']))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 105, 'text' => 'This coupon code is already used.'));
		}
		$pjVoucherModel = pjVoucherModel::factory();
		if (!in_array($params['column'], $pjVoucherModel->getI18n()))
		{
			$pjVoucherModel->where('id', $params['id'])->limit(1)->modifyAll(array($params['column'] => $params['value']));
		} else {
			pjMultiLangModel::factory()->updateMultiLang(array($this->getLocaleId() => array($params['column'] => $params['value'])), $params['id'], 'pjVoucher');
		}
		
		self::jsonResponse(array('status' => 'OK', 'code' => 200));
	}
	
	public function pjActionUpdate()
	{
		if (!$this->isXHR())
		{
			$this->couponPage(self::isPost() ? $this->_post->toInt('id') : $this->_get->toInt('id'));
			return;
		}
		$this->setAjax(true);
		if ($this->isXHR()) {
			if ($this->_post->check('voucher_update'))
			{
				$data = array();			
				$data['code'] = $this->_post->toString('code');
				$data['used_count'] = $this->_post->toInt('used_count');
				$data['discount'] = $this->_post->toString('discount');
				$data['type'] = $this->_post->toString('type');
				$data['condition'] = $this->_post->toString('condition');
				$data['apply_on'] = $this->_post->toString('apply_on');
				$data['date_from'] = pjDateTime::formatDate($this->_post->toString('date_from'), $this->option_arr['o_date_format']);
				$data['date_to'] = pjDateTime::formatDate($this->_post->toString('date_to'), $this->option_arr['o_date_format']);
				if (!$this->isCompanyRecord(pjVoucherModel::factory(), $this->_post->toInt('id')))
				{
					$this->denyCompanyAccess();
				}
				if (!$this->isCodeFree($data['code'], $this->_post->toInt('id')))
				{
					pjAppController::jsonResponse(array('status' => 'ERR', 'code' => 105, 'text' => 'This coupon code is already used.'));
				}
				$data['company_id'] = $this->getCompanyId();
				pjVoucherModel::factory()->where('id', $this->_post->toInt('id'))->limit(1)->modifyAll($data);
				
				$pjCalendarVoucherModel = pjCalendarVoucherModel::factory();
				// re-link only the properties this user manages; links to other owners' properties stay
				$managed_ids = $this->getCompanyCalendarIds();
				if (!empty($managed_ids))
				{
					$pjCalendarVoucherModel->where('voucher_id', $this->_post->toInt('id'))->whereIn('calendar_id', $managed_ids)->eraseAll();
				}
				$calendar_id_arr = $this->filterCompanyCalendarIds($this->_post->toArray('calendar_id'));
				if (count($calendar_id_arr) > 0)
				{
					$pjCalendarVoucherModel->reset();
	                foreach ($calendar_id_arr as $calendar_id)
    	            {
    	                $pjCalendarVoucherModel->addBatchRow(array($this->_post->toInt('id'), $calendar_id));
    	            }
    	            $pjCalendarVoucherModel->setBatchFields(array('voucher_id', 'calendar_id'))->insertBatch();
				}
				pjAppController::jsonResponse(array('status' => 'OK'));	
			} else {
				if (!$this->isCompanyRecord(pjVoucherModel::factory(), $this->_get->toInt('id')))
				{
					$this->denyCompanyAccess();
				}
				$arr = pjVoucherModel::factory()->find($this->_get->toInt('id'))->getData();
				$this->set('arr', $arr);
				
				$this->set('calendar_arr', $this->couponCalendars());
			
				$this->set('calendar_id_arr', pjCalendarVoucherModel::factory()
					->where("voucher_id", $this->_get->toInt('id'))
					->findAll()
					->getDataPair("calendar_id", "calendar_id"));
			}
		}
	}
}
?>