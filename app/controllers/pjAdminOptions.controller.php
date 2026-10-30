<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjAdminOptions extends pjAdmin
{
	public function pjActionInstall()
	{
		$this->checkLogin();
	    
	    if (!pjAuth::factory()->hasAccess())
	    {
	        $this->sendForbidden();
	        return;
	    }
		
		$locale_arr = pjLocaleModel::factory()->select('t1.*, t2.title')
			->join('pjLocaleLanguage', 't2.iso=t1.language_iso', 'left')
			->orderBy('t1.sort ASC')->findAll()->getData();
		$this->set('locale_arr', $locale_arr);
				
		$this->appendJs('pjAdminOptions.js');
	}
	
	/**
	 * Options > AI settings (administrators only): AI service, model and API keys used by the
	 * public sign-up to write property descriptions (pjAi). Platform settings, not per company.
	 *   GET                       page
	 *   POST ai_save=1            save (service, per service: model, new key, remove key)
	 *   XHR POST do=models|test   load the models of a service / test service + key + model
	 */
	public function pjActionAi()
	{
		$this->checkLogin();
		if (!pjAuth::factory()->hasAccess() || !$this->isAdmin())
		{
			$this->sendForbidden();
			return;
		}

		if ($this->isXHR() && self::isPost())
		{
			$this->setAjax(true);
			$service = $this->_post->toString('service');
			if (!isset(pjAi::$services[$service]))
			{
				self::jsonResponse(array('status' => 'ERR', 'text' => 'Unknown AI service.'));
			}
			$key = trim((string) $this->_post->toString('key'));
			switch ($this->_post->toString('do'))
			{
				case 'models':
					self::jsonResponse(pjAi::listModels($service, $key));
					break;
				case 'test':
					@set_time_limit(pjAi::TIMEOUT + 30);
					$model = trim((string) $this->_post->toString('model'));
					if (!pjAi::validModel($model))
					{
						self::jsonResponse(array('status' => 'ERR', 'text' => 'Choose a model first.'));
					}
					$check = pjAi::test($service, $key, $model);
					self::jsonResponse($check === true
						? array('status' => 'OK', 'text' => pjAi::$services[$service]['name'] . ' answered with ' . $model . ' in ' . pjAi::$lastSeconds . ' s. The settings work.')
						: array('status' => 'ERR', 'text' => (string) $check));
					break;
			}
			self::jsonResponse(array('status' => 'ERR', 'text' => 'Bad request.'));
		}

		if (self::isPost() && $this->_post->check('ai_save'))
		{
			// Every value is read back after saving; what did not stick is reported by name.
			$errors = array();
			$saved_keys = array();
			$provider = $this->_post->toString('ai_provider');
			if (isset(pjAi::$services[$provider]) && !pjAi::saveOption('o_ai_provider', $provider))
			{
				$errors[] = 'service';
			}
			foreach (pjAi::$services as $s => $info)
			{
				// Model: from the list, or "Custom model..." + the id typed in
				$model = trim((string) $this->_post->toString('model_' . $s));
				if ($model === '__custom__')
				{
					$model = trim((string) $this->_post->toString('custom_model_' . $s));
					if ($model !== '' && pjAi::validModel($model))
					{
						$custom = pjAi::modelList('o_ai_custom_models_' . $s);
						if (!in_array($model, $custom, true) && !in_array($model, pjAi::modelList('o_ai_models_' . $s), true))
						{
							$custom[] = $model;
							pjAi::saveOption('o_ai_custom_models_' . $s, json_encode(array_values($custom)));
						}
					}
				}
				$drop = $this->_post->raw('remove_custom_' . $s);
				if (is_array($drop) && !empty($drop))
				{
					pjAi::saveOption('o_ai_custom_models_' . $s, json_encode(array_values(array_diff(pjAi::modelList('o_ai_custom_models_' . $s), array_map('strval', $drop)))));
				}
				if ($model !== '' && !pjAi::validModel($model))
				{
					$errors[] = 'model_' . $s;
				} elseif ($model !== '' && !pjAi::saveOption('o_ai_model_' . $s, $model)) {
					$errors[] = 'db_model_' . $s;
				}
				// API key: empty field keeps the saved key
				if ($this->_post->check('remove_key_' . $s))
				{
					pjAi::saveOption('o_' . $s . '_api_key', '');
				} else {
					$key = trim((string) $this->_post->raw('key_' . $s));
					// the field shows the saved key: unchanged means nothing to save
					if ($key !== '' && $key !== pjAi::apiKey($s))
					{
						if (!preg_match('/^\S{10,300}$/', $key))
						{
							$errors[] = 'key_' . $s;
						} elseif (pjAi::saveOption('o_' . $s . '_api_key', $key)) {
							$saved_keys[] = $s;
						} else {
							$errors[] = 'db_key_' . $s;
						}
					}
				}
			}
			$q = '&err=' . (empty($errors) ? 'AIS01' : 'AIS02') . '&keys=' . implode(',', $saved_keys) . '&errs=' . implode(',', $errors);
			pjUtil::redirect($_SERVER['PHP_SELF'] . '?controller=pjAdminOptions&action=pjActionAi' . $q);
		}

		$this->set('ai', pjAi::settings());
		$this->appendJs('pjAdminAi.js');
	}

	public function pjActionNotifications()
	{
		if (!pjAuth::factory()->hasAccess())
	    {
	        $this->sendForbidden();
	        return;
	    }
	    // Platform notifications of the administrator (company_id 0), used for every company.
	    // "Sign-up (new hosts)" tab: welcome email to new hosts
	    $this->set('show_signup', $this->isAdmin());
	    $this->setLocalesData();
	    
	    $this->appendCss('awesome-bootstrap-checkbox.css', PJ_THIRD_PARTY_PATH . 'awesome_bootstrap_checkbox/');
	    $this->appendJs('jquery.multilang.js', $this->getConstant('pjBase', 'PLUGIN_JS_PATH'), false, false);
	    $this->appendJs('tinymce.min.js', PJ_THIRD_PARTY_PATH . 'tinymce/');
	    $this->appendJs('pjAdminOptions.js');
	}
	
	public function pjActionNotificationsGetMetaData()
	{
	    $this->setAjax(true);
	    
	    if (!$this->isXHR())
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Missing headers.'));
	    }
	    
	    if (!self::isGet())
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Invalid request.'));
	    }
	    
	    if (!(isset($this->query['recipient']) && pjValidation::pjActionNotEmpty($this->query['recipient'])))
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Missing, empty or invalid parameters.'));
	    }
	    if ($this->query['recipient'] === pjSignupMail::RECIPIENT)
	    {
	        // Sign-up emails: platform notifications, administrators only
	        if (!$this->isAdmin())
	        {
	            $this->sendForbidden();
	            return;
	        }
	        $rows = pjSignupMail::rows();
	        $arr = isset($rows['signup_host']) ? array($rows['signup_host']) : array();
	    } else {
	    // the administrator's messages include the "new sign-up" email (signup_admin)
	    $arr = pjNotificationModel::factory()
		    ->where('t1.recipient', $this->query['recipient'])
		    ->where('t1.is_general', 1)
		    ->where('t1.foreign_id', 0)
		    ->where('t1.company_id', 0)
		    ->whereNotIn('t1.variant', array('signup_host'))
		    ->orderBy('t1.id ASC')
		    ->findAll()
		    ->getData();
	    }
	    $this->set('arr', $arr);
	}
	
	public function pjActionNotificationsGetContent()
	{
	    $this->setAjax(true);
	    
	    if (!$this->isXHR())
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Missing headers.'));
	    }
	    
	    if (!self::isGet())
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Invalid request.'));
	    }
	    
	    if (!($this->_get->check('recipient') && $this->_get->check('variant') && $this->_get->check('transport'))
	        && pjValidation::pjActionNotEmpty($this->_get->toString('recipient'))
	        && pjValidation::pjActionNotEmpty($this->_get->toString('variant'))
	        && pjValidation::pjActionNotEmpty($this->_get->toString('transport'))
	        && in_array($this->_get->toString('transport'), array('email', 'sms'))
	        )
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Missing, empty or invalid parameters.'));
	    }
	    
	    if ($this->_get->toString('recipient') === pjSignupMail::RECIPIENT)
	    {
	        $rows = $this->isAdmin() ? pjSignupMail::rows() : array();
	        $arr = $this->_get->toString('variant') === 'signup_host' && isset($rows['signup_host']) ? $rows['signup_host'] : false;
	    } else {
	    $arr = pjNotificationModel::factory()
		    ->where('t1.recipient', $this->_get->toString('recipient'))
		    ->where('t1.variant', $this->_get->toString('variant'))
		    ->where('t1.transport', $this->_get->toString('transport'))
		    ->where('t1.is_general', 1)
		    ->where('t1.foreign_id', 0)
		    ->where('t1.company_id', 0)
		    ->whereNotIn('t1.variant', array('signup_host'))
		    ->limit(1)
		    ->findAll()
		    ->getDataIndex(0);
	    }	    
	    if (!$arr)
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Message not found.'));
	    }
	    
	    $arr['i18n'] = pjBaseMultiLangModel::factory()->getMultiLang($arr['id'], 'pjNotification');
	    $this->set('arr', $arr);
	    
	    # Check SMS
	    $this->set('is_sms_ready', (isset($this->option_arr['plugin_sms_api_key']) && !empty($this->option_arr['plugin_sms_api_key']) ? 1 : 0));
	    
	    # Get locales
	    $locale_arr = pjBaseLocaleModel::factory()
		    ->select('t1.*, t2.file, t2.title')
		    ->join('pjBaseLocaleLanguage', 't2.iso=t1.language_iso', 'left')
		    ->where('t2.file IS NOT NULL')
		    ->orderBy('t1.sort ASC')
		    ->findAll()
		    ->getData();
	    
	    $lp_arr = array();
	    foreach ($locale_arr as $item)
	    {
	        $lp_arr[$item['id']."_"] = array($item['file'], $item['title']);
	    }
	    $this->set('lp_arr', $locale_arr);
	    $this->set('locale_str', self::jsonEncode($lp_arr));
	    $this->set('is_flag_ready', $this->requestAction(array('controller' => 'pjBaseLocale', 'action' => 'pjActionIsFlagReady'), array('return')));
	}
	
	public function pjActionNotificationsSetContent()
	{
	    $this->setAjax(true);
	    
	    if (!$this->isXHR())
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Missing headers.'));
	    }
	    
	    if (!self::isPost())
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Invalid request.'));
	    }
	    
	    if (!(isset($this->body['id']) && pjValidation::pjActionNumeric($this->body['id'])))
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Missing, empty or invalid parameters.'));
	    }
	    
	    $isToggle = $this->_post->check('is_active') && in_array($this->_post->toInt('is_active'), array(1,0));
	    $isFormSubmit = $this->_post->check('i18n') && !$this->_post->isEmpty('i18n');
	    
	    if (!($isToggle xor $isFormSubmit))
	    {
	        self::jsonResponse(array('status' => 'ERR', 'code' => 103, 'text' => 'Data mismatch.'));
	    }
	    
	    // Platform notifications (company_id 0) of the administrator only.
	    $row = pjNotificationModel::factory()->find($this->_post->toInt('id'))->getData();
	    if (empty($row) || (int) $row['company_id'] !== 0 || (int) $row['is_general'] !== 1 || (int) $row['foreign_id'] !== 0 || !$this->isAdmin())
	    {
	        $this->denyCompanyAccess();
	    }
	    
	    if ($isToggle)
	    {
	        pjNotificationModel::factory()
	        ->set('id', $this->_post->toInt('id'))
	        ->modify(array('is_active' => $this->_post->toInt('is_active')));
	    } elseif ($isFormSubmit) {
	        pjBaseMultiLangModel::factory()->updateMultiLang($this->_post->toArray('i18n'), $this->_post->toInt('id'), 'pjNotification');
	    }
	    
	    self::jsonResponse(array('status' => 'OK', 'code' => 200, 'text' => 'Notification has been updated.'));
	}
	
	public function pjActionPreview()
	{
		$this->checkLogin();
	    
	    if (!pjAuth::factory()->hasAccess())
	    {
	        $this->sendForbidden();
	        return;
	    }

	    $this->appendJs('pjAdminOptions.js');
	}

	public function pjActionColors()
	{
		$this->checkLogin();
		$this->requirePlanFeature('themes');

		if (!pjAuth::factory()->hasAccess())
		{
			$this->sendForbidden();
			return;
		}

		$this->set('palettes', pjColorHelper::getPalettes());

		$opt = is_array($this->option_arr) ? $this->option_arr : array();
		// Company colours override the global ones.
		$opt = array_merge($opt, pjCompanyOptionModel::factory()->getPairs($this->getCompanyId()));
		$this->set('theme_colors', array(
			'o_brand_color'           => (isset($opt['o_brand_color'])           && pjColorHelper::isValidHex($opt['o_brand_color']))           ? $opt['o_brand_color']           : '#b45309',
			'o_accent_color'          => (isset($opt['o_accent_color'])          && pjColorHelper::isValidHex($opt['o_accent_color']))          ? $opt['o_accent_color']          : '#f3f4f6',
			'o_text_color'            => (isset($opt['o_text_color'])            && pjColorHelper::isValidHex($opt['o_text_color']))            ? $opt['o_text_color']            : '#111827',
			'o_background_color'      => (isset($opt['o_background_color'])      && pjColorHelper::isValidHex($opt['o_background_color']))      ? $opt['o_background_color']      : '#ffffff',
			'o_price_color'           => (isset($opt['o_price_color'])           && pjColorHelper::isValidHex($opt['o_price_color']))           ? $opt['o_price_color']           : '#b45309',
			'o_page_background_color' => (isset($opt['o_page_background_color']) && pjColorHelper::isValidHex($opt['o_page_background_color'])) ? $opt['o_page_background_color'] : '#ffffff',
			'o_font_family'           => isset($opt['o_font_family']) ? preg_replace('/[^a-zA-Z0-9 ]/', '', (string) $opt['o_font_family']) : '',
			'o_color_palette'         => isset($opt['o_color_palette']) ? preg_replace('/[^0-9]/', '', (string) $opt['o_color_palette']) : '0'
		));

		$this->appendJs('pjAdminTheme.js?v=' . @filemtime(PJ_INSTALL_PATH . 'app/web/js/pjAdminTheme.js'));
	}

	public function pjActionSaveThemeColors()
	{
		$this->requirePlanFeature('themes');
		$this->setAjax(true);

		if (!$this->isXHR() || !self::isPost())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Bad request.'));
		}
		if (!pjAuth::factory('pjAdminOptions', 'pjActionPreview')->hasAccess())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 403, 'text' => 'Forbidden.'));
		}

		$colorFields = array('o_brand_color', 'o_accent_color', 'o_text_color', 'o_background_color', 'o_price_color', 'o_page_background_color');
		$saved = array();

		foreach ($colorFields as $key)
		{
			$val = $this->_post->toString($key);
			if (pjColorHelper::isValidHex($val))
			{
				$saved[$key] = strtolower($val);
			}
		}
		// font: letters, digits and spaces only
		$font = preg_replace('/[^a-zA-Z0-9 ]/', '', (string) $this->_post->toString('o_font_family'));
		$saved['o_font_family'] = trim($font);
		// palette id: digits only
		$saved['o_color_palette'] = preg_replace('/[^0-9]/', '', (string) $this->_post->toString('o_color_palette'));

		// Apply the palette to the booking-widget colours so the calendar follows the
		// theme everywhere it renders (detail page, Preview, and embed/booknow code).
		if (isset($saved['o_brand_color']))
		{
			$saved['o_background_month'] = $saved['o_brand_color']; // calendar header colour
			$saved['o_color_month']      = '#ffffff';               // header text
			$saved['o_background_nav']   = $saved['o_brand_color']; // availability calendar header
		}
		if (isset($saved['o_text_color']))
		{
			$saved['o_color_available'] = $saved['o_text_color'];   // day font colour
		}

		// Theme colours are stored per company.
		$company_id = $this->getCompanyId();
		if ($company_id <= 0)
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => __('msgNoCompanySelected', true)));
		}
		$pjCompanyOptionModel = pjCompanyOptionModel::factory();
		foreach ($saved as $key => $value)
		{
			$pjCompanyOptionModel->saveValue($company_id, $key, $value);
		}

		self::jsonResponse(array('status' => 'OK', 'text' => 'Saved.'));
	}
}
?>