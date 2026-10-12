<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjBaseCountries extends pjBase
{
    public function pjActionCheckAlpha()
    {
        $this->setAjax(true);
        
        if ($this->isXHR())
        {
            $pjBaseCountryModel = pjBaseCountryModel::factory();
            
            if ($this->_get->check("alpha_2"))
            {
                $pjBaseCountryModel->where('t1.alpha_2', $this->_get->toString("alpha_2"));
            } elseif ($this->_get->check("alpha_3")) {
                $pjBaseCountryModel->where('t1.alpha_3', $this->_get->toString("alpha_3"));
            }
            if ($this->_get->check("id"))
            {
                $pjBaseCountryModel->where('t1.id !=', $this->_get->toInt("id"));
            }
            
            $cnt = $pjBaseCountryModel->findCount()->getData();
            echo (int) $cnt === 0 ? 'true' : 'false';
        }
        exit;
    }
    
	/* ---- countries per company ----------------------------------------------
	 * Shared (built-in) countries: each company has its own active/inactive status.
	 * Countries added while a company is active belong to that company only.
	 */
	private function cCompanyId()
	{
		return class_exists('pjCompanyContext') ? (int) pjCompanyContext::activeId($this->getRoleId(), $this->getUserId()) : 0;
	}

	/** 'own' (company's country), 'shared', or false (another company's country / not found). */
	private function countryScope($id)
	{
		$arr = pjBaseCountryModel::factory()->find((int) $id)->getData();
		if (empty($arr))
		{
			return false;
		}
		if (empty($arr['company_id']))
		{
			return 'shared';
		}
		return (int) $arr['company_id'] === $this->cCompanyId() ? 'own' : false;
	}

	private function denyCountry($text = 'This country is shared by all companies: you can only activate or deactivate it.')
	{
		self::jsonResponse(array('status' => 'ERR', 'code' => 403, 'text' => $text));
	}

	public function pjActionIndex()
	{
		$this->checkLogin();
		if (!pjAuth::factory('pjBaseCountries')->hasAccess())
		{
			$this->sendForbidden();
			return;
		}
		
		if (self::isGet())
		{ 
			$this->set('has_revert', pjAuth::factory('pjBaseCountries', 'pjActionStatusCountry')->hasAccess());
			$this->appendJs('jquery.datagrid.js', PJ_FRAMEWORK_LIBS_PATH . 'pj/js/');
			$this->appendJs('pjBaseCountries.js', $this->getConst('PLUGIN_JS_PATH'));
		}
	}
	public function pjActionGetCountry()
	{
		// Same permission as the page this list/action belongs to (the plugin does not check it otherwise).
		if ($this->isLoged() && !pjAuth::factory('pjBaseCountries')->hasAccess())
		{
			if ($this->isXHR()) { self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.')); }
			$this->sendForbidden();
			return;
		}
		$this->setAjax(true);
		
		if ($this->isXHR())
		{
		    $pjBaseCountryModel = pjBaseCountryModel::factory()->join('pjBaseMultiLang', "t2.foreign_id = t1.id AND t2.model = 'pjBaseCountry' AND t2.locale = '".$this->getLocaleId()."' AND t2.field = 'name'", 'left');
		    $company_id = $this->cCompanyId();
		    $status_expr = 't1.status';
		    if (class_exists('pjCompanyContext'))
		    {
		        // shared countries + the company's own; status as set for this company
		        $pjBaseCountryModel->where(sprintf("(t1.company_id IS NULL OR t1.company_id = %u)", $company_id));
		        $status_expr = pjCompanyContext::countryStatusExpr($company_id);
		    }
		    
		    if ($q = $this->_get->toString('q'))
		    {
		        $q = str_replace(array('%', '_'), array('\%', '\_'), trim($pjBaseCountryModel->escapeStr($q)));
		        $pjBaseCountryModel->where(sprintf("(t1.alpha_2 LIKE '%1\$s' OR t1.alpha_3 LIKE '%1\$s' OR t2.content LIKE '%1\$s')", "%$q%"));
		    }
		    
		    if (in_array($this->_get->toString('status'), array('T', 'F')))
		    {
		        $pjBaseCountryModel->where(sprintf("%s = '%s'", $status_expr, $this->_get->toString('status')));
		    }
		    
		    $column = 'name';
		    $direction = 'ASC';
		    if ($this->_get->toString('column') && in_array($this->_get->toString('column'), array('name', 'alpha_2', 'alpha_3', 'status'), true) && in_array(strtoupper($this->_get->toString('direction')), array('ASC', 'DESC')))
		    {
		        $column = $this->_get->toString('column');
		        $direction = strtoupper($this->_get->toString('direction'));
		    }
		    
		    $total = (int) $pjBaseCountryModel->findCount()->getData();
		    $rowCount = $this->_get->toInt('rowCount') ?: 10;
		    $pages = ceil($total / $rowCount);
		    $page = $this->_get->toInt('page') ? : 1;
		    $offset = ((int) $page - 1) * $rowCount;
		    if ($page > $pages)
		    {
		        $page = $pages;
		    }
		    
		    $data = $pjBaseCountryModel
		    ->select(sprintf('t1.id, t1.alpha_2, t1.alpha_3, t1.company_id, %s AS status, t2.content AS name', $status_expr))
		    ->orderBy("$column $direction")
		    ->limit($rowCount, $offset)
		    ->findAll()->getData();
		    
		    pjAppController::jsonResponse(compact('data', 'total', 'pages', 'page', 'rowCount', 'column', 'direction'));
		}
		exit;
	}
	
	public function pjActionCreate()
	{
		$this->checkLogin();
		if (!pjAuth::factory()->hasAccess())
		{
			$this->sendForbidden();
			return;
		}
		
		if (self::isPost() && $this->_post->toInt('country_create'))
		{
		    $data = array();
		    $post = $this->_post->raw();
		    $data['status'] = $this->_post->check('status') ? 'T' : 'F';
		    // A country added while a company is active belongs to that company.
		    $data['company_id'] = $this->cCompanyId() > 0 ? $this->cCompanyId() : ':NULL';
		    $id = pjBaseCountryModel::factory(array_merge($post, $data))->insert()->getInsertId();
		    if ($id !== false && (int) $id > 0)
		    {
		        if (isset($post['i18n']))
		        {
		            pjBaseMultiLangModel::factory()->saveMultiLang($post['i18n'], $id, 'pjBaseCountry', 'data');
		        }
		        $err = 'PCY03';
		    }else{
		        $err = 'PCY04';
		    }
		    pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjBaseCountries&action=pjActionIndex&err=$err");
		}
		if (self::isGet())
		{
		    $this->setLocalesData();
		    
		    $this->appendJs('jquery.validate.min.js', PJ_THIRD_PARTY_PATH . 'validate/');
		    $this->appendJs('jquery.multilang.js', $this->getConstant('pjBase', 'PLUGIN_JS_PATH'), false, false);
		    $this->appendJs('jquery.tipsy.js', PJ_THIRD_PARTY_PATH . 'tipsy/');
		    $this->appendCss('jquery.tipsy.css', PJ_THIRD_PARTY_PATH . 'tipsy/');
			$this->appendJs('pjBaseCountries.js', $this->getConst('PLUGIN_JS_PATH'));
		}
	}
	
	public function pjActionUpdate()
	{
		$this->checkLogin();
		if (!pjAuth::factory()->hasAccess())
		{
			$this->sendForbidden();
			return;
		}
		
		$has_revert = pjAuth::factory('pjBaseCountries', 'pjActionStatusCountry')->hasAccess();
		
		if (self::isPost() && $this->_post->toInt('country_update') && $this->_post->toInt('id'))
		{
		    $data = array();
		    $post = $this->_post->raw();
		    $data['status'] = $this->_post->check('status') ? 'T' : 'F';
		    $scope = class_exists('pjCompanyContext') ? $this->countryScope($this->_post->toInt('id')) : 'own';
		    if ($scope === false)
		    {
		        pjUtil::redirect(PJ_INSTALL_URL. "index.php?controller=pjBaseCountries&action=pjActionIndex&err=PCY08");
		    }
		    if ($scope === 'shared' && $this->cCompanyId() > 0)
		    {
		        // shared country: only this company's status is saved
		        pjCompanyContext::setCountryStatus($this->cCompanyId(), $this->_post->toInt('id'), $data['status']);
		        pjUtil::redirect(PJ_INSTALL_URL. "index.php?controller=pjBaseCountries&action=pjActionIndex&err=PCY01");
		    }
		    unset($post['company_id']);
		    pjBaseCountryModel::factory()->where('id', $this->_post->toInt('id'))->limit(1)->modifyAll(array_merge($post, $data));
		    if (isset($post['i18n']))
		    {
		        pjBaseMultiLangModel::factory()->updateMultiLang($post['i18n'], $post['id'], 'pjBaseCountry', 'data');
		    }
		    pjUtil::redirect(PJ_INSTALL_URL. "index.php?controller=pjBaseCountries&action=pjActionIndex&err=PCY01");
		}
		if (self::isGet() && $this->_get->toInt('id'))
		{
		    $id = $this->_get->toInt('id');
		    $arr = pjBaseCountryModel::factory()->find($id)->getData();
		    if (class_exists('pjCompanyContext') && $this->countryScope($id) === false)
		    {
		        $arr = array();
		    }
		    if (!empty($arr) && class_exists('pjCompanyContext'))
		    {
		        $st = pjCompanyCountryModel::factory()->where('company_id', $this->cCompanyId())->where('country_id', $id)->limit(1)->findAll()->getDataIndex(0);
		        if (!empty($st))
		        {
		            $arr['status'] = $st['status'];
		        }
		    }
		    if (count($arr) === 0)
		    {
		        pjUtil::redirect(PJ_INSTALL_URL. "index.php?controller=pjBaseCountries&action=pjActionIndex&err=PCY08");
		    }
		    $arr['i18n'] = pjBaseMultiLangModel::factory()->getMultiLang($arr['id'], 'pjBaseCountry');
			$this->set('arr', $arr);
			$this->setLocalesData();
			
			$this->appendJs('jquery.validate.min.js', PJ_THIRD_PARTY_PATH . 'validate/');
			$this->appendJs('jquery.multilang.js', $this->getConstant('pjBase', 'PLUGIN_JS_PATH'), false, false);
			$this->appendJs('jquery.tipsy.js', PJ_THIRD_PARTY_PATH . 'tipsy/');
			$this->appendCss('jquery.tipsy.css', PJ_THIRD_PARTY_PATH . 'tipsy/');
			$this->appendJs('pjBaseCountries.js', $this->getConst('PLUGIN_JS_PATH'));
		}
	}
	
	public function pjActionSaveCountry()
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
		if (!pjAuth::factory('pjBaseCountries', 'pjActionUpdate')->hasAccess())
		{
		    self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Access denied.'));
		}
		$pjBaseCountryModel = pjBaseCountryModel::factory();
		$arr = $pjBaseCountryModel->find($this->_get->toInt('id'))->getData();
		if (!$arr)
		{
		    self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Country not found.'));
		}
		if (class_exists('pjCompanyContext'))
		{
		    $scope = $this->countryScope($arr['id']);
		    if ($scope === false)
		    {
		        $this->denyCountry('This country belongs to another company.');
		    }
		    if ($params['column'] === 'status' && $this->cCompanyId() > 0)
		    {
		        pjCompanyContext::setCountryStatus($this->cCompanyId(), $arr['id'], $params['value']);
		        self::jsonResponse(array('status' => 'OK', 'code' => 201, 'text' => 'Country has been updated.'));
		    }
		    if ($scope === 'shared' || !in_array($params['column'], array_merge(array('alpha_2', 'alpha_3'), $pjBaseCountryModel->getI18n()), true))
		    {
		        $this->denyCountry();
		    }
		}
		
		if (!in_array($this->_post->toString('column'), $pjBaseCountryModel->getI18n()))
		{
		    $pjBaseCountryModel->reset()->where('id', $this->_get->toInt('id'))->limit(1)->modifyAll(array($this->_post->toString('column') => $this->_post->toString('value')));
		} else {
		    pjBaseMultiLangModel::factory()->updateMultiLang(array($this->getLocaleId() => array($this->_post->toString('column') => $this->_post->toString('value'))), $this->_get->toInt('id'), 'pjBaseCountry', 'data');
		}
		
		self::jsonResponse(array('status' => 'OK', 'code' => 201, 'text' => 'Country has been updated.'));
	}
	
	public function pjActionStatusCountry()
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
		if (!pjAuth::factory('pjBaseCountries')->hasAccess())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Access denied.'));
		}
		$record = $this->_post->toArray('record');
		if (empty($record))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Missing, empty or invalid parameters.'));
		}
		if (class_exists('pjCompanyContext') && $this->cCompanyId() > 0)
		{
			// toggle this company's status of each country
			$cid = $this->cCompanyId();
			$rows = pjBaseCountryModel::factory()
				->select(sprintf('t1.id, %s AS st', pjCompanyContext::countryStatusExpr($cid)))
				->whereIn('t1.id', array_map('intval', $record))
				->where(sprintf("(t1.company_id IS NULL OR t1.company_id = %u)", $cid))
				->findAll()->getData();
			foreach ($rows as $r)
			{
				pjCompanyContext::setCountryStatus($cid, $r['id'], $r['st'] == 'T' ? 'F' : 'T');
			}
			self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Country status has been updated.'));
		}
		pjBaseCountryModel::factory()
			->whereIn('id', $record)
			->modifyAll(array(
				'status' => ":IF(`status`='F','T','F')"
			));
		self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Country status has been updated.'));
	}
	
	public function pjActionDeleteCountry()
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
		if (!pjAuth::factory()->hasAccess())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Access denied.'));
		}
		if (!($this->_get->toInt('id')))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Missing, empty or invalid parameters.'));
		}
		
		if (class_exists('pjCompanyContext') && $this->countryScope($this->_get->toInt('id')) !== 'own')
		{
			$this->denyCountry('Only countries added by this company can be deleted. Deactivate shared countries instead.');
		}
		if (!pjBaseCountryModel::factory()->set('id', $this->_get->toInt('id'))->erase()->getAffectedRows())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 105, 'text' => 'Country has not been deleted.'));
		}
		
		self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Country has been deleted'));
	}
	
	public function pjActionDeleteCountryBulk()
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
		if (!pjAuth::factory()->hasAccess())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Access denied.'));
		}
		if (!$this->_post->has('record'))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Missing, empty or invalid parameters.'));
		}
		$record = $this->_post->toArray('record');
		if (empty($record))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 104, 'text' => 'Missing, empty or invalid parameters.'));
		}
		$m = pjBaseCountryModel::factory()->whereIn('id', array_map('intval', $record));
		if (class_exists('pjCompanyContext'))
		{
			$m->where('company_id', $this->cCompanyId()); // own countries only
		}
		$m->eraseAll();
			
		self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Countries has been deleted.'));
	}
}
?>