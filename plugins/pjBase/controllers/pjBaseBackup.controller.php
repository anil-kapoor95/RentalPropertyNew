<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjBaseBackup extends pjBase
{
    /* ---- company-wise backups ------------------------------------------------ */

    private function hasCompanies()
    {
        return class_exists('pjCompanyContext') && class_exists('pjCompanyBackup');
    }

    private function backupCompanyId()
    {
        return $this->hasCompanies() ? (int) pjCompanyContext::activeId($this->getRoleId(), $this->getUserId()) : 0;
    }

    /** Only administrators may create / see backups of the whole system (all companies). */
    private function canSystemBackup()
    {
        return !$this->hasCompanies() || pjCompanyContext::isAdmin($this->getRoleId(), $this->getUserId());
    }

    /**
     * Resolve a backup id from the grid to a file path, or false.
     * Company backups: "company-<hash>/<name>" (active company only); system backups: "<name>" (admins only).
     */
    private function resolveBackupFile($id)
    {
        $id = (string) $id;
        if (!preg_match('~^(?:company-([a-f0-9]{32})/)?((?:database|files)-backup-\d{10}\.(?:sql|zip))$~', $id, $m))
        {
            return false;
        }
        if ($m[1] !== '')
        {
            if (pjCompanyContext::idFromHash($m[1]) !== $this->backupCompanyId() || $this->backupCompanyId() <= 0)
            {
                return false;
            }
            $file = PJ_WEB_PATH . 'backup/company-' . $m[1] . '/' . $m[2];
        } else {
            if (!$this->canSystemBackup())
            {
                return false;
            }
            $file = PJ_WEB_PATH . 'backup/' . $m[2];
        }
        clearstatcache();
        return is_file($file) ? $file : false;
    }

    public function pjActionIndex()
    {
    	if (!pjAuth::factory()->hasAccess())
        {
            $this->sendForbidden();
            return;
        }

        if(self::isGet())
        {
            $this->appendJs('jquery.datagrid.js', PJ_FRAMEWORK_LIBS_PATH . 'pj/js/');
            $this->appendJs('pjBaseBackup.js', $this->getConst('PLUGIN_JS_PATH'));
        }
    }

    public function pjActionBackup()
    {
    	if (!pjAuth::factory()->hasAccess())
        {
            $this->sendForbidden();
            return;
        }

        if (self::isPost() && $this->_post->check('backup'))
        {
            $backup_folder = PJ_WEB_PATH . 'backup/';
            if (!is_dir($backup_folder))
            {
                pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjBaseBackup&action=pjActionIndex&err=PBU05");
            }
            if (!is_writable($backup_folder))
            {
                pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjBaseBackup&action=pjActionIndex&err=PBU06");
            }
            if (!$this->_post->check('backup_database') && !$this->_post->check('backup_files'))
            {
                pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjBaseBackup&action=pjActionIndex&err=PBU03");
            }

            @set_time_limit(600);
            $err = 'PBU04';

            // Company-wise backup (default). The whole system is backed up only when an admin asks for it.
            $company_id = $this->backupCompanyId();
            $system = !$this->hasCompanies() || ($this->_post->toString('backup_scope') === 'system' && $this->canSystemBackup());
            if (!$system)
            {
                if ($company_id <= 0)
                {
                    pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjBaseBackup&action=pjActionIndex&err=PBU04");
                }
                $folder = pjCompanyBackup::folder($company_id);
                $cb = new pjCompanyBackup($company_id);
                $ts = time();
                if ($this->_post->check('backup_database') && !$cb->exportSql($folder . 'database-backup-' . $ts . '.sql'))
                {
                    pjUtil::redirect(sprintf("%sindex.php?controller=pjBaseBackup&action=pjActionIndex&err=%s", PJ_INSTALL_URL, 'PBU07'));
                }
                if ($this->_post->check('backup_files') && !$cb->exportFiles($folder . 'files-backup-' . $ts . '.zip'))
                {
                    pjUtil::redirect(sprintf("%sindex.php?controller=pjBaseBackup&action=pjActionIndex&err=%s", PJ_INSTALL_URL, 'PBU08'));
                }
                pjUtil::redirect(sprintf("%sindex.php?controller=pjBaseBackup&action=pjActionIndex&err=%s", PJ_INSTALL_URL, 'PBU01'));
            }
            if ($this->hasCompanies())
            {
                pjCompanyBackup::protect(PJ_WEB_PATH . 'backup/');
            }

            if ($this->_post->check('backup_database'))
            {
                $app_models = array();
                pjToolkit::readDir($app_models, PJ_MODELS_PATH);

                $plugin_models = array();
                pjToolkit::readDir($plugin_models, PJ_PLUGINS_PATH);

                $sql = array();

                $err = 'PBU01';
                $file_name = PJ_WEB_PATH . 'backup/database-backup-'.time().'.sql';
                if($this->pjActionWriteSql($app_models, $file_name) == true)
                {
                    if($this->pjActionWriteSql($plugin_models, $file_name, true) == false)
                    {
                        pjUtil::redirect(sprintf("%sindex.php?controller=pjBaseBackup&action=pjActionIndex&err=%s", PJ_INSTALL_URL, 'PBU07'));
                    }
                }else{
                    pjUtil::redirect(sprintf("%sindex.php?controller=pjBaseBackup&action=pjActionIndex&err=%s", PJ_INSTALL_URL, 'PBU07'));
                }
            }
            if ($this->_post->check('backup_files'))
            {
                $files = array();
                pjToolkit::readDir($files, PJ_UPLOAD_PATH);

                $zipName = 'files-backup-'.time().'.zip';
                $zip = new pjZipStream();
                $zip->setZipFile(PJ_WEB_PATH . 'backup/' . $zipName);

                $backup_file = true;
                foreach ($files as $file)
                {
                    $handle = @fopen($file, "rb");
                    if ($handle !== false)
                    {
                        $buffer = "";
                        while (!feof($handle))
                        {
                            $buffer .= fgets($handle, 4096);
                        }
                        $zip->addFile($buffer, $file);
                        fclose($handle);
                    }else{
                        $backup_file = false;
                        break;
                    }
                }
                $zip->finalize();

                $err = 'PBU01';
                if($backup_file == false)
                {
                    pjUtil::redirect(sprintf("%sindex.php?controller=pjBaseBackup&action=pjActionIndex&err=%s", PJ_INSTALL_URL, 'PBU08'));
                }
            }

            pjUtil::redirect(sprintf("%sindex.php?controller=pjBaseBackup&action=pjActionIndex&err=%s", PJ_INSTALL_URL, $err));
        }
    }
    private function pjActionWriteSql($files, $sql_file, $is_plugin=FALSE)
    {
        $sql = array();
        foreach ($files as $filepath)
        {
            $filename = basename($filepath);
            if (preg_match('/^(\w+)\.model\.php$/', $filename, $matches))
            {
                $modelName = $matches[1] . 'Model';
                if ($is_plugin)
                {
                    if (!preg_match('/\/(\w+)\/models\/(\w+)\.model\.php$/', $filepath, $m) || !in_array($m[1], @$GLOBALS['CONFIG']['plugins']))
                    {
                        continue;
                    }
                }
                $model = new $modelName;
                
                $schema = $model->getSchema();
                if (empty($schema))
                {
                    continue;
                }
                
                $table = $model->getTable();
                if ($table == PJ_PREFIX . PJ_SCRIPT_PREFIX)
                {
                    continue;
                }
                
                $fields = array();
                $columns = array();
                $schema_index = array();
                
                foreach ($schema as $col)
                {
                    if ($col['type'] != 'blob')
                    {
                        $fields[] = sprintf("`%s`", $col['name']);
                    } else {
                        $fields[] = sprintf("LOWER(HEX(`%1\$s`)) AS `%1\$s`", $col['name']);
                    }
                    $columns[] = $col['name'];
                    $schema_index[$col['name']] = $col;
                }
                
                $result = $model->reset()->select(join(", ", $fields))->findAll()->getData();
                $sql[] = sprintf("DROP TABLE IF EXISTS `%s`;\n\n", $table);
                
                $create = $model->reset()->prepare(sprintf("SHOW CREATE TABLE `%s`", $table))->exec()->getData();
                $create = array_values($create[0]);
                $sql[] = sprintf("%s;\n\n", $create[1]);
                
                foreach ($result as $row)
                {
                    $sql[] = sprintf("INSERT INTO `%s` (`%s`) VALUES(", $table, join('`, `', $columns));
                    $insert = array();
                    foreach ($row as $key => $val)
                    {
                        if (isset($schema_index[$key], $schema_index[$key]['type']) && $schema_index[$key]['type'] == 'blob')
                        {
                            $insert[] = "'" . $val . "'";
                        } else {
                            if (isset($schema_index[$key], $schema_index[$key]['default'])
                                && $val == '')
                            {
                                $insert[] = strpos($schema_index[$key]['default'], ':') === 0
                                ? substr($schema_index[$key]['default'], 1)
                                : "'" . $schema_index[$key]['default'] . "'";
                            } else {
                                $val = str_replace('\n', '\r\n', $val);
                                $val = preg_replace("/\r\n/", '\r\n', $val);
                                $insert[] = "'" . str_replace("'", "''", stripslashes($val)) . "'";
                            }
                        }
                    }
                    $sql[] = join(", ", $insert);
                    $sql[] = ");\n";
                }
                $sql[] = "\n";
            }
            $content = join("", $sql);
            if (mb_strlen($content) >  $this->getConst('PLUGIN_BACKUP_DB_CHUNK') * 1000) 
            {
                if (($handle = fopen($sql_file, "a")) !==false) 
                {
                    fwrite($handle, $content);
                    fclose($handle);
                    $sql = array();
                    $content = '';
                }else{
                    @unlink($sql_file);
                    return false;
                }
            }
        }
        if(!empty($sql))
        {
            $content = join("", $sql);
            if (($handle = fopen($sql_file, "a")) !==false)
            {
                fwrite($handle, $content);
                fclose($handle);
            }else{
                @unlink($sql_file);
                return false;
            }
        }
        return true;
    }
    public function pjActionGet()
    {
		// Same permission as the page this list/action belongs to (the plugin does not check it otherwise).
		if ($this->isLoged() && !pjAuth::factory('pjBaseBackup')->hasAccess())
		{
			if ($this->isXHR()) { self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.')); }
			$this->sendForbidden();
			return;
		}
        $this->setAjax(true);
        
        if ($this->isXHR())
        {
            $column = 'created';
            $direction = 'DESC';
            if (in_array(strtoupper($this->_get->toString('direction')), array('ASC', 'DESC')))
            {
                $column = $this->_get->toString('column');
                $direction = strtoupper($this->_get->toString('direction'));
            }
            
            $data = $id = $created = $type = $size = array();
            // Folders to list: the active company's backups, plus the full-system backups for admins.
            $folders = array();
            $company_id = $this->backupCompanyId();
            if ($this->hasCompanies() && $company_id > 0)
            {
                pjCompanyBackup::folder($company_id); // creates / migrates the folder
                $folders['company-' . pjCompanyContext::hashOf($company_id) . '/'] = ' - ' . __('lblBackupScopeCompany', true);
            }
            if ($this->canSystemBackup())
            {
                $folders[''] = $this->hasCompanies() ? ' - ' . __('lblBackupScopeSystem', true) : '';
            }
            $i = 0;
            foreach ($folders as $sub => $suffix)
            {
                if (!is_dir(PJ_WEB_PATH . 'backup/' . $sub) || !($handle = opendir(PJ_WEB_PATH . 'backup/' . $sub)))
                {
                    continue;
                }
                while (false !== ($entry = readdir($handle)))
                {
                    if (!preg_match('/^(database-backup|files-backup)-(\d{10})\.(sql|zip)$/', $entry, $m))
                    {
                        continue;
                    }
                    $id[$i] = $sub . $entry;
                    $created[$i] = $m[2];
                    $type[$i] = ($m[1] == 'database-backup' ? __('plugin_backup_types_ARRAY_database', true) : __('plugin_backup_types_ARRAY_files', true)) . $suffix;
                    $data[$i]['id'] = $id[$i];
                    $data[$i]['created'] = $created[$i];
                    $data[$i]['type'] = $type[$i];
                    $size[$i] = filesize(PJ_WEB_PATH . 'backup/' . $sub . $entry);
                    $data[$i]['size'] = $size[$i];
                    $i++;
                }
                closedir($handle);
            }
            
            switch ($column)
            {
                case 'created':
                    array_multisort($created, $direction == 'ASC' ? SORT_ASC : SORT_DESC, $id, SORT_DESC, $type, SORT_ASC, $data);
                    break;
                case 'type':
                    array_multisort($type, $direction == 'ASC' ? SORT_ASC : SORT_DESC, $id, SORT_DESC, $created, SORT_DESC, $data);
                    break;
                case 'id':
                    array_multisort($id, $direction == 'ASC' ? SORT_ASC : SORT_DESC, $type, SORT_ASC, $created, SORT_DESC, $data);
                    break;
                case 'size':
                    array_multisort($size, $direction == 'ASC' ? SORT_ASC : SORT_DESC, $id, SORT_DESC, $type, SORT_ASC, $data);
                    break;
            }
            
            $total = count($data);
            $rowCount = $this->_get->toInt('rowCount') > 0 ? $this->_get->toInt('rowCount') : 10;
            $pages = ceil($total / $rowCount);
            $page = $this->_get->toInt('page') > 0 ? $this->_get->toInt('page') : 1;
            $offset = ((int) $page - 1) * $rowCount;
            if ($page > $pages)
            {
                $page = $pages;
            }
            foreach ($data as $k => $v)
            {
                $v['size'] = $this->formatSizeUnits($v['size']);
                $created = date($this->option_arr['o_date_format'] . ", H:i", $v['created']);
                if(isset($this->option_arr['o_time_format']) && !empty($this->option_arr['o_time_format']))
                {
                    $created = date($this->option_arr['o_date_format'] . ", " . $this->option_arr['o_time_format'], $v['created']);
                }
                $v['created'] = $created;
                $data[$k] = $v;
            }
            
            $data = array_slice($data, $offset, $rowCount);
            
            self::jsonResponse(compact('data', 'total', 'pages', 'page', 'rowCount', 'column', 'direction'));
        }
        exit;
    }
    
    public function pjActionDelete()
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
                self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.'));
            }
            if ($this->_get->toInt('id') > 0)
            {
                self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Missing, empty or invalid parameters.'));
            }
            $file = $this->resolveBackupFile($this->_get->toString('id'));
            if ($file !== false)
            {
                @unlink($file);
                pjAppController::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'File has been deleted.'));
            } else {
                pjAppController::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'File not found.'));
            }
        }
        exit;
    }
    
    public function pjActionDeleteBulk()
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
                self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.'));
            }
            $record = $this->_post->toArray('record');
            if (empty($record))
            {
                self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Missing, empty or invalid parameters.'));
            }
            
            foreach ($record as $item)
            {
                $file = $this->resolveBackupFile($item);
                if ($file !== false)
                {
                    @unlink($file);
                }
            }
            pjAppController::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Delete operation complete.'));
        }
        exit;
    }
    
    public function pjActionDownload()
    {
    	if (!pjAuth::factory()->hasAccess())
        {
            $this->sendForbidden();
            return;
        }

        $this->setAjax(true);

        if ($this->_get->toString('id'))
        {
            $file = $this->resolveBackupFile($this->_get->toString('id'));
            $id = $file !== false ? basename($file) : '';
            $buffer = "";
            if ($file !== false)
            {
                $handle = @fopen($file, "rb");
                if ($handle)
                {
                    while (!feof($handle))
                    {
                        $buffer .= fgets($handle, 4096);
                    }
                    fclose($handle);
                }
                pjToolkit::download($buffer, $id);
                exit;
            }
            self::jsonResponse(array('status' => 'ERR', 'text' => 'File not found.'));
        }
        self::jsonResponse(array('status' => 'ERR', 'text' => 'Missing or empty parameters.'));
        exit;
    }
    
    public function pjActionSetBackup()
    {
		// Same permission as the page this list/action belongs to (the plugin does not check it otherwise).
		if ($this->isLoged() && !pjAuth::factory('pjBaseBackup')->hasAccess())
		{
			if ($this->isXHR()) { self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Access denied.')); }
			$this->sendForbidden();
			return;
		}
        $this->setAjax(true);
        if ($this->isXHR())
        {
            if (!self::isPost())
            {
                self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'HTTP method not allowed.'));
            }
            if(!in_array($this->_post->toInt('set'), array(0, 1)))
            {
                self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Missing, empty or invalid parameters.'));
            }
            $set = $this->_post->toInt('set') == 1 ? 'Yes' : 'No';
            // In a company the switch turns that company's automatic backup on or off
            // (its own cron row); the system-wide switch belongs to administrators.
            $cid = $this->backupCompanyId();
            if ($cid > 0 && $this->_post->toString('scope') !== 'system')
            {
                $job = pjBaseCronJobModel::factory()->where('t1.controller', 'pjBaseBackup')->where('t1.action', 'pjActionAutoBackup')->limit(1)->findAll()->getDataIndex(0);
                if (!empty($job))
                {
                    pjCompanyContext::saveCronRow($cid, $job['id'], array('is_active' => $this->_post->toInt('set') == 1 ? 1 : 0));
                }
            }
            // The system-wide automatic backup (all companies) is switched only without a
            // company, or explicitly (scope=system) by an administrator.
            if ($cid <= 0 || ($this->_post->toString('scope') === 'system' && $this->canSystemBackup()))
            {
                pjBaseOptionModel::factory()
                    ->where('foreign_id', $this->getForeignId())
                    ->where('`key`', 'o_auto_backup')
                    ->limit(1)
                    ->modifyAll(array('value' => "Yes|No::{$set}"));
                pjBaseCronJobModel::factory()->setIsActive($this->_post->toInt('set'), 'pjBaseBackup', 'pjActionAutoBackup');
            }
            
            self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => ''));
            exit;
        }
    }
    
    private function pjActionLoop(&$sql, $files, $is_plugin=FALSE)
    {
        foreach ($files as $filepath)
        {
            $filename = basename($filepath);
            if (preg_match('/^(\w+)\.model\.php$/', $filename, $matches))
            {
                $modelName = $matches[1] . 'Model';
                if ($is_plugin)
                {
                    if (!preg_match('/\/(\w+)\/models\/(\w+)\.model\.php$/', $filepath, $m) || !in_array($m[1], @$GLOBALS['CONFIG']['plugins']))
                    {
                        continue;
                    }
                }
                $model = new $modelName;
                
                $schema = $model->getSchema();
                if (empty($schema))
                {
                    continue;
                }
                
                $table = $model->getTable();
                if ($table == PJ_PREFIX . PJ_SCRIPT_PREFIX)
                {
                    continue;
                }
                
                $fields = array();
                $columns = array();
                $schema_index = array();
                
                foreach ($schema as $col)
                {
                    if ($col['type'] != 'blob')
                    {
                        $fields[] = sprintf("`%s`", $col['name']);
                    } else {
                        $fields[] = sprintf("LOWER(HEX(`%1\$s`)) AS `%1\$s`", $col['name']);
                    }
                    $columns[] = $col['name'];
                    $schema_index[$col['name']] = $col;
                }
                
                $result = $model->reset()->select(join(", ", $fields))->findAll()->getData();
                $sql[] = sprintf("DROP TABLE IF EXISTS `%s`;\n\n", $table);
                
                $create = $model->reset()->prepare(sprintf("SHOW CREATE TABLE `%s`", $table))->exec()->getData();
                $create = array_values($create[0]);
                $sql[] = sprintf("%s;\n\n", $create[1]);
                
                foreach ($result as $row)
                {
                    $sql[] = sprintf("INSERT INTO `%s` (`%s`) VALUES(", $table, join('`, `', $columns));
                    $insert = array();
                    foreach ($row as $key => $val)
                    {
                        if (isset($schema_index[$key], $schema_index[$key]['type']) && $schema_index[$key]['type'] == 'blob')
                        {
                            $insert[] = '0x' . $val;
                        } else {
                            if (isset($schema_index[$key], $schema_index[$key]['default'])
                                && $val == '')
                            {
                                $insert[] = strpos($schema_index[$key]['default'], ':') === 0
                                ? substr($schema_index[$key]['default'], 1)
                                : "'" . $schema_index[$key]['default'] . "'";
                            } else {
                                $val = str_replace('\n', '\r\n', $val);
                                $val = preg_replace("/\r\n/", '\r\n', $val);
                                $insert[] = "'" . str_replace("'", "''", $val) . "'";
                            }
                        }
                    }
                    $sql[] = join(", ", $insert);
                    $sql[] = ");\n";
                }
                $sql[] = "\n";
            }
        }
    }
    
    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }elseif ($bytes >= 1048576){
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }else{
            $bytes = '0 bytes';
        }
        
        return $bytes;
    }

    /**
     * Cron-job for creating back-up files if auto-backup option is enabled.
     *
     * @return string
     */
    public function pjActionAutoBackup()
	{
		$this->setLayout('pjActionEmpty');

		// A company run is governed by the company's own switch (its cron row, checked
		// by the cron runner); the system run by the system-wide option.
		$cron_company_run = $this->hasCompanies() && (int) pjCompanyContext::$cronCompanyId > 0;
		if ($cron_company_run || $this->option_arr['o_auto_backup'] == 'Yes')
		{
		    $titles = __('plugin_base_error_titles', true);
    	    $bodies = __('plugin_base_error_bodies', true);

		    $backup_folder = PJ_WEB_PATH . 'backup/';
            if (!is_dir($backup_folder))
            {
                return $titles['PBU05'] . ' ' . $bodies['PBU05'];
            }
            if (!is_writable($backup_folder))
            {
                return $titles['PBU06'] . ' ' . $bodies['PBU06'];
            }

            $code = 'PBU01';

            // Cron job running for one company: back up that company only.
            $cron_company = $this->hasCompanies() ? (int) pjCompanyContext::$cronCompanyId : 0;
            if ($cron_company > 0)
            {
                pjCompanyBackup::protect($backup_folder);
                $ts = time();
                $folder = pjCompanyBackup::folder($cron_company);
                $cb = new pjCompanyBackup($cron_company);
                $cb->exportSql($folder . 'database-backup-' . $ts . '.sql');
                $cb->exportFiles($folder . 'files-backup-' . $ts . '.zip');
                return $titles[$code] . ' ' . $bodies[$code];
            }

            $app_models = array();
            pjToolkit::readDir($app_models, PJ_MODELS_PATH);

            $plugin_models = array();
            pjToolkit::readDir($plugin_models, PJ_PLUGINS_PATH);

            $sql = array();
            
            $file_name = PJ_WEB_PATH . 'backup/database-backup-'.time().'.sql';
            if($this->pjActionWriteSql($app_models, $file_name) == true)
            {
                if($this->pjActionWriteSql($plugin_models, $file_name, true) == false)
                {
                    return $titles['PBU07'] . ' ' . $bodies['PBU07'];
                }
            }else{
                return $titles['PBU07'] . ' ' . $bodies['PBU07'];
            }

            $files = array();
            pjToolkit::readDir($files, PJ_UPLOAD_PATH);

            $zipName = 'files-backup-'.time().'.zip';
            $zip = new pjZipStream();
            $zip->setZipFile(PJ_WEB_PATH . 'backup/' . $zipName);

            $backup_file = true;
            foreach ($files as $file)
            {
                $handle = @fopen($file, "rb");
                if ($handle !== false)
                {
                    $buffer = "";
                    while (!feof($handle))
                    {
                        $buffer .= fgets($handle, 4096);
                    }
                    $zip->addFile($buffer, $file);
                    fclose($handle);
                }else{
                    $backup_file = false;
                    break;
                }
            }
            $zip->finalize();
            if($backup_file == false)
            {
                return $titles['PBU08'] . ' ' . $bodies['PBU08'];
            }

            // One backup (database + files) per company as well.
            if ($this->hasCompanies())
            {
                pjCompanyBackup::protect($backup_folder);
                $ts = time();
                foreach (pjCompanyModel::factory()->findAll()->getDataPair(null, 'id') as $cid)
                {
                    $folder = pjCompanyBackup::folder($cid);
                    $cb = new pjCompanyBackup($cid);
                    $cb->exportSql($folder . 'database-backup-' . $ts . '.sql');
                    $cb->exportFiles($folder . 'files-backup-' . $ts . '.zip');
                }
            }
            return $titles[$code] . ' ' . $bodies[$code];
		}

		return "Automatic back-ups are disabled.";
	}
}
?>