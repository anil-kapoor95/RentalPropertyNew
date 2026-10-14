<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjBaseOptions extends pjBase
{
	/** Option tabs saved by each System Options page (the form posts its page as next_action). */
	private static $pageTabs = array(
		'pjActionIndex' => array(1),
		'pjActionVisual' => array(2),
		'pjActionEmailSettings' => array(3),
		'pjActionLoginProtection' => array(4, 5, 6, 7),
		'pjActionCaptchaSpam' => array(8),
		'pjActionApiKeys' => array(10),
	);

	/** Stop an Ajax request unless the user may open the given System Options page. */
	private function requirePageAccess($page)
	{
		if (!$this->isLoged() || !pjAuth::factory('pjBaseOptions', $page)->hasAccess())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Access denied.'));
		}
	}

	public function pjActionOptionsUpdate()
	{
		if (self::isPost())
		{
			$this->checkLogin();
			// Saves only the options of the page that was submitted, and only with access to that page.
			$next_action = $this->_post->toString('next_action');
			if (!isset(self::$pageTabs[$next_action]) || !pjAuth::factory('pjBaseOptions', $next_action)->hasAccess())
			{
				$this->sendForbidden();
				return;
			}
			$pjBaseOptionModel = new pjBaseOptionModel();
			// Options belong to the active company; only the admin login-screen keys stay shared.
			$company_fid = $this->getOptionsForeignId();
			$allowed_keys = pjBaseOptionModel::factory()
				->whereIn('t1.foreign_id', array_unique(array((int) $company_fid, 1)))
				->whereIn('t1.tab_id', self::$pageTabs[$next_action])
				->findAll()->getDataPair('key', 'key');
			// The shared (all companies) login-screen keys are changed by administrators only.
			$may_edit_shared = (int) $this->getRoleId() === 1;
			
			foreach ($this->_post->raw() as $key => $value)
			{
				if (preg_match('/value-(string|text|int|float|enum|bool|color)-(.*)/', $key) === 1)
				{
					list(, $type, $k) = explode("-", $key);
					if (!empty($k) && isset($allowed_keys[$k])
						&& ($may_edit_shared || !class_exists('pjCompanyContext') || !in_array($k, pjCompanyContext::$globalOptionKeys, true)))
					{
						$_value = ':NULL';
						if ($value)
						{
							switch ($type)
							{
								case 'string':
								case 'text':
								case 'enum':
								case 'color':
									if (in_array($k, array('o_failed_login_send_sms_message', 'o_failed_login_send_email_message', 'o_secure_login_send_password_to_email_message', 'o_secure_login_send_password_to_sms_message')))
									{
										$_value = $this->_post->toI18n($key);
									}
									else
									{
										$_value = $this->_post->raw($key);
									}
									break;
								case 'int':
								case 'bool':
									$_value = $this->_post->toInt($key);
									break;
								case 'float':
									$_value = $this->_post->toFloat($key);
									break;
							}
						}
				
						$pjBaseOptionModel
							->reset()
							->where('foreign_id', class_exists('pjCompanyContext') ? pjCompanyContext::fidForKey($k, $company_fid) : $company_fid)
							->where('`key`', $k)
							->limit(1)
							->modifyAll(array('value' => $_value));
					}
				}
			}
			$i18n_arr = $this->_post->toI18n('i18n');
			if (!empty($i18n_arr))
			{
				pjBaseMultiLangModel::factory()->updateMultiLang($i18n_arr, $company_fid, 'pjBaseOption');
			}
			
			switch ($this->_post->toString('next_action'))
			{
				case 'pjActionIndex':
					$err = 'PBS01';
					break;
				case 'pjActionVisual':
					$err = 'PBS02';
					break;
				case 'pjActionEmailSettings':
					$err = 'PBS03';
					break;
				case 'pjActionLoginProtection':
					$err = 'PBS04';
					break;
				case 'pjActionCaptchaSpam':
					$err = 'PBS05';
					break;
				case 'pjActionApiKeys':
					$err = 'PBS06';
					break;
			}
			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjBaseOptions&action=" . $this->_post->toString('next_action') . "&err=$err");
		}
	}
	
	public function pjActionIndex()
	{
		if (!pjAuth::factory()->hasAccess())
		{
			$this->sendForbidden();
			return;
		}
		
		$arr = pjBaseOptionModel::factory()
			->where('t1.foreign_id', $this->getOptionsForeignId())
			->where('t1.tab_id', 1)
			->orderBy('t1.order ASC')
			->findAll()
			->getData();
		
		$this->set('arr', $arr);
		$this->appendJs('pjBaseOptions.js', $this->getConst('PLUGIN_JS_PATH'));
	}
	
	public function pjActionLoginProtection()
	{
		if (!pjAuth::factory()->hasAccess())
		{
			$this->sendForbidden();
			return;
		}

		$company_fid = $this->getOptionsForeignId();
		$arr = class_exists('pjCompanyContext')
			? pjCompanyContext::optionRows(array(4, 5, 6, 7), $company_fid)
			: pjBaseOptionModel::factory()->where('t1.foreign_id', $company_fid)->whereIn('t1.tab_id', array(4, 5, 6, 7))->orderBy('t1.tab_id ASC, t1.order ASC, t1.key ASC')->findAll()->getData();
		$tmp = $arr;
		$arr['i18n'] = pjBaseMultiLangModel::factory()->getMultiLang($company_fid, 'pjBaseOption');

		$o_arr = array();
		foreach ($tmp as $item)
		{
			$o_arr[$item['key']] = $item;
		}
		$this->set('o_arr', $o_arr);
		
		$this->set('arr', $arr);

		$this->setLocalesData();
		
		$this->set('has_access_password', pjAuth::factory('pjBaseOptions', 'pjActionLoginProtection')->hasAccess('password'));
		$this->set('has_access_secure_login', pjAuth::factory('pjBaseOptions', 'pjActionLoginProtection')->hasAccess('secure_login'));
		$this->set('has_access_failed_login', pjAuth::factory('pjBaseOptions', 'pjActionLoginProtection')->hasAccess('failed_login'));
		$this->set('has_access_forgot', pjAuth::factory('pjBaseOptions', 'pjActionLoginProtection')->hasAccess('forgot'));

		$this->appendJs('jquery.multilang.js', $this->getConstant('pjBase', 'PLUGIN_JS_PATH'), false, false);
		$this->appendJs('tinymce.min.js', PJ_THIRD_PARTY_PATH . 'tinymce/');
		$this->appendJs('pjBaseOptions.js', $this->getConst('PLUGIN_JS_PATH'));
	}
	
	public function pjActionVisual()
	{
		if (!pjAuth::factory()->hasAccess())
		{
			$this->sendForbidden();
			return;
		}

		$arr = pjBaseOptionModel::factory()
			->where('t1.foreign_id', $this->getOptionsForeignId())
			->where('t1.tab_id', 2)
			->orderBy('t1.order ASC')
			->findAll()
			->getData();
		
		$this->set('arr', $arr);
		$this->appendJs('pjBaseOptions.js', $this->getConst('PLUGIN_JS_PATH'));
	}
	
	public function pjActionCaptchaSpam()
	{
		$auth = pjAuth::factory();
		if (!$auth->hasAccess())
		{
			$this->sendForbidden();
			return;
		}

		$pjBaseOptionModel = pjBaseOptionModel::factory();
		
		$company_fid = $this->getOptionsForeignId();
		$rows = class_exists('pjCompanyContext')
			? pjCompanyContext::optionRows(array(8), $company_fid)
			: $pjBaseOptionModel->where('t1.foreign_id', $company_fid)->where('t1.tab_id', 8)->orderBy('t1.order ASC')->findAll()->getData();
		$arr = array();
		foreach ($rows as $row)
		{
			$arr[$row['key']] = $row;
		}
		$arr['i18n'] = pjBaseMultiLangModel::factory()->getMultiLang($company_fid, 'pjBaseOption');
		
		$this->setLocalesData();
		
		$this->set('_arr', $arr);

		$this->set('has_access_captcha', $auth->hasAccess('captcha'));
		$this->set('has_access_spam', $auth->hasAccess('spam'));

		$this->appendJs('jquery.multilang.js', $this->getConstant('pjBase', 'PLUGIN_JS_PATH'), false, false);
		$this->appendCss('css/select2.css', PJ_THIRD_PARTY_PATH . 'select2/');
		$this->appendJs('js/select2.min.js',  PJ_THIRD_PARTY_PATH . 'select2/');
		$this->appendJs('pjBaseOptions.js', $this->getConst('PLUGIN_JS_PATH'));
	}
	
	public function pjActionUpdateTheme()
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
		
		$this->requirePageAccess('pjActionVisual');
		if (!$this->_post->has('theme') || !preg_match('/^theme[0-9]+$/', $this->_post->toString('theme')))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Missing, empty or invalid parameters.'));
		}
		
		pjBaseOptionModel::factory()
			->where('foreign_id', $this->getOptionsForeignId())
			->where('`key`', 'o_base_theme')
			->limit(1)
			->modifyAll(array('value' => 'theme1|theme2|theme3::' . $this->_post->toString('theme')));
			
		self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Theme has been changed.'));
	}
	
	public function pjActionEmailSettings()
	{
		if (!pjAuth::factory()->hasAccess())
		{
			$this->sendForbidden();
			return;
		}

		$arr = pjBaseOptionModel::factory()
			->where('t1.foreign_id', $this->getOptionsForeignId())
			->where('t1.tab_id', 3)
			->orderBy('t1.order ASC')
			->findAll()
			->getData();
		
		$this->set('arr', $arr);
		$this->appendJs('pjBaseOptions.js', $this->getConst('PLUGIN_JS_PATH'));
	}
	
	public function pjActionApiKeys()
	{
		if (!pjAuth::factory()->hasAccess())
		{
			$this->sendForbidden();
			return;
		}

		$arr = pjBaseOptionModel::factory()
			->where('t1.foreign_id', $this->getOptionsForeignId())
			->where('t1.tab_id', 10)
			->orderBy('t1.order ASC')
			->findAll()
			->getData();
		
		$this->set('arr', $arr);
		$this->appendJs('pjBaseOptions.js', $this->getConst('PLUGIN_JS_PATH'));
	}
	
	public function pjActionAjaxSmtp()
	{
		$this->setAjax(true);
		
		if (!$this->isXHR())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => __('plugin_base_email_text_ARRAY_1', true)));
		}
		
		if (!self::isPost())
		{
		    self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => __('plugin_base_email_text_ARRAY_2', true)));
		}
		$this->requirePageAccess('pjActionEmailSettings');
		
		$post = array();
		foreach ($this->_post->raw() as $key => $value)
		{
			if (!preg_match('/value-(string|text|int|float|enum|bool|color)-(.*)/', $key))
			{
				continue;
			}
			
			list(, $type, $k) = explode("-", $key);
			if (empty($k))
			{
				continue;
			}
			
			$_value = NULL;
			if ($value)
			{
				switch ($type)
				{
					case 'string':
					case 'text':
					case 'color':
						$_value = $this->_post->toString($key);
						break;
					case 'enum':
						list(,$_value) = explode('::', $this->_post->toString($key));
						break;
					case 'int':
					case 'bool':
						$_value = $this->_post->toInt($key);
						break;
					case 'float':
						$_value = $this->_post->toFloat($key);
						break;
				}
			}

			$post[$k] = $_value;
		}
		
		if (!(isset($post['o_smtp_host'], $post['o_smtp_port'], $post['o_smtp_user'], $post['o_smtp_pass'], $post['o_smtp_auth'], $post['o_smtp_secure'])
			&& !empty($post['o_smtp_host'])
			&& !empty($post['o_smtp_port'])
			&& !empty($post['o_smtp_user'])
			&& !empty($post['o_smtp_auth'])
		))
		{
		    self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => __('plugin_base_email_text_ARRAY_3', true)));
		}

		$mail = new pjPHPMailer(true);
		$mail->isSMTP();
		$mail->Host = $post['o_smtp_host'];
		$mail->Port = $post['o_smtp_port'];
		if (in_array($post['o_smtp_secure'], array('ssl', 'tls')))
		{
			$mail->SMTPSecure = $post['o_smtp_secure'];
		}
		if (!empty($post['o_smtp_user']))
		{
			$mail->SMTPAuth = true;
			$mail->AuthType = $post['o_smtp_auth'];
			$mail->Username = $post['o_smtp_user'];
			$mail->Password = $post['o_smtp_pass'];
		}

		try {
			$result = $mail->smtpConnect();
			$mail->smtpClose();
		} catch (pjException $e) {
			$result = false;
			$message = $e->getMessage();
		} catch (Exception $e) {
			$result = false;
			$message = $e->getMessage();
		}
		
		if ($result)
		{
		    self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => __('plugin_base_email_text_ARRAY_4', true)));
		}
		self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => __('plugin_base_email_text_ARRAY_5', true), 'message' => $message));
	}
	
	public function pjActionAjaxSend()
	{
		$this->setAjax(true);
		
		if (!$this->isXHR())
		{
		    self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => __('plugin_base_email_text_ARRAY_1', true)));
		}
		
		if (!self::isPost())
		{
		    self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => __('plugin_base_email_text_ARRAY_2', true)));
		}
		$this->requirePageAccess('pjActionEmailSettings');
		
		if (!$this->_post->check('email'))
		{
		    self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => __('plugin_base_email_text_ARRAY_6', true)));
		} else {
			if (!pjValidation::pjActionEmail($this->_post->toString('email')))
			{
			    self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => __('plugin_base_email_text_ARRAY_7', true)));
			}
		}
		
		$post = array();
		foreach ($this->_post->raw() as $key => $value)
		{
			if (!preg_match('/value-(string|text|int|float|enum|bool|color)-(.*)/', $key))
			{
				continue;
			}
			
			list(, $type, $k) = explode("-", $key);
			if (empty($k))
			{
				continue;
			}
			
			$_value = NULL;
			if ($value)
			{
				switch ($type)
				{
					case 'string':
					case 'text':
					case 'color':
						$_value = $this->_post->toString($key);
						break;
					case 'enum':
						list(,$_value) = explode('::', $this->_post->toString($key));
						break;
					case 'int':
					case 'bool':
						$_value = $this->_post->toInt($key);
						break;
					case 'float':
						$_value = $this->_post->toFloat($key);
						break;
				}
			}

			$post[$k] = $_value;
		}
		
		$subject = __('plugin_base_test_email_subject', true);
		$message = __('plugin_base_test_email_message', true);
		if (!empty($subject) && !empty($message)) {
			$pjEmail = self::getMailer($post);
			$pjEmail->setDebugger($this);
			$pjEmail->setTo($this->_post->toString('email'));
			$pjEmail->setSubject(stripslashes($subject));
			
			if ($pjEmail->send($message))
			{
			    self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => __('plugin_base_email_text_ARRAY_9', true) . ' ' . $this->_post->toString('email')));
			}
		}
		self::jsonResponse(array('status' => 'ERR', 'code' => 104, 'text' => __('plugin_base_email_text_ARRAY_8', true)));
	}
}
?>