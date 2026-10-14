<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjBaseCron extends pjBase
{
    public function pjActionIndex()
    {
    	if (!pjAuth::factory()->hasAccess())
        {
            $this->sendForbidden();
            return;
        }

        $arr = pjBaseCronJobModel::factory()->findAll()->getData();

        // Company: its own on/off switch, next run, last run and result for every job.
        $cid = $this->cronCompany();
        if ($cid > 0)
        {
            $rows = pjCompanyContext::companyCronRows($cid);
            foreach ($arr as $k => $job)
            {
                $row = isset($rows[$job['id']]) ? $rows[$job['id']] : array();
                $arr[$k]['system_active'] = (int) $job['is_active'];
                $arr[$k]['is_active'] = (int) $job['is_active'] && !empty($row['is_active']) ? 1 : 0;
                $arr[$k]['company_active'] = !empty($row['is_active']) ? 1 : 0;
                $arr[$k]['next_run'] = isset($row['next_run']) ? $row['next_run'] : null;
                $arr[$k]['last_run'] = isset($row['last_run']) ? $row['last_run'] : null;
                $arr[$k]['status'] = isset($row['status']) ? $row['status'] : null;
            }
        }
        $this->set('company_cron', $cid > 0);

        $this->set('arr', $arr);
        $this->set('has_execute', pjAuth::factory('pjBaseCron', 'pjActionExecute')->hasAccess());
        
        $this->appendJs('pjBaseCron.js', $this->getConst('PLUGIN_JS_PATH'));
    }

    public function pjActionRun()
	{
	    $arr = pjBaseCronJobModel::factory()->where('t1.is_active', 1)->findAll()->getData();
	    $companies = $this->hasCompanyCron() ? pjCompanyModel::factory()->where('t1.status', 'T')->findAll()->getDataPair(null, 'id') : array();
	    foreach($arr as $job)
	    {
	        if (!empty($companies))
	        {
	            // Every company on its own schedule (its switch, next run, result).
	            foreach ($companies as $cid)
	            {
	                $rows = pjCompanyContext::companyCronRows($cid);
	                $row = isset($rows[$job['id']]) ? $rows[$job['id']] : null;
	                if ($row && (int) $row['is_active'] === 1 && (empty($row['next_run']) || time() >= strtotime($row['next_run'])))
	                {
	                    $this->executeCronJob($job['id'], (int) $cid);
	                }
	            }
	            // The system run on the job's own schedule: work no company covers
	            // (full system backup, properties without an active company).
	            if (empty($job['next_run']) || time() >= strtotime($job['next_run']))
	            {
	                $this->executeCronJob($job['id'], -1);
	            }
	            continue;
	        }
	        if(empty($job['next_run']) || time() >= strtotime($job['next_run']))
	        {
                $this->executeCronJob($job['id']);
	        }
	    }
	    exit;
	}

	public function pjActionExecute()
	{
	    $this->setAjax(true);
	    
	    if (!pjAuth::factory()->hasAccess())
	    {
	    	self::jsonResponse(array('status' => 'ERR', 'code' => 104, 'text' => 'Access denied.'));
	    }
	    
	    if (!$this->isXHR())
	    {
	    	self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Missing headers.'));
	    }
	    
	    if (!self::isPost())
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'HTTP method not allowed.'));
	    }
	    
	    if (!$this->_post->toInt('id'))
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Missing, empty or invalid parameters.'));
	    }
	    
	    $cid = $this->cronCompany();
	    if ($cid > 0)
	    {
	        $rows = pjCompanyContext::companyCronRows($cid);
	        if (empty($rows[$this->_post->toInt('id')]['is_active']))
	        {
	            self::jsonResponse(array('status' => 'ERR', 'code' => 105, 'text' => 'Cron job is not active for this company.'));
	        }
	    }
	    $success = $this->executeCronJob($this->_post->toInt('id'), $cid);
	    if ($success)
        {
            self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Cron job has been executed.'));
        }
        
		self::jsonResponse(array('status' => 'OK', 'code' => 103, 'text' => 'Cron job not found.'));
	}

	protected function executeCronJob($id, $company_id = 0)
	{
	    $pjBaseCronJobModel = pjBaseCronJobModel::factory();
	    $job = $pjBaseCronJobModel->find($id)->getData();

	    if ($job)
        {
            $controller = $job['controller'];
            $action     = $job['action'];

            if (class_exists($controller))
            {
                // The job works for this company only (0 = all companies / system).
                if (class_exists('pjCompanyContext'))
                {
                    pjCompanyContext::$cronCompanyId = (int) $company_id;
                }
                $controller = $controller::init();
                if(method_exists($controller, $action))
                {
                    $status = $controller->$action();
                    if (class_exists('pjCompanyContext'))
                    {
                        pjCompanyContext::$cronCompanyId = 0;
                    }

                    if ((int) $company_id > 0)
                    {
                        if (!empty($company_id) && $this->hasCompanyCron())
                        {
                            $row = pjCompanyContext::companyCronRows($company_id);
                            pjCompanyContext::saveCronRow($company_id, $job['id'], array(
                                'last_run' => ':NOW()',
                                'next_run' => $this->getNextRunDate(array_merge($job, array('last_run' => @$row[$job['id']]['last_run'])), time()),
                                'status'   => $status
                            ));
                        }
                        // the system row shows when the job last ran for any company
                        $pjBaseCronJobModel->reset()->set('id', $job['id'])->modify(array('last_run' => ':NOW()'));
                        return true;
                    }

                    $pjBaseCronJobModel->reset()->set('id', $job['id'])->modify(array(
                        'last_run' => ':NOW()',
                        'next_run' => $this->getNextRunDate($job, time()),
                        'status'   => $status
                    ));

                    return true;
                }
            }
        }

	    return false;
	}

	/** Companies are installed (per-company cron rows can be used). */
	private function hasCompanyCron()
	{
	    return class_exists('pjCompanyContext') && class_exists('pjCompanyCronModel') && class_exists('pjCompanyModel');
	}

	/** Active company of the logged-in user (0 = none). */
	private function cronCompany()
	{
	    if (!$this->hasCompanyCron() || !$this->isLoged())
	    {
	        return 0;
	    }
	    return (int) pjCompanyContext::activeId($this->getRoleId(), $this->getUserId());
	}

	/** Company: switch a job on or off for the active company only. */
	public function pjActionToggle()
	{
	    $this->setAjax(true);
	    if (!$this->isXHR() || !self::isPost())
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Invalid request.'));
	    }
	    if (!pjAuth::factory('pjBaseCron', 'pjActionExecute')->hasAccess() && !pjAuth::factory('pjBaseCron')->hasAccess())
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 104, 'text' => 'Access denied.'));
	    }
	    $cid = $this->cronCompany();
	    $rows = $cid > 0 ? pjCompanyContext::companyCronRows($cid) : array();
	    if ($cid <= 0 || !isset($rows[$this->_post->toInt('id')]))
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Cron job not found.'));
	    }
	    $active = (int) $rows[$this->_post->toInt('id')]['is_active'] === 1 ? 0 : 1;
	    pjCompanyContext::saveCronRow($cid, $this->_post->toInt('id'), array('is_active' => $active));
	    self::jsonResponse(array('status' => 'OK', 'code' => 200, 'is_active' => $active));
	}

	private function getNextRunDate($job, $last_run_ts=null)
    {
		if ($last_run_ts == null)
		{
        	$last_run_ts = !empty($job['last_run'])? strtotime($job['last_run']): time();
		}
        $next_ts = strtotime(date('Y-m-d', $last_run_ts) . ' ' . $this->option_arr['o_cron_start_time']);

        $interval = 0;
        $units = (int) $job['interval'];
        switch ($job['period'])
        {
            case 'minute':
                $interval = $units * 60;
                break;
            case 'hour':
                $interval = $units * 60 * 60;
                break;
            case 'day':
                $interval = $units * 60 * 60 * 24;
                break;
            case 'week':
                $interval = $units * 60 * 60 * 24 * 7;
                break;
            case 'month':
                $interval = $units * 60 * 60 * 24 * 30;
                break;
        }

        if ($interval)
        {
            while ($last_run_ts > $next_ts)
            {
                $next_ts += $interval;
            }
        }

        return date('Y-m-d H:i:s', $next_ts);
    }
}
?>