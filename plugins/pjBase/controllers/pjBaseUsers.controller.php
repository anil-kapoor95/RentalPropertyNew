<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjBaseUsers extends pjBase
{
	/* ---- users per company -------------------------------------------------- */

	private function usersCompanyId()
	{
		return class_exists('pjCompanyContext') ? (int) pjCompanyContext::activeId($this->getRoleId(), $this->getUserId()) : 0;
	}

	/** Administrators manage the users of every company. */
	private function isUsersAdmin()
	{
		return !class_exists('pjCompanyContext') || pjCompanyContext::isAdmin($this->getRoleId(), $this->getUserId());
	}

	/**
	 * company_id to store for a user: administrators have none; otherwise the company
	 * chosen in the form (hash), falling back to the active company.
	 */
	private function postedCompanyId($role_id)
	{
		if (!class_exists('pjCompanyContext'))
		{
			return null;
		}
		// Users made by an administrator belong to the admin side: no company
		// (they work in every company with the company switcher, see pjCompanyContext::isGlobalUser).
		if ($this->isUsersAdmin())
		{
			return ':NULL';
		}
		return pjCompanyContext::companyForRole($role_id, $this->usersCompanyId());
	}

	const ROLE_COMPANY = 4;
	const ROLE_COMPANY_USER = 5;

	/** The logged-in user is a company login (manages the company's own users). */
	private function isCompanyLogin()
	{
		return (int) $this->getRoleId() === self::ROLE_COMPANY;
	}

	/**
	 * SQL condition: the users this user may see and manage in Users.
	 * - Administrators: the system users (no company, no Company / Company user role).
	 * - A company login: only the "Company user" users of its own company.
	 * - Other roles: the users of their company, never administrators, company logins or company users.
	 */
	private function usersScopeSql($alias = 't1')
	{
		if (!class_exists('pjCompanyContext'))
		{
			return '1 = 1';
		}
		if ($this->isUsersAdmin())
		{
			// every admin-side user, also users an older version tied to a company
			return sprintf("(%1\$s.`role_id` NOT IN (%2\$u, %3\$u))", $alias, self::ROLE_COMPANY, self::ROLE_COMPANY_USER);
		}
		$cid = $this->usersCompanyId();
		if ($this->isCompanyLogin())
		{
			return sprintf("(%1\$s.`company_id` = %2\$u AND %1\$s.`role_id` = %3\$u)", $alias, $cid, self::ROLE_COMPANY_USER);
		}
		return sprintf("(%s AND %s.`role_id` NOT IN (%u, %u))", pjCompanyContext::userScopeSql($cid, $alias, false), $alias, self::ROLE_COMPANY, self::ROLE_COMPANY_USER);
	}

	/** May the logged-in user give this role in Users? */
	private function canGiveRole($role_id)
	{
		$role_id = (int) $role_id;
		if ($this->isCompanyLogin())
		{
			return $role_id === self::ROLE_COMPANY_USER;
		}
		return $this->isRoleAllowed($role_id) && !in_array($role_id, array(self::ROLE_COMPANY, self::ROLE_COMPANY_USER), true);
	}

	/** Stop unless the user is one this user may manage (see usersScopeSql). */
	private function assertCompanyUser($user_id)
	{
		if ($this->isUsersAdmin())
		{
			// administrators manage every user except the users that a company created
			$row = pjAuthUserModel::factory()->select('t1.role_id')->find((int) $user_id)->getData();
			$ok = empty($row) || (int) $row['role_id'] !== self::ROLE_COMPANY_USER;
		} else {
			$ok = (int) pjAuthUserModel::factory()->where('t1.id', (int) $user_id)->where($this->usersScopeSql('t1'))->findCount()->getData() > 0;
		}
		if (!$ok)
		{
			if ($this->isXHR())
			{
				self::jsonResponse(array('status' => 'ERR', 'code' => 403, 'text' => 'This user belongs to another company.'));
			}
			pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjBaseUsers&action=pjActionIndex&err=PU08");
		}
	}

	/** Only administrators may give the Administrator role. */
	private function isRoleAllowed($role_id)
	{
		return (int) $role_id !== 1 || $this->isUsersAdmin();
	}

	/** True when $user_id is the only active administrator left. */
	private function isLastAdmin($user_id)
	{
		$row = pjAuthUserModel::factory()->select('t1.role_id')->find((int) $user_id)->getData();
		if (empty($row) || (int) $row['role_id'] !== 1)
		{
			return false;
		}
		return (int) pjAuthUserModel::factory()
			->where('t1.role_id', 1)
			->where('t1.status', 'T')
			->where('t1.id !=', (int) $user_id)
			->findCount()->getData() === 0;
	}

	/** After a role change the user follows the permissions of the new role. */
	private function afterRoleChange($user_id, $old_role_id, $new_role_id)
	{
		if ((int) $old_role_id !== (int) $new_role_id)
		{
			pjAuthUserPermissionModel::factory()->where('user_id', (int) $user_id)->eraseAll();
		}
	}

	private function filterCompanyUsers($ids)
	{
		$ids = array_values(array_filter(array_map('intval', (array) $ids)));
		if (empty($ids))
		{
			return array();
		}
		$m = pjAuthUserModel::factory()->whereIn('t1.id', $ids);
		if ($this->isUsersAdmin())
		{
			$m->where('t1.role_id !=', self::ROLE_COMPANY_USER);
		} else {
			$m->where($this->usersScopeSql('t1'));
		}
		return array_map('intval', $m->findAll()->getDataPair(null, 'id'));
	}

	public function pjActionCheckEmail()
	{
		$this->setAjax(true);
		
		if ($this->isXHR())
		{
			$params = array(
				'controller' => $this->_get->toString('controller'),
				'action' => $this->_get->toString('action'),
				'email' => $this->_get->toString('email'),
				'id' => $this->_get->toInt('id'),
			);
			if ($this->_get->check('profile'))
			{
				$params['profile'] = $this->_get->toInt('profile');
			}

			echo pjAuth::init($params)->checkEmail();
		}
		exit;
	}
	
	public function pjActionCheckPassword()
	{
		$this->setAjax(true);
		if (!self::isPost())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'HTTP method not allowed.'));
		}
		if (!$this->_post->toString('password'))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Missing, empty or invalid parameters.'));
		}
		$response = pjAuth::init()->validatePassword($this->_post->toString('password'));
		self::jsonResponse($response);
		exit;
	}
	
	public function pjActionIndex()
	{
		$this->checkLogin();
		if (!pjAuth::factory('pjBaseUsers')->hasAccess())
		{
			$this->sendForbidden();
			return;
		}
		
		if (self::isGet())
		{ 
			$this->set('has_revert', pjAuth::factory('pjBaseUsers', 'pjActionStatusUser')->hasAccess());
			$this->appendJs('jquery.datagrid.js', PJ_FRAMEWORK_LIBS_PATH . 'pj/js/');
			$this->appendJs('pjBaseUsers.js', $this->getConst('PLUGIN_JS_PATH'));
			
			$this->set('has_update', pjAuth::factory('pjBaseUsers', 'pjActionUpdate')->hasAccess());
			$this->set('has_create', pjAuth::factory('pjBaseUsers', 'pjActionCreate')->hasAccess());
			$this->set('has_delete', pjAuth::factory('pjBaseUsers', 'pjActionDeleteUser')->hasAccess());
			$this->set('has_delete_bulk', pjAuth::factory('pjBaseUsers', 'pjActionDeleteUserBulk')->hasAccess());
			$this->set('has_user_permission', pjAuth::factory('pjBasePermissions', 'pjActionUserPermission')->hasAccess());
		}
	}
	public function pjActionGetUser()
	{
		$this->setAjax(true);
		
		if ($this->isXHR())
		{
			// Only users who may open the Users page get the list (it holds e-mail addresses).
			if (!pjAuth::factory('pjBaseUsers')->hasAccess())
			{
				self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.'));
			}
		    $pjAuthUserModel = pjAuthUserModel::factory()
		    					->join('pjAuthRole', 't2.id=t1.role_id', 'left')
		    					->where('t2.is_backend', 'T')
		    					->where('t2.is_admin', 'T');
			// who is listed: see usersScopeSql (a company sees only its own company users)
			$pjAuthUserModel->where($this->usersScopeSql('t1'));
			if ($q = $this->_get->toString('q'))
			{
				$q = str_replace(array('%', '_'), array('\%', '\_'), trim($pjAuthUserModel->escapeStr($q)));
				$pjAuthUserModel->where("(t1.email LIKE '%$q%' OR t1.name LIKE '%$q%')");
			}
			
			if (in_array($this->_get->toString('status'), array('T', 'F')))
			{
				$pjAuthUserModel->where('t1.status', $this->_get->toString('status'));
			}
			
			$column = 'name';
			$direction = 'ASC';
			if ($this->_get->toString('column') && in_array($this->_get->toString('column'), array('name', 'email', 'last_login', 'role', 'status', 'locked', 'company'), true) && in_array(strtoupper($this->_get->toString('direction')), array('ASC', 'DESC')))
			{
				$column = $this->_get->toString('column');
				$direction = strtoupper($this->_get->toString('direction'));
			}
			
			$total = $pjAuthUserModel->findCount()->getData();
			$rowCount = $this->_get->toInt('rowCount') ?: 10;
			$pages = ceil($total / $rowCount);
			$page = $this->_get->toInt('page') ?: 1;
			if ($page > $pages)
			{
				$page = $pages;
			}
			$page = $page >= 1 ? $page : 1;
			$offset = ((int) $page - 1) * $rowCount;
			
			$data = $pjAuthUserModel
				->select('t1.id, t1.email, t1.name, DATE(t1.created) AS `created`, DATE(t1.last_login) AS `last_login`, t1.status, t1.is_active, t1.locked, t1.role_id, t1.company_id'
					. (class_exists('pjCompanyContext') ? sprintf(', (SELECT c.`name` FROM `%s` c WHERE c.`id` = t1.company_id) AS `company`', pjCompanyModel::factory()->getTable()) : ''))
				->orderBy(($column === 'role' ? 't1.role_id' : ($column === 'company' ? '`company`' : $column)) . " $direction")
				->limit($rowCount, $offset)
				->findAll()
				->getData();
			
			$role_arr = __('plugin_base_role_arr', true);
			foreach($data as $k => $v) {
				$data[$k]['role'] = $role_arr[$v['role_id']];
				// Which company the user belongs to (administrators work for all companies)
				if ((int) $v['role_id'] === 1)
				{
					$data[$k]['company'] = __('lblUserAllCompanies', true);
				} else {
					$data[$k]['company'] = !empty($v['company']) ? pjSanitize::clean($v['company']) : '—';
				}
				unset($data[$k]['company_id']);
				$data[$k]['name'] = pjSanitize::clean($v['name']);
				$data[$k]['email'] = pjSanitize::clean($v['email']);
			}
			
			self::jsonResponse(compact('data', 'total', 'pages', 'page', 'rowCount', 'column', 'direction'));
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
		
		if (self::isPost() && $this->_post->toInt('user_create'))
		{
			$data = array();
			$data['status'] = $this->_post->check('status') ? 'T': 'F';
			$data['role_id'] = $this->isCompanyLogin() ? self::ROLE_COMPANY_USER : $this->_post->toInt('role_id');
			if (!$this->canGiveRole($data['role_id']))
			{
				pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjBaseUsers&action=pjActionIndex&err=PU04");
			}
			if (class_exists('pjCompanyContext'))
			{
				// New users belong to the active company (administrators are global).
				$data['company_id'] = $this->postedCompanyId($data['role_id']);
			}

			$response = pjAuth::init(array_merge($this->_post->raw(),$data))->createUser();
			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjBaseUsers&action=pjActionIndex&err=" . $response['code']);
		}
		if (self::isGet())
		{
			$this->appendJs('pjBaseUsers.js', $this->getConst('PLUGIN_JS_PATH'));
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
		
		$has_revert = pjAuth::factory('pjBaseUsers')->hasAccess();
		
		if (self::isPost() && $this->_post->toInt('user_update') && $this->_post->toInt('id'))
		{
		    $this->assertCompanyUser($this->_post->toInt('id'));
		    $user_id = $this->_post->toInt('id');
		    $new_role_id = $this->isCompanyLogin() ? self::ROLE_COMPANY_USER : $this->_post->toInt('role_id');
		    $current = pjAuthUserModel::factory()->select('t1.role_id, t1.status, t1.company_id')->find($user_id)->getData();
		    $goes_inactive = $has_revert && !$this->_post->check('status');
		    if (empty($current)
		        || !$this->isRoleAllowed($new_role_id)
		        || ($new_role_id === 4 && (int) $current['role_id'] !== 4)
		        || ($new_role_id !== (int) $current['role_id'] && !$this->canGiveRole($new_role_id))
		        || ($this->isLastAdmin($user_id) && ($new_role_id !== 1 || $goes_inactive)))
		    {
		        // never give the Administrator role from a company account, never leave the system without an active administrator
		        pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjBaseUsers&action=pjActionIndex&err=PU05");
		    }
		    // only the fields of the form; role, status and company are set below
		    $post = array();
		    foreach (array('name', 'email', 'phone', 'password') as $field)
		    {
		        if ($this->_post->has($field))
		        {
		            $post[$field] = $this->_post->toString($field);
		        }
		    }
		    if (class_exists('pjCompanyContext'))
		    {
		        // the edit form has no company field: the user keeps his company
		        // (administrators have none; a user without one gets the active company)
		        $post['company_id'] = ($this->isUsersAdmin() && (int) $current['role_id'] !== self::ROLE_COMPANY) || empty($current['company_id'])
		            ? $this->postedCompanyId($new_role_id)
		            : pjCompanyContext::companyForRole($new_role_id, (int) $current['company_id']);
		    }
			$data = array();
			$data['id'] = $this->_post->toInt('id');
			if ($has_revert)
			{
				$data['status'] = $this->_post->check('status') ? 'T' : 'F';
			}
			$data['ip'] = pjUtil::getClientIp();
			$data['role_id'] = $new_role_id;
			if ($this->_post->has('password') && !$this->_post->isEmpty('password'))
			{
				$data['password'] = $this->_post->toString('password');
				$data['pswd_modified'] = ':NOW()';
			}else{
			    unset($post['password']);
			}

			$response = pjAuth::init(array_merge($post,$data))->updateUser();
			if (isset($response['status']) && $response['status'] == 'OK')
			{
				$this->afterRoleChange($user_id, $current['role_id'], $new_role_id);
			}
			pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjBaseUsers&action=pjActionIndex&err=" . $response['code']);
		}
		if (self::isGet() && $this->_get->toInt('id'))
		{
		    // non-administrators cannot open an administrator (see assertCompanyUser)
		    $this->assertCompanyUser($this->_get->toInt('id'));
			$params = array(
				'controller' => $this->_get->toString('controller'),
				'action' => $this->_get->toString('action'),
				'id' => $this->_get->toInt('id'),
			);

			$arr = pjAuth::init($params)->getUser();
			if (!$arr)
			{
				pjUtil::redirect(PJ_INSTALL_URL. "index.php?controller=pjBaseUsers&action=pjActionIndex&err=PU08");
			}
			$this->set('arr', $arr);
			$this->set('has_revert', $has_revert);
			$this->appendJs('pjBaseUsers.js', $this->getConst('PLUGIN_JS_PATH'));
		}
	}
	
	public function pjActionProfile()
	{
	    $this->checkLogin();
	    if (!pjAuth::factory()->hasAccess())
	    {
	        $this->sendForbidden();
	        return;
	    }
	    
	    $has_revert = pjAuth::factory('pjBaseUsers', 'pjActionStatusUser')->hasAccess() && !$this->isLastAdmin($this->getUserId());
	    
	    if (self::isPost() && $this->_post->toInt('user_update') && $this->_post->toInt('id'))
	    {
	        $data = array();
	        $data['id'] = (int) $this->getUserId(); // My profile: always the logged-in user
	        if ($has_revert)
	        {
	            $data['status'] = $this->_post->check('status') ? 'T' : 'F';
	        }
	        $data['ip'] = pjUtil::getClientIp();
	        $data['email'] = $this->_post->toString('email');
	        if ($this->_post->has('password') && !$this->_post->isEmpty('password'))
	        {
	            $data['password'] = $this->_post->toString('password');
	            $data['pswd_modified'] = ':NOW()';
	        }
	        $data['name'] = $this->_post->toString('name');
	        $data['phone'] = $this->_post->toString('phone') ?: ':NULL';
	        
	        $response = pjAuth::init($data)->updateUser();
	        pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjBaseUsers&action=pjActionProfile&err=" . $response['code']);
	    }
	    if (self::isGet())
	    {
	        $params = array(
	            'controller' => $this->_get->toString('controller'),
	            'action' => $this->_get->toString('action'),
	            'id' => $this->getUserId(),
	        );
	        
	        $arr = pjAuth::init($params)->getUser();
	        if (!$arr)
	        {
	            pjUtil::redirect(PJ_INSTALL_URL. "index.php?controller=pjBaseUsers&action=pjActionIndex&err=PU08");
	        }
	        $this->set('arr', $arr);
	        $this->set('role_arr', $arr = pjAuth::init()->getRoleList());
	        $this->set('has_revert', $has_revert);
	        $this->appendJs('pjBaseUsers.js', $this->getConst('PLUGIN_JS_PATH'));
	    }
	}
	
	public function pjActionExportUser()
	{
		$this->checkLogin();
		if (!pjAuth::factory('pjBaseUsers', 'pjActionExportUser')->hasAccess())
		{
			$this->sendForbidden();
			return;
		}

		$record = $this->_post->toArray('record');
		if (count($record))
		{
			$record = $this->filterCompanyUsers($record);
			$arr = empty($record) ? array() : pjAuthUserModel::factory()->whereIn('id', $record)->findAll()->getData();
			$datetime = array('created', 'last_login', 'pswd_modified');
			foreach ($arr as &$item)
			{
				unset($item['password'], $item['login_token']);
				foreach ($datetime as $index)
				{
					if (!empty($item[$index]))
					{
						$item[$index] = pjDateTime::formatDateTime($item[$index], 'Y-m-d H:i:s', $this->option_arr['o_date_format'] . ', ' . $this->option_arr['o_time_format']);
					}
				}
			}
			$csv = new pjCSV();
			$csv
				->setHeader(true)
				->setName("Users-".time().".csv")
				->process($arr)
				->download();
		}
		exit;
	}
	
	public function pjActionSaveUser()
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
		
		$this->assertCompanyUser($params['id']);
		// only the columns the grid edits; role / company changes are done in the edit form
		if (!in_array($params['column'], array('name', 'email', 'status', 'locked'), true))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Access denied.'));
		}
		if ($params['column'] == 'status' && !pjAuth::factory('pjBaseUsers')->hasAccess())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Access denied.'));
		}
		
		if ($params['column'] == 'email')
		{
			if (pjAuthUserModel::factory()
				->join("pjAuthRole", "t1.role_id=t2.id", "left outer")
				->where('t1.id !=', $params['id'])
				->where('t1.email', $params['value'])
				->where('t2.is_backend', 'T')
				->findCount()
				->getData())
			{
				self::jsonResponse(array('status' => 'ERR', 'code' => 105, 'text' => 'Email address is already in use.'));
			}
		}
		
		$pass = true;
		if (in_array($params['column'], array('name', 'email')) && $params['value'] == '')
		{
			$pass = false;
		} elseif ($params['column'] == 'email' && !filter_var($params['value'], FILTER_VALIDATE_EMAIL)) {
			$pass = false;
		} elseif ($params['column'] == 'status' && $params['value'] != 'T' && ((int) $params['id'] === (int) $this->getUserId() || $this->isLastAdmin($params['id']))) {
			// nobody switches off their own account or the last active administrator
			$pass = false;
		}
		if (in_array($params['column'], array('locked')))
		{
			$result = pjAuth::init()->unlockAccount($params['id']);
			if ($result['status'] == 'OK')
			{
				$params['value'] = 'F';
			} else {
				$pass = false;
			}
		}
		if (!$pass)
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 104, 'text' => 'Access denied.'));
		}
		$resp = pjAuth::init($params)->updateUser();
		self::jsonResponse($resp);
	}
	
	public function pjActionStatusUser()
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
		if (!pjAuth::factory('pjBaseUsers', 'pjActionUpdate')->hasAccess())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Access denied.'));
		}
		$record = $this->_post->toArray('record');
		if (empty($record))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Missing, empty or invalid parameters.'));
		}
		$record = $this->filterCompanyUsers($record);
		if (empty($record))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Missing, empty or invalid parameters.'));
		}
		pjAuthUserModel::factory()
			->whereIn('id', $record)
			->where('id !=', $this->getUserId())
			->where('id !=', 1)
			->modifyAll(array(
				'status' => ":IF(`status`='F','T','F')"
			));
		self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'User status has been updated.'));
	}
	
	public function pjActionDeleteUser()
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
		if ($this->_get->toInt('id') == $this->getUserId() || $this->_get->toInt('id') == 1)
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 104, 'text' => 'Access denied.'));
		}
		$this->assertCompanyUser($this->_get->toInt('id'));
		if (!pjAuthUserModel::factory()->set('id', $this->_get->toInt('id'))->erase()->getAffectedRows())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 105, 'text' => 'User has not been deleted.'));
		}
		
		pjAuthUserPermissionModel::factory()->where('user_id', $this->_get->toInt('id'))->eraseAll();
		
		self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'User has been deleted'));
	}
	
	public function pjActionDeleteUserBulk()
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
		$record = $this->filterCompanyUsers($record);
		if (empty($record))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 104, 'text' => 'Missing, empty or invalid parameters.'));
		}
		pjAuthUserModel::factory()
			->where('id !=', $this->getUserId())
			->where('id !=', 1)
			->whereIn('id', $record)
			->eraseAll();
		
		pjAuthUserPermissionModel::factory()
			->where('id !=', $this->getUserId())
			->where('id !=', 1)
			->whereIn('user_id', $record)
			->eraseAll();
			
		self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'User(s) has been deleted.'));
	}
}
?>