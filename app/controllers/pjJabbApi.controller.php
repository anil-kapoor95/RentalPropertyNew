<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Public JSON API for the Next.js front end.
 *
 * Company sign-up in steps (account + company = same result as Companies > Add company;
 * property + photos = Properties > Add; theme = Options > Theme colours):
 *
 *   Step 1  pjActionSignupAccount      account (name, e-mail, phone, password)   -> signup_token
 *   Step 2  pjActionSignupCompany      company details (+ optional logo)
 *   Step 3  pjActionSignupProperty     property basics
 *   Step 4  pjActionSignupPhotos       property photos (pjActionSignupDeletePhoto removes one)
 *   Step 5  pjActionSignupDescription  description (pjActionSignupGenerateDescription: AI suggestion, Options > AI settings)
 *   Step 6  pjActionSignupTheme        colour theme (pjActionGetThemes lists them)  -> sign-up completed
 *
 *   pjActionSignupStatus   signup_token        -> next step + saved data (prefill)
 *   pjActionSignupResume   email + password    -> new signup_token + next step (token lost / other device)
 *   pjActionCheckEmail     email               -> available or "Email already exists"
 *   pjActionCheckPassword  password            -> OK or the password rule that is not met
 *   pjActionGetCountries                       -> countries for the company / property forms
 *
 * Requests: POST, form-data / x-www-form-urlencoded (through api.php) or a JSON body.
 * Responses: {status: OK|ERR, code, message, errors{field: text}, data{}}.
 */
class pjJabbApi extends pjAppController
{
	const ROLE_COMPANY = 4;

	/** Steps of the sign-up, in order (step number => name). The last one completes the sign-up. */
	private static $steps = array(1 => 'account', 2 => 'company', 3 => 'property', 4 => 'photos', 5 => 'description', 6 => 'theme');

	/** Photos one property may have during the sign-up. */
	const MAX_PHOTOS = 20;

	/** AI descriptions one sign-up may generate. */
	const MAX_AI_GENERATIONS = 10;

	/** Life time of a sign-up token. */
	const TOKEN_DAYS = 30;

	private $logoDir = 'companies/';

	private $input = null;

	/* ------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	/** All request parameters: JSON body when sent as JSON, otherwise POST. */
	private function params()
	{
		if ($this->input === null)
		{
			$this->input = array();
			$raw = file_get_contents('php://input');
			$json = $raw !== '' && $raw !== false ? json_decode($raw, true) : null;
			if (is_array($json))
			{
				$this->input = $json;
			} else {
				$post = $this->_post->raw();
				$this->input = is_array($post) ? $post : array();
			}
		}
		return $this->input;
	}

	/** One trimmed text parameter ('' when missing). */
	private function param($key)
	{
		$p = $this->params();
		if (!isset($p[$key]) || is_array($p[$key]))
		{
			return '';
		}
		return trim((string) $p[$key]);
	}

	/** True when the parameter was sent (even empty). */
	private function has($key)
	{
		$p = $this->params();
		return array_key_exists($key, $p);
	}

	/** Password parameter: never trimmed. */
	private function password($key)
	{
		$p = $this->params();
		return isset($p[$key]) && !is_array($p[$key]) ? (string) $p[$key] : '';
	}

	private function respond($code, $message, $data = array(), $errors = array())
	{
		http_response_code($code === 200 ? 200 : (int) $code);
		$resp = array(
			'status'  => $code === 200 ? 'OK' : 'ERR',
			'code'    => (int) $code,
			'message' => $message,
		);
		if (!empty($errors))
		{
			$resp['errors'] = $errors;
		}
		if (!empty($data))
		{
			$resp['data'] = $data;
		}
		pjAppController::jsonResponse($resp);
	}

	private function requirePost()
	{
		if (!self::isPost())
		{
			$this->respond(405, 'Method not allowed. Use POST.');
		}
	}

	/** The sign-up table must exist (update_2026_10_19_10_00_00.sql). */
	private function requireSignupTable()
	{
		// (pjModel::exec refuses statements with quotes: list the tables and look for ours, as pjEntitlement::isReady)
		$m = pjCompanySignupModel::factory();
		$table = $m->getTable();
		foreach ($m->prepare("SHOW TABLES")->exec()->getData() as $row)
		{
			if (in_array($table, array_values($row), true))
			{
				return;
			}
		}
		$this->respond(503, 'Sign-up is not available yet. Please run the latest system update.');
	}

