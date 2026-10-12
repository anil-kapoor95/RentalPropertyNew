<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjAdminCompanies extends pjAdmin
{
	const ROLE_COMPANY = 4;

	private $logoDir = 'companies/';

	/* ------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	/**
	 * Read and clean the posted company fields.
	 * $adminFields = false limits the result to the fields a Company user may edit.
	 */
	private function collectPostData($adminFields = true)
	{
		$text = array('name', 'legal_name', 'registration_no', 'tax_no', 'contact_person',
			'email', 'phone', 'website', 'address_1', 'address_2', 'city', 'state', 'zip');

		$data = array();
		foreach ($text as $f)
		{
			$v = trim((string) $this->_post->toString($f));
			$data[$f] = $v !== '' ? $v : ':NULL';
		}
		$data['country_id'] = $this->_post->toInt('country_id') > 0 ? $this->_post->toInt('country_id') : ':NULL';
		$desc = trim((string) $this->_post->toString('description'));
		$data['description'] = $desc !== '' ? $desc : ':NULL';

		if (!empty($data['website']) && $data['website'] !== ':NULL' && !preg_match('~^https?://~i', $data['website']))
		{
			$data['website'] = 'https://' . $data['website'];
		}

		if ($adminFields)
		{
			$data['status'] = $this->_post->check('status') ? 'T' : 'F';
			$data['user_id'] = $this->_post->toInt('user_id') > 0 ? $this->_post->toInt('user_id') : ':NULL';
			$commission = (float) $this->_post->toFloat('commission');
			$data['commission'] = max(0, min(100, round($commission, 2)));
			$notes = trim((string) $this->_post->toString('notes'));
			$data['notes'] = $notes !== '' ? $notes : ':NULL';
		}
		return $data;
	}

	/**
	 * Server-side validation. Returns an error label key or true.
	 */
	private function validateData($data, $company_id = 0)
	{
		// Required fields (also enforced in the form)
		foreach (array('name', 'contact_person', 'email', 'phone', 'address_1', 'city', 'country_id') as $f)
		{
			if (!isset($data[$f]) || $data[$f] === ':NULL' || $data[$f] === '' || $data[$f] === null)
			{
				return 'msgCompanyRequired';
			}
		}
		if ($data['email'] !== ':NULL' && !pjValidation::pjActionEmail($data['email']))
		{
			return 'msgCompanyError';
		}
		if ($data['website'] !== ':NULL' && !filter_var($data['website'], FILTER_VALIDATE_URL))
		{
			return 'msgCompanyError';
		}
		if (isset($data['user_id']) && $data['user_id'] !== ':NULL')
		{
			$user = pjAuthUserModel::factory()->find($data['user_id'])->getData();
			if (empty($user) || (int) $user['role_id'] !== self::ROLE_COMPANY)
			{
				return 'msgCompanyUserTaken';
			}
			$taken = pjCompanyModel::factory()
				->where('t1.user_id', $data['user_id'])
				->where('t1.id !=', (int) $company_id)
				->findCount()->getData();
			if ((int) $taken > 0)
			{
				return 'msgCompanyUserTaken';
			}
		}
		return true;
	}

	/**
	 * Handle the logo upload / removal. Returns the new relative path, the old one,
	 * ':NULL' when removed, or false when an upload was attempted and failed.
	 */
	private function handleLogo($company_id, $current = null)
	{
		$remove = $this->_post->check('remove_logo');
		$hasUpload = isset($_FILES['logo']) && !empty($_FILES['logo']['name']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE;

		if (!$hasUpload)
		{
			if ($remove && !empty($current))
			{
				$this->deleteLogoFile($current);
				return ':NULL';
			}
			return $current;
		}

		// Too big for PHP (upload_max_filesize / post_max_size)?
		if (in_array($_FILES['logo']['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true))
		{
			$this->logoError = 'msgCompanyLogoTooBig';
			return false;
		}

		$dir = $this->logoFolder();
		if ($dir === false)
		{
			$this->logoError = 'msgCompanyLogoFolder';
			return false;
		}

		$handle = new pjUpload();
		$handle->setAllowedExt(array('jpg', 'jpeg', 'png', 'gif', 'webp'));
		$handle->setAllowedTypes(array(
			'image/jpeg', 'image/jpg', 'image/pjpeg',
			'image/png', 'image/x-png', 'image/gif', 'image/webp'
		));
		if (!$handle->load($_FILES['logo']))
		{
			$this->logoError = in_array($handle->getErrorCode(), array('121', '122'), true) ? 'msgCompanyLogoTooBig' : 'msgCompanyInvalidLogo';
			return false;
		}
		$ext = strtolower($handle->getExtension());
		$ext = $ext === 'jpeg' ? 'jpg' : $ext;
		$path = $dir . ($dir === PJ_UPLOAD_PATH ? 'company_logo_' : '') . (int) $company_id . '_' . md5(uniqid(rand(), true)) . '.' . $ext;
		if (!$handle->save($path))
		{
			$this->logoError = 'msgCompanyLogoFolder';
			return false;
		}
		@chmod($path, 0644);
		if (@getimagesize($path) === false)
		{
			@unlink($path);
			$this->logoError = 'msgCompanyInvalidLogo';
			return false;
		}
		if (!empty($current))
		{
			$this->deleteLogoFile($current);
		}
		return $path;
	}

	/** Label key explaining why the last logo upload failed. */
	private $logoError = 'msgCompanyInvalidLogo';

	/**
	 * Writable folder for logos: app/web/upload/companies/ (created / made writable when
	 * possible), otherwise app/web/upload/ itself. false when neither is writable.
	 */
	private function logoFolder()
	{
		$dir = PJ_UPLOAD_PATH . $this->logoDir;
		if (!is_dir($dir))
		{
			@mkdir($dir, 0777, true);
		}
		if (is_dir($dir) && !is_writable($dir))
		{
			@chmod($dir, 0777);
			clearstatcache();
		}
		if (is_dir($dir) && is_writable($dir))
		{
			if (!is_file($dir . 'index.php'))
			{
				@file_put_contents($dir . 'index.php', '<?php header("HTTP/1.1 403 Forbidden"); exit; ?>');
			}
			return $dir;
		}
		return is_writable(PJ_UPLOAD_PATH) ? PJ_UPLOAD_PATH : false;
	}

	private function deleteLogoFile($path)
	{
		// Only ever delete our own logo files.
		$own = strpos((string) $path, PJ_UPLOAD_PATH . $this->logoDir) === 0 || strpos((string) $path, PJ_UPLOAD_PATH . 'company_logo_') === 0;
		if (!empty($path) && $own && strpos($path, '..') === false && is_file($path))
		{
			@unlink($path);
		}
	}

	private function setFormData($company_id = 0)
	{
		$this->set('country_arr', pjBaseCountryModel::factory()
			->select('t1.id, t2.content AS name')
			->join('pjMultiLang', "t2.model='pjBaseCountry' AND t2.foreign_id=t1.id AND t2.field='name' AND t2.locale='".$this->getLocaleId()."'", 'left outer')
			->where('t1.status', 'T')
			->orderBy('`name` ASC')->findAll()->getData()
		);

		// Company-role users that are free (or already linked to this company)
		$company_table = pjCompanyModel::factory()->getTable();
		$this->set('user_arr', pjAuthUserModel::factory()
			->select('t1.id, t1.name, t1.email')
			->where('t1.role_id', self::ROLE_COMPANY)
			->where(sprintf("t1.id NOT IN (SELECT `user_id` FROM `%s` WHERE `user_id` IS NOT NULL AND `id` != %u)", $company_table, (int) $company_id))
			// only users without a company or already belonging to this company
			->where(sprintf("(t1.company_id IS NULL OR t1.company_id = %u)", (int) $company_id))
			->orderBy('t1.name ASC')->findAll()->getData()
		);
	}

	private function appendFormAssets()
	{
		$this->appendCss('css/select2.min.css', PJ_THIRD_PARTY_PATH . 'select2/');
		$this->appendJs('js/select2.full.min.js', PJ_THIRD_PARTY_PATH . 'select2/');
		$this->appendJs('pjAdminCompanies.js');
	}

	/**
	 * A company can be deleted only when it has no properties and it is not the last one.
	 */
	public function canDeleteCompany($company_id)
	{
		$company_id = (int) $company_id;
		if ($company_id <= 0)
		{
			return false;
		}
		if ((int) pjCompanyModel::factory()->findCount()->getData() <= 1)
		{
			return false;
		}
		return (int) pjCalendarModel::factory()->where('t1.company_id', $company_id)->findCount()->getData() === 0;
	}

	/**
	 * Remove the company's own settings (extras, features, coupons, discounts,
	 * notifications, theme options). Only called for companies without properties.
	 */
	private function purgeCompanyData($company_id)
	{
		$company_id = (int) $company_id;
		$map = array(
			'pjExtra' => array(pjExtraModel::factory(), pjCalendarExtraModel::factory(), 'extra_id'),
			'pjFeature' => array(pjFeatureModel::factory(), pjCalendarFeatureModel::factory(), 'feature_id'),
			'pjDiscount' => array(pjDiscountModel::factory(), pjCalendarDiscountModel::factory(), 'discount_id'),
			'pjVoucher' => array(pjVoucherModel::factory(), pjCalendarVoucherModel::factory(), 'voucher_id'),
		);
		foreach ($map as $ml => $m)
		{
			$ids = $m[0]->reset()->where('t1.company_id', $company_id)->findAll()->getDataPair(null, 'id');
			if (!empty($ids))
			{
				$m[0]->reset()->whereIn('id', $ids)->eraseAll();
				$m[1]->reset()->whereIn($m[2], $ids)->eraseAll();
				pjMultiLangModel::factory()->where('model', $ml)->whereIn('foreign_id', $ids)->eraseAll();
			}
		}
		$nids = pjNotificationModel::factory()->where('t1.company_id', $company_id)->where('t1.foreign_id', 0)->findAll()->getDataPair(null, 'id');
		if (!empty($nids))
		{
			pjNotificationModel::factory()->whereIn('id', $nids)->eraseAll();
			pjMultiLangModel::factory()->where('model', 'pjNotification')->whereIn('foreign_id', $nids)->eraseAll();
		}
		pjCompanyOptionModel::factory()->where('company_id', $company_id)->eraseAll();
		pjCompanyLocaleModel::factory()->where('company_id', $company_id)->eraseAll(); // its language list
		pjBaseOptionModel::factory()->where('foreign_id', pjCompanyContext::OPTIONS_OFFSET + $company_id)->eraseAll();
		pjMultiLangModel::factory()->where('model', 'pjBaseOption')->where('foreign_id', pjCompanyContext::OPTIONS_OFFSET + $company_id)->eraseAll();
		pjMultiLangModel::factory()->where('model', 'pjBaseField')->where('field', pjCompanyContext::labelField($company_id))->eraseAll();
		$own_countries = pjBaseCountryModel::factory()->where('company_id', $company_id)->findAll()->getDataPair(null, 'id');
		if (!empty($own_countries))
		{
			pjMultiLangModel::factory()->where('model', 'pjBaseCountry')->whereIn('foreign_id', $own_countries)->eraseAll();
			pjBaseCountryModel::factory()->whereIn('id', $own_countries)->eraseAll();
		}
		pjCompanyCountryModel::factory()->where('company_id', $company_id)->eraseAll();
	}

	/** The linked login account belongs to this company. */
	private function syncLoginCompany($company_id, $data)
	{
		if (isset($data['user_id']) && $data['user_id'] !== ':NULL' && (int) $data['user_id'] > 0)
		{
			pjAuthUserModel::factory()->where('id', (int) $data['user_id'])->where('role_id', self::ROLE_COMPANY)->limit(1)->modifyAll(array('company_id' => (int) $company_id));
		}
	}

	/** Company id from a public hash (ids are never shown in URLs). */
	private function idParam($value)
	{
		return pjCompanyContext::idFromHash($value);
	}

	/* ------------------------------------------------------------------
	 * Actions
	 * ------------------------------------------------------------------ */

	/**
	 * Switch the active company (sidebar "Set company").
	 *   index.php?controller=pjAdminCompanies&action=pjActionSwitch&id=<hash>&csrf_token=...
	 */
	public function pjActionSwitch()
	{
		$this->checkLogin();
		$token = $this->_get->toString('csrf_token');
		if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $token))
		{
			pjUtil::redirect($_SERVER['PHP_SELF'] . '?controller=pjAdmin&action=pjActionIndex');
		}
		if (pjCompanyContext::setActive($this->idParam($this->_get->toString('id')), $this->getRoleId(), $this->getUserId()))
		{
			// admin language: the new company's default language
			$lid = pjCompanyContext::companyDefaultLocale(pjCompanyContext::activeId($this->getRoleId(), $this->getUserId()));
			if ($lid > 0)
			{
				$this->setLocale($lid);
			}
		}
		unset($_SESSION[$this->defaultCalendarId]); // pick the new company's first property

		// Go back to the same section (its list page) when possible.
		$next = $_SERVER['PHP_SELF'] . '?controller=pjAdmin&action=pjActionIndex';
		if (!empty($_SERVER['HTTP_REFERER']))
		{
			$ref = parse_url($_SERVER['HTTP_REFERER']);
			$host_ok = empty($ref['host']) || $ref['host'] === (isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : '');
			if ($host_ok && !empty($ref['query']))
			{
				parse_str($ref['query'], $q);
				$c = isset($q['controller']) ? preg_replace('/[^a-zA-Z]/', '', $q['controller']) : '';
				$a = isset($q['action']) ? preg_replace('/[^a-zA-Z]/', '', $q['action']) : '';
				$keep = array('pjActionIndex', 'pjActionSchedule', 'pjActionNotifications', 'pjActionColors', 'pjActionPreview', 'pjActionInstall', 'pjActionProfile');
				if ($c !== '' && strpos($c, 'pjAdmin') === 0)
				{
					$a = in_array($a, $keep, true) ? $a : 'pjActionIndex';
					if ($c === 'pjAdminCalendars' && $a !== 'pjActionIndex') { $a = 'pjActionIndex'; }
					$next = $_SERVER['PHP_SELF'] . '?controller=' . $c . '&action=' . $a;
				}
			}
		}
		pjUtil::redirect($next);
	}

	public function pjActionIndex()
	{
		$this->checkLogin();
		if (!pjAuth::factory()->hasAccess())
		{
			$this->sendForbidden();
			return;
		}
		$this->set('has_update', pjAuth::factory('pjAdminCompanies', 'pjActionUpdate')->hasAccess());
		$this->set('has_create', pjAuth::factory('pjAdminCompanies', 'pjActionCreate')->hasAccess());
		$this->set('has_delete', pjAuth::factory('pjAdminCompanies', 'pjActionDeleteCompany')->hasAccess());
		$this->set('has_delete_bulk', pjAuth::factory('pjAdminCompanies', 'pjActionDeleteCompanyBulk')->hasAccess());

		$this->appendJs('jquery.datagrid.js', PJ_FRAMEWORK_LIBS_PATH . 'pj/js/');
		$this->appendJs('pjAdminCompanies.js');
	}

	public function pjActionGetCompany()
	{
		$this->setAjax(true);
		if (!$this->isXHR())
		{
			exit;
		}

		$pjCompanyModel = pjCompanyModel::factory()
			->join('pjAuthUser', 't2.id=t1.user_id', 'left outer');

		if ($q = $this->_get->toString('q'))
		{
			$q = str_replace(array('_', '%'), array('\_', '\%'), $pjCompanyModel->escapeStr($q));
			$pjCompanyModel->where(sprintf("(t1.name LIKE '%%%1\$s%%' OR t1.legal_name LIKE '%%%1\$s%%' OR t1.email LIKE '%%%1\$s%%' OR t1.phone LIKE '%%%1\$s%%' OR t1.registration_no LIKE '%%%1\$s%%' OR t1.city LIKE '%%%1\$s%%')", $q));
		}
		if ($this->_get->check('status') && in_array($this->_get->toString('status'), array('T', 'F')))
		{
			$pjCompanyModel->where('t1.status', $this->_get->toString('status'));
		}

		$column = 'name';
		$direction = 'ASC';
		$allowed_columns = array('name', 'email', 'phone', 'city', 'status', 'created');
		if ($this->_get->toString('column') && in_array($this->_get->toString('column'), $allowed_columns) && in_array(strtoupper($this->_get->toString('direction')), array('ASC', 'DESC')))
		{
			$column = $this->_get->toString('column');
			$direction = strtoupper($this->_get->toString('direction'));
		}

		$total = $pjCompanyModel->findCount()->getData();
		$rowCount = $this->_get->toInt('rowCount') ? $this->_get->toInt('rowCount') : 10;
		$pages = ceil($total / $rowCount);
		$page = $this->_get->toInt('page') ? $this->_get->toInt('page') : 1;
		$offset = ((int) $page - 1) * $rowCount;
		if ($page > $pages)
		{
			$page = $pages;
		}

		$data = $pjCompanyModel
			->select('t1.id, t1.name, t1.email, t1.phone, t1.city, t1.status, t1.created, t2.name AS user_name')
			->orderBy("t1.$column $direction")
			->limit($rowCount, $offset)
			->findAll()
			->getData();

		foreach ($data as $k => $v)
		{
			$data[$k]['name'] = pjSanitize::clean($v['name']);
			$data[$k]['email'] = pjSanitize::clean((string) $v['email']);
			$data[$k]['phone'] = pjSanitize::clean((string) $v['phone']);
			$data[$k]['city'] = pjSanitize::clean((string) $v['city']);
			$data[$k]['user_name'] = !empty($v['user_name']) ? pjSanitize::clean($v['user_name']) : '—';
			$data[$k]['id'] = pjCompanyContext::hashOf($v['id']); // public reference, never the numeric id
		}

		pjAppController::jsonResponse(compact('data', 'total', 'pages', 'page', 'rowCount', 'column', 'direction'));
	}

	public function pjActionCreate()
	{
		$this->checkLogin();
		if (!pjAuth::factory()->hasAccess())
		{
			$this->sendForbidden();
			return;
		}

		if (self::isPost() && $this->_post->check('company_create'))
		{
			$data = $this->collectPostData(true);
			$valid = $this->validateData($data, 0);
			if ($valid !== true)
			{
				pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdminCompanies&action=pjActionCreate&msg=" . $valid);
			}

			$data['hash'] = bin2hex(random_bytes(16));
			$id = pjCompanyModel::factory()->setAttributes($data)->insert()->getInsertId();
			if ($id === false || (int) $id <= 0)
			{
				pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdminCompanies&action=pjActionCreate&msg=msgCompanyError");
			}

			$this->syncLoginCompany($id, $data);
			// Give the new company its own amenity list and notification texts.
			pjCompanyContext::clearCache();
			pjCompanyContext::seedFeatures($id);
			pjCompanyContext::ensureNotifications($id);
			pjCompanyContext::seedNewCompanyLocales($id); // starts with the system default language

			$msg = 'msgCompanyAdded';
			$logo = $this->handleLogo($id, null);
			if ($logo === false)
			{
				$msg = $this->logoError;
			} elseif (!empty($logo) && $logo !== ':NULL') {
				pjCompanyModel::factory()->set('id', $id)->modify(array('logo' => $logo));
			}
			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdminCompanies&action=pjActionUpdate&id=" . pjCompanyContext::hashOf($id) . "&msg=" . $msg);
		}

		$this->setFormData(0);
		$this->appendFormAssets();
	}

	public function pjActionUpdate()
	{
		$this->checkLogin();
		if (!pjAuth::factory()->hasAccess())
		{
			$this->sendForbidden();
			return;
		}

		if (self::isPost() && $this->_post->check('company_update'))
		{
			$id = $this->idParam($this->_post->toString('id'));
			$arr = pjCompanyModel::factory()->find($id)->getData();
			if (empty($arr))
			{
				pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdminCompanies&action=pjActionIndex&msg=msgCompanyNotFound");
			}

			$data = $this->collectPostData(true);
			$valid = $this->validateData($data, $id);
			if ($valid !== true)
			{
				pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdminCompanies&action=pjActionUpdate&id=" . pjCompanyContext::hashOf($id) . "&msg=" . $valid);
			}

			$msg = 'msgCompanySaved';
			$logo = $this->handleLogo($id, $arr['logo']);
			if ($logo === false)
			{
				$msg = $this->logoError;
			} else {
				$data['logo'] = empty($logo) ? ':NULL' : $logo;
			}
			$data['modified'] = date('Y-m-d H:i:s');
			pjCompanyModel::factory()->set('id', $id)->modify($data);
			$this->syncLoginCompany($id, $data);
			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdminCompanies&action=pjActionUpdate&id=" . pjCompanyContext::hashOf($id) . "&msg=" . $msg);
		}

		$arr = pjCompanyModel::factory()->find($this->idParam($this->_get->toString('id')))->getData();
		if (empty($arr))
		{
			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdminCompanies&action=pjActionIndex&msg=msgCompanyNotFound");
		}
		$this->set('arr', $arr);
		$this->setFormData($arr['id']);
		$this->appendFormAssets();
	}

	/**
	 * "My company" page for users with the Company role.
	 */
	public function pjActionProfile()
	{
		$this->checkLogin();
		if (!pjAuth::factory()->hasAccess())
		{
			$this->sendForbidden();
			return;
		}

		$arr = pjCompanyModel::factory()->where('t1.user_id', $this->getUserId())->limit(1)->findAll()->getDataIndex(0);

		if (self::isPost() && $this->_post->check('company_profile'))
		{
			if (empty($arr))
			{
				pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdminCompanies&action=pjActionProfile");
			}
			$data = $this->collectPostData(false);
			$data = array_intersect_key($data, array_flip(pjCompanyModel::$profileFields));
			$valid = $this->validateData($data, $arr['id']);
			if ($valid !== true)
			{
				pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdminCompanies&action=pjActionProfile&msg=" . $valid);
			}
			$msg = 'msgCompanySaved';
			$logo = $this->handleLogo($arr['id'], $arr['logo']);
			if ($logo === false)
			{
				$msg = $this->logoError;
			} else {
				$data['logo'] = empty($logo) ? ':NULL' : $logo;
			}
			$data['modified'] = date('Y-m-d H:i:s');
			pjCompanyModel::factory()->set('id', $arr['id'])->modify($data);
			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdminCompanies&action=pjActionProfile&msg=" . $msg);
		}

		$this->set('arr', !empty($arr) ? $arr : false);
		if (!empty($arr))
		{
			$this->setFormData($arr['id']);
			$this->appendFormAssets();
		}
	}

	/**
	 * Inline grid edit. Only whitelisted columns can be changed.
	 */
	public function pjActionSaveCompany()
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
		$id = $this->idParam($this->_get->toString('id'));
		$column = $this->_post->toString('column');
		$value = trim((string) $this->_post->toString('value'));

		if ($id <= 0 || !in_array($column, array('name', 'status'), true))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Missing, empty or invalid parameters.'));
		}
		if ($column === 'status' && !in_array($value, array('T', 'F'), true))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Invalid status.'));
		}
		if ($column === 'name' && $value === '')
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Name is required.'));
		}
		pjCompanyModel::factory()->where('id', $id)->limit(1)->modifyAll(array($column => $value, 'modified' => date('Y-m-d H:i:s')));
		self::jsonResponse(array('status' => 'OK', 'code' => 200));
	}

	public function pjActionDeleteCompany()
	{
		$this->setAjax(true);
		if (!$this->isXHR())
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 100, 'text' => 'Missing headers.'));
		}
		$id = $this->idParam($this->_get->toString('id'));
		if ($id <= 0)
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 101, 'text' => 'Missing, empty or invalid parameters.'));
		}
		$arr = pjCompanyModel::factory()->find($id)->getData();
		if (!empty($arr) && !$this->canDeleteCompany($id))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 104, 'text' => __('msgCompanyHasData', true)));
		}
		if (!empty($arr) && pjCompanyModel::factory()->set('id', $id)->erase()->getAffectedRows() == 1)
		{
			// The linked user account is kept; it simply has no company any more.
			$this->deleteLogoFile($arr['logo']);
			$this->purgeCompanyData($id);
			self::jsonResponse(array('status' => 'OK'));
		}
		self::jsonResponse(array('status' => 'ERR'));
	}

	public function pjActionDeleteCompanyBulk()
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
		$record = array_values(array_filter(array_map(array($this, 'idParam'), $record)));
		if (empty($record))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 102, 'text' => 'Missing, empty or invalid data.'));
		}
		$record = array_values(array_filter($record, array($this, 'canDeleteCompany')));
		// Never delete every company in one go: at least one must remain.
		if (!empty($record) && count($record) >= (int) pjCompanyModel::factory()->findCount()->getData())
		{
			array_pop($record);
		}
		if (empty($record))
		{
			self::jsonResponse(array('status' => 'ERR', 'code' => 104, 'text' => __('msgCompanyHasData', true)));
		}
		$logos = pjCompanyModel::factory()->whereIn('id', $record)->findAll()->getDataPair('id', 'logo');
		if (pjCompanyModel::factory()->whereIn('id', $record)->eraseAll()->getAffectedRows() > 0)
		{
			foreach ($logos as $cid => $logo)
			{
				$this->deleteLogoFile($logo);
				$this->purgeCompanyData($cid);
			}
			self::jsonResponse(array('status' => 'OK'));
		}
		self::jsonResponse(array('status' => 'ERR'));
	}
}
?>
