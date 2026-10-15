<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjBasePermissions extends pjBase
{
    /** Administrators have every permission, so they have no per-user permissions. */
    private function isAdminUser($user_id)
    {
        $row = pjAuthUserModel::factory()->select('t1.role_id')->find((int) $user_id)->getData();
        return !empty($row) && (int) $row['role_id'] === 1;
    }

    /** "Company user" users belong to their company only; nobody else (not even an administrator) manages them. */
    private function isCompanyCreatedUser($user_id)
    {
        $row = pjAuthUserModel::factory()->select('t1.role_id')->find((int) $user_id)->getData();
        return !empty($row) && (int) $row['role_id'] === 5;
    }

    /** The logged-in user is a company login. */
    private function isCompanyLogin()
    {
        return (int) $this->getRoleId() === 4;
    }

    /**
     * Who may NOT set the permissions of $user_id here:
     * a company login only its own "Company user" users; everybody else never a Company user.
     */
    private function isBlockedTarget($user_id)
    {
        if ($this->isCompanyLogin())
        {
            $row = pjAuthUserModel::factory()->select('t1.role_id, t1.company_id')->find((int) $user_id)->getData();
            $cid = class_exists('pjCompanyContext') ? (int) pjCompanyContext::activeId($this->getRoleId(), $this->getUserId()) : 0;
            return empty($row) || (int) $row['role_id'] !== 5 || $cid <= 0 || (int) $row['company_id'] !== $cid;
        }
        return $this->isCompanyCreatedUser($user_id);
    }

    /**
     * Permission keys a company may give to its users: the ones the company has itself,
     * without user / permission management and the company's own "My company" page.
     */
    private function givableKeys()
    {
        $mine = $this->session->has($this->defaultPermissions) ? (array) $this->session->getData($this->defaultPermissions) : array();
        $out = array();
        foreach ($mine as $key)
        {
            $key = (string) $key;
            if ((strpos($key, 'pjBaseUsers') === 0 && $key !== 'pjBaseUsers_pjActionProfile')
                || strpos($key, 'pjBasePermissions') === 0
                || $key === 'pjAdminCompanies_pjActionProfile')
            {
                continue;
            }
            $out[$key] = $key;
        }
        return $out;
    }

    /** Keep only the permission tree items in $keys (an item stays when one of its children stays). */
    private function filterTree($arr, $second_level, $third_level, $keys)
    {
        $keep = function ($item) use ($keys) { return isset($keys[$item['key']]); };
        foreach ($third_level as $pid => $items)
        {
            $third_level[$pid] = array_values(array_filter($items, $keep));
            if (empty($third_level[$pid])) { unset($third_level[$pid]); }
        }
        foreach ($second_level as $pid => $items)
        {
            $second_level[$pid] = array_values(array_filter($items, function ($item) use ($keep, $third_level) {
                return $keep($item) || isset($third_level[$item['id']]);
            }));
            if (empty($second_level[$pid])) { unset($second_level[$pid]); }
        }
        $arr = array_filter($arr, function ($item) use ($keep, $second_level) {
            return $keep($item) || isset($second_level[$item['id']]);
        });
        return array($arr, $second_level, $third_level);
    }

    /** After a reset, a company user keeps only what the company may give. */
    private function trimToGivable($user_id)
    {
        $keys = $this->givableKeys();
        $ids = pjAuthPermissionModel::factory()->findAll()->getDataPair('id', 'key');
        $remove = array();
        foreach ($ids as $id => $key)
        {
            if (!isset($keys[$key])) { $remove[] = (int) $id; }
        }
        if (!empty($remove))
        {
            pjAuthUserPermissionModel::factory()->where('user_id', (int) $user_id)->whereIn('permission_id', $remove)->eraseAll();
        }
    }

    public function pjActionIndex()
    {
        if (!$this->isAdmin())
        {
            $this->sendForbidden();
            return;
        }
        if(self::isGet())
        {
            $this->appendJs('jquery.datagrid.js', PJ_FRAMEWORK_LIBS_PATH . 'pj/js/');
            $this->appendJs('pjBasePermissions.js', $this->getConst('PLUGIN_JS_PATH'));
        }
    }
    
    public function pjActionRolePermission()
    {
        // role permissions are edited by administrators only
        if (!$this->isAdmin())
        {
            $this->sendForbidden();
            return;
        }

        if(self::isGet())
        {
            $role_arr = pjAuth::init()->getRoleList();
            if($this->_get->toInt('id'))
            {
                $role_id = $this->_get->toInt('id');
            }else{
                $role_id = (int) $role_arr[0]['id'];
            }
            $permission_id_arr = pjAuth::init(array('role_id' => $role_id))->getPermissionIdsByRole();
            $response = pjAuth::init()->getPermissions();
            $this->set('arr', $response['arr']);
            $this->set('second_level', $response['second_level']);
            $this->set('third_level', $response['third_level']);
            $this->set('role_arr', $role_arr);
            $this->set('role_id', $role_id);
            $this->set('permission_id_arr', $permission_id_arr);
            $this->appendJs('pjBasePermissions.js', $this->getConst('PLUGIN_JS_PATH'));
        }
    }
    
    public function pjActionUserPermission()
    {
        if (!pjAuth::factory()->hasAccess())
        {
            $this->sendForbidden();
            return;
        }

        if(self::isGet())
        {
            if($this->_get->toInt('id'))
            {
                if (class_exists('pjCompanyContext') && !pjCompanyContext::isAdmin($this->getRoleId(), $this->getUserId()) && !pjCompanyContext::isCompanyUser($this->_get->toInt('id'), pjCompanyContext::activeId($this->getRoleId(), $this->getUserId())))
                {
                    $this->sendForbidden();
                    return;
                }
                if((!$this->isAdmin() && $this->isAdminUser($this->_get->toInt('id'))) || $this->isBlockedTarget($this->_get->toInt('id')))
                {
                    $this->sendForbidden();
                    return;
                }
                $params = array(
                    'controller' => $this->_get->toString('controller'),
                    'action' => $this->_get->toString('action'),
                    'id' => $this->_get->toInt('id'),
                );

                $user = pjAuth::init($params)->getUser();
                if (!$user)
                {
                    pjUtil::redirect(PJ_INSTALL_URL. "index.php?controller=pjBaseUsers&action=pjActionIndex&err=PU08");
                }
                $role_arr = pjAuth::init()->getRoleList();
                $user_id = $this->_get->toInt('id');
                $permission_id_arr = pjAuth::init(array('user_id' => $user_id))->getPermissionIdsByUser();
                if ($this->isCompanyLogin())
                {
                    // never more than the company has itself
                    $this->trimToGivable($user_id);
                    $permission_id_arr = pjAuthUserPermissionModel::factory()->where('t1.user_id', $user_id)->findAll()->getDataPair(null, 'permission_id');
                }
                $response = pjAuth::init()->getPermissions();
                $arr = array();
                if (isset($GLOBALS['CONFIG'], $GLOBALS['CONFIG']["permission_items_order"])
                && is_array($GLOBALS['CONFIG']["permission_items_order"])
                && !empty($GLOBALS['CONFIG']["permission_items_order"]))
                {
                    foreach($GLOBALS['CONFIG']["permission_items_order"] as $k => $key)
                    {
                        foreach($response['arr'] as $v)
                        {
                            if($v['key'] == $key)
                            {
                                $arr[$k] = $v;
                            }
                        }
                    }
                }else{
                    $arr = $response['arr'];
                }
                $second_level = $response['second_level'];
                $third_level = $response['third_level'];
                if ($this->isCompanyLogin())
                {
                    // a company sees only the permissions it has itself
                    list($arr, $second_level, $third_level) = $this->filterTree($arr, $second_level, $third_level, $this->givableKeys());
                }
                $this->set('arr', $arr);
                $this->set('second_level', $second_level);
                $this->set('third_level', $third_level);
                $this->set('role_arr', $role_arr);
                $this->set('user', $user);
                $this->set('permission_id_arr', $permission_id_arr);
                $this->appendJs('pjBasePermissions.js', $this->getConst('PLUGIN_JS_PATH'));
            }else{
                pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjBaseUsers&action=pjActionIndex&err=PU05");
            }
        }
    }
    
    public function pjActionAjaxSet()
    {
        $this->setAjax(true);
        
        if ($this->isXHR())
        {
            if (!self::isPost())
            {
                self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'HTTP method not allowed.'));
            }
            if (!pjAuth::factory()->hasAccess())
            {
                self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Access denied!'));
            }
            $data = array();
            $data['type'] = $this->_post->toString('type');
            $data['permission_id'] = $this->_post->toInt('permission_id');

            if($this->_post->toInt('role_id'))
            {
                if (!$this->isAdmin())
                {
                    self::jsonResponse(array('status' => 'ERR', 'code' => 403, 'text' => 'Access denied.'));
                }
                $data['role_id'] = $this->_post->toInt('role_id');

                self::jsonResponse(pjAuth::init($data)->setRolePermission());
            }
            if($this->_post->toInt('user_id'))
            {
                if (class_exists('pjCompanyContext') && !pjCompanyContext::isAdmin($this->getRoleId(), $this->getUserId()) && !pjCompanyContext::isCompanyUser($this->_post->toInt('user_id'), pjCompanyContext::activeId($this->getRoleId(), $this->getUserId())))
                {
                    self::jsonResponse(array('status' => 'ERR', 'code' => 403, 'text' => 'Access denied.'));
                }
                if($this->isAdminUser($this->_post->toInt('user_id')) || $this->isBlockedTarget($this->_post->toInt('user_id')))
                {
                    self::jsonResponse(array('status' => 'ERR', 'code' => '100'));
                }
                if (!$this->isAdmin())
                {
                    // a non-administrator never changes his own rights and can only pass on
                    // permissions he holds himself
                    if ($this->_post->toInt('user_id') === (int) $this->getUserId())
                    {
                        self::jsonResponse(array('status' => 'ERR', 'code' => 403, 'text' => 'Access denied.'));
                    }
                    if ($data['type'] === 'insert')
                    {
                        $perm = pjAuthPermissionModel::factory()->find($data['permission_id'])->getData();
                        $mine = $this->isCompanyLogin()
                            ? array_values($this->givableKeys())
                            : ($this->session->has($this->defaultPermissions) ? (array) $this->session->getData($this->defaultPermissions) : array());
                        if (empty($perm) || !in_array($perm['key'], $mine, true))
                        {
                            self::jsonResponse(array('status' => 'ERR', 'code' => 403, 'text' => 'Access denied.'));
                        }
                    }
                }
                
                $data['user_id'] = $this->_post->toInt('user_id');

                self::jsonResponse(pjAuth::init($data)->setUserPermission());
            }
        }
        exit;
    }

    public function pjActionResetPermission()
	{
		$this->setAjax(true);

		if ($this->isXHR())
		{
		    if (!self::isPost())
            {
                self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'HTTP method not allowed.'));
            }
            if(!pjAuth::factory('pjBasePermissions', 'pjActionUserPermission')->hasAccess())
            {
                self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Access denied!'));
            }
            if($this->isAdminUser($this->_post->toInt('user_id')) || $this->isBlockedTarget($this->_post->toInt('user_id')))
            {
                self::jsonResponse(array('status' => 'ERR', 'code' => '100'));
            }
            if (class_exists('pjCompanyContext') && !pjCompanyContext::isAdmin($this->getRoleId(), $this->getUserId()) && !pjCompanyContext::isCompanyUser($this->_post->toInt('user_id'), pjCompanyContext::activeId($this->getRoleId(), $this->getUserId())))
            {
                self::jsonResponse(array('status' => 'ERR', 'code' => 403, 'text' => 'Access denied.'));
            }
            $response = pjAuth::init(array('user_id' => $this->_post->toInt('user_id')))->resetUserPermission();
            if ($this->isCompanyLogin() && isset($response['status']) && $response['status'] === 'OK')
            {
                $this->trimToGivable($this->_post->toInt('user_id'));
            }
            self::jsonResponse($response);
		}
	}
}
?>