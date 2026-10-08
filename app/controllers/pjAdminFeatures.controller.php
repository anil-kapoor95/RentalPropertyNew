<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjAdminFeatures extends pjAdmin
{
	public function pjActionCreate()
	{
		$this->setAjax(true);
		if ($this->isXHR()) {
			if ($this->_post->check('feature_create'))
			{
				$pjFeatureModel = pjFeatureModel::factory();

				$data = array();
				$data['status'] = $this->_post->check('status') ? 'T' : 'F';
				$data['category_id'] = $this->_post->toInt('category_id');
				$id = $pjFeatureModel->setAttributes(array_merge($this->_post->raw(), $data))->insert()->getInsertId();
				if ($id !== false && (int) $id > 0)
				{
					$i18n_arr = $this->_post->toI18n('i18n');
					if (!empty($i18n_arr))
					{
						pjMultiLangModel::factory()->saveMultiLang($i18n_arr, $id, 'pjFeature');
					}
				}
				pjAppController::jsonResponse(array('status' => 'OK'));
			} else {
				$this->set('feature_categories', __('feature_categories', true, false));
				$this->setLocalesData();
			}
		}
	}

	public function pjActionDeleteFeature()
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
		if (pjFeatureModel::factory()->set('id', $this->_get->toInt('id'))->erase()->getAffectedRows() == 1)
		{
			pjMultiLangModel::factory()->where('model', 'pjFeature')->where('foreign_id', $this->_get->toInt('id'))->eraseAll();
			pjCalendarFeatureModel::factory()->where('feature_id', $this->_get->toInt('id'))->eraseAll();
			$response = array('status' => 'OK');
		} else {
			$response = array('status' => 'ERR');
		}

		self::jsonResponse($response);
	}

	public function pjActionDeleteFeatureBulk()
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

		if (pjFeatureModel::factory()->whereIn('id', $record)->eraseAll()->getAffectedRows() > 0)
		{
			pjMultiLangModel::factory()->where('model', 'pjFeature')->whereIn('foreign_id', $record)->eraseAll();
			pjCalendarFeatureModel::factory()->whereIn('feature_id', $record)->eraseAll();
			self::jsonResponse(array('status' => 'OK'));
		}

		self::jsonResponse(array('status' => 'ERR'));
	}

	public function pjActionGetFeature()
	{
		$this->setAjax(true);

		if ($this->isXHR())
		{
			$pjFeatureModel = pjFeatureModel::factory()
				->join('pjMultiLang', "t2.foreign_id = t1.id AND t2.model = 'pjFeature' AND t2.locale = '".$this->getLocaleId()."' AND t2.field = 'name'", 'left');

			if ($q = $this->_get->toString('q'))
			{
				$q = str_replace(array('_', '%'), array('\_', '\%'), $pjFeatureModel->escapeStr($q));
				$pjFeatureModel->where('t2.content LIKE', "%$q%");
			}

			if ($this->_get->check('status') && in_array($this->_get->toString('status'), array('T', 'F')))
			{
				$pjFeatureModel->where('t1.status', $this->_get->toString('status'));
			}

			$column = 'name';
			$direction = 'ASC';
			$allowed_columns = array('name', 'status');
			if ($this->_get->toString('column') && in_array($this->_get->toString('column'), $allowed_columns) && in_array(strtoupper($this->_get->toString('direction')), array('ASC', 'DESC')))
			{
				$column = $this->_get->toString('column');
				$direction = strtoupper($this->_get->toString('direction'));
			}

			$total = $pjFeatureModel->findCount()->getData();
			$rowCount = $this->_get->toInt('rowCount') ? $this->_get->toInt('rowCount') : 10;
			$pages = ceil($total / $rowCount);
			$page = $this->_get->toInt('page') ? $this->_get->toInt('page') : 1;
			$offset = ((int) $page - 1) * $rowCount;
			if ($page > $pages)
			{
				$page = $pages;
			}

			$data = $pjFeatureModel
				->select('t1.*, t2.content AS name')
				->orderBy("$column $direction")
				->limit($rowCount, $offset)
				->findAll()
				->getData();

			$feature_categories = __('feature_categories', true, false);
			foreach($data as $k => $v)
			{
				$v['name'] = pjSanitize::clean($v['name']);
				$v['category'] = isset($feature_categories[$v['category_id']]) ? $feature_categories[$v['category_id']] : '';
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
		$this->setLocalesData();
		$this->set('has_update', pjAuth::factory('pjAdminFeatures', 'pjActionUpdate')->hasAccess());
		$this->set('has_create', pjAuth::factory('pjAdminFeatures', 'pjActionCreate')->hasAccess());
		$this->set('has_delete', pjAuth::factory('pjAdminFeatures', 'pjActionDeleteFeature')->hasAccess());
		$this->set('has_delete_bulk', pjAuth::factory('pjAdminFeatures', 'pjActionDeleteFeatureBulk')->hasAccess());

		$this->appendJs('jquery.multilang.js', PJ_FRAMEWORK_LIBS_PATH . 'pj/js/');
		$this->appendJs('jquery.datagrid.js', PJ_FRAMEWORK_LIBS_PATH . 'pj/js/');
		$this->appendJs('pjAdminFeatures.js');
	}

	public function pjActionSaveFeature()
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

		$pjFeatureModel = pjFeatureModel::factory();
		if (!in_array($params['column'], $pjFeatureModel->getI18n()))
		{
			$pjFeatureModel->where('id', $params['id'])->limit(1)->modifyAll(array($params['column'] => $params['value']));
		} else {
			pjMultiLangModel::factory()->updateMultiLang(array($this->getLocaleId() => array($params['column'] => $params['value'])), $params['id'], 'pjFeature');
		}

		self::jsonResponse(array('status' => 'OK', 'code' => 200));
	}

	public function pjActionUpdate()
	{
		$this->setAjax(true);
		if ($this->isXHR()) {
			if ($this->_post->check('feature_update'))
			{
				$data = array();
				$data['status'] = $this->_post->check('status') ? 'T' : 'F';
				$data['category_id'] = $this->_post->toInt('category_id');
				pjFeatureModel::factory()->set('id', $this->_post->toInt('id'))->modify(array_merge($this->_post->raw(), $data));

				$i18n_arr = $this->_post->toI18n('i18n');
				if (!empty($i18n_arr))
				{
					pjMultiLangModel::factory()->updateMultiLang($i18n_arr, $this->_post->toInt('id'), 'pjFeature');
				}
				pjAppController::jsonResponse(array('status' => 'OK'));
			} else {
				$arr = pjFeatureModel::factory()->find($this->_get->toInt('id'))->getData();
				$arr['i18n'] = pjMultiLangModel::factory()->getMultiLang($arr['id'], 'pjFeature');
				$this->set('arr', $arr);

				$this->set('feature_categories', __('feature_categories', true, false));
				$this->setLocalesData();
			}
		}
	}
}
?>