	/** Length limit of a text value (in characters). */
	private static function tooLong($value, $max)
	{
		return (function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value)) > $max;
	}

	private static function validPhone($phone)
	{
		return (bool) preg_match('/^\+?[0-9\s\-().]{6,30}$/', $phone) && strlen(preg_replace('/\D/', '', $phone)) >= 6;
	}

	/** Back-office user with this e-mail (any back-office role), except $except_user_id. Same rule as Companies > Add. */
	private function findUserByEmail($email, $except_user_id = 0)
	{
		return pjAuthUserModel::factory()
			->select('t1.id, t1.role_id, t1.company_id, t1.email, t1.name, t1.phone, t1.status')
			->join('pjAuthRole', 't2.id=t1.role_id', 'left outer')
			->where('t1.email', $email)
			->where('t2.is_backend', 'T')
			->where('t1.id !=', (int) $except_user_id)
			->limit(1)
			->findAll()
			->getDataIndex(0);
	}

	private function isCurrentPassword($user_id, $password)
	{
		if ((int) $user_id <= 0 || $password === '')
		{
			return false;
		}
		$m = pjAuthUserModel::factory();
		return (int) $m->where('t1.id', (int) $user_id)
			->where(sprintf('t1.password = %s', $m->aesEncrypt($password)), null, false)
			->findCount()->getData() > 0;
	}

	/** Password rules of the system (Options > Login & Protection). Returns error text or true. */
	private function checkPasswordRules($password)
	{
		$check = pjAuth::init()->validatePassword($password);
		if (isset($check['status']) && $check['status'] === 'ERR')
		{
			return !empty($check['text']) ? $check['text'] : 'Password does not meet the password rules.';
		}
		return true;
	}

	/** New random token for a sign-up row; returns the plain token (only the hash is stored). */
	private function issueToken($signup_id)
	{
		$token = bin2hex(random_bytes(32));
		pjCompanySignupModel::factory()->set('id', (int) $signup_id)->modify(array(
			'token_hash'    => hash('sha256', $token),
			'token_expires' => date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_DAYS . ' days')),
			'modified'      => date('Y-m-d H:i:s'),
		));
		return $token;
	}

	/** Sign-up row of the token sent (signup_token parameter or "Authorization: Bearer"), or stops with 401. */
	private function signupFromToken()
	{
		$token = $this->param('signup_token');
		if ($token === '' && !empty($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/^Bearer\s+(\S+)$/i', $_SERVER['HTTP_AUTHORIZATION'], $m))
		{
			$token = $m[1];
		}
		if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token))
		{
			$this->respond(401, 'Sign-up token is missing or invalid.');
		}
		$signup = pjCompanySignupModel::factory()
			->where('t1.token_hash', hash('sha256', $token))
			->limit(1)
			->findAll()
			->getDataIndex(0);
		if (empty($signup) || empty($signup['token_expires']) || strtotime($signup['token_expires']) < time())
		{
			$this->respond(401, 'Sign-up token is invalid or has expired. Please sign in to continue your registration.');
		}
		$user = pjAuthUserModel::factory()->find((int) $signup['user_id'])->getData();
		if (empty($user) || (int) $user['role_id'] !== self::ROLE_COMPANY)
		{
			$this->respond(401, 'Sign-up account not found.');
		}
		$signup['user'] = $user;
		return $signup;
	}

	/** Next step to show: step number, or 'done'. */
	private static function nextStep($signup)
	{
		if ($signup['status'] === 'completed')
		{
			return 'done';
		}
		$next = (int) $signup['step'] + 1;
		return isset(self::$steps[$next]) ? $next : 'done';
	}

	/** Stops unless the sign-up is still open and step $step may be saved (all steps before it are done). */
	private function requireStep($signup, $step)
	{
		if ($signup['status'] === 'completed')
		{
			$this->respond(409, 'Registration is already completed. Please log in.', $this->signupState($signup));
		}
		if ((int) $signup['step'] < $step - 1)
		{
			$this->respond(409, sprintf('Please complete step %u first.', (int) $signup['step'] + 1), $this->signupState($signup));
		}
	}

	/**
	 * Step $step is done. After the last step the sign-up is completed and the login
	 * becomes active (until then it cannot log in to the back office).
	 */
	private function completeStep($signup, $step, $extra = array())
	{
		$now = date('Y-m-d H:i:s');
		$data = array_merge($extra, array('step' => max((int) $signup['step'], $step), 'modified' => $now));
		$done = $step >= max(array_keys(self::$steps));
		if ($done)
		{
			$data['status'] = 'completed';
			$data['completed_at'] = $now;
			pjAuthUserModel::factory()->set('id', (int) $signup['user_id'])->modify(array('status' => 'T'));
		}
		pjCompanySignupModel::factory()->set('id', (int) $signup['id'])->modify($data);
		return $done;
	}

	/** Property created in step 3 (with its company), or stops. */
	private function signupProperty($signup)
	{
		$calendar = !empty($signup['calendar_id']) ? pjCalendarModel::factory()->find((int) $signup['calendar_id'])->getData() : array();
		if (empty($calendar) || (int) $calendar['company_id'] !== (int) $signup['company_id'])
		{
			$this->respond(409, 'Please complete step 3 (property) first.', $this->signupState($signup));
		}
		return $calendar;
	}

	/** Language ids of the sign-up company (texts are saved in each of them). */
	private static function companyLocales($company_id)
	{
		$ids = pjCompanyContext::companyLocaleIds((int) $company_id);
		if (empty($ids))
		{
			$def = pjBaseLocaleModel::factory()->unscoped()->orderBy('t1.is_default DESC, t1.sort ASC')->limit(1)->findAll()->getDataIndex(0);
			$ids = !empty($def) ? array((int) $def['id']) : array(1);
		}
		return $ids;
	}

	/** Texts of a property in the company default language. */
	private static function propertyTexts($calendar_id, $company_id)
	{
		$locale = pjCompanyContext::companyDefaultLocale((int) $company_id);
		return pjMultiLangModel::factory()
			->where('t1.model', 'pjCalendar')
			->where('t1.foreign_id', (int) $calendar_id)
			->where('t1.locale', $locale > 0 ? $locale : 1)
			->findAll()
			->getDataPair('field', 'content');
	}

	private static function photoList($calendar_id)
	{
		$list = array();
		foreach (pjCalendarPhotoModel::factory()->where('t1.calendar_id', (int) $calendar_id)->orderBy('t1.sort ASC, t1.id ASC')->findAll()->getData() as $p)
		{
			$list[] = array(
				'id'        => (int) $p['id'],
				'file_name' => $p['file_name'],
				'url'       => PJ_INSTALL_URL . ltrim($p['large_path'], '/'),
				'thumb_url' => PJ_INSTALL_URL . ltrim($p['small_path'], '/'),
			);
		}
		return $list;
	}

	/** <p>/<br> HTML (as saved by step 5 or the editor) to plain text with blank lines between paragraphs. */
	private static function htmlToText($html)
	{
		$text = preg_replace(array('~<br\s*/?>\s*~i', '~</p>\s*~i'), array("\n", "\n\n"), (string) $html);
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
		return trim(preg_replace("/\n{3,}/", "\n\n", $text));
	}

	/** What the front end needs to continue: steps, next step, and the saved values for prefill. */
	private function signupState($signup)
	{
		$signup = pjCompanySignupModel::factory()->find((int) $signup['id'])->getData();
		$user = pjAuthUserModel::factory()->find((int) $signup['user_id'])->getData();
		$name = isset($user['name']) ? (string) $user['name'] : '';
		$parts = explode(' ', $name, 2);

		$company = null;
		if (!empty($signup['company_id']))
		{
			$c = pjCompanyModel::factory()->find((int) $signup['company_id'])->getData();
			if (!empty($c))
			{
				$company = array();
				foreach (array('name', 'legal_name', 'registration_no', 'tax_no', 'contact_person', 'email', 'phone',
					'website', 'address_1', 'address_2', 'city', 'state', 'zip', 'country_id', 'description') as $f)
				{
					$company[$f] = isset($c[$f]) ? $c[$f] : null;
				}
				$company['logo'] = !empty($c['logo']) ? PJ_INSTALL_URL . ltrim($c['logo'], '/') : null;
			}
		}

		$property = null;
		$photos = array();
		$description = null;
		if (!empty($signup['calendar_id']))
		{
			$cal = pjCalendarModel::factory()->find((int) $signup['calendar_id'])->getData();
			if (!empty($cal))
			{
				$t = self::propertyTexts($cal['id'], $signup['company_id']);
				$property = array(
					'name'       => isset($t['name']) ? $t['name'] : '',
					'address_1'  => isset($t['address_1']) ? $t['address_1'] : '',
					'city'       => isset($t['address_city']) ? $t['address_city'] : '',
					'state'      => $cal['address_state'],
					'zip'        => $cal['address_zip'],
					'country_id' => $cal['address_country'],
					'bedrooms'   => $cal['bedrooms'],
					'bathrooms'  => $cal['bathrooms'],
				);
				$photos = self::photoList($cal['id']);
				// stored as HTML for the back-office editor; the form gets plain text
				$description = isset($t['description']) && $t['description'] !== '' ? self::htmlToText($t['description']) : null;
			}
		}
		$theme = null;
		if (!empty($signup['company_id']) && (int) $signup['step'] >= 6)
		{
			$opt = pjCompanyOptionModel::factory()->getPairs((int) $signup['company_id']);
			$theme = array(
				'palette_id'  => isset($opt['o_color_palette']) ? (string) $opt['o_color_palette'] : null,
				'font_family' => isset($opt['o_font_family']) ? $opt['o_font_family'] : '',
			);
		}

		$next = self::nextStep($signup);
		$state = array(
			'status'          => $signup['status'],
			'completed_steps' => (int) $signup['step'],
			'total_steps'     => count(self::$steps),
			'next_step'       => $next,
			'next_step_name'  => $next === 'done' ? 'done' : self::$steps[$next],
			'account' => array(
				'first_name' => $parts[0],
				'last_name'  => isset($parts[1]) ? $parts[1] : '',
				'name'       => $name,
				'email'      => isset($user['email']) ? $user['email'] : '',
				'phone'      => isset($user['phone']) ? $user['phone'] : '',
			),
			'company' => $company,
			'property' => $property,
			'photos' => $photos,
			'description' => $description,
			'theme' => $theme,
			'ai_available' => pjAi::isConfigured(),
			'ai_generations_left' => max(0, self::MAX_AI_GENERATIONS - (int) (isset($signup['ai_generations']) ? $signup['ai_generations'] : 0)),
		);
		if ($signup['status'] === 'completed')
		{
			$state['login_url'] = PJ_INSTALL_URL . 'index.php?controller=pjBase&action=pjActionLogin';
		}
		return $state;
	}

	/** Account fields of step 1. $user_id: the sign-up user when he edits step 1 again. */
	private function validateAccount($user_id = 0)
	{
		$errors = array();
		$first = $this->param('first_name');
		$last = $this->param('last_name');
		$name = $this->param('name');
		if ($name === '')
		{
			$name = trim($first . ' ' . $last);
		}
		$email = $this->param('email');
		$phone = $this->param('phone');
		$password = $this->password('password');
		$confirm = $this->password('confirm_password');
		$editing = (int) $user_id > 0;

		if ($name === '')
		{
			$errors['first_name'] = 'Name is required';
		} elseif (self::tooLong($name, 255)) {
			$errors['first_name'] = 'Name is too long';
		}

		if ($email === '')
		{
			$errors['email'] = 'Email is required';
		} elseif (!pjValidation::pjActionEmail($email) || self::tooLong($email, 255)) {
			$errors['email'] = 'Invalid email format';
		}

		if ($phone === '')
		{
			$errors['phone'] = 'Phone is required';
		} elseif (!self::validPhone($phone)) {
			$errors['phone'] = 'Invalid phone number';
		}

		// On a new sign-up the password is required; when step 1 is edited again it is optional.
		if (!$editing || $password !== '' || $confirm !== '')
		{
			if ($password === '')
			{
				$errors['password'] = 'Password is required';
			} else {
				$rule = $this->checkPasswordRules($password);
				if ($rule !== true)
				{
					$errors['password'] = $rule;
				}
			}
			if ($confirm === '')
			{
				$errors['confirm_password'] = 'Confirm password is required';
			} elseif ($password !== $confirm) {
				$errors['confirm_password'] = 'Passwords do not match';
			}
		}

		return array($errors, array('name' => $name, 'email' => $email, 'phone' => $phone, 'password' => $password));
	}

	/** Writable folder for logos (same place as Companies > logo). */
	private function logoFolder()
	{
		$dir = PJ_UPLOAD_PATH . $this->logoDir;
		if (!is_dir($dir))
		{
			@mkdir($dir, 0777, true);
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
		$own = strpos((string) $path, PJ_UPLOAD_PATH . $this->logoDir) === 0 || strpos((string) $path, PJ_UPLOAD_PATH . 'company_logo_') === 0;
		if (!empty($path) && $own && strpos($path, '..') === false && is_file($path))
		{
			@unlink($path);
		}
	}

	/** Check the optional logo upload before anything is saved. Returns error text or null. */
	private function checkLogoUpload()
	{
		if (!isset($_FILES['logo']) || empty($_FILES['logo']['name']) || $_FILES['logo']['error'] === UPLOAD_ERR_NO_FILE)
		{
			return null;
		}
		if (in_array($_FILES['logo']['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true))
		{
			return 'Logo file is too big';
		}
		if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['logo']['tmp_name']))
		{
			return 'Logo could not be uploaded';
		}
		$ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
		if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true) || @getimagesize($_FILES['logo']['tmp_name']) === false)
		{
			return 'Logo must be a JPG, PNG, GIF or WEBP image';
		}
		return null;
	}

	/** Optional logo upload (field "logo"). Returns array(path|null, error|null). */
	private function handleLogo($company_id, $current = null)
	{
		if (!isset($_FILES['logo']) || empty($_FILES['logo']['name']) || $_FILES['logo']['error'] === UPLOAD_ERR_NO_FILE)
		{
			return array(null, null);
		}
		if (in_array($_FILES['logo']['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true))
		{
			return array(null, 'Logo file is too big');
		}
		$dir = $this->logoFolder();
		if ($dir === false)
		{
			return array(null, 'Logo could not be saved');
		}
		$handle = new pjUpload();
		$handle->setAllowedExt(array('jpg', 'jpeg', 'png', 'gif', 'webp'));
		$handle->setAllowedTypes(array('image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/gif', 'image/webp'));
		if (!$handle->load($_FILES['logo']))
		{
			return array(null, in_array($handle->getErrorCode(), array('121', '122'), true) ? 'Logo file is too big' : 'Logo must be a JPG, PNG, GIF or WEBP image');
		}
		$ext = strtolower($handle->getExtension());
		$ext = $ext === 'jpeg' ? 'jpg' : $ext;
		$path = $dir . ($dir === PJ_UPLOAD_PATH ? 'company_logo_' : '') . (int) $company_id . '_' . md5(uniqid(rand(), true)) . '.' . $ext;
		if (!$handle->save($path))
		{
			return array(null, 'Logo could not be saved');
		}
		@chmod($path, 0644);
		if (@getimagesize($path) === false)
		{
			@unlink($path);
			return array(null, 'Logo must be a JPG, PNG, GIF or WEBP image');
		}
		if (!empty($current))
		{
			$this->deleteLogoFile($current);
		}
		return array($path, null);
	}

	/* ------------------------------------------------------------------
	 * Actions
	 * ------------------------------------------------------------------ */

	/**
	 * Step 1: account.
	 *   New sign-up:  first_name, last_name (or name), email, phone, password, confirm_password
	 *   Edit step 1:  the same + signup_token (password optional)
	 * An e-mail of an unfinished sign-up with the right password continues that sign-up.
	 */
	public function pjActionSignupAccount()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		// Editing step 1 of an existing sign-up
		if ($this->param('signup_token') !== '')
		{
			$signup = $this->signupFromToken();
			if ($signup['status'] === 'completed')
			{
				$this->respond(409, 'Registration is already completed. Please log in.', $this->signupState($signup));
			}
			list($errors, $acc) = $this->validateAccount((int) $signup['user_id']);
			if (empty($errors['email']) && $this->findUserByEmail($acc['email'], (int) $signup['user_id']))
			{
				$errors['email'] = 'Email already exists';
			}
			if (!empty($errors))
			{
				$this->respond(422, 'Validation failed', array(), $errors);
			}
			$data = array('name' => $acc['name'], 'email' => $acc['email'], 'phone' => $acc['phone']);
			if ($acc['password'] !== '')
			{
				$data['password'] = $acc['password'];
				$data['pswd_modified'] = ':NOW()';
			}
			pjAuthUserModel::factory()->set('id', (int) $signup['user_id'])->modify($data);
			pjCompanySignupModel::factory()->set('id', (int) $signup['id'])->modify(array(
				'step' => max(1, (int) $signup['step']),
				'modified' => date('Y-m-d H:i:s'),
			));
			$this->respond(200, 'Account details saved.', $this->signupState($signup));
		}

		list($errors, $acc) = $this->validateAccount(0);

		if (empty($errors['email']))
		{
			$existing = $this->findUserByEmail($acc['email']);
			if (!empty($existing))
			{
				// Unfinished sign-up of the same person (same password): continue it.
				$pending = pjCompanySignupModel::factory()
					->where('t1.user_id', (int) $existing['id'])
					->where('t1.status', 'pending')
					->limit(1)->findAll()->getDataIndex(0);
				if (!empty($pending) && $acc['password'] !== '' && $this->isCurrentPassword((int) $existing['id'], $acc['password']))
				{
					$token = $this->issueToken((int) $pending['id']);
					$this->respond(200, 'Welcome back! Please continue your registration.',
						array_merge(array('signup_token' => $token, 'resumed' => true), $this->signupState($pending)));
				}
				$errors['email'] = 'Email already exists';
			}
		}

		if (!empty($errors))
		{
			$this->respond(422, 'Validation failed', array(), $errors);
		}

		// Login of the company (Company role). Inactive until the sign-up is completed,
		// so a half-finished account cannot log in to the back office without a company.
		$user_id = pjAuthUserModel::factory(array(
			'role_id'    => self::ROLE_COMPANY,
			'company_id' => ':NULL',
			'email'      => $acc['email'],
			'password'   => $acc['password'],
			'name'       => $acc['name'],
			'phone'      => $acc['phone'],
			'status'     => 'F',
			'is_active'  => 'T',
			'ip'         => pjUtil::getClientIp(),
		))->insert()->getInsertId();
		if ($user_id === false || (int) $user_id <= 0)
		{
			$this->respond(500, 'Account could not be created. Please try again.');
		}

		$now = date('Y-m-d H:i:s');
		$signup_id = pjCompanySignupModel::factory(array(
			'user_id'  => (int) $user_id,
			'step'     => 1,
			'status'   => 'pending',
			'ip'       => substr((string) pjUtil::getClientIp(), 0, 45),
			'created'  => $now,
			'modified' => $now,
		))->insert()->getInsertId();
		if ($signup_id === false || (int) $signup_id <= 0)
		{
			pjAuthUserModel::factory()->set('id', (int) $user_id)->erase();
			$this->respond(500, 'Account could not be created. Please try again.');
		}

		$token = $this->issueToken((int) $signup_id);
		$this->respond(200, 'Account created. Please continue with your company details.',
			array_merge(array('signup_token' => $token, 'resumed' => false), $this->signupState(array('id' => $signup_id))));
	}

	/**
	 * Step 2: company. signup_token + company fields (same fields as Companies > Add company):
	 *   name*, legal_name, registration_no, tax_no, contact_person, company_email, phone,
	 *   website, address_1*, address_2, city*, state, zip, country_id*, description, logo (file)
	 * contact_person / company_email / phone default to the account values.
	 */
	public function pjActionSignupCompany()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$signup = $this->signupFromToken();
		$this->requireStep($signup, 2);
		$user = $signup['user'];

		$limits = array('name' => 255, 'legal_name' => 255, 'registration_no' => 100, 'tax_no' => 100,
			'contact_person' => 255, 'email' => 255, 'phone' => 50, 'website' => 255, 'address_1' => 255,
			'address_2' => 255, 'city' => 100, 'state' => 100, 'zip' => 20);

		$data = array();
		foreach ($limits as $f => $max)
		{
			$data[$f] = $this->param($f === 'email' ? 'company_email' : $f);
		}
		// Defaults from the account
		if ($data['contact_person'] === '') { $data['contact_person'] = (string) $user['name']; }
		if ($data['email'] === '') { $data['email'] = (string) $user['email']; }
		if ($data['phone'] === '') { $data['phone'] = (string) $user['phone']; }
		$data['description'] = $this->param('description');
		$country_id = (int) $this->param('country_id');

		if ($data['website'] !== '' && !preg_match('~^https?://~i', $data['website']))
		{
			$data['website'] = 'https://' . $data['website'];
		}

		$errors = array();
		$labels = array('name' => 'Company name', 'contact_person' => 'Contact person', 'email' => 'Company email',
			'phone' => 'Phone', 'address_1' => 'Address', 'city' => 'City');
		foreach ($labels as $f => $label)
		{
			if ($data[$f] === '')
			{
				$errors[$f === 'email' ? 'company_email' : $f] = $label . ' is required';
			}
		}
		foreach ($limits as $f => $max)
		{
			$key = $f === 'email' ? 'company_email' : $f;
			if (!isset($errors[$key]) && $data[$f] !== '' && self::tooLong($data[$f], $max))
			{
				$errors[$key] = sprintf('Maximum %u characters allowed', $max);
			}
		}
		if (!isset($errors['company_email']) && !pjValidation::pjActionEmail($data['email']))
		{
			$errors['company_email'] = 'Invalid email format';
		}
		if (!isset($errors['phone']) && !self::validPhone($data['phone']))
		{
			$errors['phone'] = 'Invalid phone number';
		}
		if (!isset($errors['website']) && $data['website'] !== '' && !filter_var($data['website'], FILTER_VALIDATE_URL))
		{
			$errors['website'] = 'Invalid website address';
		}
		if ($country_id <= 0)
		{
			$errors['country_id'] = 'Country is required';
		} elseif ((int) pjBaseCountryModel::factory()->where('t1.id', $country_id)->where('t1.status', 'T')->where('t1.company_id IS NULL')->findCount()->getData() === 0) {
			$errors['country_id'] = 'Invalid country';
		}
		$logo_check = $this->checkLogoUpload();
		if ($logo_check !== null)
		{
			$errors['logo'] = $logo_check;
		}
		if (!empty($errors))
		{
			$this->respond(422, 'Validation failed', array(), $errors);
		}

		foreach ($data as $f => $v)
		{
			$data[$f] = $v !== '' ? $v : ':NULL';
		}
		$data['country_id'] = $country_id;

		$company_id = (int) $signup['company_id'];
		$current = $company_id > 0 ? pjCompanyModel::factory()->find($company_id)->getData() : array();
		$is_new = empty($current);

		if ($is_new)
		{
			$data['user_id'] = (int) $user['id'];
			$data['status'] = 'T';
			$data['hash'] = bin2hex(random_bytes(16));
			$company_id = pjCompanyModel::factory()->setAttributes($data)->insert()->getInsertId();
			if ($company_id === false || (int) $company_id <= 0)
			{
				$this->respond(500, 'Company could not be created. Please try again.');
			}
			$company_id = (int) $company_id;
		} else {
			$data['modified'] = date('Y-m-d H:i:s');
			pjCompanyModel::factory()->set('id', $company_id)->modify($data);
		}

		list($logo, $logo_error) = $this->handleLogo($company_id, $is_new ? null : $current['logo']);
		if ($logo !== null)
		{
			pjCompanyModel::factory()->set('id', $company_id)->modify(array('logo' => $logo));
		}

		// The login belongs to the company (it becomes active when the last step is done).
		pjAuthUserModel::factory()->set('id', (int) $user['id'])->modify(array('company_id' => $company_id));

		if ($is_new)
		{
			// Same set-up as a company added in the back office.
			pjCompanyContext::clearCache();
			pjCompanyContext::seedFeatures($company_id);
			pjCompanyContext::ensureNotifications($company_id);
			pjCompanyContext::seedNewCompanyLocales($company_id);
		}

		$done = $this->completeStep($signup, 2, array('company_id' => $company_id));

		$state = $this->signupState($signup);
		if ($logo_error !== null)
		{
			$state['logo_error'] = $logo_error;
		}
		$this->respond(200, $done ? 'Registration completed. You can now log in.' : 'Company details saved.', $state);
	}

	/**
	 * Step 3: property basics (same as Properties > Add property).
	 *   signup_token, name*, address_1*, city*, country_id*, state, zip, bedrooms, bathrooms
	 * Sent again (while the sign-up is open) it updates the same property.
	 */
	public function pjActionSignupProperty()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$signup = $this->signupFromToken();
		$this->requireStep($signup, 3);
		$company_id = (int) $signup['company_id'];

		$v = array();
		foreach (array('name' => 255, 'address_1' => 255, 'city' => 255, 'state' => 255, 'zip' => 255) as $f => $max)
		{
			$v[$f] = $this->param($f);
		}
		$country_id = (int) $this->param('country_id');
		$bedrooms = $this->param('bedrooms');
		$bathrooms = $this->param('bathrooms');

		$errors = array();
		foreach (array('name' => 'Property name', 'address_1' => 'Address', 'city' => 'City') as $f => $label)
		{
			if ($v[$f] === '')
			{
				$errors[$f] = $label . ' is required';
			} elseif (self::tooLong($v[$f], 255)) {
				$errors[$f] = 'Maximum 255 characters allowed';
			}
		}
		if (self::tooLong($v['state'], 100)) { $errors['state'] = 'Maximum 100 characters allowed'; }
		if (self::tooLong($v['zip'], 20)) { $errors['zip'] = 'Maximum 20 characters allowed'; }
		if ($country_id <= 0)
		{
			$errors['country_id'] = 'Country is required';
		} elseif ((int) pjBaseCountryModel::factory()->where('t1.id', $country_id)->where('t1.status', 'T')->where('t1.company_id IS NULL')->findCount()->getData() === 0) {
			$errors['country_id'] = 'Invalid country';
		}
		foreach (array('bedrooms' => $bedrooms, 'bathrooms' => $bathrooms) as $f => $val)
		{
			if ($val !== '' && (!ctype_digit($val) || (int) $val > 99))
			{
				$errors[$f] = 'Enter a number from 0 to 99';
			}
		}
		if (!empty($errors))
		{
			$this->respond(422, 'Validation failed', array(), $errors);
		}

		$attr = array(
			'bedrooms'        => $bedrooms !== '' ? (int) $bedrooms : ':NULL',
			'bathrooms'       => $bathrooms !== '' ? (int) $bathrooms : ':NULL',
			'address_country' => $country_id,
			'address_state'   => $v['state'] !== '' ? $v['state'] : ':NULL',
			'address_zip'     => $v['zip'] !== '' ? $v['zip'] : ':NULL',
		);
		$i18n = array();
		foreach (self::companyLocales($company_id) as $lid)
		{
			$i18n[$lid] = array('name' => $v['name'], 'address_1' => $v['address_1'], 'address_city' => $v['city']);
		}

		$calendar_id = (int) $signup['calendar_id'];
		$current = $calendar_id > 0 ? pjCalendarModel::factory()->find($calendar_id)->getData() : array();
		if (!empty($current) && (int) $current['company_id'] === $company_id)
		{
			pjCalendarModel::factory()->set('id', $calendar_id)->modify($attr);
			pjMultiLangModel::factory()->saveMultiLang($i18n, $calendar_id, 'pjCalendar');
		} else {
			// Subscription plan: number of properties
			if (class_exists('pjEntitlement') && pjEntitlement::isReady() && !pjEntitlement::canAddProperty($company_id))
			{
				$this->respond(403, 'Your plan does not allow more properties.');
			}
			$uuid = '';
			for ($i = 0; $i < 10 && $uuid === ''; $i++)
			{
				$try = substr(pjUtil::uuid(), 0, 12);
				if ((int) pjCalendarModel::factory()->where('t1.uuid', $try)->findCount()->getData() === 0)
				{
					$uuid = $try;
				} else {
					usleep(1100000);
				}
			}
			if ($uuid === '')
			{
				$this->respond(500, 'Property could not be created. Please try again.');
			}
			$calendar_id = pjCalendarModel::factory(array_merge($attr, array(
				'uuid'       => $uuid,
				'user_id'    => (int) $signup['user_id'],
				'company_id' => $company_id,
			)))->insert()->getInsertId();
			if ($calendar_id === false || (int) $calendar_id <= 0)
			{
				$this->respond(500, 'Property could not be created. Please try again.');
			}
			$calendar_id = (int) $calendar_id;

			// Same set-up as Properties > Add property
			pjAppController::ensureCalendarIcalFile($calendar_id);
			pjCompanyContext::setLocaleScope($company_id);
			$locale_arr = pjLocaleModel::factory()
				->select('t1.*, t2.file')
				->join('pjBaseLocaleLanguage', 't2.iso=t1.language_iso', 'left')
				->where('t2.file IS NOT NULL')
				->orderBy('t1.sort ASC')->findAll()->getData();
			$pjOptionModel = pjOptionModel::factory();
			$pjOptionModel->init($calendar_id);
			$pjOptionModel->initConfirmation($calendar_id, $locale_arr);
			pjMultiLangModel::factory()->saveMultiLang($i18n, $calendar_id, 'pjCalendar');
			pjUtil::pjActionGenerateImages($calendar_id, $pjOptionModel->reset()->getAllPairs($calendar_id));
		}

		$this->completeStep($signup, 3, array('calendar_id' => $calendar_id));
		$this->respond(200, 'Property saved.', $this->signupState($signup));
	}

	/**
	 * Step 4: photos of the property. signup_token + photos[] (files: jpg, jpeg, png, gif).
	 * Can be called several times to add more; done when the property has at least one photo.
	 */
	public function pjActionSignupPhotos()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$signup = $this->signupFromToken();
		$this->requireStep($signup, 4);
		$calendar = $this->signupProperty($signup);
		$calendar_id = (int) $calendar['id'];

		// photos[] (several) or photos / photo (one)
		$files = array();
		foreach (array('photos', 'photo') as $field)
		{
			if (empty($_FILES[$field]))
			{
				continue;
			}
			$f = $_FILES[$field];
			if (is_array($f['name']))
			{
				foreach ($f['name'] as $i => $name)
				{
					$files[] = array('name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]);
				}
			} else {
				$files[] = $f;
			}
		}
		$files = array_values(array_filter($files, function ($f) { return !empty($f['name']) && $f['error'] !== UPLOAD_ERR_NO_FILE; }));

		$existing = (int) pjCalendarPhotoModel::factory()->where('t1.calendar_id', $calendar_id)->findCount()->getData();
		if ($existing + count($files) > self::MAX_PHOTOS)
		{
			$this->respond(422, 'Validation failed', array(), array('photos' => sprintf('You can add up to %u photos', self::MAX_PHOTOS)));
		}
		if (empty($files) && $existing === 0)
		{
			$this->respond(422, 'Validation failed', array(), array('photos' => 'Please add at least one photo'));
		}

		$file_errors = array();
		if (!empty($files))
		{
			$dir = PJ_UPLOAD_PATH . 'calendar_photos';
			if (!is_dir($dir))
			{
				@mkdir($dir, 0777, true);
			}
			if (!is_writable($dir))
			{
				$this->respond(500, 'Photos could not be saved (upload folder is not writable).');
			}
			$pjCalendarPhotoModel = pjCalendarPhotoModel::factory();
			$sort = $pjCalendarPhotoModel->getNextSort($calendar_id);
			foreach ($files as $file)
			{
				$label = (string) $file['name'];
				if (in_array($file['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true))
				{
					$file_errors[] = array('file' => $label, 'error' => 'File is too big');
					continue;
				}
				if ($file['error'] !== UPLOAD_ERR_OK || @getimagesize($file['tmp_name']) === false)
				{
					$file_errors[] = array('file' => $label, 'error' => 'Photo must be a JPG, PNG or GIF image');
					continue;
				}
				// Same storage as Properties > Photos (pjAdminCalendars::uploadCalendarPhotos)
				$handle = new pjUpload();
				$handle->setAllowedExt(array('jpg', 'jpeg', 'png', 'gif'));
				$handle->setAllowedTypes(array('image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/gif'));
				if (!$handle->load($file))
				{
					$file_errors[] = array('file' => $label, 'error' => in_array($handle->getErrorCode(), array('121', '122'), true) ? 'File is too big' : 'Photo must be a JPG, PNG or GIF image');
					continue;
				}
				$hash = md5(uniqid(rand(), true));
				$ext = strtolower($handle->getExtension());
				$large = PJ_UPLOAD_PATH . 'calendar_photos/' . $calendar_id . '_' . $hash . '.' . $ext;
				$small = PJ_UPLOAD_PATH . 'calendar_photos/' . $calendar_id . '_' . $hash . '_small.' . $ext;
				if (!$handle->save($large))
				{
					$file_errors[] = array('file' => $label, 'error' => 'Photo could not be saved');
					continue;
				}
				$small_path = $large;
				try
				{
					$img = new pjImage();
					$img->loadImage($large);
					$img->resizeToWidth(300);
					$img->saveImage($small);
					if (is_file($small))
					{
						$small_path = $small;
					}
				} catch (Exception $e) {
					$small_path = $large;
				}
				$pjCalendarPhotoModel->reset()->setAttributes(array(
					'calendar_id' => $calendar_id,
					'hash'        => $hash,
					'file_name'   => $label,
					'mime_type'   => $file['type'],
					'small_path'  => $small_path,
					'large_path'  => $large,
					'sort'        => $sort++,
					'created'     => date('Y-m-d H:i:s'),
				))->insert();
			}
		}

		$total = (int) pjCalendarPhotoModel::factory()->where('t1.calendar_id', $calendar_id)->findCount()->getData();
		if ($total === 0)
		{
			$this->respond(422, 'Validation failed', array('photo_errors' => $file_errors), array('photos' => 'None of the photos could be saved'));
		}
		$this->completeStep($signup, 4);
		$state = $this->signupState($signup);
		if (!empty($file_errors))
		{
			$state['photo_errors'] = $file_errors;
		}
		$this->respond(200, empty($file_errors) ? 'Photos saved.' : 'Some photos could not be saved.', $state);
	}

	/** Remove one photo of the sign-up property. signup_token, photo_id */
	public function pjActionSignupDeletePhoto()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$signup = $this->signupFromToken();
		$this->requireStep($signup, 4);
		$calendar = $this->signupProperty($signup);
		$photo_id = (int) $this->param('photo_id');
		$photo = $photo_id > 0 ? pjCalendarPhotoModel::factory()->find($photo_id)->getData() : array();
		if (empty($photo) || (int) $photo['calendar_id'] !== (int) $calendar['id'])
		{
			$this->respond(404, 'Photo not found.');
		}
		$total = (int) pjCalendarPhotoModel::factory()->where('t1.calendar_id', (int) $calendar['id'])->findCount()->getData();
		if ($total <= 1 && (int) $signup['step'] >= 4)
		{
			$this->respond(422, 'Validation failed', array(), array('photos' => 'At least one photo is required'));
		}
		foreach (array('large_path', 'small_path') as $key)
		{
			$path = (string) $photo[$key];
			if ($path !== '' && strpos($path, PJ_UPLOAD_PATH . 'calendar_photos/') === 0 && strpos($path, '..') === false && is_file($path))
			{
				@unlink($path);
			}
		}
		pjCalendarPhotoModel::factory()->set('id', $photo_id)->erase();
		$this->respond(200, 'Photo removed.', $this->signupState($signup));
	}

	/**
	 * Step 5 helper: let the AI service (Options > AI settings) write a description (not saved; the user can edit it, then
	 * saves it with pjActionSignupDescription). signup_token, highlights (optional, max 1000 chars)
	 */
	public function pjActionSignupGenerateDescription()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$signup = $this->signupFromToken();
		$this->requireStep($signup, 5);
		$calendar = $this->signupProperty($signup);

		if (!pjAi::isConfigured())
		{
			$this->respond(503, 'AI description is not available at the moment. Please write the description yourself.');
		}
		if ((int) $signup['ai_generations'] >= self::MAX_AI_GENERATIONS)
		{
			$this->respond(429, 'You have used all AI suggestions. Please edit the last text or write your own.');
		}
		$highlights = $this->param('highlights');
		if (self::tooLong($highlights, 1000))
		{
			$this->respond(422, 'Validation failed', array(), array('highlights' => 'Maximum 1000 characters allowed'));
		}

		$company_id = (int) $signup['company_id'];
		$t = self::propertyTexts($calendar['id'], $company_id);
		$country = '';
		if (!empty($calendar['address_country']))
		{
			$locale_for_names = pjCompanyContext::companyDefaultLocale($company_id);
			$row = pjMultiLangModel::factory()->where('t1.model', 'pjBaseCountry')->where('t1.foreign_id', (int) $calendar['address_country'])
				->where('t1.field', 'name')->where('t1.locale', $locale_for_names > 0 ? $locale_for_names : 1)->limit(1)->findAll()->getDataIndex(0);
			$country = !empty($row) ? $row['content'] : '';
		}
		$language = 'English';
		$lid = pjCompanyContext::companyDefaultLocale($company_id);
		if ($lid > 0)
		{
			$loc = pjBaseLocaleModel::factory()->unscoped()->find($lid)->getData();
			if (!empty($loc['name']))
			{
				$language = $loc['name'];
			}
		}
		$company = pjCompanyModel::factory()->find($company_id)->getData();

		$facts = array_filter(array(
			'Property name' => isset($t['name']) ? $t['name'] : '',
			'Location' => implode(', ', array_filter(array(isset($t['address_city']) ? $t['address_city'] : '', $calendar['address_state'], $country))),
			'Bedrooms' => $calendar['bedrooms'],
			'Bathrooms' => $calendar['bathrooms'],
			'Host' => !empty($company['name']) ? $company['name'] : '',
			'Owner notes' => $highlights,
		), function ($x) { return $x !== null && $x !== ''; });
		$lines = array();
		foreach ($facts as $k => $val)
		{
			$lines[] = '- ' . $k . ': ' . str_replace(array("\r", "\n"), ' ', (string) $val);
		}
		$prompt = "Write a warm, inviting description of a holiday rental property for its booking website.\n"
			. "Language: " . $language . ".\n"
			. "Length: 120 to 180 words, in 2 or 3 short paragraphs separated by a blank line.\n"
			. "Plain text only: no headings, no markdown, no bullet points, no emojis, no prices.\n"
			. "Use only the facts below. Do not invent amenities, distances, ratings or awards.\n"
			. "Facts:\n" . implode("\n", $lines) . "\n";

		@set_time_limit(pjAi::TIMEOUT + 30); // slow (thinking) models
		$res = pjAi::generate($prompt);
		if ($res['status'] !== 'OK')
		{
			$this->respond((int) $res['code'], $res['text']);
		}
		pjCompanySignupModel::factory()->set('id', (int) $signup['id'])->modify(array(
			'ai_generations' => (int) $signup['ai_generations'] + 1,
			'modified' => date('Y-m-d H:i:s'),
		));
		// plain text even if the model used markdown (bold markers, heading hashes, list bullets)
		$text = str_replace(array("\r\n", '**', '__'), array("\n", '', ''), $res['text']);
		$text = preg_replace('/^\s{0,3}(#{1,6}\s+|[*\-]\s+)/m', '', $text);
		$text = trim(preg_replace("/\n{3,}/", "\n\n", $text));
		$this->respond(200, 'Description generated. You can edit it before saving.', array(
			'description' => $text,
			'ai_generations_left' => max(0, self::MAX_AI_GENERATIONS - (int) $signup['ai_generations'] - 1),
		));
	}

	/** Step 5: save the property description (AI text edited by the user, or his own). signup_token, description* */
	public function pjActionSignupDescription()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$signup = $this->signupFromToken();
		$this->requireStep($signup, 5);
		$calendar = $this->signupProperty($signup);

		$p = $this->params();
		$text = isset($p['description']) && !is_array($p['description']) ? trim(strip_tags((string) $p['description'])) : '';
		if ($text === '')
		{
			$this->respond(422, 'Validation failed', array(), array('description' => 'Description is required'));
		}
		if (self::tooLong($text, 5000))
		{
			$this->respond(422, 'Validation failed', array(), array('description' => 'Maximum 5000 characters allowed'));
		}
		// The back office edits descriptions as HTML: plain paragraphs become <p>.
		$paras = preg_split("/\R\s*\R/", str_replace("\r\n", "\n", $text));
		$html = '';
		foreach ($paras as $para)
		{
			$para = trim($para);
			if ($para !== '')
			{
				$html .= '<p>' . nl2br(htmlspecialchars($para, ENT_QUOTES, 'UTF-8'), false) . '</p>';
			}
		}
		$i18n = array();
		foreach (self::companyLocales((int) $signup['company_id']) as $lid)
		{
			$i18n[$lid] = array('description' => $html);
		}
		pjMultiLangModel::factory()->saveMultiLang($i18n, (int) $calendar['id'], 'pjCalendar');

		$this->completeStep($signup, 5);
		$this->respond(200, 'Description saved.', $this->signupState($signup));
	}

	/** Themes (colour palettes) for step 6. signup_token optional: tells whether the plan allows choosing one. */
	public function pjActionGetThemes()
	{
		$this->setAjax(true);
		$allowed = true;
		if ($this->param('signup_token') !== '')
		{
			$this->requireSignupTable();
			$signup = $this->signupFromToken();
			$allowed = $this->themesAllowed((int) $signup['company_id']);
		}
		$themes = array();
		foreach (pjColorHelper::getPalettes() as $id => $p)
		{
			$themes[] = array(
				'id' => (string) $id, 'name' => $p['name'],
				'colors' => array('brand' => $p['brand'], 'accent' => $p['accent'], 'text' => $p['text'],
					'background' => $p['bg'], 'price' => $p['price'], 'page_background' => $p['pageBg']),
			);
		}
		$this->respond(200, 'OK', array('themes' => $themes, 'default_id' => '0', 'allowed' => $allowed));
	}

	private function themesAllowed($company_id)
	{
		return !(class_exists('pjEntitlement') && pjEntitlement::isReady()) || pjEntitlement::allows((int) $company_id, 'themes');
	}

	/**
	 * Step 6 (last): theme. signup_token, palette_id* (from pjActionGetThemes), font_family (optional)
	 * Saved like Options > Theme colours. Completes the sign-up.
	 */
	public function pjActionSignupTheme()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$signup = $this->signupFromToken();
		$this->requireStep($signup, 6);
		$this->signupProperty($signup);
		$company_id = (int) $signup['company_id'];

		$palettes = pjColorHelper::getPalettes();
		$palette_id = $this->param('palette_id');
		$font = trim(preg_replace('/[^a-zA-Z0-9 ]/', '', $this->param('font_family')));
		$errors = array();
		if ($palette_id === '')
		{
			$errors['palette_id'] = 'Please choose a theme';
		} elseif (!ctype_digit($palette_id) || !isset($palettes[$palette_id])) {
			$errors['palette_id'] = 'Invalid theme';
		}
		if (self::tooLong($font, 50))
		{
			$errors['font_family'] = 'Maximum 50 characters allowed';
		}
		if (!empty($errors))
		{
			$this->respond(422, 'Validation failed', array(), $errors);
		}

		$applied = $this->themesAllowed($company_id);
		if ($applied)
		{
			$p = $palettes[$palette_id];
			$saved = array(
				'o_brand_color' => $p['brand'], 'o_accent_color' => $p['accent'], 'o_text_color' => $p['text'],
				'o_background_color' => $p['bg'], 'o_price_color' => $p['price'], 'o_page_background_color' => $p['pageBg'],
				'o_font_family' => $font, 'o_color_palette' => (string) $palette_id,
				// booking-widget colours follow the theme (as pjAdminOptions::pjActionSaveThemeColors)
				'o_background_month' => $p['brand'], 'o_color_month' => '#ffffff', 'o_background_nav' => $p['brand'], 'o_color_available' => $p['text'],
			);
			$m = pjCompanyOptionModel::factory();
			foreach ($saved as $key => $value)
			{
				$m->saveValue($company_id, $key, $value);
			}
		}

		$this->completeStep($signup, 6);
		$state = $this->signupState($signup);
		$state['theme_applied'] = $applied;
		$this->respond(200, $applied ? 'Registration completed. You can now log in.' : 'Registration completed. Your plan uses the default theme. You can now log in.', $state);
	}

	/** Where is this sign-up? signup_token -> next step + saved values. */
	public function pjActionSignupStatus()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();
		$signup = $this->signupFromToken();
		$this->respond(200, 'OK', $this->signupState($signup));
	}

	/** Continue an unfinished sign-up without a token: email + password -> new token. */
	public function pjActionSignupResume()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$email = $this->param('email');
		$password = $this->password('password');
		$errors = array();
		if ($email === '')
		{
			$errors['email'] = 'Email is required';
		} elseif (!pjValidation::pjActionEmail($email)) {
			$errors['email'] = 'Invalid email format';
		}
		if ($password === '')
		{
			$errors['password'] = 'Password is required';
		}
		if (!empty($errors))
		{
			$this->respond(422, 'Validation failed', array(), $errors);
		}

		$user = $this->findUserByEmail($email);
		if (empty($user) || !$this->isCurrentPassword((int) $user['id'], $password))
		{
			$this->respond(401, 'Invalid email or password');
		}
		$signup = pjCompanySignupModel::factory()->where('t1.user_id', (int) $user['id'])->limit(1)->findAll()->getDataIndex(0);
		if (empty($signup))
		{
			$this->respond(404, 'No registration found for this account. Please log in.');
		}
		if ($signup['status'] === 'completed')
		{
			$this->respond(409, 'Registration is already completed. Please log in.', $this->signupState($signup));
		}
		$token = $this->issueToken((int) $signup['id']);
		$this->respond(200, 'Please continue your registration.', array_merge(array('signup_token' => $token, 'resumed' => true), $this->signupState($signup)));
	}

	/** Live e-mail check. email (+ signup_token when the user edits his own step 1). */
	public function pjActionCheckEmail()
	{
		$this->setAjax(true);
		$this->requirePost();
		$email = $this->param('email');
		if ($email === '')
		{
			$this->respond(422, 'Validation failed', array(), array('email' => 'Email is required'));
		}
		if (!pjValidation::pjActionEmail($email))
		{
			$this->respond(422, 'Validation failed', array(), array('email' => 'Invalid email format'));
		}
		$except = 0;
		if ($this->param('signup_token') !== '')
		{
			$this->requireSignupTable();
			$signup = $this->signupFromToken();
			$except = (int) $signup['user_id'];
		}
		if ($this->findUserByEmail($email, $except))
		{
			$this->respond(422, 'Email already exists', array('available' => false), array('email' => 'Email already exists'));
		}
		$this->respond(200, 'Email is available', array('available' => true));
	}

	/** Live password check against the password rules. password */
	public function pjActionCheckPassword()
	{
		$this->setAjax(true);
		$this->requirePost();
		$password = $this->password('password');
		if ($password === '')
		{
			$this->respond(422, 'Validation failed', array(), array('password' => 'Password is required'));
		}
		$rule = $this->checkPasswordRules($password);
		if ($rule !== true)
		{
			$this->respond(422, 'Validation failed', array('valid' => false), array('password' => $rule));
		}
		$this->respond(200, 'Password is valid', array('valid' => true));
	}

	/** Active countries for the company form. */
	public function pjActionGetCountries()
	{
		$this->setAjax(true);
		$locale_id = (int) $this->getLocaleId();
		if ($locale_id <= 0)
		{
			$def = pjBaseLocaleModel::factory()->where('t1.is_default', 1)->limit(1)->findAll()->getDataIndex(0);
			$locale_id = !empty($def) ? (int) $def['id'] : 1;
		}
		$countries = pjBaseCountryModel::factory()
			->select('t1.id, t1.alpha_2, t2.content AS name')
			->join('pjMultiLang', "t2.model='pjBaseCountry' AND t2.foreign_id=t1.id AND t2.field='name' AND t2.locale='" . $locale_id . "'", 'left outer')
			->where('t1.status', 'T')
			->where('t1.company_id IS NULL')
			->orderBy('`name` ASC')
			->findAll()
			->getData();
		$this->respond(200, 'OK', array('countries' => $countries));
	}
}
?>
