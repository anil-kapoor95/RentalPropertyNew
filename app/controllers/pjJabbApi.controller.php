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
 *   Step 2  pjActionSignupProperty     property basics (creates the company too, filled in from the
 *                                      account and the property address, when there is none yet)
 *   Step 3  pjActionSignupCompany      company details, prefilled from step 2 (+ optional logo)
 *   Step 4  pjActionSignupPhotos       property photos (pjActionSignupDeletePhoto removes one)
 *   Step 5  pjActionSignupDescription  page headline, description, guest questions
 *           (pjActionSignupGenerateDescription: AI suggestion in English, tone warm|premium|simple, Options > AI settings)
 *   Step 6  pjActionSignupTheme        colour theme (pjActionGetThemes lists them)  -> sign-up completed
 *
 *   pjActionSignupStatus   signup_token        -> next step + saved data (prefill)
 *   pjActionSignupResume   email + password    -> new signup_token + next step (token lost / other device)
 *   pjActionSignupLogin    email + password    -> signup_token + state, also when the sign-up is completed (host log-in)
 *   pjActionSignupLogout   signup_token        -> the token stops working (log out on this device)
 *   pjActionSignupGoogle   access_token, client_id -> Google account: log in, or {new_account, google{email, name}} to sign up
 *   pjActionGetAuthConfig                      -> {google_client_id} for "Continue with Google" (app/config/google.php)
 *   pjActionCheckEmail     email               -> available or "Email already exists"
 *   pjActionCheckPassword  password            -> OK or the password rule that is not met
 *   pjActionGetCountries                       -> countries for the company / property forms
 *
 *   pjActionCheckAirbnbListing signup_token + listing_id       -> is this Airbnb listing already connected (iCal feeds)?
 *   pjActionGetPaymentMethods  signup_token                    -> saved Razorpay / Stripe settings of the sign-up property (prefill)
 *   pjActionSavePaymentMethod  signup_token + gateway settings -> online payment gateway of the sign-up property
 *                              (same table and fields as Properties > Payments; secret keys are never sent back)
 *
 * Requests: POST, form-data / x-www-form-urlencoded (through api.php) or a JSON body.
 * Responses: {status: OK|ERR, code, message, errors{field: text}, data{}}.
 */
class pjJabbApi extends pjAppController
{
	const ROLE_COMPANY = 4;

	/** Steps of the sign-up, in order (step number => name). The last one completes the sign-up. */
	private static $steps = array(1 => 'account', 2 => 'property', 3 => 'company', 4 => 'photos', 5 => 'description', 6 => 'theme');

	/*
	 * Limits of the sign-up, the life time of its token and the writing tones of the AI texts are set by the
	 * administrator in Options > Sign-up settings: pjSignupConfig::get('max_photos' | 'max_ai_generations' |
	 * 'max_amenities' | 'max_amenity_length' | 'amenity_category' | 'max_faqs' | 'token_days'), pjSignupConfig::tone().
	 */

	/** Property types the front end offers (saved as the property text "property_type"). */
	private static $propertyTypes = array('villa', 'homestay', 'apartment', 'cottage', 'resort');

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

	/** Work done after the answer is sent (e-mails): the caller does not wait for the mail server. */
	private static $afterResponse = array();

	private static function afterResponse($fn)
	{
		self::$afterResponse[] = $fn;
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
		if (empty(self::$afterResponse))
		{
			pjAppController::jsonResponse($resp);
		}
		// Send the whole answer first (length known, connection closed), then do the queued work
		$json = pjAppController::jsonEncode($resp);
		ignore_user_abort(true);
		@set_time_limit(120);
		while (ob_get_level() > 0)
		{
			@ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Encoding: none');
		header('Content-Length: ' . strlen($json));
		header('Connection: close');
		echo $json;
		@flush();
		if (function_exists('fastcgi_finish_request'))
		{
			fastcgi_finish_request();
		}
		$jobs = self::$afterResponse;
		self::$afterResponse = array();
		foreach ($jobs as $job)
		{
			try {
				call_user_func($job);
			} catch (\Throwable $e) {
				error_log('API after-response job: ' . $e->getMessage());
			}
		}
		exit;
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
		// any country: + country code and number, 6 to 15 digits (E.164)
		$digits = strlen(preg_replace('/\D/', '', $phone));
		return (bool) preg_match('/^\+?[0-9\s\-().]{6,30}$/', $phone) && $digits >= 6 && $digits <= 15;
	}

	/* ------------------------------------------------------------------
	 * Throttling (brute force / enumeration protection)
	 * ------------------------------------------------------------------ */

	/** bucket => array(max requests, window in seconds). */
	private static $throttleRules = array(
		'login_ip'    => array(30, 900),  // failed log-ins from one IP address
		'login_email' => array(8, 900),   // failed log-ins for one e-mail address
		'check_email' => array(60, 900),  // live e-mail checks from one IP address
		'airbnb'      => array(30, 3600), // Airbnb listing checks of one sign-up
		'booking'     => array(10, 900),  // bookings made on the websites from one IP address
	);

	/**
	 * Client IP for throttling. api.php calls this controller over HTTP from the server itself and sends the
	 * caller's address as api_client_ip; it is only trusted on such local calls (forwarded headers can be forged).
	 */
	private function throttleIp()
	{
		$remote = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
		$local = array('127.0.0.1', '::1', isset($_SERVER['SERVER_ADDR']) ? (string) $_SERVER['SERVER_ADDR'] : '');
		$forwarded = $this->param('api_client_ip');
		if ($forwarded !== '' && in_array($remote, $local, true) && filter_var($forwarded, FILTER_VALIDATE_IP))
		{
			return $forwarded;
		}
		return $remote;
	}

	/** Address of the sign-up website that made the call (api.php sends it only for an allowed origin), or ''. */
	private function signupSiteUrl()
	{
		$remote = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
		$local = array('127.0.0.1', '::1', isset($_SERVER['SERVER_ADDR']) ? (string) $_SERVER['SERVER_ADDR'] : '');
		$url = $this->param('api_site_url');
		return in_array($remote, $local, true) && preg_match('~^https?://[^\s/]+$~i', $url) ? $url : '';
	}

	/** Folder of the throttle counters: app/web/upload/api_throttle (web access denied), else the system temp folder. */
	private static function throttleDir()
	{
		static $dir = null;
		if ($dir !== null)
		{
			return $dir;
		}
		$candidates = array(PJ_UPLOAD_PATH . 'api_throttle', rtrim(sys_get_temp_dir(), '/\\') . '/stayowned_api_throttle');
		foreach ($candidates as $candidate)
		{
			if (!is_dir($candidate))
			{
				@mkdir($candidate, 0755, true);
			}
			if (is_dir($candidate) && is_writable($candidate))
			{
				if (!is_file($candidate . '/.htaccess'))
				{
					@file_put_contents($candidate . '/.htaccess', "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n");
					@file_put_contents($candidate . '/index.html', '');
				}
				return $dir = $candidate;
			}
		}
		return $dir = '';
	}

	private static function throttleFile($bucket, $key)
	{
		$dir = self::throttleDir();
		if ($dir === '')
		{
			return '';
		}
		return $dir . '/' . $bucket . '_' . hash('sha256', ROOT_PATH . '|' . $bucket . '|' . strtolower($key)) . '.json';
	}

	/** Reads (and with $add > 0 increments) the counter of a bucket: array(count, resets_at). */
	private static function throttleCount($bucket, $key, $add = 0)
	{
		$window = self::$throttleRules[$bucket][1];
		$now = time();
		$file = self::throttleFile($bucket, $key);
		$fp = $file !== '' ? @fopen($file, 'c+') : false;
		if ($fp === false)
		{
			// Never lock people out because the counter can't be stored.
			return array(0, $now + $window);
		}
		flock($fp, $add > 0 ? LOCK_EX : LOCK_SH);
		$data = json_decode((string) stream_get_contents($fp), true);
		$count = is_array($data) && isset($data['c'], $data['r']) && (int) $data['r'] > $now ? (int) $data['c'] : 0;
		$resets = $count > 0 ? (int) $data['r'] : $now + $window;
		if ($add > 0)
		{
			$count += $add;
			ftruncate($fp, 0);
			rewind($fp);
			fwrite($fp, json_encode(array('c' => $count, 'r' => $resets)));
			fflush($fp);
		}
		flock($fp, LOCK_UN);
		fclose($fp);
		return array($count, $resets);
	}

	private static function throttleHit($bucket, $key)
	{
		self::throttleCount($bucket, $key, 1);
	}

	private static function throttleClear($bucket, $key)
	{
		$file = self::throttleFile($bucket, $key);
		if ($file !== '' && is_file($file))
		{
			@unlink($file);
		}
	}

	/** Answers 429 when the bucket is full. */
	private function throttleCheck($bucket, $key, $message = 'Too many attempts. Please wait a few minutes and try again.')
	{
		list($count, $resets) = self::throttleCount($bucket, $key);
		if ($count >= self::$throttleRules[$bucket][0])
		{
			header('Retry-After: ' . max(1, $resets - time()));
			$this->respond(429, $message);
		}
	}

	/** Checks e-mail + password with the log-in throttle (failed attempts per IP and per e-mail). */
	private function verifyCredentials($email, $password)
	{
		$ip = $this->throttleIp();
		$message = 'Too many failed log-in attempts. Please wait 15 minutes and try again.';
		$this->throttleCheck('login_ip', $ip, $message);
		$this->throttleCheck('login_email', $email, $message);
		$user = $this->findUserByEmail($email);
		if (empty($user) || !$this->isCurrentPassword((int) $user['id'], $password))
		{
			self::throttleHit('login_ip', $ip);
			self::throttleHit('login_email', $email);
			$this->respond(401, 'Invalid email or password');
		}
		self::throttleClear('login_email', $email);
		return $user;
	}

	/** Back-office user with this e-mail (any back-office role), except $except_user_id. Same rule as Companies > Add. */
	private function findUserByEmail($email, $except_user_id = 0)
	{
		return pjAuthUserModel::factory()
			->select('t1.id, t1.role_id, t1.company_id, t1.email, t1.name, t1.phone, t1.status, t1.locked')
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
			'token_expires' => date('Y-m-d H:i:s', strtotime('+' . pjSignupConfig::get('token_days') . ' days')),
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
		$signup = self::normalizeStep($signup);
		$signup['user'] = $user;
		if ((int) $signup['company_id'] > 0)
		{
			self::useCompanyTimezone((int) $signup['company_id']);
		}
		return $signup;
	}

	/**
	 * Dates written for a company (bookings, subscriptions, sign-up steps) use the company's time zone
	 * (Company options > General), as the back office does when that company is active.
	 */
	private static function useCompanyTimezone($company_id)
	{
		static $done = array();
		$company_id = (int) $company_id;
		if ($company_id <= 0 || isset($done[$company_id]))
		{
			return;
		}
		$done[$company_id] = true;
		$settings = self::companySettings($company_id);
		$tz = isset($settings['timezone']) ? (string) $settings['timezone'] : '';
		if ($tz !== '' && in_array($tz, timezone_identifiers_list(), true))
		{
			pjTimezone::factory()->setAllTimezones($tz);
		}
	}

	/**
	 * Until 2026-10-28 the company was step 2 and the property step 3. A sign-up that stopped after the old
	 * company step (step 2 without a property) has not done the new step 2 (property) yet: it continues there,
	 * and its company is kept (the company step shows it again).
	 */
	private static function normalizeStep($signup)
	{
		if (!empty($signup) && $signup['status'] !== 'completed' && (int) $signup['step'] === 2 && empty($signup['calendar_id']))
		{
			pjCompanySignupModel::factory()->set('id', (int) $signup['id'])->modify(array('step' => 1));
			$signup['step'] = 1;
		}
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
	 * Step $step is done. The login becomes active once the company exists (created with the property, step 2), as with
	 * a company added in the back office; only a login without a company stays inactive.
	 * After the last step the sign-up is completed.
	 */
	private function completeStep($signup, $step, $extra = array())
	{
		$now = date('Y-m-d H:i:s');
		$data = array_merge($extra, array('step' => max((int) $signup['step'], $step), 'modified' => $now));
		$done = $step >= max(array_keys(self::$steps));
		// Activate once, when the company step is first done (an administrator may disable it later).
		if ((int) $signup['step'] < 2 && $step >= 2)
		{
			pjAuthUserModel::factory()->set('id', (int) $signup['user_id'])->modify(array('status' => 'T'));
		}
		if ($done)
		{
			$data['status'] = 'completed';
			$data['completed_at'] = $now;
		}
		pjCompanySignupModel::factory()->set('id', (int) $signup['id'])->modify($data);
		return $done;
	}

	/** Property created in step 2 (with its company), or stops. */
	private function signupProperty($signup)
	{
		$calendar = !empty($signup['calendar_id']) ? pjCalendarModel::factory()->find((int) $signup['calendar_id'])->getData() : array();
		if (empty($calendar) || (int) $calendar['company_id'] !== (int) $signup['company_id'])
		{
			$this->respond(409, 'Please complete step 2 (property) first.', $this->signupState($signup));
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
		$locale = $locale > 0 ? $locale : 1;
		$rows = pjMultiLangModel::factory()
			->where('t1.model', 'pjCalendar')
			->where('t1.foreign_id', (int) $calendar_id)
			->findAll()
			->getData();
		// The website is in English: English texts when the property has them, otherwise the company default language.
		$en = pjBaseLocaleModel::factory()->unscoped()->where('t1.language_iso LIKE', 'en%')->orderBy('t1.id ASC')->limit(1)->findAll()->getDataIndex(0);
		$en_id = !empty($en) ? (int) $en['id'] : 0;
		$default = $english = array();
		foreach ($rows as $r)
		{
			if ((int) $r['locale'] === $locale)
			{
				$default[$r['field']] = $r['content'];
			}
			if ((int) $r['locale'] === $en_id && trim(strip_tags((string) $r['content'])) !== '')
			{
				$english[$r['field']] = $r['content'];
			}
		}
		return array_merge($default, $english);
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

	/**
	 * Default price of a property: the row the back office shows as "Default price" on Properties > Prices
	 * (tab 1, no dates, adults 0, children 0).
	 */
	private static function defaultPriceRow($calendar_id)
	{
		// Same rule as pjPriceModel (bookings): no dates. Prefer the row for any number of guests (adults/children 0).
		return pjPriceModel::factory()
			->where('t1.foreign_id', (int) $calendar_id)
			->where("(t1.date_from IS NULL OR t1.date_from = '0000-00-00')")
			->where("(t1.date_to IS NULL OR t1.date_to = '0000-00-00')")
			->orderBy('IFNULL(t1.adults, 0) + IFNULL(t1.children, 0) ASC, t1.tab_id ASC, t1.id ASC')
			->limit(1)
			->findAll()
			->getDataIndex(0);
	}

	/**
	 * Price per night shown to the host: the default price when it is the same for every weekday,
	 * otherwise the lowest weekday price (price_varies = true). Reads the table on every call, so a
	 * rate changed later in the back office is what the front end shows.
	 */
	private static function propertyPrice($calendar_id, $company_id)
	{
		// Currency as the back office shows it for this property: account options + the property's own options.
		$opt = self::calendarOptions($calendar_id, $company_id);
		$out = array(
			'price'        => null,
			'price_varies' => false,
			'currency'     => !empty($opt['o_currency']) ? (string) $opt['o_currency'] : self::accountCurrency($company_id),
		);
		$row = self::defaultPriceRow($calendar_id);
		if (empty($row))
		{
			return $out;
		}
		$days = array();
		foreach (array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun') as $d)
		{
			if ($row[$d] !== null && $row[$d] !== '' && (float) $row[$d] > 0)
			{
				$days[] = (float) $row[$d];
			}
		}
		if (!empty($days))
		{
			$out['price'] = min($days);
			$out['price_varies'] = max($days) - min($days) > 0.001;
		}
		return $out;
	}

	/** Options of a property as the back office uses them (account options, then the property's own). */
	private static function calendarOptions($calendar_id, $company_id)
	{
		return array_merge(pjCompanyContext::basePairs((int) $company_id), pjOptionModel::factory()->getPairs((int) $calendar_id));
	}

	/** Currency code of the account (o_currency: the company's own setting, otherwise the general one). */
	private static function accountCurrency($company_id)
	{
		$base = pjCompanyContext::basePairs((int) $company_id);
		return !empty($base['o_currency']) ? (string) $base['o_currency'] : '';
	}

	/** Sets the default price for every weekday (adds the row when the property has none). */
	private static function saveDefaultPrice($calendar_id, $price)
	{
		$days = array('mon' => $price, 'tue' => $price, 'wed' => $price, 'thu' => $price, 'fri' => $price, 'sat' => $price, 'sun' => $price);
		$row = self::defaultPriceRow($calendar_id);
		if (!empty($row))
		{
			pjPriceModel::factory()->set('id', (int) $row['id'])->modify($days);
			return;
		}
		pjPriceModel::factory()->reset()->setAttributes(array_merge(array(
			'foreign_id' => (int) $calendar_id,
			'tab_id'     => 1,
			'season'     => __('prices_price_default', true),
			'date_from'  => null,
			'date_to'    => null,
			'adults'     => 0,
			'children'   => 0,
		), $days))->insert();
	}

	/** Saved guest questions ([{question, answer}]) from their JSON text. */
	private static function decodeFaqs($json)
	{
		$list = json_decode((string) $json, true);
		$faqs = array();
		if (is_array($list))
		{
			foreach ($list as $f)
			{
				if (is_array($f) && isset($f['question'], $f['answer']) && is_string($f['question']) && is_string($f['answer'])
					&& trim($f['question']) !== '' && trim($f['answer']) !== '')
				{
					$faqs[] = array('question' => trim($f['question']), 'answer' => trim($f['answer']));
				}
			}
		}
		return array_slice($faqs, 0, pjSignupConfig::get('max_faqs'));
	}

	/** What the front end needs to continue: steps, next step, and the saved values for prefill. */
	private function signupState($signup)
	{
		$signup = self::normalizeStep(pjCompanySignupModel::factory()->find((int) $signup['id'])->getData());
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
				// link of the company page on the website (/stays/<slug>)
				$company['slug'] = self::companyLink((int) $c['id'], $c['name']);
			}
		}

		$property = null;
		$photos = array();
		$description = null;
		$headline = null;
		$faqs = array();
		$tone = null;
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
					'ical_urls'  => self::propertyFeeds($cal['id']),
				);
				// price per night from the prices table (may have been changed in the back office)
				$property = array_merge($property, self::propertyPrice($cal['id'], $signup['company_id']));
				// Guests: "Max people" of the property (Limits in the back office)
				$opt = self::calendarOptions($cal['id'], $signup['company_id']);
				$property['id'] = (int) $cal['id'];
				// stored link of the property (website/<slug>); unique even when two properties have the same name
				$property['slug'] = self::propertyLink((int) $cal['id'], isset($t['name']) ? $t['name'] : '');
				$property['property_type'] = isset($t['property_type']) && in_array($t['property_type'], self::$propertyTypes, true) ? $t['property_type'] : null;
				$property['amenities'] = self::propertyAmenities($cal['id'], $signup['company_id']);
				$property['max_guests'] = isset($opt['o_max_people']) && (int) $opt['o_max_people'] > 0 ? (int) $opt['o_max_people'] : null;
				$photos = self::photoList($cal['id']);
				// stored as HTML for the back-office editor; the form gets plain text
				$description = isset($t['description']) && $t['description'] !== '' ? self::htmlToText($t['description']) : null;
				$headline = isset($t['headline']) && $t['headline'] !== '' ? $t['headline'] : null;
				$faqs = isset($t['faqs']) ? self::decodeFaqs($t['faqs']) : array();
				$tone = isset($t['content_tone']) && pjSignupConfig::tone($t['content_tone']) !== null ? $t['content_tone'] : null;
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
			'headline' => $headline,
			'faqs' => $faqs,
			'tone' => $tone,
			'tones' => pjSignupConfig::toneKeys(),
			// currency of the prices (Options of the account); the price itself is property.price
			'currency' => self::accountCurrency(!empty($signup['company_id']) ? (int) $signup['company_id'] : 0),
			// System options > Price format and currency position (for showing prices like the back office)
			'price_format' => self::priceFormat(!empty($signup['company_id']) ? (int) $signup['company_id'] : 0),
			'currency_sign' => self::currencySign(self::accountCurrency(!empty($signup['company_id']) ? (int) $signup['company_id'] : 0)),
			// Company options > General of the account (saved in step 3)
			'settings' => self::companySettings(!empty($signup['company_id']) ? (int) $signup['company_id'] : 0),
			'theme' => $theme,
			// AI writing: configured (Options > AI settings) and included in the company's plan
			'ai_available' => pjAi::isConfigured() && self::aiAllowed(!empty($signup['company_id']) ? (int) $signup['company_id'] : 0),
			'ai_locked'    => pjAi::isConfigured() && !self::aiAllowed(!empty($signup['company_id']) ? (int) $signup['company_id'] : 0),
			'ai_generations_left' => max(0, pjSignupConfig::get('max_ai_generations') - (int) (isset($signup['ai_generations']) ? $signup['ai_generations'] : 0)),
			// Options > Sign-up settings
			'limits' => array(
				'max_photos'         => pjSignupConfig::get('max_photos'),
				'max_faqs'           => pjSignupConfig::get('max_faqs'),
				'max_amenities'      => pjSignupConfig::get('max_amenities'),
				'max_amenity_length' => pjSignupConfig::get('max_amenity_length'),
			),
		);
		if ($signup['status'] === 'completed')
		{
			$state['login_url'] = PJ_INSTALL_URL . 'index.php?controller=pjBase&action=pjActionLogin';
		}
		return $state;
	}

	/** Account fields of step 1. $user_id: the sign-up user when he edits step 1 again. */
	private function validateAccount($user_id = 0, $google = false)
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

		// On a new sign-up the password is required (not with Google); when step 1 is edited again it is optional.
		if ((!$editing && !$google) || $password !== '' || $confirm !== '')
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

		// Step 1 is done once: an existing sign-up can't change its account details here.
		if ($this->param('signup_token') !== '')
		{
			$signup = $this->signupFromToken();
			if ($signup['status'] === 'completed')
			{
				$this->respond(409, 'Registration is already completed. Please log in.', $this->signupState($signup));
			}
			$this->respond(409, 'Your account is already created. Please continue with the next step.', $this->signupState($signup));
		}

		// Sign-up with Google: the e-mail is the verified Google one, the password is optional.
		$google = null;
		if ($this->param('google_access_token') !== '')
		{
			$google = $this->googleProfile($this->param('google_access_token'), $this->param('google_client_id'));
		}
		list($errors, $acc) = $this->validateAccount(0, $google !== null);
		if ($google !== null)
		{
			if (empty($errors['email']) && strtolower($acc['email']) !== $google['email'])
			{
				$errors['email'] = 'Use the e-mail of your Google account (' . $google['email'] . ')';
			}
			if ($acc['password'] === '')
			{
				// No password chosen: a random one (log in with Google; a password can be set later).
				$acc['password'] = bin2hex(random_bytes(16)) . 'Aa1!';
			}
		}

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

		// Login of the company (Company role). Inactive until its company exists (step 2),
		// so an account cannot log in to the back office without a company.
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
		// Options > Sign-up emails: welcome email to the host, "new sign-up" email to the administrator
		// welcome / new sign-up e-mails after the answer (the mail server can take seconds)
		$mail_host = array('name' => $acc['name'], 'email' => $acc['email'], 'phone' => $acc['phone']);
		$mail_site = $this->signupSiteUrl();
		self::afterResponse(function () use ($mail_host, $mail_site) {
			pjSignupMail::accountCreated($mail_host, $mail_site);
		});
		$this->respond(200, 'Account created. Please continue with your property details.',
			array_merge(array('signup_token' => $token, 'resumed' => false), $this->signupState(array('id' => $signup_id))));
	}

	/* ------------------------------------------------------------------
	 * Currencies and price format (System options in the back office)
	 * ------------------------------------------------------------------ */

	/** Currency codes offered in System options > Currency (the admin's list), in its order. */
	private static function currencyCodes()
	{
		$row = pjBaseOptionModel::factory()->where('t1.foreign_id', 1)->where('t1.key', 'o_currency')->limit(1)->findAll()->getDataIndex(0);
		if (empty($row))
		{
			return array();
		}
		list($list) = explode('::', (string) $row['value'] . '::');
		return array_values(array_unique(array_filter(array_map('trim', explode('|', $list)), function ($c) { return (bool) preg_match('/^[A-Z]{3}$/', $c); })));
	}

	/** Currency of the company's prices (its System options), or of the whole system. */
	private static function companyCurrency($company_id)
	{
		$pairs = pjCompanyContext::basePairs((int) $company_id);
		return !empty($pairs['o_currency']) ? (string) $pairs['o_currency'] : 'USD';
	}

	/**
	 * Sets the currency of the company's prices (its System options > Currency), keeping the admin's list.
	 * Returns false when the company has no own currency setting.
	 */
	private static function saveCompanyCurrency($company_id, $code)
	{
		$fid = pjCompanyContext::optionsForeignId((int) $company_id);
		$m = pjBaseOptionModel::factory();
		$row = $m->where('t1.foreign_id', $fid)->where('t1.key', 'o_currency')->limit(1)->findAll()->getDataIndex(0);
		if (empty($row))
		{
			return false;
		}
		list($list) = explode('::', (string) $row['value'] . '::');
		$m->reset()->where('foreign_id', $fid)->where('`key`', 'o_currency')->limit(1)->modifyAll(array('value' => $list . '::' . $code));
		pjCompanyContext::clearCache();
		return true;
	}

	/** Sign of a currency (Currencies data in the back office), or its code. */
	private static function currencySign($code)
	{
		$row = pjBaseCurrencyDataModel::factory()->where('t1.code', (string) $code)->limit(1)->findAll()->getDataIndex(0);
		$sign = !empty($row['sign']) ? trim(html_entity_decode((string) $row['sign'], ENT_QUOTES, 'UTF-8')) : '';
		return $sign !== '' ? $sign : (string) $code;
	}

	/** Price format for the front end: System options > Price format (1-6) and currency position. */
	private static function priceFormat($company_id)
	{
		$pairs = pjCompanyContext::basePairs((int) $company_id);
		$format = isset($pairs['o_price_format']) ? (int) $pairs['o_price_format'] : 4;
		return array(
			'price_format'   => $format >= 1 && $format <= 6 ? $format : 4,
			'currency_place' => isset($pairs['o_currency_place']) && $pairs['o_currency_place'] === 'front' ? 'front' : 'back',
		);
	}

	/**
	 * Currencies for the price field (System options > Currency) with their signs, the account's
	 * currency and the price format. signup_token optional (then the company's own settings).
	 * Data: currencies[] {code, sign}, default, price_format (1-6), currency_place (front|back)
	 */
	public function pjActionGetCurrencies()
	{
		$this->setAjax(true);
		$company_id = 0;
		if ($this->param('signup_token') !== '')
		{
			$this->requireSignupTable();
			$signup = $this->signupFromToken();
			$company_id = (int) $signup['company_id'];
		}
		$signs = pjBaseCurrencyDataModel::factory()->findAll()->getDataPair('code', 'sign');
		$currencies = array();
		foreach (self::currencyCodes() as $code)
		{
			$sign = isset($signs[$code]) ? trim(html_entity_decode((string) $signs[$code], ENT_QUOTES, 'UTF-8')) : '';
			$currencies[] = array('code' => $code, 'sign' => $sign !== '' ? $sign : $code);
		}
		$this->respond(200, 'OK', array_merge(array(
			'currencies' => $currencies,
			'default'    => self::companyCurrency($company_id),
		), self::priceFormat($company_id)));
	}

	/* ------------------------------------------------------------------
	 * General settings of the account (Company options > General): date / time format, time zone,
	 * first day of week, currency, price format, currency position. Not asked in the sign-up: saved
	 * automatically as the company's own options (defaults below, the currency of step 2 and the time
	 * zone of the company's country), so the back office and the website use them.
	 * ------------------------------------------------------------------ */

	/** Sign-up field => option key. */
	private static $settingKeys = array(
		'date_format'    => 'o_date_format',
		'time_format'    => 'o_time_format',
		'timezone'       => 'o_timezone',
		'week_start'     => 'o_week_start',
		'currency'       => 'o_currency',
		'price_format'   => 'o_price_format',
		'currency_place' => 'o_currency_place',
	);

	/** Defaults of a new account (as Company options > General ships them). */
	private static $settingDefaults = array(
		'date_format' => 'd.m.Y', 'time_format' => 'H:i', 'timezone' => 'Asia/Kolkata', 'week_start' => '1',
		'currency' => 'USD', 'price_format' => '4', 'currency_place' => 'back',
	);

	/** Current settings of the company (its own options, otherwise the system's). */
	private static function companySettings($company_id)
	{
		$pairs = pjCompanyContext::basePairs((int) $company_id);
		$out = array();
		foreach (self::$settingKeys as $field => $key)
		{
			$out[$field] = isset($pairs[$key]) && (string) $pairs[$key] !== '' ? (string) $pairs[$key] : self::$settingDefaults[$field];
		}
		return $out;
	}

	/**
	 * Saves settings as the company's own options (Company options > General): an enum keeps its list and gets
	 * the new selection ("a|b|c::b"), the time zone is a plain value. Missing rows are copied from the system first.
	 */
	private static function saveCompanySettings($company_id, $values)
	{
		$fid = pjCompanyContext::optionsForeignId((int) $company_id);
		$m = pjBaseOptionModel::factory();
		foreach ($values as $field => $value)
		{
			if (!isset(self::$settingKeys[$field]))
			{
				continue;
			}
			$key = self::$settingKeys[$field];
			$row = $m->reset()->where('t1.foreign_id', $fid)->where('t1.key', $key)->limit(1)->findAll()->getDataIndex(0);
			if (empty($row))
			{
				continue;
			}
			if ($row['type'] === 'enum')
			{
				list($list) = explode('::', (string) $row['value'] . '::');
				$new = $list . '::' . $value;
			} else {
				$new = (string) $value;
			}
			if ($new !== (string) $row['value'])
			{
				$m->reset()->where('foreign_id', $fid)->where('`key`', $key)->limit(1)->modifyAll(array('value' => $new));
			}
		}
		pjCompanyContext::clearCache();
	}

	/* ------------------------------------------------------------------
	 * Property names (unique)
	 * ------------------------------------------------------------------ */

	/** "  Green   CASA " / "Green &amp; Casa" -> "green casa" / "green & casa": names compared this way. */
	private static function propertyNameKey($name)
	{
		$name = html_entity_decode(strip_tags((string) $name), ENT_QUOTES, 'UTF-8');
		$name = trim(preg_replace('/\s+/u', ' ', $name));
		return function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
	}

	/**
	 * Another property (any account, any language) already has this name. Compared without
	 * case, extra spaces or HTML entities, so "Green Casa", "green  casa" and "GREEN CASA" are the same.
	 */
	private static function propertyNameTaken($name, $except_calendar_id = 0)
	{
		$key = self::propertyNameKey($name);
		if ($key === '')
		{
			return false;
		}
		$rows = pjMultiLangModel::factory()
			->select('t1.foreign_id, t1.content')
			->where('t1.model', 'pjCalendar')
			->where('t1.field', 'name')
			->where('t1.foreign_id !=', (int) $except_calendar_id)
			->findAll()
			->getData();
		$ids = array();
		foreach ($rows as $row)
		{
			if (self::propertyNameKey($row['content']) === $key)
			{
				$ids[(int) $row['foreign_id']] = true;
			}
		}
		if (empty($ids))
		{
			return false;
		}
		// Only properties that still exist count.
		return (int) pjCalendarModel::factory()->whereIn('t1.id', array_keys($ids))->findCount()->getData() > 0;
	}

	/** Another company (any account) already has this name (compared like property names). */
	private static function companyNameTaken($name, $except_company_id = 0)
	{
		$key = self::propertyNameKey($name);
		if ($key === '')
		{
			return false;
		}
		$rows = pjCompanyModel::factory()
			->select('t1.id, t1.name')
			->where('t1.id !=', (int) $except_company_id)
			->findAll()
			->getData();
		foreach ($rows as $row)
		{
			if (self::propertyNameKey($row['name']) === $key)
			{
				return true;
			}
		}
		return false;
	}

	/** $name, or "$name 2", "$name 3"… when another company has it (placeholder names stay unique too). */
	private static function uniqueCompanyName($name, $except_company_id = 0)
	{
		$name = self::cut(trim($name) !== '' ? trim($name) : 'My company', 240);
		$try = $name;
		for ($n = 2; $n < 1000 && self::companyNameTaken($try, $except_company_id); $n++)
		{
			$try = $name . ' ' . $n;
		}
		return $try;
	}

	/** Live check of the company name. name (+ signup_token: the sign-up's own company doesn't count) */
	public function pjActionCheckCompanyName()
	{
		$this->setAjax(true);
		$this->requirePost();
		$name = trim(preg_replace('/\s+/u', ' ', $this->param('name')));
		if ($name === '')
		{
			$this->respond(422, 'Validation failed', array(), array('name' => 'Company name is required'));
		}
		$except = 0;
		if ($this->param('signup_token') !== '')
		{
			$this->requireSignupTable();
			$signup = $this->signupFromToken();
			$except = (int) $signup['company_id'];
		}
		if (self::companyNameTaken($name, $except))
		{
			$this->respond(422, 'Company name is taken', array('available' => false), array('name' => 'This company name is already taken. Please choose another name.'));
		}
		$this->respond(200, 'Company name is available', array('available' => true));
	}

	/** Live check of the property name. name (+ signup_token: the sign-up's own property doesn't count) */
	public function pjActionCheckPropertyName()
	{
		$this->setAjax(true);
		$this->requirePost();
		$name = trim(preg_replace('/\s+/u', ' ', $this->param('name')));
		if ($name === '')
		{
			$this->respond(422, 'Validation failed', array(), array('name' => 'Property name is required'));
		}
		$except = 0;
		if ($this->param('signup_token') !== '')
		{
			$this->requireSignupTable();
			$signup = $this->signupFromToken();
			$except = (int) $signup['calendar_id'];
		}
		if (self::propertyNameTaken($name, $except))
		{
			$this->respond(422, 'Property name is taken', array('available' => false), array('name' => 'This property name is already taken. Please choose another name.'));
		}
		$this->respond(200, 'Property name is available', array('available' => true));
	}

	/* ------------------------------------------------------------------
	 * AI writing
	 * ------------------------------------------------------------------ */

	/** AI writing is open to every sign-up (no plan needed); this is the one place to gate it again later. */
	private static function aiAllowed($company_id)
	{
		return true;
	}

	/** "Pro", "Pro or Business" … : names of the active, public plans with a feature (Subscriptions > Plans). */
	private static function planNamesWith($feature)
	{
		$names = array();
		if (class_exists('pjEntitlement') && pjEntitlement::isReady())
		{
			foreach (pjEntitlement::plans(true) as $plan)
			{
				if ((int) $plan['is_public'] === 1 && in_array($feature, $plan['feature_list'], true))
				{
					$names[] = (string) $plan['name'];
				}
			}
		}
		if (empty($names))
		{
			return '';
		}
		$last = array_pop($names);
		return empty($names) ? $last : implode(', ', $names) . ' or ' . $last;
	}

	/* ------------------------------------------------------------------
	 * Amenities (Options > Features of the company, Properties > Features)
	 * ------------------------------------------------------------------ */

	/** "Wi-Fi", "wifi", " WIFI " -> "wifi": the same amenity whatever its spelling. */
	private static function amenityKey($name)
	{
		$name = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
		return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', $name);
	}

	/**
	 * amenities parameter (JSON array of names) -> clean, unique names, or an error text.
	 * Returns array(names, error).
	 */
	private function amenitiesParam()
	{
		$raw = $this->param('amenities');
		$list = $raw === '' ? array() : json_decode($raw, true);
		if (!is_array($list))
		{
			return array(array(), 'Send the amenities as a list');
		}
		$names = array();
		foreach ($list as $item)
		{
			if (!is_string($item))
			{
				continue;
			}
			$name = trim(preg_replace('/\s+/u', ' ', strip_tags($item)));
			$key = self::amenityKey($name);
			if ($key === '' || isset($names[$key]))
			{
				continue;
			}
			if (self::tooLong($name, pjSignupConfig::get('max_amenity_length')))
			{
				return array(array(), sprintf('Amenity names can have at most %u characters', pjSignupConfig::get('max_amenity_length')));
			}
			$names[$key] = $name;
		}
		if (count($names) > pjSignupConfig::get('max_amenities'))
		{
			return array(array(), sprintf('A property can have at most %u amenities', pjSignupConfig::get('max_amenities')));
		}
		return array($names, null);
	}

	/** Features of the company: amenity key => feature id (names in every language count). */
	private static function companyFeatureKeys($company_id)
	{
		$ids = pjFeatureModel::factory()->where('t1.company_id', (int) $company_id)->orderBy('t1.id ASC')->findAll()->getDataPair(null, 'id');
		$keys = array();
		if (!empty($ids))
		{
			$rows = pjMultiLangModel::factory()
				->where('t1.model', 'pjFeature')
				->where('t1.field', 'name')
				->whereIn('t1.foreign_id', array_map('intval', $ids))
				->orderBy('t1.foreign_id ASC')
				->findAll()
				->getData();
			foreach ($rows as $r)
			{
				$key = self::amenityKey((string) $r['content']);
				if ($key !== '' && !isset($keys[$key]))
				{
					$keys[$key] = (int) $r['foreign_id'];
				}
			}
		}
		return $keys;
	}

	/**
	 * Links the property to exactly these amenities (as Properties > Features does). Amenities the company
	 * doesn't have yet are added to its features (Options > Features); an existing one is reused whatever
	 * its spelling ("Wi-Fi" = "wifi"), so nothing is created twice.
	 */
	private static function saveAmenities($calendar_id, $company_id, $names)
	{
		$keys = self::companyFeatureKeys($company_id);
		$locales = self::companyLocales($company_id);
		$ids = array();
		foreach ($names as $key => $name)
		{
			if (!isset($keys[$key]))
			{
				$id = pjFeatureModel::factory()->setAttributes(array(
					'company_id'  => (int) $company_id,
					'category_id' => pjSignupConfig::get('amenity_category'),
					'status'      => 'T',
				))->insert()->getInsertId();
				if ($id === false || (int) $id <= 0)
				{
					continue;
				}
				$i18n = array();
				foreach ($locales as $lid)
				{
					$i18n[$lid] = array('name' => $name);
				}
				pjMultiLangModel::factory()->saveMultiLang($i18n, (int) $id, 'pjFeature');
				$keys[$key] = (int) $id;
			}
			$ids[$keys[$key]] = true;
		}
		pjCalendarFeatureModel::factory()->where('calendar_id', (int) $calendar_id)->eraseAll();
		if (!empty($ids))
		{
			$model = pjCalendarFeatureModel::factory();
			foreach (array_keys($ids) as $fid)
			{
				$model->addBatchRow(array((int) $calendar_id, (int) $fid));
			}
			$model->setBatchFields(array('calendar_id', 'feature_id'))->insertBatch();
		}
	}

	/** Names of the property's amenities (company language; English when there is one). */
	private static function propertyAmenities($calendar_id, $company_id)
	{
		$fids = pjCalendarFeatureModel::factory()->where('t1.calendar_id', (int) $calendar_id)->findAll()->getDataPair(null, 'feature_id');
		if (empty($fids))
		{
			return array();
		}
		$fids = array_map('intval', $fids);
		$active = pjFeatureModel::factory()->whereIn('t1.id', $fids)->where('t1.status', 'T')->findAll()->getDataPair(null, 'id');
		if (empty($active))
		{
			return array();
		}
		$locale = pjCompanyContext::companyDefaultLocale((int) $company_id);
		$en = pjBaseLocaleModel::factory()->unscoped()->where('t1.language_iso LIKE', 'en%')->orderBy('t1.id ASC')->limit(1)->findAll()->getDataIndex(0);
		$en_id = !empty($en) ? (int) $en['id'] : 0;
		$rows = pjMultiLangModel::factory()
			->where('t1.model', 'pjFeature')
			->where('t1.field', 'name')
			->whereIn('t1.foreign_id', array_map('intval', $active))
			->findAll()
			->getData();
		// English name first, then the company's default language, then any other.
		$names = array();
		$ranks = array();
		foreach ($rows as $r)
		{
			$fid = (int) $r['foreign_id'];
			$content = trim((string) $r['content']);
			if ($content === '')
			{
				continue;
			}
			$rank = (int) $r['locale'] === $en_id ? 0 : ((int) $r['locale'] === (int) $locale ? 1 : 2);
			if (!isset($ranks[$fid]) || $rank < $ranks[$fid])
			{
				$ranks[$fid] = $rank;
				$names[$fid] = $content;
			}
		}
		$out = array();
		foreach ($active as $fid)
		{
			if (isset($names[(int) $fid]))
			{
				$out[] = $names[(int) $fid];
			}
		}
		return $out;
	}

	/**
	 * Creates the company of a sign-up (same set-up as Companies > Add company) and links the login to it.
	 * $data: company fields (values or ':NULL'). Returns the company id, or stops.
	 */
	private function createSignupCompany($user, $data)
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
		// The login belongs to the company.
		pjAuthUserModel::factory()->set('id', (int) $user['id'])->modify(array('company_id' => $company_id));
		pjCompanyContext::clearCache();
		pjCompanyContext::seedFeatures($company_id);
		pjCompanyContext::ensureNotifications($company_id);
		pjCompanyContext::seedNewCompanyLocales($company_id);
		// Subscriptions: the plan for new companies (without a plan a company has no full access)
		if (class_exists('pjEntitlement'))
		{
			pjEntitlement::assignDefaultPlan($company_id, (int) $user['id']);
		}
		return $company_id;
	}

	/**
	 * Before the company step (3) is done, the company follows the property: a company field that is empty or
	 * still holds the value it took from the property (name, address) is updated to the property's new value;
	 * empty contact fields are taken from the account. Values the host typed in the company step are kept.
	 */
	private function syncSignupCompany($signup, $company_id, $v, $country_id)
	{
		$company = pjCompanyModel::factory()->find((int) $company_id)->getData();
		if (empty($company))
		{
			return;
		}
		$old = array('name' => '', 'address_1' => '', 'city' => '', 'state' => '', 'zip' => '', 'country_id' => '');
		$cal = !empty($signup['calendar_id']) ? pjCalendarModel::factory()->find((int) $signup['calendar_id'])->getData() : array();
		if (!empty($cal) && (int) $cal['company_id'] === (int) $company_id)
		{
			$t = self::propertyTexts($cal['id'], $company_id);
			$old = array(
				'name'       => isset($t['name']) ? (string) $t['name'] : '',
				'address_1'  => isset($t['address_1']) ? (string) $t['address_1'] : '',
				'city'       => isset($t['address_city']) ? (string) $t['address_city'] : '',
				'state'      => (string) $cal['address_state'],
				'zip'        => (string) $cal['address_zip'],
				'country_id' => (string) $cal['address_country'],
			);
		}
		$new = array(
			'name'       => self::uniqueCompanyName($v['name'], (int) $company_id),
			'address_1'  => self::cut($v['address_1'], 255),
			'city'       => self::cut($v['city'], 100),
			'state'      => self::cut($v['state'], 100),
			'zip'        => self::cut($v['zip'], 20),
			'country_id' => (string) (int) $country_id,
		);
		$data = array();
		foreach ($new as $f => $value)
		{
			$current = isset($company[$f]) ? trim((string) $company[$f]) : '';
			if (($current === '' || $current === '0' || $current === trim($old[$f])) && $current !== $value)
			{
				$data[$f] = $value !== '' ? $value : ':NULL';
			}
		}
		$user = $signup['user'];
		foreach (array('contact_person' => 'name', 'email' => 'email', 'phone' => 'phone') as $f => $uf)
		{
			if ((!isset($company[$f]) || trim((string) $company[$f]) === '') && isset($user[$uf]) && (string) $user[$uf] !== '')
			{
				$data[$f] = (string) $user[$uf];
			}
		}
		if (!empty($data))
		{
			if (isset($data['name']) && $data['name'] === ':NULL')
			{
				unset($data['name']); // the company always keeps a name
			}
			$data['modified'] = date('Y-m-d H:i:s');
			pjCompanyModel::factory()->set('id', (int) $company_id)->modify($data);
		}
	}

	/** First characters of $value (UTF-8), for a shorter column. */
	private static function cut($value, $max)
	{
		return function_exists('mb_substr') ? mb_substr($value, 0, $max, 'UTF-8') : substr($value, 0, $max);
	}

	/**
	 * Step 3: company. signup_token + company fields (same fields as Companies > Add company):
	 *   name*, legal_name, registration_no, tax_no, contact_person, company_email, phone,
	 *   website, address_1*, address_2, city*, state, zip, country_id*, description, logo (file)
	 *   device_timezone (optional: time zone of the host's device, used when it is in the company's country)
	 * contact_person / company_email / phone default to the account values.
	 */
	public function pjActionSignupCompany()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$signup = $this->signupFromToken();
		$this->requireStep($signup, 3);
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
		$data['name'] = trim(preg_replace('/\s+/u', ' ', $data['name']));
		if (!isset($errors['name']) && self::companyNameTaken($data['name'], (int) $signup['company_id']))
		{
			$errors['name'] = 'This company name is already taken. Please choose another name.';
		}
		if (!isset($errors['company_email']) && !pjValidation::pjActionEmail($data['email']))
		{
			$errors['company_email'] = 'Invalid email format';
		}
		if (!isset($errors['phone']) && !self::validPhone($data['phone']))
		{
			$errors['phone'] = 'Invalid phone number';
		}
		// A real address: valid URL and a host with a dot ("example.com", not "notaurl")
		if (!isset($errors['website']) && $data['website'] !== '' && (!filter_var($data['website'], FILTER_VALIDATE_URL)
			|| strpos((string) parse_url($data['website'], PHP_URL_HOST), '.') === false))
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
			$company_id = $this->createSignupCompany($user, $data);
		} else {
			$data['modified'] = date('Y-m-d H:i:s');
			pjCompanyModel::factory()->set('id', $company_id)->modify($data);
		}

		// General settings (Company options > General), saved automatically the first time this step is done:
		// the defaults and the time zone of the company's country (this device's time zone when it is in that
		// country). The currency stays the one of step 2. Later changes are made in the back office.
		if ((int) $signup['step'] < 3)
		{
			$settings = self::$settingDefaults;
			unset($settings['currency'], $settings['timezone']);
			$country = pjBaseCountryModel::factory()->find($country_id)->getData();
			if (!empty($country['alpha_2']))
			{
				$device = $this->param('device_timezone');
				$zone = in_array($device, pjCountryDefaults::timezones($country['alpha_2']), true) ? $device : pjCountryDefaults::timezone($country['alpha_2']);
				if ($zone !== null && in_array($zone, timezone_identifiers_list(), true))
				{
					$settings['timezone'] = $zone;
				}
			}
			self::saveCompanySettings($company_id, $settings);
		}

		list($logo, $logo_error) = $this->handleLogo($company_id, $is_new ? null : $current['logo']);
		if ($logo !== null)
		{
			pjCompanyModel::factory()->set('id', $company_id)->modify(array('logo' => $logo));
		}

		$done = $this->completeStep($signup, 3, array('company_id' => $company_id));

		$state = $this->signupState($signup);
		if ($logo_error !== null)
		{
			$state['logo_error'] = $logo_error;
		}
		$this->respond(200, $done ? 'Registration completed. You can now log in.' : 'Company details saved.', $state);
	}

	/**
	 * Step 2: property basics (same as Properties > Add property).
	 *   property_type (villa|homestay|apartment|cottage|resort, optional), amenities (optional JSON list of names:
	 *   the property gets exactly these; names the company doesn't have yet are added to Options > Features, without duplicates)
	 * The sign-up has no company yet: it is created here, filled in from the account (contact, e-mail, phone)
	 * and the property (name, address), and the host checks it in step 3.
	 *   signup_token, name*, address_1*, city*, country_id*, state, zip, bedrooms, bathrooms,
	 *   ical_url (optional: calendar export link of Airbnb / Booking.com / Vrbo ...)
	 * Sent again (while the sign-up is open) it updates the same property.
	 * An iCal link imports the bookings / blocked dates (all an iCal feed contains) with the
	 * script's own importer, and is kept as a feed of the property so later changes sync too.
	 */
	public function pjActionSignupProperty()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$signup = $this->signupFromToken();
		$this->requireStep($signup, 2);

		$v = array();
		foreach (array('name' => 255, 'address_1' => 255, 'city' => 255, 'state' => 255, 'zip' => 255) as $f => $max)
		{
			$v[$f] = $this->param($f);
		}
		$country_id = (int) $this->param('country_id');
		$bedrooms = $this->param('bedrooms');
		$bathrooms = $this->param('bathrooms');
		// Price per night (optional): saved as the default price of the property (Properties > Prices)
		$price = str_replace(array(' ', ','), '', $this->param('price'));
		// Currency of the prices (required): one of System options > Currency; saved as the account's currency
		$currency = strtoupper($this->param('currency'));
		$v['name'] = trim(preg_replace('/\s+/u', ' ', $v['name']));

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
		if (!isset($errors['name']) && self::propertyNameTaken($v['name'], (int) $signup['calendar_id']))
		{
			$errors['name'] = 'This property name is already taken. Please choose another name.';
		}
		if ($currency === '')
		{
			$errors['currency'] = 'Currency is required';
		} elseif (!in_array($currency, self::currencyCodes(), true)) {
			$errors['currency'] = 'Choose a currency from the list';
		}
		if (self::tooLong($v['state'], 100)) { $errors['state'] = 'Maximum 100 characters allowed'; }
		if (self::tooLong($v['zip'], 20)) { $errors['zip'] = 'Maximum 20 characters allowed'; }
		if ($country_id <= 0)
		{
			$errors['country_id'] = 'Country is required';
		} elseif ((int) pjBaseCountryModel::factory()->where('t1.id', $country_id)->where('t1.status', 'T')->where('t1.company_id IS NULL')->findCount()->getData() === 0) {
			$errors['country_id'] = 'Invalid country';
		}
		$property_type = strtolower($this->param('property_type'));
		if ($property_type !== '' && !in_array($property_type, self::$propertyTypes, true))
		{
			$errors['property_type'] = 'Choose one of: ' . implode(', ', self::$propertyTypes);
		}
		// Amenities (optional): sent -> the property has exactly these (new ones are added to the company's features)
		$amenities = null;
		if ($this->has('amenities'))
		{
			list($amenities, $amenity_error) = $this->amenitiesParam();
			if ($amenity_error !== null)
			{
				$errors['amenities'] = $amenity_error;
			}
		}
		$max_guests = $this->param('max_guests');
		if ($max_guests !== '' && (!ctype_digit($max_guests) || (int) $max_guests < 1 || (int) $max_guests > 99))
		{
			$errors['max_guests'] = 'Enter a number from 1 to 99';
		}
		foreach (array('bedrooms' => $bedrooms, 'bathrooms' => $bathrooms) as $f => $val)
		{
			if ($val !== '' && (!ctype_digit($val) || (int) $val > 99))
			{
				$errors[$f] = 'Enter a number from 0 to 99';
			}
		}
		if ($price !== '' && (!is_numeric($price) || (float) $price <= 0 || (float) $price > 99999999))
		{
			$errors['price'] = 'Enter a price greater than 0';
		}
		// Optional calendar link: checked (and read once) before anything is saved.
		$ical_url = $this->param('ical_url');
		if ($ical_url !== '')
		{
			if (self::tooLong($ical_url, 1000) || !preg_match('~^https?://~i', $ical_url) || !pjAppController::isSafeFeedUrl($ical_url))
			{
				// only public http(s) links: no files of this server, no internal addresses
				$errors['ical_url'] = 'Enter a valid calendar (iCal) link starting with https://';
			} else {
				$contents = pjAppController::fetchIcalContents($ical_url);
				if ($contents === false)
				{
					$errors['ical_url'] = 'The calendar link could not be opened. Check the link and try again.';
				} elseif (stripos($contents, 'BEGIN:VCALENDAR') === false) {
					$errors['ical_url'] = 'This link is not a calendar (iCal) feed. On Airbnb: Listing > Availability > Connect calendars > Export calendar.';
				}
			}
		}
		if (!empty($errors))
		{
			$this->respond(422, 'Validation failed', array(), $errors);
		}

		// The property belongs to the company: create it now when the sign-up has none yet.
		$company_id = (int) $signup['company_id'];
		if ($company_id <= 0 || !pjCompanyModel::factory()->find($company_id)->getData())
		{
			$user = $signup['user'];
			$company_id = $this->createSignupCompany($user, array(
				'name'           => self::uniqueCompanyName($v['name']),
				'contact_person' => (string) $user['name'] !== '' ? self::cut((string) $user['name'], 255) : ':NULL',
				'email'          => (string) $user['email'] !== '' ? (string) $user['email'] : ':NULL',
				'phone'          => (string) $user['phone'] !== '' ? self::cut((string) $user['phone'], 50) : ':NULL',
				'address_1'      => self::cut($v['address_1'], 255),
				'city'           => self::cut($v['city'], 100),
				'state'          => $v['state'] !== '' ? self::cut($v['state'], 100) : ':NULL',
				'zip'            => $v['zip'] !== '' ? self::cut($v['zip'], 20) : ':NULL',
				'country_id'     => $country_id,
			));
			pjCompanySignupModel::factory()->set('id', (int) $signup['id'])->modify(array('company_id' => $company_id));
			$signup['company_id'] = $company_id;
			// New account: the default general settings, with the time zone of the property's country
			// (the host confirms or changes them in step 3)
			$defaults = self::$settingDefaults;
			$country = pjBaseCountryModel::factory()->find($country_id)->getData();
			$zone = !empty($country['alpha_2']) ? pjCountryDefaults::timezone($country['alpha_2']) : null;
			if ($zone !== null && in_array($zone, timezone_identifiers_list(), true))
			{
				$defaults['timezone'] = $zone;
			}
			unset($defaults['currency']); // saved below from the currency field
			self::saveCompanySettings($company_id, $defaults);
		} elseif ((int) $signup['step'] < 3) {
			// Company not checked yet (step 3): keep the details it took from the property and the account up to date.
			$this->syncSignupCompany($signup, $company_id, $v, $country_id);
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
			if ($property_type !== '')
			{
				$i18n[$lid]['property_type'] = $property_type;
			}
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

		// The link follows the name until the sign-up is finished (then it is kept, so shared links keep working)
		if (self::linksReady() && $signup['status'] !== 'completed')
		{
			pjSiteLinks::renameCalendarSlug($calendar_id, $v['name']);
		}
		if ($amenities !== null)
		{
			self::saveAmenities($calendar_id, $company_id, $amenities);
		}
		if ($currency !== '' && $currency !== self::companyCurrency($company_id))
		{
			self::saveCompanyCurrency($company_id, $currency);
		}
		if ($max_guests !== '')
		{
			// Guests: "Max people" of the property (Limits in the back office)
			pjOptionModel::factory()->where('foreign_id', $calendar_id)->where('`key`', 'o_max_people')->modifyAll(array('value' => (int) $max_guests));
		}
		if ($price !== '')
		{
			// Only when it changed: weekday prices set in the back office are kept when the host saves the same price again.
			$current_price = self::propertyPrice($calendar_id, $company_id);
			if ($current_price['price'] === null || abs($current_price['price'] - (float) $price) > 0.001)
			{
				self::saveDefaultPrice($calendar_id, round((float) $price, 2));
			}
		}

		$this->completeStep($signup, 2, array('calendar_id' => $calendar_id, 'company_id' => $company_id));

		$state = $this->signupState($signup);
		if ($ical_url !== '')
		{
			$state['ical_import'] = $this->importIcal($calendar_id, $ical_url);
			$state['property']['ical_urls'] = self::propertyFeeds($calendar_id);
		}
		$this->respond(200, 'Property saved.', $state);
	}

	/** Problem of a calendar (iCal) link, '' when it is a readable public iCal feed. */
	private static function icalLinkError($url)
	{
		if (self::tooLong($url, 1000) || !preg_match('~^https?://~i', $url) || !pjAppController::isSafeFeedUrl($url))
		{
			return 'Enter a valid calendar (iCal) link starting with https://';
		}
		$contents = pjAppController::fetchIcalContents($url);
		if ($contents === false)
		{
			return 'The calendar link could not be opened. Check the link and try again.';
		}
		if (stripos($contents, 'BEGIN:VCALENDAR') === false)
		{
			return 'This link is not a calendar (iCal) feed. On Airbnb: Listing > Availability > Connect calendars > Export calendar.';
		}
		return '';
	}

	/** Calendars of the property and the link of its own calendar (to paste into Airbnb). */
	private static function calendarSyncState($calendar_id)
	{
		$names = array(1 => 'other', 2 => 'airbnb', 3 => 'vrbo', 4 => 'homeaway', 5 => 'tripadvisor', 6 => 'booking.com');
		$feeds = array();
		foreach (pjFeedModel::factory()->where('t1.calendar_id', (int) $calendar_id)->orderBy('t1.id ASC')->findAll()->getData() as $f)
		{
			$feeds[] = array('id' => (int) $f['id'], 'url' => (string) $f['url'],
				'provider' => isset($names[(int) $f['provider_id']]) ? $names[(int) $f['provider_id']] : 'other');
		}
		$path = pjAppController::ensureCalendarIcalFile((int) $calendar_id);
		$export = '';
		if ($path !== false)
		{
			$rel = ltrim(str_replace('\\', '/', (string) $path), '/');
			$root = str_replace('\\', '/', rtrim((string) PJ_INSTALL_PATH, '/\\')) . '/';
			if (strpos($rel, ltrim($root, '/')) === 0)
			{
				$rel = substr($rel, strlen(ltrim($root, '/')));
			}
			$export = PJ_INSTALL_URL . $rel;
		}
		return array('feeds' => $feeds, 'export_url' => $export);
	}

	/**
	 * Calendar sync of the sign-up property (Live step > Connect your Airbnb calendar), as Properties > iCal feeds.
	 *   POST signup_token, op: get | save (ical_url*: adds the link and imports it) | sync (imports every link again)
	 *        | remove (feed_id*: the link is no longer synchronised)
	 * Data: feeds[] {id, url, provider}, export_url (this property's own calendar: paste it into Airbnb > Import
	 *   calendar so direct bookings block Airbnb), import {status, text} after save / sync.
	 */
	public function pjActionCalendarSync()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();
		$signup = $this->signupFromToken();
		$calendar = $this->signupProperty($signup);
		$calendar_id = (int) $calendar['id'];
		$op = $this->param('op');
		$import = null;

		if ($op === 'save')
		{
			$url = trim($this->param('ical_url'));
			$error = $url === '' ? 'Paste the calendar (iCal) link of your listing.' : self::icalLinkError($url);
			if ($error !== '')
			{
				$this->respond(422, 'Validation failed', array(), array('ical_url' => $error));
			}
			$import = $this->importIcal($calendar_id, $url);
		} elseif ($op === 'sync') {
			$added = 0;
			$updated = 0;
			$failed = 0;
			foreach (pjFeedModel::factory()->where('t1.calendar_id', $calendar_id)->findAll()->getData() as $f)
			{
				$res = $this->importIcal($calendar_id, (string) $f['url']);
				if ($res['status'] === 'OK')
				{
					$added += $res['added'];
					$updated += $res['updated'];
				} else {
					$failed++;
				}
			}
			$import = $failed > 0
				? array('status' => 'ERR', 'text' => sprintf('%u calendar link(s) could not be read. They are synchronised again automatically.', $failed))
				: array('status' => 'OK', 'text' => sprintf('Calendars synchronised: %u new and %u updated bookings / blocked dates.', $added, $updated));
		} elseif ($op === 'remove') {
			$feed = pjFeedModel::factory()->where('t1.id', (int) $this->param('feed_id'))->where('t1.calendar_id', $calendar_id)->limit(1)->findAll()->getDataIndex(0);
			if (empty($feed))
			{
				$this->respond(404, 'Calendar link not found.');
			}
			pjFeedModel::factory()->set('id', (int) $feed['id'])->erase();
		} elseif ($op !== 'get' && $op !== '') {
			$this->respond(422, 'Unknown operation.');
		}

		$data = self::calendarSyncState($calendar_id);
		if ($import !== null)
		{
			$data['import'] = array('status' => $import['status'], 'text' => $import['text']);
		}
		$this->respond(200, 'OK', $data);
	}

	/** Calendar feed links of a property. */
	private static function propertyFeeds($calendar_id)
	{
		return array_values(pjFeedModel::factory()->where('t1.calendar_id', (int) $calendar_id)->orderBy('t1.id ASC')->findAll()->getDataPair(null, 'url'));
	}

	/**
	 * Keep $url as a feed of the property (as Properties > iCal feeds does) and import it now
	 * with the script's importer (pjAppController::syncFeeds). Returns the import result.
	 */
	private function importIcal($calendar_id, $url)
	{
		$provider_id = 1;
		foreach (array('airbnb' => 2, 'vrbo.com' => 3, 'homeaway' => 4, 'tripadvisor.com' => 5, 'booking.com' => 6) as $needle => $pid)
		{
			if (stripos($url, $needle) !== false) { $provider_id = $pid; break; }
		}
		$feed = pjFeedModel::factory()->where('t1.calendar_id', (int) $calendar_id)->where('t1.url', $url)->limit(1)->findAll()->getDataIndex(0);
		$feed_id = !empty($feed) ? (int) $feed['id'] : (int) pjFeedModel::factory(array(
			'calendar_id' => (int) $calendar_id, 'url' => $url, 'provider_id' => $provider_id,
		))->insert()->getInsertId();
		if ($feed_id <= 0)
		{
			return array('status' => 'ERR', 'text' => 'The calendar link could not be saved.');
		}
		$res = pjAppController::syncFeeds($feed_id);
		if (!isset($res['status']) || $res['status'] !== 'OK')
		{
			return array('status' => 'ERR', 'text' => 'The calendar link was saved, but importing failed: ' . (isset($res['text']) ? $res['text'] : 'unknown error') . '. It is synchronised again automatically.');
		}
		$st = isset($res['stats']) ? $res['stats'] : array();
		$added = isset($st['inserted']) ? (int) $st['inserted'] : 0;
		$updated = isset($st['updated']) ? (int) $st['updated'] : 0;
		return array(
			'status'   => 'OK',
			'text'     => sprintf('Calendar imported: %u new and %u updated bookings / blocked dates.', $added, $updated),
			'added'    => $added,
			'updated'  => $updated,
			'removed'  => isset($st['removed']) ? (int) $st['removed'] : 0,
			'provider' => strtr((string) $provider_id, array('1' => 'other', '2' => 'airbnb', '3' => 'vrbo', '4' => 'homeaway', '5' => 'tripadvisor', '6' => 'booking.com')),
		);
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
		if ($existing + count($files) > pjSignupConfig::get('max_photos'))
		{
			$this->respond(422, 'Validation failed', array(), array('photos' => sprintf('You can add up to %u photos', pjSignupConfig::get('max_photos'))));
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
	 * Step 5 helper: let the AI service (Options > AI settings) write the page headline, the description and the
	 * questions guests ask, always in English, in the chosen tone. Nothing is saved: the host edits the texts and saves
	 * them with pjActionSignupDescription.
	 *   signup_token, tone (warm|premium|simple, default warm),
	 *   field (all|headline|description|faqs, default all: "Rewrite" of one card),
	 *   current (optional: text of that card now, so the rewrite is different), highlights (optional notes, max 1000 chars)
	 * Data: {tone, headline?, description?, faqs?: [{question, answer}], ai_generations_left}
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
			$this->respond(503, 'AI writing is not available at the moment. Please write the texts yourself.');
		}
		if (!self::aiAllowed((int) $signup['company_id']))
		{
			$this->respond(402, 'AI writing comes with the paid plans. Please write the texts yourself, or choose a plan.');
		}
		if ((int) $signup['ai_generations'] >= pjSignupConfig::get('max_ai_generations'))
		{
			$this->respond(429, 'You have used all AI suggestions. Please edit the last text or write your own.');
		}
		$tone = strtolower($this->param('tone'));
		if ($tone === '')
		{
			$tone = current(pjSignupConfig::toneKeys());
		}
		$field = strtolower($this->param('field'));
		if ($field === '')
		{
			$field = 'all';
		}
		$highlights = $this->param('highlights');
		$current = $this->param('current');
		$errors = array();
		if (pjSignupConfig::tone($tone) === null)
		{
			$errors['tone'] = 'Choose one of: ' . implode(', ', pjSignupConfig::toneKeys());
		}
		if (!in_array($field, array('all', 'headline', 'description', 'faqs'), true))
		{
			$errors['field'] = 'Choose one of: all, headline, description, faqs';
		}
		if (self::tooLong($highlights, 1000))
		{
			$errors['highlights'] = 'Maximum 1000 characters allowed';
		}
		if (self::tooLong($current, 6000))
		{
			$errors['current'] = 'Maximum 6000 characters allowed';
		}
		if (!empty($errors))
		{
			$this->respond(422, 'Validation failed', array(), $errors);
		}

		$company_id = (int) $signup['company_id'];
		$t = self::propertyTexts($calendar['id'], $company_id);
		$country = '';
		if (!empty($calendar['address_country']))
		{
			// English country name when there is one (the AI writes in English)
			$en = pjBaseLocaleModel::factory()->unscoped()->where('t1.language_iso LIKE', 'en%')->limit(1)->findAll()->getDataIndex(0);
			$lid = !empty($en) ? (int) $en['id'] : pjCompanyContext::companyDefaultLocale($company_id);
			$row = pjMultiLangModel::factory()->where('t1.model', 'pjBaseCountry')->where('t1.foreign_id', (int) $calendar['address_country'])
				->where('t1.field', 'name')->where('t1.locale', $lid > 0 ? $lid : 1)->limit(1)->findAll()->getDataIndex(0);
			$country = !empty($row) ? $row['content'] : '';
		}
		$company = pjCompanyModel::factory()->find($company_id)->getData();
		$opt = self::calendarOptions($calendar['id'], $company_id);

		$facts = array_filter(array(
			'Property name' => isset($t['name']) ? $t['name'] : '',
			'Location' => implode(', ', array_filter(array(isset($t['address_city']) ? $t['address_city'] : '', $calendar['address_state'], $country))),
			'Property type' => isset($t['property_type']) && in_array($t['property_type'], self::$propertyTypes, true) ? $t['property_type'] : '',
			'Bedrooms' => $calendar['bedrooms'],
			'Bathrooms' => $calendar['bathrooms'],
			'Maximum guests' => (int) (isset($opt['o_max_people']) ? $opt['o_max_people'] : 0) > 0 ? (int) $opt['o_max_people'] : '',
			'Amenities' => implode(', ', array_slice(self::propertyAmenities($calendar['id'], $company_id), 0, 40)),
			'Host' => !empty($company['name']) ? $company['name'] : '',
			'Owner notes' => $highlights,
		), function ($x) { return $x !== null && $x !== ''; });
		$lines = array();
		foreach ($facts as $k => $val)
		{
			$lines[] = '- ' . $k . ': ' . str_replace(array("\r", "\n"), ' ', (string) $val);
		}
		// What guests ask about, answered from this property's own amenities ("not listed" when it has none).
		$topics = self::faqTopics(self::propertyAmenities($calendar['id'], $company_id));
		$topic_lines = array();
		foreach ($topics as $topic => $answer)
		{
			$topic_lines[] = '- ' . $topic . ': ' . $answer;
		}

		$keys = array(
			'headline'    => '"headline": the page headline, one line, 4 to 10 words, at most 70 characters, no quotes, no full stop at the end.',
			'description' => '"description": 100 to 160 words in 2 or 3 short paragraphs separated by a blank line (\n\n). Plain text.',
			'faqs'        => '"faqs": an array of 4 to 6 objects {"question": "...", "answer": "..."} (fewer when the property has few amenities; never the same topic twice): questions guests ask before booking THIS property, answered from the facts and the guest topics below. Cover: how many bedrooms and bathrooms, how many guests can stay, parking, pets, then up to two other amenities this property HAS (from the Amenities fact or a guest topic marked yes, e.g. its pool, Wi-Fi or barbeque grill); never ask about an amenity it does not have. Answers: 1 or 2 short sentences with the real numbers and amenity names. When parking or pets is "not listed", say it is not listed for this property and guests can ask the host before booking; never answer yes for it. Do not ask about check-in times, prices, distances or places nearby.',
		);
		$wanted = $field === 'all' ? array_keys($keys) : array($field);
		$spec = array();
		foreach ($wanted as $k)
		{
			$spec[] = '- ' . $keys[$k];
		}
		$prompt = "You write the guest page of a holiday rental property on its own booking website.\n"
			. "LANGUAGE: write every word in English only, whatever the language of the facts. If a fact is written in another language or script, translate it (or transliterate a name) into English.\n"
			. "TONE: " . pjSignupConfig::tone($tone) . "\n"
			. "Use only the facts below. Do not invent amenities, distances, ratings, awards or prices. No emojis, no markdown.\n"
			. "Facts:\n" . implode("\n", $lines) . "\n"
			. (in_array('faqs', $wanted, true) ? "Guest topics (from the property's amenity list):\n" . implode("\n", $topic_lines) . "\n" : '')
			. ($field !== 'all' && $current !== '' ? "Write a new version that is clearly different from the current one:\n" . $current . "\n" : '')
			. "Answer with one JSON object only (no code fence, no text before or after) with " . (count($wanted) > 1 ? 'these keys' : 'this key') . ":\n"
			. implode("\n", $spec) . "\n";

		@set_time_limit(pjAi::TIMEOUT + 30); // slow (thinking) models
		$res = pjAi::generate($prompt);
		if ($res['status'] !== 'OK')
		{
			$this->respond((int) $res['code'], $res['text']);
		}
		$out = self::parseAiJson($res['text'], $wanted);
		if ($out === null)
		{
			$this->respond(502, 'The AI answer could not be read. Please try again.');
		}
		pjCompanySignupModel::factory()->set('id', (int) $signup['id'])->modify(array(
			'ai_generations' => (int) $signup['ai_generations'] + 1,
			'modified' => date('Y-m-d H:i:s'),
		));
		$out['tone'] = $tone;
		$out['ai_generations_left'] = max(0, pjSignupConfig::get('max_ai_generations') - (int) $signup['ai_generations'] - 1);
		$this->respond(200, 'Text generated. Check it and edit it before you publish.', $out);
	}

	/**
	 * Guest questions topics for the FAQ prompt: each topic -> the matching amenities of the property,
	 * or "not listed" (the AI must then not say yes).
	 */
	private static function faqTopics($amenities)
	{
		$rules = array(
			'Parking'           => '/parking|garage/i',
			'Pets'              => '/\\bpets?\\b|pet[- ]friendly|dogs?\\b/i',
			'Wi-Fi'             => '/wi-?fi|internet|broadband/i',
			'Kitchen'           => '/kitchen|cooking/i',
			'Air conditioning'  => '/air[- ]?condition|\\ba\\/?c\\b/i',
			'Swimming pool'     => '/pool/i',
			'Workspace'         => '/workspace|work space|desk/i',
			'Laundry'           => '/washing|laundry|washer|dryer/i',
			'Housekeeping'      => '/housekeep|cleaning/i',
			'Meals / chef'      => '/breakfast|chef|meal|cook on request/i',
			'Power backup'      => '/power backup|inverter|generator/i',
		);
		$out = array();
		foreach ($rules as $topic => $re)
		{
			$hit = array();
			foreach ((array) $amenities as $name)
			{
				if (preg_match($re, (string) $name))
				{
					$hit[] = (string) $name;
				}
			}
			$out[$topic] = empty($hit) ? 'not listed' : 'yes (' . implode(', ', $hit) . ')';
		}
		return $out;
	}

	/** Plain text from an AI answer (no markdown markers). */
	private static function plainAiText($text)
	{
		$text = str_replace(array("\r\n", '**', '__'), array("\n", '', ''), (string) $text);
		$text = preg_replace('/^\s{0,3}(#{1,6}\s+|[*\-]\s+)/m', '', $text);
		return trim(preg_replace("/\n{3,}/", "\n\n", $text));
	}

	/** The JSON object of an AI answer with the wanted keys checked, or null. */
	private static function parseAiJson($text, $wanted)
	{
		$text = trim((string) $text);
		$start = strpos($text, '{');
		$end = strrpos($text, '}');
		if ($start === false || $end === false || $end <= $start)
		{
			return null;
		}
		$data = json_decode(substr($text, $start, $end - $start + 1), true);
		if (!is_array($data))
		{
			return null;
		}
		$out = array();
		foreach ($wanted as $k)
		{
			if ($k === 'faqs')
			{
				$faqs = array();
				if (isset($data['faqs']) && is_array($data['faqs']))
				{
					foreach ($data['faqs'] as $f)
					{
						if (is_array($f) && isset($f['question'], $f['answer']) && is_string($f['question']) && is_string($f['answer']))
						{
							$q = self::plainAiText($f['question']);
							$a = self::plainAiText($f['answer']);
							if ($q !== '' && $a !== '')
							{
								$faqs[] = array('question' => $q, 'answer' => $a);
							}
						}
					}
				}
				if (empty($faqs))
				{
					return null;
				}
				$out['faqs'] = array_slice($faqs, 0, pjSignupConfig::get('max_faqs'));
			} else {
				if (!isset($data[$k]) || !is_string($data[$k]) || trim($data[$k]) === '')
				{
					return null;
				}
				$value = self::plainAiText($data[$k]);
				if ($k === 'headline')
				{
					$value = rtrim(trim(preg_replace('/\s+/', ' ', $value), " \"'"), '.');
				}
				$out[$k] = $value;
			}
		}
		return $out;
	}

	/**
	 * Step 5: save the page texts (AI texts edited by the host, or his own).
	 *   signup_token, description*, headline (max 120 chars), faqs (JSON [{question, answer}], max 8),
	 *   tone (warm|premium|simple: the tone last chosen, shown again when the host comes back)
	 * Saved in every company language (pjCalendar texts: description as HTML, headline, faqs as JSON, content_tone).
	 */
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
		$headline = isset($p['headline']) && !is_array($p['headline']) ? trim(preg_replace('/\s+/', ' ', strip_tags((string) $p['headline']))) : '';
		$tone = strtolower($this->param('tone'));
		$faqs_raw = isset($p['faqs']) ? $p['faqs'] : '';
		$faqs_list = is_array($faqs_raw) ? $faqs_raw : json_decode((string) $faqs_raw, true);

		$errors = array();
		if ($text === '')
		{
			$errors['description'] = 'Description is required';
		} elseif (self::tooLong($text, 5000)) {
			$errors['description'] = 'Maximum 5000 characters allowed';
		}
		if (self::tooLong($headline, 120))
		{
			$errors['headline'] = 'Maximum 120 characters allowed';
		}
		if ($tone !== '' && pjSignupConfig::tone($tone) === null)
		{
			$errors['tone'] = 'Choose one of: ' . implode(', ', pjSignupConfig::toneKeys());
		}
		$faqs = array();
		if ($faqs_raw !== '' && $faqs_raw !== array())
		{
			if (!is_array($faqs_list))
			{
				$errors['faqs'] = 'Invalid questions';
			} elseif (count($faqs_list) > pjSignupConfig::get('max_faqs')) {
				$errors['faqs'] = 'Maximum ' . pjSignupConfig::get('max_faqs') . ' questions allowed';
			} else {
				foreach ($faqs_list as $f)
				{
					$q = is_array($f) && isset($f['question']) && is_string($f['question']) ? trim(strip_tags($f['question'])) : '';
					$a = is_array($f) && isset($f['answer']) && is_string($f['answer']) ? trim(strip_tags($f['answer'])) : '';
					if ($q === '' && $a === '')
					{
						continue; // empty row: skipped
					}
					if ($q === '' || $a === '')
					{
						$errors['faqs'] = 'Every question needs an answer';
						break;
					}
					if (self::tooLong($q, 200) || self::tooLong($a, 1000))
					{
						$errors['faqs'] = 'Questions: maximum 200 characters, answers: maximum 1000 characters';
						break;
					}
					$faqs[] = array('question' => $q, 'answer' => $a);
				}
			}
		}
		if (!empty($errors))
		{
			$this->respond(422, 'Validation failed', array(), $errors);
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
		$fields = array(
			'description' => $html,
			'headline'    => $headline,
			'faqs'        => json_encode($faqs, JSON_UNESCAPED_UNICODE),
		);
		if ($tone !== '')
		{
			$fields['content_tone'] = $tone;
		}
		$i18n = array();
		foreach (self::companyLocales((int) $signup['company_id']) as $lid)
		{
			$i18n[$lid] = $fields;
		}
		pjMultiLangModel::factory()->saveMultiLang($i18n, (int) $calendar['id'], 'pjCalendar');

		$this->completeStep($signup, 5);
		$this->respond(200, 'Page texts saved.', $this->signupState($signup));
	}

	/** Themes (colour palettes) for step 6. signup_token optional: tells whether the plan allows choosing one. */
	/** The free theme: the first palette (Options > Theme colours). Every other palette needs a plan with themes. */
	private static function freeThemeId()
	{
		$ids = array_keys(pjColorHelper::getPalettes());
		return !empty($ids) ? (string) $ids[0] : '0';
	}

	/**
	 * Paid themes for this company: its plan must include themes (Pro / Business) and be current
	 * (trial, active or in grace). A company without a plan gets the free theme only, whatever
	 * "unassigned_mode" says. Without the subscription system every theme is free.
	 */
	private function paidThemesAllowed($company_id)
	{
		if (!class_exists('pjEntitlement') || !pjEntitlement::isReady())
		{
			return true;
		}
		if ((int) $company_id <= 0)
		{
			return false;
		}
		$state = pjEntitlement::state((int) $company_id);
		if ($state['source'] === 'admin')
		{
			return true;
		}
		return $state['source'] === 'plan' && $state['entitled'] && in_array('themes', $state['features'], true);
	}

	/**
	 * Themes (colour palettes). signup_token optional.
	 * Data: themes[] {id, name, colors, free, locked}, default_id (the free theme), free_id, allowed (paid themes unlocked),
	 *   plan_names (plans that unlock the paid themes)
	 */
	public function pjActionGetThemes()
	{
		$this->setAjax(true);
		$allowed = false;
		if ($this->param('signup_token') !== '')
		{
			$this->requireSignupTable();
			$signup = $this->signupFromToken();
			$allowed = $this->paidThemesAllowed((int) $signup['company_id']);
		} elseif (!class_exists('pjEntitlement') || !pjEntitlement::isReady()) {
			$allowed = true;
		}
		$free_id = self::freeThemeId();
		$themes = array();
		foreach (pjColorHelper::getPalettes() as $id => $p)
		{
			$free = (string) $id === $free_id;
			$themes[] = array(
				'id' => (string) $id, 'name' => $p['name'],
				'colors' => array('brand' => $p['brand'], 'accent' => $p['accent'], 'text' => $p['text'],
					'background' => $p['bg'], 'price' => $p['price'], 'page_background' => $p['pageBg']),
				'free' => $free,
				'locked' => !$free && !$allowed,
			);
		}
		$this->respond(200, 'OK', array('themes' => $themes, 'default_id' => $free_id, 'free_id' => $free_id, 'allowed' => $allowed,
			// names of the active public plans that unlock the paid themes ("Pro or Business"), '' when none
			'plan_names' => self::planNamesWith('themes')));
	}

	/* ------------------------------------------------------------------
	 * Guest page of a published property (localhost:3000/property/<slug>)
	 * ------------------------------------------------------------------ */

	/** "Green Casa, Goa!" -> "green-casa-goa" (same rule as slugify() of the website). */
	private static function slugOf($name)
	{
		$s = html_entity_decode((string) $name, ENT_QUOTES, 'UTF-8');
		$s = function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
		if (function_exists('iconv'))
		{
			$t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
			if ($t !== false)
			{
				$s = strtolower($t);
			}
		}
		$s = trim(preg_replace('/[^a-z0-9]+/', '-', $s), '-');
		return substr($s, 0, 60);
	}

	/** Stored links (update_2026_11_03_14_00_00.sql) are there. */
	private static function linksReady()
	{
		return class_exists('pjSiteLinks') && pjSiteLinks::isReady();
	}

	/** Link of a property: its stored, unique slug (made from the name the first time). */
	private static function propertyLink($calendar_id, $name)
	{
		return self::linksReady() ? pjSiteLinks::calendarSlug((int) $calendar_id, (string) $name) : self::slugOf($name);
	}

	/** Link of a company page (/stays/<slug>): its stored, unique slug. */
	private static function companyLink($company_id, $name)
	{
		return self::linksReady() ? pjSiteLinks::companySlug((int) $company_id, (string) $name) : self::slugOf($name);
	}

	/** Name of a country in English (the website is in English), '' when unknown. */
	private static function countryNameEn($country_id, $company_id)
	{
		if ((int) $country_id <= 0)
		{
			return '';
		}
		$en = pjBaseLocaleModel::factory()->unscoped()->where('t1.language_iso LIKE', 'en%')->limit(1)->findAll()->getDataIndex(0);
		$lid = !empty($en) ? (int) $en['id'] : pjCompanyContext::companyDefaultLocale((int) $company_id);
		$row = pjMultiLangModel::factory()->where('t1.model', 'pjBaseCountry')->where('t1.foreign_id', (int) $country_id)
			->where('t1.field', 'name')->where('t1.locale', $lid > 0 ? $lid : 1)->limit(1)->findAll()->getDataIndex(0);
		return !empty($row) ? (string) $row['content'] : '';
	}

	/**
	 * Properties shown on the website: active properties of active companies. A property of a sign-up that
	 * is not finished yet stays hidden until the sign-up is completed.
	 */
	private static function publicCalendars($company_id = 0)
	{
		$active = array_map('intval', pjCompanyModel::factory()->where('t1.status', 'T')->findAll()->getDataPair(null, 'id'));
		if (empty($active))
		{
			return array();
		}
		$hidden = array_map('intval', pjCompanySignupModel::factory()->where('t1.status !=', 'completed')
			->where('t1.calendar_id IS NOT NULL')->findAll()->getDataPair(null, 'calendar_id'));
		$m = pjCalendarModel::factory()->where('t1.status', 'T')->whereIn('t1.company_id', $active);
		if ((int) $company_id > 0)
		{
			$m->where('t1.company_id', (int) $company_id);
		}
		if (!empty($hidden))
		{
			$m->whereNotIn('t1.id', $hidden);
		}
		return $m->orderBy('t1.id ASC')->findAll()->getData();
	}

	/**
	 * Public property whose name gives this link: array(calendar, texts) or null. The names of all public
	 * properties are read with one query; only the matching property's texts are loaded.
	 */
	private static function publicCalendarBySlug($slug)
	{
		// 1. The stored link (unique: "casanova", "casanova-2" ...). Properties without one get it first.
		if (self::linksReady())
		{
			for ($pass = 0; $pass < 2; $pass++)
			{
				$cal = pjCalendarModel::factory()->where('t1.slug', $slug)->limit(1)->findAll()->getDataIndex(0);
				if (!empty($cal))
				{
					foreach (self::publicCalendars((int) $cal['company_id']) as $c)
					{
						if ((int) $c['id'] === (int) $cal['id'])
						{
							return array($c, self::propertyTexts((int) $c['id'], (int) $c['company_id']));
						}
					}
					return null;
				}
				if ($pass === 0)
				{
					pjSiteLinks::ensureAll();
				}
			}
		}
		// 2. Old links made from the current name (e.g. after a rename); the page then shows the stored link.
		$cals = array();
		foreach (self::publicCalendars() as $c)
		{
			$cals[(int) $c['id']] = $c;
		}
		if (empty($cals))
		{
			return null;
		}
		$rows = pjMultiLangModel::factory()->select('t1.foreign_id, t1.content')->where('t1.model', 'pjCalendar')->where('t1.field', 'name')
			->whereIn('t1.foreign_id', array_keys($cals))->orderBy('t1.foreign_id ASC')->findAll()->getData();
		$tried = array();
		foreach ($rows as $r)
		{
			$id = (int) $r['foreign_id'];
			if (isset($tried[$id]) || self::slugOf($r['content']) !== $slug)
			{
				continue;
			}
			$tried[$id] = true;
			// the page uses the name the website shows (English, otherwise the company's language)
			$t = self::propertyTexts($id, (int) $cals[$id]['company_id']);
			if (isset($t['name']) && self::slugOf($t['name']) === $slug)
			{
				return array($cals[$id], $t);
			}
		}
		return null;
	}

	/** "Designed by PHPJabbers" on the company's website: hidden only when its plan says so (Subscriptions > Plans). */
	/**
	 * Text and link of the branding line on the websites (System Options > Visual & Branding).
	 * text: line at the top, footer: text in the footer, url: optional link. An emptied text is not shown.
	 */
	private static function brandingLine()
	{
		static $line = null;
		if ($line !== null)
		{
			return $line;
		}
		$pairs = pjBaseOptionModel::factory()->where('t1.foreign_id', 1)
			->whereIn('t1.key', array('o_website_branding_text', 'o_website_branding_footer', 'o_website_branding_url'))
			->findAll()->getDataPair('key', 'value');
		$url = isset($pairs['o_website_branding_url']) ? trim((string) $pairs['o_website_branding_url']) : 'https://www.phpjabbers.com';
		return $line = array(
			'text'   => isset($pairs['o_website_branding_text']) ? trim((string) $pairs['o_website_branding_text']) : 'Designed by PHPJabbers',
			'footer' => isset($pairs['o_website_branding_footer']) ? trim((string) $pairs['o_website_branding_footer']) : 'Website by PHPJabbers',
			'url'    => preg_match('~^https?://~i', $url) ? $url : '',
		);
	}

	private static function showBranding($company_id)
	{
		if (!class_exists('pjEntitlement') || !pjEntitlement::isReady())
		{
			return true;
		}
		$state = pjEntitlement::state((int) $company_id);
		return !($state['entitled'] && !empty($state['plan']) && isset($state['plan']['show_branding']) && (int) $state['plan']['show_branding'] === 0);
	}

	/** Theme the company chose (sign-up step 6 / Display), null when none. */
	private static function companyTheme($company_id)
	{
		$copt = pjCompanyOptionModel::factory()->getPairs((int) $company_id);
		$palette_id = isset($copt['o_color_palette']) ? (string) $copt['o_color_palette'] : '';
		foreach (pjColorHelper::getPalettes() as $id => $p)
		{
			if ((string) $id === $palette_id)
			{
				return array('id' => (string) $id, 'name' => $p['name'],
					'colors' => array('brand' => $p['brand'], 'accent' => $p['accent'], 'text' => $p['text'],
						'background' => $p['bg'], 'price' => $p['price'], 'page_background' => $p['pageBg']));
			}
		}
		return null;
	}

	/**
	 * Social links of the website (System Options > Visual & Branding, administrators only).
	 * Data: links[] {network: instagram|facebook|linkedin|x|youtube, url} (only the ones filled in, in this order)
	 */
	public function pjActionGetSocialLinks()
	{
		$this->setAjax(true);
		$networks = array('instagram', 'facebook', 'linkedin', 'x', 'youtube');
		$pairs = pjBaseOptionModel::factory()->where('t1.foreign_id', 1)
			->whereIn('t1.key', array_map(function ($n) { return 'o_social_' . $n; }, $networks))
			->findAll()->getDataPair('key', 'value');
		$links = array();
		foreach ($networks as $n)
		{
			$url = isset($pairs['o_social_' . $n]) ? trim((string) $pairs['o_social_' . $n]) : '';
			// only real web links (no javascript: or other schemes)
			if ($url !== '' && preg_match('~^https?://[^\s<>"]+$~i', $url))
			{
				$links[] = array('network' => $n, 'url' => $url);
			}
		}
		$this->respond(200, 'OK', array('links' => $links));
	}

	/**
	 * Directory of the website (localhost:3000/properties): every published property of every active company.
	 *   q (place or name), type (villa|homestay|apartment|cottage|resort), guests (min), sort (recommended|price-asc|
	 *   price-desc|newest), page, per_page (1-24, default 9)
	 * Data: items[] {slug, name, property_type, headline, location {city, state, country}, bedrooms, bathrooms, max_guests,
	 *   price, price_varies, price_based_on, currency, currency_sign, price_format, photo, host {name, slug}},
	 *   total, page, per_page, total_pages
	 */
	public function pjActionGetDirectory()
	{
		$this->setAjax(true);
		$this->requireSignupTable();
		$q = function_exists('mb_strtolower') ? mb_strtolower(trim($this->param('q')), 'UTF-8') : strtolower(trim($this->param('q')));
		$q = function_exists('mb_substr') ? mb_substr($q, 0, 80, 'UTF-8') : substr($q, 0, 80);
		$type = in_array($this->param('type'), self::$propertyTypes, true) ? $this->param('type') : '';
		$guests = max(0, min(50, (int) $this->param('guests')));
		$sort = in_array($this->param('sort'), array('price-asc', 'price-desc', 'newest'), true) ? $this->param('sort') : 'recommended';
		$per_page = (int) $this->param('per_page') > 0 ? min(24, (int) $this->param('per_page')) : 9;

		$companies = array();
		$items = array();
		foreach (self::publicCalendars() as $cal)
		{
			$cid = (int) $cal['company_id'];
			$t = self::propertyTexts((int) $cal['id'], $cid);
			if (empty($t['name']) || trim((string) $t['name']) === '')
			{
				continue;
			}
			if (!isset($companies[$cid]))
			{
				$c = pjCompanyModel::factory()->find($cid)->getData();
				$companies[$cid] = array('name' => !empty($c['name']) ? html_entity_decode((string) $c['name'], ENT_QUOTES, 'UTF-8') : '',
					'slug' => !empty($c) ? self::companyLink($cid, $c['name']) : '', 'price_format' => self::priceFormat($cid));
			}
			$ptype = isset($t['property_type']) && in_array($t['property_type'], self::$propertyTypes, true) ? $t['property_type'] : null;
			$opt = self::calendarOptions($cal['id'], $cid);
			$max = isset($opt['o_max_people']) && (int) $opt['o_max_people'] > 0 ? (int) $opt['o_max_people'] : null;
			$name = html_entity_decode((string) $t['name'], ENT_QUOTES, 'UTF-8');
			$city = isset($t['address_city']) ? trim((string) $t['address_city']) : '';
			$country = self::countryNameEn($cal['address_country'], $cid);
			// filters
			if ($type !== '' && $ptype !== $type)
			{
				continue;
			}
			if ($guests > 0 && $max !== null && $max < $guests)
			{
				continue;
			}
			if ($q !== '')
			{
				$hay = $name . ' ' . $city . ' ' . $cal['address_state'] . ' ' . $country . ' ' . $companies[$cid]['name'] . ' ' . (isset($t['headline']) ? $t['headline'] : '');
				$hay = function_exists('mb_strtolower') ? mb_strtolower($hay, 'UTF-8') : strtolower($hay);
				$match = true;
				foreach (preg_split('/\s+/u', $q) as $word)
				{
					if ($word !== '' && strpos($hay, $word) === false)
					{
						$match = false;
						break;
					}
				}
				if (!$match)
				{
					continue;
				}
			}
			$price = self::propertyPrice($cal['id'], $cid);
			$photos = self::photoList($cal['id']);
			$items[] = array(
				'id'            => (int) $cal['id'],
				'slug'          => self::propertyLink((int) $cal['id'], $t['name']),
				'name'          => $name,
				'property_type' => $ptype,
				'headline'      => isset($t['headline']) && trim((string) $t['headline']) !== '' ? (string) $t['headline'] : null,
				'location'      => array('city' => $city, 'state' => (string) $cal['address_state'], 'country' => $country),
				'bedrooms'      => $cal['bedrooms'] !== null && $cal['bedrooms'] !== '' ? (int) $cal['bedrooms'] : null,
				'bathrooms'     => $cal['bathrooms'] !== null && $cal['bathrooms'] !== '' ? (int) $cal['bathrooms'] : null,
				'max_guests'    => $max,
				'price'         => $price['price'],
				'price_varies'  => $price['price_varies'],
				'price_based_on'=> isset($opt['o_price_based_on']) && $opt['o_price_based_on'] === 'days' ? 'days' : 'nights',
				'currency'      => $price['currency'],
				'currency_sign' => self::currencySign($price['currency']),
				'price_format'  => $companies[$cid]['price_format'],
				'photo'         => !empty($photos) ? $photos[0]['url'] : null,
				'host'          => array('name' => $companies[$cid]['name'], 'slug' => $companies[$cid]['slug']),
			);
		}

		// sort: recommended = with photos first, then the newest
		usort($items, function ($a, $b) use ($sort) {
			if ($sort === 'price-asc' || $sort === 'price-desc')
			{
				if ($a['price'] === null || $b['price'] === null)
				{
					return ($a['price'] === null) - ($b['price'] === null);
				}
				$d = (float) $a['price'] - (float) $b['price'];
				$d = $sort === 'price-asc' ? $d : -$d;
				return $d < 0 ? -1 : ($d > 0 ? 1 : $b['id'] - $a['id']);
			}
			if ($sort === 'recommended' && (bool) $a['photo'] !== (bool) $b['photo'])
			{
				return $a['photo'] ? -1 : 1;
			}
			return $b['id'] - $a['id'];
		});

		$total = count($items);
		$pages = max(1, (int) ceil($total / $per_page));
		$page = min(max(1, (int) $this->param('page')), $pages);
		$slice = array_slice($items, ($page - 1) * $per_page, $per_page);
		foreach ($slice as $k => $item)
		{
			unset($slice[$k]['id']);
		}
		$this->respond(200, 'OK', array('items' => array_values($slice), 'total' => $total, 'page' => $page, 'per_page' => $per_page, 'total_pages' => $pages));
	}

	/**
	 * Company page (localhost:3000/stays/<company-slug>): the company and all its properties on the website.
	 * Data: slug, name, description, logo, phone, email, website, places[], price_format, theme,
	 *   properties[] {slug, name, property_type, headline, location {city, state, country}, bedrooms, bathrooms,
	 *   max_guests, price, price_varies, price_based_on, currency, currency_sign, photo}
	 */
	public function pjActionGetCompanyPage()
	{
		$this->setAjax(true);
		$this->requireSignupTable();
		$slug = strtolower($this->param('slug'));
		if ($slug === '' || !preg_match('/^[a-z0-9-]{1,60}$/', $slug))
		{
			$this->respond(404, 'Page not found.');
		}
		$company = null;
		if (self::linksReady())
		{
			$company = pjCompanyModel::factory()->where('t1.status', 'T')->where('t1.slug', $slug)->limit(1)->findAll()->getDataIndex(0);
			if (empty($company))
			{
				pjSiteLinks::ensureAll();
				$company = pjCompanyModel::factory()->where('t1.status', 'T')->where('t1.slug', $slug)->limit(1)->findAll()->getDataIndex(0);
			}
			$company = !empty($company) ? $company : null;
		}
		if ($company === null)
		{
			foreach (pjCompanyModel::factory()->where('t1.status', 'T')->orderBy('t1.id ASC')->findAll()->getData() as $c)
			{
				if (self::slugOf($c['name']) === $slug)
				{
					$company = $c;
					break;
				}
			}
		}
		if ($company === null)
		{
			$this->respond(404, 'Page not found.');
		}
		$cid = (int) $company['id'];
		$properties = array();
		$places = array();
		foreach (self::publicCalendars($cid) as $cal)
		{
			$t = self::propertyTexts((int) $cal['id'], $cid);
			if (empty($t['name']) || trim((string) $t['name']) === '')
			{
				continue;
			}
			$opt = self::calendarOptions($cal['id'], $cid);
			$price = self::propertyPrice($cal['id'], $cid);
			$photos = self::photoList($cal['id']);
			$city = isset($t['address_city']) ? trim((string) $t['address_city']) : '';
			if ($city !== '')
			{
				$places[$city] = true;
			}
			$properties[] = array(
				'slug'          => self::propertyLink((int) $cal['id'], $t['name']),
				'name'          => html_entity_decode((string) $t['name'], ENT_QUOTES, 'UTF-8'),
				'property_type' => isset($t['property_type']) && in_array($t['property_type'], self::$propertyTypes, true) ? $t['property_type'] : null,
				'headline'      => isset($t['headline']) && trim((string) $t['headline']) !== '' ? (string) $t['headline'] : null,
				'location'      => array('city' => $city, 'state' => (string) $cal['address_state'], 'country' => self::countryNameEn($cal['address_country'], $cid)),
				'bedrooms'      => $cal['bedrooms'] !== null && $cal['bedrooms'] !== '' ? (int) $cal['bedrooms'] : null,
				'bathrooms'     => $cal['bathrooms'] !== null && $cal['bathrooms'] !== '' ? (int) $cal['bathrooms'] : null,
				'max_guests'    => isset($opt['o_max_people']) && (int) $opt['o_max_people'] > 0 ? (int) $opt['o_max_people'] : null,
				'price'         => $price['price'],
				'price_varies'  => $price['price_varies'],
				'price_based_on'=> isset($opt['o_price_based_on']) && $opt['o_price_based_on'] === 'days' ? 'days' : 'nights',
				'currency'      => $price['currency'],
				'currency_sign' => self::currencySign($price['currency']),
				'photo'         => !empty($photos) ? $photos[0]['url'] : null,
			);
		}
		if (empty($properties))
		{
			$this->respond(404, 'Page not found.');
		}
		$description = isset($company['description']) && is_string($company['description']) ? self::htmlToText($company['description']) : '';
		$this->respond(200, 'OK', array(
			'slug'         => self::companyLink($cid, $company['name']),
			'name'         => html_entity_decode((string) $company['name'], ENT_QUOTES, 'UTF-8'),
			'description'  => $description !== '' ? $description : null,
			'logo'         => !empty($company['logo']) ? PJ_INSTALL_URL . ltrim($company['logo'], '/') : null,
			'phone'        => !empty($company['phone']) ? (string) $company['phone'] : '',
			'email'        => !empty($company['email']) ? (string) $company['email'] : '',
			'website'      => !empty($company['website']) ? (string) $company['website'] : '',
			'places'       => array_keys($places),
			'price_format' => self::priceFormat($cid),
			'theme'        => self::companyTheme($cid),
			'branding'     => self::showBranding($cid),
			'branding_line' => self::brandingLine(),
			'properties'   => $properties,
		));
	}

	/**
	 * Path of a property on the website that made the call: "/casanova" on the main website; on the company's
	 * own domain "" when the company has one public property (it is the home page), else "/casanova".
	 */
	private static function sitePropertyPath($site, $company_id, $slug)
	{
		if ($site !== '' && self::linksReady())
		{
			$c = pjSiteLinks::companyOfDomain((string) parse_url($site, PHP_URL_HOST));
			if (!empty($c) && (int) $c['id'] === (int) $company_id && count(self::publicCalendars((int) $company_id)) === 1)
			{
				return '';
			}
		}
		return '/' . $slug;
	}

	/**
	 * Website of a company's own domain (the website's proxy asks this for every host that is not its own).
	 *   host* (e.g. www.myvilla.com)
	 * Data: company_slug, property_slug (when the company has exactly one public property: it is the home page,
	 *   else null: the home page lists the properties), domain
	 */
	public function pjActionResolveDomain()
	{
		$this->setAjax(true);
		$this->requireSignupTable();
		$company = self::linksReady() ? pjSiteLinks::companyOfDomain($this->param('host')) : null;
		if (empty($company) || (class_exists('pjEntitlement') && pjEntitlement::isReady() && !pjEntitlement::allows((int) $company['id'], 'custom_domain')))
		{
			$this->respond(404, 'Unknown domain.');
		}
		$cid = (int) $company['id'];
		$cals = self::publicCalendars($cid);
		if (empty($cals))
		{
			$this->respond(404, 'Unknown domain.');
		}
		$property = null;
		if (count($cals) === 1)
		{
			$t = self::propertyTexts((int) $cals[0]['id'], $cid);
			$property = self::propertyLink((int) $cals[0]['id'], isset($t['name']) ? $t['name'] : '');
		}
		$this->respond(200, 'OK', array(
			'company_slug'  => self::companyLink($cid, $company['name']),
			'property_slug' => $property,
			'domain'        => (string) $company['custom_domain'],
		));
	}

	/**
	 * Public page data of a property on the website (an active property of an active company; a sign-up
	 * property once the sign-up is completed).
	 *   slug (from the property name)
	 * Data: slug, name, property_type, headline, description, faqs[], location {city, state, country, lat, lng},
	 *   bedrooms, bathrooms, max_guests, amenities[], price, price_varies, price_based_on (nights|days), currency,
	 *   currency_sign, price_format, photos[] {url, thumb_url}, host {name, logo, phone, email},
	 *   theme {id, name, colors{brand, accent, text, background, price, page_background}} | null,
	 *   booked[] {from, to} (next 12 months, "to" = check-out day), booking_url
	 */
	public function pjActionGetPropertyPage()
	{
		$this->setAjax(true);
		$this->requireSignupTable();
		$slug = strtolower($this->param('slug'));
		if ($slug === '' || !preg_match('/^[a-z0-9-]{1,60}$/', $slug))
		{
			$this->respond(404, 'Page not found.');
		}
		$found = self::publicCalendarBySlug($slug);
		if ($found === null)
		{
			$this->respond(404, 'Page not found.');
		}
		list($cal, $t) = $found;
		$company_id = (int) $cal['company_id'];
		self::loadWebsiteLabels($company_id);
		$company = pjCompanyModel::factory()->find($company_id)->getData();
		if (empty($cal) || (isset($cal['status']) && $cal['status'] === 'F') || empty($company) || (isset($company['status']) && $company['status'] !== 'T'))
		{
			$this->respond(404, 'Page not found.');
		}
		$opt = self::calendarOptions($cal['id'], $company_id);
		$price = self::propertyPrice($cal['id'], $company_id);

		$theme = self::companyTheme($company_id);

		// nights already taken (bookings and imported calendars), next 12 months
		$booked = array();
		$rows = pjReservationModel::factory()
			->where('t1.calendar_id', (int) $cal['id'])
			->whereIn('t1.status', array('Confirmed', 'Pending'))
			->where('t1.date_to >=', date('Y-m-d'))
			->where('t1.date_from <=', date('Y-m-d', strtotime('+12 months')))
			->orderBy('t1.date_from ASC')->findAll()->getData();
		foreach ($rows as $r)
		{
			$booked[] = array('from' => $r['date_from'], 'to' => $r['date_to']);
		}

		$host_phone = !empty($company['phone']) ? (string) $company['phone'] : '';
		$out = array(
			'slug'          => self::propertyLink((int) $cal['id'], $t['name']),
			'name'          => (string) $t['name'],
			'property_type' => isset($t['property_type']) && in_array($t['property_type'], self::$propertyTypes, true) ? $t['property_type'] : null,
			'headline'      => isset($t['headline']) && $t['headline'] !== '' ? (string) $t['headline'] : null,
			'description'   => isset($t['description']) && $t['description'] !== '' ? self::htmlToText($t['description']) : null,
			'faqs'          => isset($t['faqs']) ? self::decodeFaqs($t['faqs']) : array(),
			'location'      => array(
				'city'    => isset($t['address_city']) ? (string) $t['address_city'] : '',
				'state'   => (string) $cal['address_state'],
				'country' => self::countryNameEn($cal['address_country'], $company_id),
				'lat'     => isset($cal['lat']) && is_numeric($cal['lat']) && (float) $cal['lat'] != 0 ? (float) $cal['lat'] : null,
				'lng'     => isset($cal['lng']) && is_numeric($cal['lng']) && (float) $cal['lng'] != 0 ? (float) $cal['lng'] : null,
			),
			'bedrooms'      => $cal['bedrooms'] !== null && $cal['bedrooms'] !== '' ? (int) $cal['bedrooms'] : null,
			'bathrooms'     => $cal['bathrooms'] !== null && $cal['bathrooms'] !== '' ? (int) $cal['bathrooms'] : null,
			'max_guests'    => isset($opt['o_max_people']) && (int) $opt['o_max_people'] > 0 ? (int) $opt['o_max_people'] : null,
			'amenities'     => self::propertyAmenities($cal['id'], $company_id),
			'price'         => $price['price'],
			'price_varies'  => $price['price_varies'],
			'price_based_on'=> isset($opt['o_price_based_on']) && $opt['o_price_based_on'] === 'days' ? 'days' : 'nights',
			'currency'      => $price['currency'],
			'currency_sign' => self::currencySign($price['currency']),
			'price_format'  => self::priceFormat($company_id),
			'photos'        => array_map(function ($p) { return array('url' => $p['url'], 'thumb_url' => $p['thumb_url']); }, self::photoList($cal['id'])),
			'host'          => array(
				'name'  => (string) $company['name'],
				// company page with all the host's properties (/stays/<slug>)
				'slug'  => self::companyLink((int) $company['id'], $company['name']),
				'stays' => count(self::publicCalendars($company_id)),
				'logo'  => !empty($company['logo']) ? PJ_INSTALL_URL . ltrim($company['logo'], '/') : null,
				'phone' => $host_phone,
				'email' => !empty($company['email']) ? (string) $company['email'] : '',
			),
			'theme'         => $theme,
			// "Designed by PHPJabbers" (Subscriptions > Plans)
			'branding'      => self::showBranding($company_id),
			'branding_line' => self::brandingLine(),
			'booked'        => $booked,
			'booking_url'   => PJ_INSTALL_URL . 'preview.php?cid=' . (int) $cal['id'],
			// booking card texts (back office > Languages)
			// page texts (System Options > Languages: book_* and site_* labels)
			'labels'        => array_merge(array(
				'book_now'     => self::lbl('book_book_now'),
				'check_in'     => self::lbl('book_check_in'),
				'check_out'    => self::lbl('book_check_out'),
				'choose_dates' => self::lbl('book_choose_dates'),
				'adults'       => self::lbl('bf_adults'),
				'children'     => self::lbl('bf_children'),
			), self::labelList(self::$siteLabels)),
			// Adults / children of the property's booking form (Booking options > Booking form)
			'guests'        => array(
				'adults'   => array('show' => (int) $opt['o_bf_adults'] !== 1, 'max' => max(1, (int) $opt['o_bf_adults_max'])),
				'children' => array('show' => (int) $opt['o_bf_children'] !== 1, 'max' => max(0, (int) $opt['o_bf_children_max'])),
			),
		);
		$this->respond(200, 'OK', $out);
	}

	/**
	 * Plans hosts can buy (Subscriptions > Plans: active and public), for the upgrade dialog. signup_token optional:
	 * then also the company's current plan and where to buy (Subscriptions > My plan in the dashboard).
	 * Data: plans[] {id, code, name, description, price, currency, billing_interval, interval_count, trial_days,
	 *       features[] {key, label}, includes_themes, purchasable}, current {code, name, status} | null, my_plan_url
	 */
	public function pjActionGetPlans()
	{
		$this->setAjax(true);
		$plans = array();
		if (class_exists('pjEntitlement') && pjEntitlement::isReady())
		{
			foreach (pjEntitlement::plans(true) as $plan)
			{
				if ((int) $plan['is_public'] !== 1)
				{
					continue;
				}
				$features = array();
				foreach ($plan['feature_list'] as $key)
				{
					$label = __(pjEntitlement::$features[$key], true);
					$features[] = array('key' => $key, 'label' => is_string($label) && $label !== '' ? $label : $key);
				}
				$plans[] = array(
					'id'               => (int) $plan['id'],
					'code'             => (string) $plan['code'],
					'name'             => (string) $plan['name'],
					'description'      => (string) $plan['description'],
					'price'            => $plan['price'],
					'currency'         => (string) $plan['currency'],
					'billing_interval' => (string) $plan['billing_interval'],
					'interval_count'   => (int) $plan['interval_count'],
					'trial_days'       => (int) $plan['trial_days'],
					'features'         => $features,
					'includes_themes'  => in_array('themes', $plan['feature_list'], true),
					'purchasable'      => pjEntitlement::isPurchasable($plan),
					'limits'           => array(
						'max_properties'       => $plan['max_properties'],
						'max_users'            => $plan['max_users'],
						'max_payment_gateways' => $plan['max_payment_gateways'],
					),
				);
			}
		}
		$current = null;
		if ($this->param('signup_token') !== '')
		{
			$this->requireSignupTable();
			$signup = $this->signupFromToken();
			if ((int) $signup['company_id'] > 0 && class_exists('pjEntitlement') && pjEntitlement::isReady())
			{
				$state = pjEntitlement::state((int) $signup['company_id']);
				if ($state['source'] === 'plan' && !empty($state['plan']))
				{
					$current = array('code' => (string) $state['plan']['code'], 'name' => (string) $state['plan']['name'], 'status' => (string) $state['status']);
				}
			}
		}
		$this->respond(200, 'OK', array(
			'plans'       => $plans,
			'current'     => $current,
			'my_plan_url' => PJ_INSTALL_URL . 'index.php?controller=pjAdminSubscriptions&action=pjActionMyPlan',
		));
	}

	/**
	 * Step 6 (last): theme. signup_token, palette_id* (from pjActionGetThemes), font_family (optional)
	 * Saved like Options > Theme colours. Completes the sign-up. The first theme is free; another theme
	 * needs a plan with themes (402 otherwise, nothing saved: the front end offers the plans).
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
		if ((string) $palette_id !== self::freeThemeId() && !$this->paidThemesAllowed($company_id))
		{
			$this->respond(402, 'This theme is part of the paid plans. Choose a plan to unlock it, or publish with the free theme.',
				array('free_id' => self::freeThemeId()), array('palette_id' => 'This theme needs a paid plan'));
		}

		$applied = true;
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
		$this->respond(200, 'Registration completed. You can now log in.', $state);
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

	/* ------------------------------------------------------------------
	 * Airbnb listing import
	 * ------------------------------------------------------------------ */

	/**
	 * Duplicate check before an Airbnb listing is imported (called by the Next.js import route).
	 * A listing counts as connected when one of its Airbnb calendar links (Properties > iCal feeds,
	 * https://www.airbnb.com/calendar/ical/<listing id>.ics) is saved for a property.
	 *
	 * POST signup_token, listing_id (digits)
	 * -> status: none | this_property | company (another property of the same company) | other (another account)
	 */
	public function pjActionCheckAirbnbListing()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$signup = $this->signupFromToken();
		$this->throttleCheck('airbnb', 'signup:' . (int) $signup['id'], 'Too many listing checks. Please wait a while and try again.');
		self::throttleHit('airbnb', 'signup:' . (int) $signup['id']);
		$listing_id = $this->param('listing_id');
		if (!preg_match('/^[0-9]{1,20}$/', $listing_id))
		{
			$this->respond(422, 'Validation failed', array(), array('listing_id' => 'Invalid Airbnb listing ID'));
		}

		$status = 'none';
		$feeds = pjFeedModel::factory()
			->select('t1.calendar_id, t1.url, t2.company_id')
			->join('pjCalendar', 't2.id=t1.calendar_id', 'left')
			->where('t1.provider_id', 2)
			// listing_id is digits only (checked above); every match is checked again exactly below
			->where(sprintf("t1.url LIKE '%%/ical/%s.ics%%'", $listing_id))
			->findAll()
			->getData();
		foreach ($feeds as $feed)
		{
			if (!preg_match('#/calendar/ical/' . $listing_id . '\.ics(\?|$)#', (string) $feed['url']))
			{
				continue;
			}
			if (!empty($signup['calendar_id']) && (int) $feed['calendar_id'] === (int) $signup['calendar_id'])
			{
				$status = 'this_property';
				break;
			}
			if ((int) $feed['company_id'] === (int) $signup['company_id'])
			{
				$status = 'company';
			} elseif ($status === 'none') {
				$status = 'other';
			}
		}
		// Another account's property is never named.
		$this->respond(200, 'OK', array('listing_id' => $listing_id, 'status' => $status));
	}

	/* ------------------------------------------------------------------
	 * Payment gateways
	 * ------------------------------------------------------------------ */

	/**
	 * Payment methods the host sets up on the front end, in this order. Saved per property in the same
	 * plugin_payment_options fields as the back office's Properties > Payments (test mode: test_<column>).
	 *   tier    free: every plan; pro: plans with "Payment gateway choice" (feature payment_gateway_choice)
	 *   fields  form field => column, secret (never sent back), optional, pattern, mode (pattern that captures
	 *           live|test, checked against test mode), timezone (Authorize.Net time zone)
	 * Card details never reach this API.
	 */
	private static $paymentGateways = array(
		'paypal' => array(
			'plugin' => 'pjPaypal', 'name' => 'PayPal', 'tier' => 'free',
			'fields' => array(
				'client_id' => array('column' => 'merchant_id', 'label' => 'PayPal client ID', 'pattern' => '/^[A-Za-z0-9_-]{20,128}$/'),
				'secret'    => array('column' => 'private_key', 'label' => 'PayPal secret', 'secret' => true, 'pattern' => '/^[A-Za-z0-9_-]{20,128}$/'),
			),
		),
		'authorize' => array(
			'plugin' => 'pjAuthorize', 'name' => 'Authorize.Net', 'tier' => 'pro',
			'fields' => array(
				'login_id'        => array('column' => 'merchant_id', 'label' => 'API login ID', 'pattern' => '/^[A-Za-z0-9]{6,25}$/'),
				'transaction_key' => array('column' => 'public_key', 'label' => 'transaction key', 'secret' => true, 'pattern' => '/^[A-Za-z0-9]{16}$/'),
				'signature_key'   => array('column' => 'private_key', 'label' => 'signature key', 'secret' => true, 'optional' => true, 'pattern' => '/^[A-Fa-f0-9]{128}$/'),
				'tz'              => array('column' => 'tz', 'label' => 'time zone', 'timezone' => true),
			),
		),
		'razorpay' => array(
			'plugin' => 'pjRazorpay', 'name' => 'Razorpay', 'tier' => 'pro',
			'fields' => array(
				'key_id'     => array('column' => 'public_key', 'label' => 'Razorpay key ID', 'pattern' => '/^rzp_(live|test)_[A-Za-z0-9]{8,40}$/', 'mode' => '/^rzp_(live|test)_/', 'hint' => 'rzp_%s_'),
				'key_secret' => array('column' => 'private_key', 'label' => 'Razorpay key secret', 'secret' => true, 'pattern' => '/^[A-Za-z0-9]{16,64}$/'),
			),
		),
		'stripe' => array(
			'plugin' => 'pjStripe', 'name' => 'Stripe', 'tier' => 'pro',
			'fields' => array(
				'public_key' => array('column' => 'public_key', 'label' => 'Stripe public key', 'pattern' => '/^pk_(live|test)_[A-Za-z0-9]{10,247}$/', 'mode' => '/^pk_(live|test)_/', 'hint' => 'pk_%s_'),
				'secret_key' => array('column' => 'private_key', 'label' => 'Stripe secret key', 'secret' => true, 'pattern' => '/^(?:sk|rk)_(live|test)_[A-Za-z0-9]{10,247}$/', 'mode' => '/^(?:sk|rk)_(live|test)_/', 'hint' => 'sk_%s_'),
			),
		),
		'cash' => array(
			'plugin' => null, 'name' => 'Cash', 'tier' => 'free', 'offline' => true,
			'fields' => array(),
		),
	);

	/** Payment methods available on this system (plugin installed), in the order above. */
	private static function availableGateways()
	{
		if (pjObject::getPlugin('pjPayments') === NULL || !class_exists('pjPaymentOptionModel'))
		{
			return array();
		}
		$out = array();
		foreach (self::$paymentGateways as $method => $gateway)
		{
			if ($gateway['plugin'] === null || pjObject::getPlugin($gateway['plugin']) !== NULL)
			{
				$out[$method] = $gateway;
			}
		}
		return $out;
	}

	/** May this company use the Pro payment gateways? (plan with "Payment gateway choice", administrators always) */
	private static function proGatewaysAllowed($company_id)
	{
		if (!class_exists('pjEntitlement') || !pjEntitlement::isReady())
		{
			return true;
		}
		if ((int) $company_id <= 0)
		{
			return false;
		}
		$state = pjEntitlement::state((int) $company_id);
		if ($state['source'] === 'admin')
		{
			return true;
		}
		return $state['source'] === 'plan' && $state['entitled'] && in_array('payment_gateway_choice', $state['features'], true);
	}

	/**
	 * Saved settings of one payment method for the API answer: values of the public fields per mode,
	 * and for secret fields only whether one is saved.
	 */
	private static function paymentMethodState($calendar_id, $payment_method, $pro_allowed)
	{
		$gateway = self::$paymentGateways[$payment_method];
		$row = pjPaymentOptionModel::factory()->getOptions((int) $calendar_id, $payment_method);
		$labels = pjMultiLangModel::factory()->getMultiLang((int) $calendar_id, 'pjPayment');
		$label = '';
		foreach ($labels as $locale_arr)
		{
			if (!empty($locale_arr[$payment_method]))
			{
				$label = $locale_arr[$payment_method];
				break;
			}
		}
		$values = array('live' => array(), 'test' => array());
		$saved = array('live' => array(), 'test' => array());
		foreach ($gateway['fields'] as $field => $f)
		{
			foreach (array('live' => '', 'test' => 'test_') as $mode => $prefix)
			{
				$v = !empty($row[$prefix . $f['column']]) ? (string) $row[$prefix . $f['column']] : '';
				if (!empty($f['secret']))
				{
					$saved[$mode][$field] = $v !== '';
				} else {
					$values[$mode][$field] = $v;
				}
			}
		}
		return array(
			'payment_method' => $payment_method,
			'name'           => $gateway['name'],
			'tier'           => $gateway['tier'],
			'offline'        => !empty($gateway['offline']),
			'locked'         => $gateway['tier'] === 'pro' && !$pro_allowed,
			'is_active'      => !empty($row) && (int) $row['is_active'] === 1,
			'is_test_mode'   => !empty($row) && (int) $row['is_test_mode'] === 1,
			'label'          => (string) $label,
			'values'         => $values,
			'saved_secrets'  => $saved,
		);
	}

	/**
	 * Payment methods of the sign-up property (PayPal, Authorize.Net, Razorpay, Stripe, Cash), to fill in the forms.
	 * Data: payment_methods[] (in order), pro_allowed, active_gateways (online gateways switched on)
	 */
	public function pjActionGetPaymentMethods()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$signup = $this->signupFromToken();
		$calendar = $this->signupProperty($signup);
		$calendar_id = (int) $calendar['id'];
		$pro = self::proGatewaysAllowed((int) $signup['company_id']);

		$methods = array();
		foreach (array_keys(self::availableGateways()) as $payment_method)
		{
			$methods[] = self::paymentMethodState($calendar_id, $payment_method, $pro);
		}
		$this->respond(200, 'OK', array(
			'payment_methods' => $methods,
			'pro_allowed'     => $pro,
			'active_gateways' => class_exists('pjEntitlement') ? pjEntitlement::activeGateways($calendar_id) : array(),
		));
	}

	/**
	 * Saves one payment method of the sign-up property (Properties > Payments, plugin_payment_options).
	 *
	 * POST signup_token, payment_method (paypal|authorize|razorpay|stripe|cash), is_active (0|1), is_test_mode (0|1),
	 *      label (optional), keys (JSON {field: value} for the mode sent; an empty secret keeps the saved one).
	 */
	public function pjActionSavePaymentMethod()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$signup = $this->signupFromToken();
		$calendar = $this->signupProperty($signup);
		$calendar_id = (int) $calendar['id'];
		$company_id = (int) $signup['company_id'];

		$payment_method = $this->param('payment_method');
		$available = self::availableGateways();
		if (!isset(self::$paymentGateways[$payment_method]))
		{
			$this->respond(422, 'Validation failed', array(), array('payment_method' => 'Unknown payment method'));
		}
		$gateway = self::$paymentGateways[$payment_method];
		if (!isset($available[$payment_method]))
		{
			$this->respond(503, sprintf('%s payments are not available on this system.', $gateway['name']));
		}

		$is_active = $this->param('is_active') === '1';
		$pro = self::proGatewaysAllowed($company_id);
		if ($gateway['tier'] === 'pro' && $is_active && !$pro)
		{
			$plans = self::planNamesWith('payment_gateway_choice');
			$this->respond(402, $plans !== ''
				? sprintf('%s comes with the %s plan. PayPal and cash are free on every plan.', $gateway['name'], $plans)
				: sprintf('%s is not included in your plan. PayPal and cash are free on every plan.', $gateway['name']));
		}
		$offline = !empty($gateway['offline']);
		$is_test_mode = !$offline && $this->param('is_test_mode') === '1';
		$mode = $is_test_mode ? 'test' : 'live';
		$prefix = $is_test_mode ? 'test_' : '';
		$label = $this->has('label') ? trim(strip_tags($this->param('label'))) : '';
		$keys = json_decode($this->param('keys'), true);
		$keys = is_array($keys) ? $keys : array();
		$current = pjPaymentOptionModel::factory()->getOptions($calendar_id, $payment_method);

		$errors = array();
		$data = array();
		foreach ($gateway['fields'] as $field => $f)
		{
			$value = isset($keys[$field]) && is_string($keys[$field]) ? trim($keys[$field]) : '';
			$column = $prefix . $f['column'];
			$secret = !empty($f['secret']);
			$has_saved = !empty($current[$column]);
			if ($value === '')
			{
				// empty: keep what is saved; needed when switching on
				if ($is_active && empty($f['optional']) && !$has_saved)
				{
					$errors[$field] = !empty($f['timezone']) ? 'Choose the time zone of your Authorize.Net account' : sprintf('Enter your %s', $f['label']);
				}
				continue;
			}
			if (!empty($f['timezone']))
			{
				if (!in_array($value, timezone_identifiers_list(), true))
				{
					$errors[$field] = 'Choose a time zone from the list';
				}
			} elseif (strlen($value) > 255 || ctype_digit($value) && strlen($value) > 12) {
				// (long digits only: never a gateway key, possibly a card number: not saved)
				$errors[$field] = sprintf('This is not a valid %s', $f['label']);
			} elseif (!$secret && self::looksLikeSecret($gateway, $value)) {
				// A secret pasted into a field that is shown again must not be saved there.
				$errors[$field] = 'This is a secret key. Enter it in its own field.';
			} elseif (!preg_match($f['pattern'], $value)) {
				$errors[$field] = isset($f['hint'])
					? sprintf('This is not a valid %s. It should start with %s', $f['label'], sprintf($f['hint'], $mode))
					: sprintf('This is not a valid %s', $f['label']);
			} elseif (isset($f['mode']) && preg_match($f['mode'], $value, $m) && $m[1] !== $mode) {
				$errors[$field] = sprintf('Test mode is %s, so use your %s key', $is_test_mode ? 'on' : 'off', $mode);
			}
			if (!isset($errors[$field]))
			{
				$data[$column] = $value;
			}
		}
		if (self::tooLong($label, 255))
		{
			$errors['label'] = 'Maximum 255 characters allowed';
		} elseif (preg_match('/\d(?:[ -]?\d){12,18}/', $label)) {
			// The label is shown to guests: no card or account numbers.
			$errors['label'] = 'The label cannot contain long numbers';
		}
		if (!empty($errors))
		{
			$this->respond(422, 'Validation failed', array(), $errors);
		}

		// Subscription plan: number of online gateways switched on per property (same rule as Properties > Payments).
		if ($is_active && !$offline && class_exists('pjEntitlement') && pjEntitlement::countsAsGateway($company_id, $payment_method))
		{
			$max = pjEntitlement::limit($company_id, 'max_payment_gateways');
			$active = pjEntitlement::countedGateways($company_id, $calendar_id);
			if ($max !== null && !in_array($payment_method, $active, true) && count($active) >= $max)
			{
				$this->respond(402, __('subGatewayLimit', true), array('active_gateways' => $active));
			}
		}

		if ($offline)
		{
			// Cash: only switched on / off (as Properties > Payments does)
			$m = pjPaymentOptionModel::factory();
			if ((int) $m->where('foreign_id', $calendar_id)->where('`payment_method`', $payment_method)->findCount()->getData() === 0)
			{
				$m->reset()->setAttributes(array('foreign_id' => $calendar_id, 'payment_method' => $payment_method, 'is_active' => $is_active ? 1 : 0))->insert();
			} else {
				$m->reset()->where('foreign_id', $calendar_id)->where('`payment_method`', $payment_method)->limit(1)->modifyAll(array('is_active' => $is_active ? 1 : 0));
			}
		} else {
			$data['is_active'] = $is_active ? 1 : 0;
			$data['is_test_mode'] = $is_test_mode ? 1 : 0;
			pjPaymentOptionModel::factory()->saveOptions(array($payment_method => $data), $calendar_id);
		}

		if ($label !== '')
		{
			$i18n = array();
			foreach (self::companyLocales($company_id) as $lid)
			{
				$i18n[$lid] = array($payment_method => $label);
			}
			pjMultiLangModel::factory()->updateMultiLang($i18n, $calendar_id, 'pjPayment', 'data');
		}

		$state = self::paymentMethodState($calendar_id, $payment_method, $pro);
		$state['active_gateways'] = class_exists('pjEntitlement') ? pjEntitlement::activeGateways($calendar_id) : array();
		$this->respond(200, sprintf('%s settings saved.', $gateway['name']), $state);
	}

	/** Does a value match one of the gateway's secret fields? */
	private static function looksLikeSecret($gateway, $value)
	{
		foreach ($gateway['fields'] as $f)
		{
			if (!empty($f['secret']) && isset($f['mode']) && preg_match($f['pattern'], $value))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Host log-in on the front end: email + password of a company account made with the sign-up.
	 * Works for unfinished and completed sign-ups: returns a new signup_token (other devices are logged out)
	 * and the state (completed -> the front end shows the "your page is live / payments" page).
	 */
	public function pjActionSignupLogin()
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

		$user = $this->verifyCredentials($email, $password);
		$this->loginUser($user);
	}

	/**
	 * Logs a checked user in on the front end (password or Google): new signup_token + state.
	 * Companies added in the back office get a completed sign-up on their first log-in.
	 */
	private function loginUser($user)
	{
		$dashboard = PJ_INSTALL_URL . 'index.php?controller=pjBase&action=pjActionLogin';
		$signup = (int) $user['role_id'] === self::ROLE_COMPANY
			? pjCompanySignupModel::factory()->where('t1.user_id', (int) $user['id'])->limit(1)->findAll()->getDataIndex(0)
			: array();
		// A sign-up stopped after step 1 has no company yet; otherwise the login needs a company of its own.
		if (empty($signup) && ((int) $user['role_id'] !== self::ROLE_COMPANY || (int) $user['company_id'] <= 0))
		{
			// Administrators and other back-office users have no booking page: they use the dashboard.
			$this->respond(404, 'This account has no StayOwned page. Please log in to the dashboard.', array('login_url' => $dashboard));
		}
		if (empty($signup) || $signup['status'] === 'completed')
		{
			// Same rules as the back-office log-in: active, not locked, and the company still exists.
			$company = (int) $user['company_id'] > 0 ? pjCompanyModel::factory()->find((int) $user['company_id'])->getData() : array();
			if ($user['status'] !== 'T' || (isset($user['locked']) && $user['locked'] === 'T')
				|| empty($company) || (isset($company['status']) && $company['status'] !== 'T'))
			{
				$this->respond(403, 'This account is disabled. Please contact us.');
			}
		}
		if (empty($signup))
		{
			// Company added in the back office (Companies > Add): no sign-up yet. Its log-in is linked to a
			// completed sign-up here, with the company's first property, so it opens its page like a host who signed up.
			$calendar = self::firstCalendar((int) $user['company_id']);
			$now = date('Y-m-d H:i:s');
			$id = pjCompanySignupModel::factory(array(
				'user_id'      => (int) $user['id'],
				'company_id'   => (int) $user['company_id'],
				'calendar_id'  => !empty($calendar) ? (int) $calendar['id'] : ':NULL',
				'step'         => count(self::$steps),
				'status'       => 'completed',
				'ip'           => pjUtil::getClientIp(),
				'created'      => $now,
				'modified'     => $now,
				'completed_at' => $now,
			))->insert()->getInsertId();
			if ($id === false || (int) $id <= 0)
			{
				$this->respond(500, 'You could not be logged in. Please try again.');
			}
			$signup = pjCompanySignupModel::factory()->find((int) $id)->getData();
		} elseif ($signup['status'] === 'completed') {
			// The property may have been deleted or added in the back office since: show the company's current one.
			$current = !empty($signup['calendar_id']) ? pjCalendarModel::factory()->find((int) $signup['calendar_id'])->getData() : array();
			if (empty($current) || (int) $current['company_id'] !== (int) $signup['company_id'])
			{
				$calendar = self::firstCalendar((int) $signup['company_id']);
				pjCompanySignupModel::factory()->set('id', (int) $signup['id'])->modify(array(
					'calendar_id' => !empty($calendar) ? (int) $calendar['id'] : ':NULL',
				));
			}
		}
		$token = $this->issueToken((int) $signup['id']);
		$this->respond(200, $signup['status'] === 'completed' ? 'You are logged in.' : 'Please continue your registration.',
			array_merge(array('signup_token' => $token), $this->signupState($signup)));
	}

	/* ------------------------------------------------------------------
	 * Google sign-in
	 * ------------------------------------------------------------------ */

	/** OAuth client id of the Google Cloud project (app/config/google.php), or ''. */
	private static function googleClientId()
	{
		// System options > API Keys: Google client ID (administrator); app/config/google.php as a fallback
		$row = pjBaseOptionModel::factory()->select('t1.value')->where('t1.foreign_id', 1)->where('t1.key', 'o_google_client_id')->limit(1)->findAll()->getDataIndex(0);
		if (!empty($row) && trim((string) $row['value']) !== '')
		{
			return trim((string) $row['value']);
		}
		$file = dirname(__FILE__) . '/../config/google.php';
		$cfg = is_file($file) ? include $file : array();
		return is_array($cfg) && !empty($cfg['client_id']) ? trim((string) $cfg['client_id']) : '';
	}

	/** GET a Google JSON endpoint (optionally with a Bearer token). Array, or null on any error. */
	private static function googleGet($url, $bearer = null)
	{
		$headers = array('Accept: application/json');
		if ($bearer !== null)
		{
			$headers[] = 'Authorization: Bearer ' . $bearer;
		}
		$body = false;
		$status = 0;
		if (function_exists('curl_init'))
		{
			$ch = curl_init($url);
			curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 8,
				CURLOPT_HTTPHEADER => $headers, CURLOPT_FOLLOWLOCATION => false));
			$body = curl_exec($ch);
			$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
		} else {
			$ctx = stream_context_create(array('http' => array('method' => 'GET', 'header' => implode("\r\n", $headers), 'timeout' => 15, 'ignore_errors' => true)));
			$body = @file_get_contents($url, false, $ctx);
			if (isset($http_response_header[0]) && preg_match('~\s(\d{3})\s~', $http_response_header[0], $m))
			{
				$status = (int) $m[1];
			}
		}
		if ($body === false || $status !== 200)
		{
			return null;
		}
		$data = json_decode($body, true);
		return is_array($data) ? $data : null;
	}

	/**
	 * Checks a Google access token (from "Continue with Google" on the front end): it must have been issued
	 * to this site's client id and belong to a verified e-mail. Returns {email, name} or stops with an error.
	 */
	private function googleProfile($access_token, $site_client_id = '')
	{
		$client_id = self::googleClientId();
		if ($client_id === '')
		{
			// Not set in app/config/google.php: the client id of the website (NEXT_PUBLIC_GOOGLE_CLIENT_ID).
			// For development only; on a live site set it in app/config/google.php so the back end decides.
			$site_client_id = trim((string) $site_client_id);
			if (preg_match('/^[0-9A-Za-z\-]{6,120}\.apps\.googleusercontent\.com$/', $site_client_id))
			{
				$client_id = $site_client_id;
			}
		}
		if ($client_id === '')
		{
			$this->respond(503, 'Google sign-in is not set up yet. Please use your e-mail and password.');
		}
		$access_token = (string) $access_token;
		if (!preg_match('#^[A-Za-z0-9._\-~+/=]{20,4096}$#', $access_token))
		{
			$this->respond(401, 'Google sign-in failed. Please try again.');
		}
		$info = self::googleGet('https://oauth2.googleapis.com/tokeninfo?access_token=' . rawurlencode($access_token));
		$aud = isset($info['aud']) ? $info['aud'] : (isset($info['azp']) ? $info['azp'] : '');
		if (empty($info) || $aud !== $client_id || (isset($info['expires_in']) && (int) $info['expires_in'] <= 0))
		{
			$this->respond(401, 'Google sign-in failed. Please try again.');
		}
		$profile = self::googleGet('https://openidconnect.googleapis.com/v1/userinfo', $access_token);
		$email = isset($profile['email']) ? strtolower(trim((string) $profile['email'])) : '';
		$verified = isset($profile['email_verified']) && ($profile['email_verified'] === true || $profile['email_verified'] === 'true');
		if ($email === '' || !pjValidation::pjActionEmail($email))
		{
			$this->respond(401, 'Google did not share your e-mail address. Please try again.');
		}
		if (!$verified)
		{
			$this->respond(401, 'Your Google e-mail address is not verified.');
		}
		$name = isset($profile['name']) ? trim((string) $profile['name']) : '';
		return array('email' => $email, 'name' => self::tooLong($name, 255) ? '' : $name);
	}

	/** Settings the front end needs for sign-in. */
	public function pjActionGetAuthConfig()
	{
		$this->setAjax(true);
		$this->respond(200, 'OK', array('google_client_id' => self::googleClientId()));
	}

	/**
	 * "Continue with Google": access_token (Google OAuth token for this site's client id).
	 * Known e-mail -> logged in like pjActionSignupLogin. Unknown -> {new_account: true, google: {email, name}}:
	 * the front end shows step 1 with these values and sends google_access_token with it.
	 */
	public function pjActionSignupGoogle()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$google = $this->googleProfile($this->param('access_token'), $this->param('client_id'));
		$user = $this->findUserByEmail($google['email']);
		if (!empty($user))
		{
			$this->loginUser($user);
		}
		$this->respond(200, 'Welcome! Add your WhatsApp number to create your account.', array(
			'new_account' => true,
			'google' => $google,
		));
	}

	/** First property of a company (lowest id), or an empty array. */
	private static function firstCalendar($company_id)
	{
		return pjCalendarModel::factory()
			->where('t1.company_id', (int) $company_id)
			->orderBy('t1.id ASC')
			->limit(1)
			->findAll()
			->getDataIndex(0);
	}

	/** Log out on this device: the token is removed (log in again with email + password). signup_token */
	public function pjActionSignupLogout()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();
		$signup = $this->signupFromToken();
		pjCompanySignupModel::factory()->set('id', (int) $signup['id'])->modify(array(
			'token_hash'    => ':NULL',
			'token_expires' => ':NULL',
			'modified'      => date('Y-m-d H:i:s'),
		));
		$this->respond(200, 'You are logged out.');
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

		$user = $this->verifyCredentials($email, $password);
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
		$this->throttleCheck('check_email', $this->throttleIp(), 'Too many checks. Please wait a few minutes and try again.');
		self::throttleHit('check_email', $this->throttleIp());
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
		// Suggestions for a new account: the country's currency (when offered) and time zones (main one first)
		$codes = self::currencyCodes();
		$known = array_flip(timezone_identifiers_list());
		foreach ($countries as $i => $c)
		{
			$currency = pjCountryDefaults::currency($c['alpha_2']);
			$main = pjCountryDefaults::timezone($c['alpha_2']);
			$zones = array_values(array_filter(pjCountryDefaults::timezones($c['alpha_2']), function ($z) use ($known) { return isset($known[$z]); }));
			$countries[$i]['currency'] = $currency !== null && in_array($currency, $codes, true) ? $currency : null;
			$countries[$i]['timezone'] = $main !== null && isset($known[$main]) ? $main : (!empty($zones) ? $zones[0] : null);
			$countries[$i]['timezones'] = $zones;
		}
		$this->respond(200, 'OK', array('countries' => $countries));
	}

	/* ------------------------------------------------------------------
	 * Booking on the host's website (localhost:3000/property/<slug>/book)
	 * Everything comes from the property's settings in the back office: guests (Booking options), booking form
	 * fields (o_bf_*: 1 hidden, 2 optional, 3 required), extras, coupons and discounts, prices, tax, deposit,
	 * security, terms, payment methods (Properties > Payments, allowed by the plan) and the texts (back office
	 * language labels). Prices come from the back office's own calculation (pjActionCalcPrices) and the
	 * booking is saved like the old booking form (pjFront::pjActionBookingSave).
	 * ------------------------------------------------------------------ */

	/** Language of the website texts: English, otherwise the company's default language. */
	private static function websiteLocale($company_id)
	{
		static $cache = array();
		$company_id = (int) $company_id;
		if (!isset($cache[$company_id]))
		{
			$en = pjBaseLocaleModel::factory()->unscoped()->where('t1.language_iso LIKE', 'en%')->orderBy('t1.id ASC')->limit(1)->findAll()->getDataIndex(0);
			$lid = !empty($en) ? (int) $en['id'] : pjCompanyContext::companyDefaultLocale($company_id);
			$cache[$company_id] = $lid > 0 ? $lid : 1;
		}
		return $cache[$company_id];
	}

	/** Public property of the link, its company and options: array(cal, texts, company_id, options, slug) or a 404. */
	private function bookingProperty()
	{
		$slug = strtolower($this->param('slug'));
		$found = ($slug !== '' && preg_match('/^[a-z0-9-]{1,60}$/', $slug)) ? self::publicCalendarBySlug($slug) : null;
		if ($found === null)
		{
			$this->respond(404, self::lbl('book_not_found'));
		}
		list($cal, $t) = $found;
		$cid = (int) $cal['company_id'];
		self::useCompanyTimezone($cid);
		self::loadWebsiteLabels($cid);
		$opt = self::calendarOptions($cal['id'], $cid);
		// the price calculation also reads options from the controller
		$this->option_arr = $opt;
		return array($cal, $t, $cid, $opt, self::propertyLink((int) $cal['id'], isset($t['name']) ? $t['name'] : $slug));
	}

	/** Language whose label texts are loaded for this request (null: not loaded yet). */
	private static $labelsLocale = null;

	/**
	 * Loads the label texts (System Options > Languages) of the website language fresh from the database.
	 * The API has no back-office session options, so the texts cached in its session were never refreshed:
	 * a label changed in the back office kept its old text on the websites.
	 */
	private static function loadWebsiteLabels($company_id)
	{
		$lid = self::websiteLocale((int) $company_id);
		if (self::$labelsLocale === $lid)
		{
			return;
		}
		pjAppController::setFields($lid);
		self::$labelsLocale = $lid;
	}

	/** Text of the back office language (the key itself when it has none). */
	private static function lbl($key)
	{
		if (self::$labelsLocale === null)
		{
			self::loadWebsiteLabels(0);
		}
		$v = __($key, true);
		return is_string($v) && $v !== '' ? $v : $key;
	}

	/** Booking form fields (Booking options > Booking form): name => array(option, label, input type). */
	private static $bookingFields = array(
		'c_name'    => array('o_bf_name', 'bf_name', 'text'),
		'c_email'   => array('o_bf_email', 'bf_email', 'email'),
		'c_phone'   => array('o_bf_phone', 'bf_phone', 'tel'),
		'c_country' => array('o_bf_country', 'bf_country', 'country'),
		'c_address' => array('o_bf_address', 'bf_address', 'text'),
		'c_city'    => array('o_bf_city', 'bf_city', 'text'),
		'c_state'   => array('o_bf_state', 'bf_state', 'text'),
		'c_zip'     => array('o_bf_zip', 'bf_zip', 'text'),
		'c_notes'   => array('o_bf_notes', 'bf_notes', 'textarea'),
	);

	/** Payment methods of the property that are switched on and allowed by the company's plan: id => {id, title, online}. */
	/** Currencies PayPal takes (INR only works for PayPal accounts registered in India, so it is not offered). */
	private static $paypalCurrencies = array('AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN',
		'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'THB', 'USD');

	/** Can this gateway take payments in this currency? (only PayPal has a fixed list; the others check on their own page) */
	private static function gatewayTakesCurrency($method, $currency)
	{
		$currency = strtoupper(trim((string) $currency));
		if ($currency !== '' && in_array($method, array('paypal', 'paypal_express'), true))
		{
			return in_array($currency, self::$paypalCurrencies, true);
		}
		return true;
	}

	private static function bookingPaymentMethods($calendar_id, $company_id, $locale_id, $currency = '')
	{
		if (pjObject::getPlugin('pjPayments') === NULL)
		{
			return array();
		}
		$options = pjPaymentOptionModel::factory()->getOptions($calendar_id);
		$titles = pjPayments::getPaymentTitles($calendar_id, $locale_id);
		$names = __('payment_methods', true);
		$order = array('paypal', 'authorize', 'paypal_express', '2checkout', 'stripe', 'razorpay', 'mollie', 'skrill', 'world_pay', 'braintree', 'bank', 'cash');
		$out = array();
		foreach ($order as $method)
		{
			$o = isset($options[$method]) ? $options[$method] : null;
			if (!is_array($o) || !isset($o['is_active']) || (int) $o['is_active'] !== 1)
			{
				continue;
			}
			$offline = in_array($method, array('cash', 'bank'), true);
			if (!$offline && pjObject::getPlugin(pjPayments::getPluginName($method)) === NULL)
			{
				continue;
			}
			if (class_exists('pjEntitlement') && !pjEntitlement::allowsPaymentMethod($company_id, $method))
			{
				continue;
			}
			if (!$offline && !self::gatewayTakesCurrency($method, $currency))
			{
				continue;
			}
			$title = !empty($titles[$method]) ? $titles[$method] : (isset($names[$method]) ? $names[$method] : ucfirst($method));
			$out[$method] = array('id' => $method, 'title' => (string) $title, 'online' => !$offline);
		}
		return $out;
	}

	/** Bank account text of the property (bank transfer). */
	private static function bankAccount($calendar_id, $locale_id)
	{
		$row = pjMultiLangModel::factory()->select('t1.content')->where('t1.model', 'pjOption')->where('t1.locale', (int) $locale_id)
			->where('t1.foreign_id', (int) $calendar_id)->where('t1.field', 'o_bank_account')->limit(1)->findAll()->getDataIndex(0);
		return $row ? trim((string) $row['content']) : '';
	}

	/** Extras of the property (active ones), in the website language. */
	private static function bookingExtras($calendar_id, $locale_id)
	{
		$rows = pjExtraModel::factory()
			// name in the website language, else in any language that has it (extras added in another language)
			->select(sprintf("t1.*, COALESCE(NULLIF(t2.content, ''), (SELECT ml.content FROM `%s` AS ml WHERE ml.model='pjExtra' AND ml.foreign_id=t1.id AND ml.field='name' AND ml.content <> '' ORDER BY ml.locale ASC LIMIT 1)) AS name", pjMultiLangModel::factory()->getTable()))
			->join('pjMultiLang', sprintf("t2.model='pjExtra' AND t2.foreign_id=t1.id AND t2.field='name' AND t2.locale='%u'", (int) $locale_id), 'left outer')
			->where('t1.status', 'T')
			->where(sprintf("t1.id IN (SELECT `extra_id` FROM `%s` WHERE `calendar_id` = %u)", pjCalendarExtraModel::factory()->getTable(), (int) $calendar_id))
			->orderBy('`name` ASC')->findAll()->getData();
		$out = array();
		foreach ($rows as $r)
		{
			$max = ($r['multi'] == 'T' || in_array($r['price_type'], array('count', 'count_night'), true)) ? max(1, (int) $r['max_count']) : 1;
			$out[] = array('id' => (int) $r['id'], 'name' => (string) $r['name'], 'price' => round((float) $r['price'], 2),
				'price_type' => (string) $r['price_type'], 'required' => $r['required'] == 'T', 'max' => $max);
		}
		return $out;
	}

	/** Min / max stay of the period the check-in falls in (Properties > Limits): array(min, max), null = no rule. */
	private static function stayLimits($calendar_id, $date_from)
	{
		$row = pjLimitModel::factory()->where('t1.calendar_id', (int) $calendar_id)->where('t1.date_from <=', $date_from)->where('t1.date_to >=', $date_from)
			->where("(t1.blocked IS NULL OR t1.blocked <> 'T')")->orderBy('t1.id DESC')->limit(1)->findAll()->getDataIndex(0);
		return array(!empty($row['min_nights']) ? (int) $row['min_nights'] : null, !empty($row['max_nights']) ? (int) $row['max_nights'] : null);
	}

	/** Dates, guests, extras and coupon of the request, checked against the property: array(values, error text or ''). */
	private function bookingRequest($cal, $opt)
	{
		$from = $this->param('date_from');
		$to = $this->param('date_to');
		$v = array('date_from' => $from, 'date_to' => $to,
			'adults' => (int) $opt['o_bf_adults'] !== 1 ? (int) $this->param('adults') : 0,
			'children' => (int) $opt['o_bf_children'] !== 1 ? (int) $this->param('children') : 0,
			'extras' => array(), 'qty' => array(), 'promo_code' => trim((string) $this->param('promo_code')));
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) || !strtotime($from) || !strtotime($to))
		{
			return array($v, self::lbl('book_choose_dates'));
		}
		if ($from < date('Y-m-d'))
		{
			return array($v, self::lbl('book_err_past'));
		}
		if ($to < $from || ($opt['o_price_based_on'] !== 'days' && $to === $from))
		{
			return array($v, self::lbl('book_err_dates'));
		}
		$nights = (int) round((strtotime($to) - strtotime($from)) / 86400) + ($opt['o_price_based_on'] === 'days' ? 1 : 0);
		list($min, $max) = self::stayLimits($cal['id'], $from);
		if ($min !== null && $nights < $min)
		{
			return array($v, str_replace('{N}', $min, self::lbl('book_err_min_nights')));
		}
		if ($max !== null && $nights > $max)
		{
			return array($v, str_replace('{N}', $max, self::lbl('book_err_max_nights')));
		}
		if ((int) $opt['o_bf_adults'] === 3 && $v['adults'] < 1)
		{
			return array($v, self::lbl('book_err_guests'));
		}
		if ($v['adults'] < 0 || $v['adults'] > max(1, (int) $opt['o_bf_adults_max']) || $v['children'] < 0 || $v['children'] > max(0, (int) $opt['o_bf_children_max']))
		{
			return array($v, self::lbl('book_err_guests'));
		}
		$people = $v['adults'] + $v['children'];
		if (!empty($opt['o_max_people']) && (int) $opt['o_max_people'] > 0 && $people > (int) $opt['o_max_people'])
		{
			return array($v, str_replace('{MAX}', (int) $opt['o_max_people'], self::lbl('front_max_people_msg')));
		}
		if (!empty($opt['o_min_people']) && (int) $opt['o_min_people'] > 0 && $people < (int) $opt['o_min_people'])
		{
			return array($v, str_replace('{MIN}', (int) $opt['o_min_people'], self::lbl('front_min_people_msg')));
		}
		// extras {id: qty}; the required ones are always included
		$wanted = json_decode((string) $this->param('extras'), true);
		$extras = array();
		$qty = array();
		foreach ((is_array($wanted) ? $wanted : array()) as $id => $q)
		{
			if ((int) $q > 0)
			{
				$extras[(int) $id] = 'on';
				$qty[(int) $id] = (int) $q;
			}
		}
		foreach (self::bookingExtras($cal['id'], self::websiteLocale($cal['company_id'])) as $e)
		{
			if ($e['required'] && !isset($extras[$e['id']]))
			{
				$extras[$e['id']] = 'on';
				$qty[$e['id']] = 1;
			}
		}
		list($v['extras'], $v['qty']) = pjAppController::sanitizeExtras($cal['id'], $extras, $qty);
		// free dates: bookings, blocked periods, bookings per day
		$check = $this->pjActionCheckDt($from, $to, (int) $cal['id'], NULL, TRUE);
		if ($check['status'] !== 'OK')
		{
			return array($v, !empty($check['text']) ? $check['text'] : self::lbl('book_err_taken'));
		}
		return array($v, '');
	}

	/** Price of a request (the back office's calculation). */
	private function bookingPrice($cal, $opt, $v, $company_id)
	{
		$p = $this->pjActionCalcPrices((int) $cal['id'], strtotime($v['date_from']), strtotime($v['date_to']), $v['adults'], $v['children'],
			$v['extras'], $v['qty'], $v['promo_code'], $opt, self::websiteLocale($company_id));
		$f = function ($k) use ($p) { return isset($p[$k]) && is_numeric($p[$k]) ? round((float) $p[$k], 2) : 0.0; };
		$promo = (int) $p['promo_valid'] === 1;
		$discount = (int) $p['discount_valid'] === 1;
		return array(
			'nights'          => (int) $p['nights'],
			'amount'          => $f('amount'),
			'extra_price'     => $f('extra_price'),
			'promo_valid'     => $promo,
			'promo_amount'    => $promo ? $f('promo_amount') : 0.0,
			'discount_valid'  => $discount,
			'discount_amount' => $discount ? $f('discount_amount') : 0.0,
			'discount_name'   => (string) $p['discount_name'],
			'tax'             => $f('tax'),
			'tourist_tax'     => $f('tourist_tax'),
			'security'        => $f('security'),
			'total'           => $f('total'),
			'deposit'         => $f('deposit'),
			// left after the payment now (the security deposit too when it is not part of the payment now)
			'on_arrival'      => round(max(0, $f('total') + $f('security') - $f('deposit')), 2),
			'raw'             => $p,
		);
	}

	/**
	 * Booking page: property, guests, booking form fields, extras, payment methods, terms, taken dates and the
	 * texts of the page (back office language). slug*
	 */
	public function pjActionBookingSetup()
	{
		$this->setAjax(true);
		list($cal, $t, $cid, $opt, $slug) = $this->bookingProperty();
		$lid = self::websiteLocale($cid);
		$price = self::propertyPrice($cal['id'], $cid);
		$photos = self::photoList($cal['id']);
		$company = pjCompanyModel::factory()->find($cid)->getData();

		$fields = array();
		foreach (self::$bookingFields as $name => $f)
		{
			$mode = isset($opt[$f[0]]) ? (int) $opt[$f[0]] : 1;
			if ($mode === 2 || $mode === 3)
			{
				$fields[] = array('name' => $name, 'label' => self::lbl($f[1]), 'type' => $f[2], 'required' => $mode === 3);
			}
		}
		$countries = array();
		if ((int) $opt['o_bf_country'] !== 1)
		{
			$countries = pjBaseCountryModel::factory()->select('t1.id, t2.content AS name')
				->join('pjMultiLang', sprintf("t2.model='pjBaseCountry' AND t2.foreign_id=t1.id AND t2.field='name' AND t2.locale='%u'", $lid), 'left outer')
				->where(pjCompanyContext::countryActiveSql($cid))->orderBy('`name` ASC')->findAll()->getData();
		}
		$terms = null;
		if ((int) $opt['o_bf_terms'] !== 1)
		{
			$tt = pjMultiLangModel::factory()->select('t1.field, t1.content')->where('t1.model', 'pjCalendar')->where('t1.foreign_id', (int) $cal['id'])
				->where('t1.locale', $lid)->whereIn('t1.field', array('terms_url', 'terms_body'))->findAll()->getDataPair('field', 'content');
			$terms = array(
				'required' => (int) $opt['o_bf_terms'] === 3,
				'label'    => self::lbl('bf_terms'),
				'url'      => isset($tt['terms_url']) && preg_match('~^https?://~', (string) $tt['terms_url']) ? (string) $tt['terms_url'] : '',
				'body'     => isset($tt['terms_body']) ? self::htmlToText($tt['terms_body']) : '',
			);
		}
		// taken dates: bookings and blocked periods, next 12 months ("to" = check-out day)
		$booked = array();
		// status: pending (waiting for the host / payment) or booked (confirmed bookings and blocked periods)
		foreach (pjReservationModel::factory()->select('t1.date_from, t1.date_to, t1.status')->where('t1.calendar_id', (int) $cal['id'])
			->where('t1.status <>', 'Cancelled')->where('t1.date_to >=', date('Y-m-d'))
			->where('t1.date_from <=', date('Y-m-d', strtotime('+12 months')))->orderBy('t1.date_from ASC')->findAll()->getData() as $r)
		{
			$booked[] = array('from' => $r['date_from'], 'to' => $r['date_to'], 'status' => $r['status'] === 'Pending' ? 'pending' : 'booked');
		}
		foreach (pjLimitModel::factory()->select('t1.date_from, t1.date_to')->where('t1.calendar_id', (int) $cal['id'])->where('t1.blocked', 'T')
			->where('t1.date_to >=', date('Y-m-d'))->findAll()->getData() as $r)
		{
			$booked[] = array('from' => $r['date_from'], 'to' => date('Y-m-d', strtotime($r['date_to'] . ' +1 day')), 'status' => 'booked');
		}
		// first day of the week of the calendar (Company options > General: 0 = Sunday, 1 = Monday ...)
		$settings = self::companySettings($cid);
		$week_start = (string) $settings['week_start'];
		if (strpos($week_start, '::') !== false)
		{
			$week_start = substr($week_start, strrpos($week_start, '::') + 2);
		}
		$week_start = max(0, min(6, (int) $week_start));
		$labels = array();
		foreach (self::$bookingLabels as $k)
		{
			$labels[$k] = self::lbl($k);
		}
		$price_types = __($opt['o_price_based_on'] === 'days' ? 'day_price_types' : 'price_types', true);
		$this->respond(200, 'OK', array(
			'slug' => $slug,
			// company page link (/stays/<company>): the website checks it on a company's own domain
			'company_slug' => self::companyLink($cid, isset($company['name']) ? $company['name'] : ''),
			'property' => array(
				'name'           => (string) $t['name'],
				'photo'          => !empty($photos) ? $photos[0]['url'] : null,
				'city'           => isset($t['address_city']) ? (string) $t['address_city'] : '',
				'state'          => (string) $cal['address_state'],
				'country'        => self::countryNameEn($cal['address_country'], $cid),
				'price'          => $price['price'],
				'price_based_on' => $opt['o_price_based_on'] === 'days' ? 'days' : 'nights',
				'currency'       => $price['currency'],
				'currency_sign'  => self::currencySign($price['currency']),
				'price_format'   => self::priceFormat($cid),
				'theme'          => self::companyTheme($cid),
				'branding'       => self::showBranding($cid),
				'host'           => array('name' => (string) $company['name'], 'phone' => !empty($company['phone']) ? (string) $company['phone'] : '',
					'logo' => !empty($company['logo']) ? PJ_INSTALL_URL . ltrim($company['logo'], '/') : null),
			),
			'guests' => array(
				'adults'     => array('show' => (int) $opt['o_bf_adults'] !== 1, 'required' => (int) $opt['o_bf_adults'] === 3, 'max' => max(1, (int) $opt['o_bf_adults_max'])),
				'children'   => array('show' => (int) $opt['o_bf_children'] !== 1, 'required' => (int) $opt['o_bf_children'] === 3, 'max' => max(0, (int) $opt['o_bf_children_max'])),
				'min_people' => !empty($opt['o_min_people']) ? (int) $opt['o_min_people'] : null,
				'max_people' => !empty($opt['o_max_people']) ? (int) $opt['o_max_people'] : null,
			),
			'fields'            => $fields,
			'countries'         => $countries,
			'extras'            => self::bookingExtras($cal['id'], $lid),
			'extra_price_types' => is_array($price_types) ? $price_types : new stdClass(),
			// the coupon field is always offered; a code only works when it is a coupon of this property
			// (and the company may use coupons: pjAppController::couponsActive, checked in the price calculation)
			'coupons'           => true,
			'payment_methods'   => array_values(self::bookingPaymentMethods($cal['id'], $cid, $lid, isset($opt['o_currency']) ? $opt['o_currency'] : '')),
			'terms'             => $terms,
			'booked'            => $booked,
			'week_start'        => $week_start,
			'labels'            => $labels,
		));
	}

	/** Texts of the property website page (update_2026_11_03_18_00_00.sql). */
	private static $siteLabels = array(
		'site_nav_sections', 'site_nav_overview', 'site_nav_amenities', 'site_nav_location', 'site_nav_faq', 'site_hosted_by',
		'site_see_all_stays', 'site_book_direct', 'site_bedroom', 'site_bedrooms', 'site_bathroom', 'site_bathrooms',
		'site_up_to_guest', 'site_up_to_guests', 'site_entire', 'site_type_villa', 'site_type_homestay', 'site_type_apartment',
		'site_type_cottage', 'site_type_resort', 'site_about', 'site_amenities', 'site_show_all_amenities', 'site_show_less',
		'site_availability', 'site_good_to_know', 'site_guests', 'site_up_to', 'site_prices', 'site_per_night', 'site_per_day',
		'site_per_night_varies', 'site_per_day_varies', 'site_host', 'site_location', 'site_map_of', 'site_faq',
		'site_price_on_request', 'site_price_from', 'site_night', 'site_nights', 'site_day', 'site_days', 'site_dates_free',
		'site_dates_taken', 'site_whatsapp', 'site_new_tab', 'site_whatsapp_text', 'site_whatsapp_dates', 'site_guest_word',
		'site_guests_word', 'site_total', 'site_estimate', 'site_book_direct_owner', 'site_rights', 'site_photos_title',
		'site_photo_prev', 'site_photo_next', 'site_photo_alt', 'site_open_photo', 'site_view_all_photos', 'site_cal_prev',
		'site_cal_next', 'site_cal_weekdays', 'site_cal_your_dates', 'site_cal_taken', 'site_cal_available', 'site_cal_pick_in',
		'site_cal_pick_out', 'site_cal_summary'
	);

	/** key => text of these labels (the key itself when a text is missing). */
	private static function labelList($keys)
	{
		$out = array();
		foreach ($keys as $k)
		{
			$out[$k] = self::lbl($k);
		}
		return $out;
	}

	/** Texts of the booking pages (back office > Languages); the update adds the ones the old booking form had not. */
	private static $bookingLabels = array(
		'bf_adults', 'bf_children', 'bf_start_date', 'bf_end_date', 'bf_night', 'bf_nights', 'bf_day', 'bf_days', 'bf_price',
		'bf_extra_price', 'bf_discount_amount', 'bf_tax', 'bf_tourist_tax', 'bf_security', 'bf_total', 'bf_deposit', 'bf_payment',
		'bf_booking_summary', 'bf_bank_account', 'front_extras', 'front_apply_promo_code', 'front_enter_promo_code', 'front_invalid_promo_code',
		'front_discount', 'front_paid_on_arrival',
		'book_step_stay', 'book_step_extras', 'book_step_details', 'book_step_payment',
		'book_title_stay', 'book_desc_stay', 'book_title_extras', 'book_desc_extras', 'book_title_details', 'book_desc_details',
		'book_title_payment', 'book_desc_payment', 'book_continue', 'book_continue_payment', 'book_back', 'book_back_to_property',
		'book_available', 'book_checking', 'book_confirm_pay', 'book_confirm', 'book_pay_now', 'book_total', 'book_coupon',
		'book_coupon_applied', 'book_add', 'book_remove', 'book_included', 'book_err_required', 'book_err_email', 'book_err_terms',
		'book_err_payment', 'book_choose_dates', 'book_confirmed_title', 'book_pending_title', 'book_confirmed_desc', 'book_pending_desc',
		'book_reference', 'book_paid_now', 'book_message_host', 'book_payment_failed', 'book_redirecting', 'book_guests',
		'book_no_payment_needed', 'book_adults_hint', 'book_children_hint', 'book_extras_none', 'book_dates', 'book_extras_summary',
		'book_payment_method', 'book_secure', 'book_book_now', 'book_add_date', 'book_check_in', 'book_check_out', 'book_not_found',
		'book_bank_transfer_info', 'book_pay_on_arrival', 'book_security_refundable', 'book_on_arrival',
		'book_cal_available', 'book_cal_pending', 'book_cal_booked', 'book_cal_past', 'book_cal_selected', 'book_cal_pick_in',
		'book_cal_pick_out', 'book_cal_taken', 'book_cal_prev', 'book_cal_next', 'book_cal_clear',
	);

	/** Price of dates, guests, extras and coupon. slug*, date_from*, date_to*, adults, children, extras (JSON {id: qty}), promo_code */
	public function pjActionBookingQuote()
	{
		$this->setAjax(true);
		list($cal, $t, $cid, $opt) = $this->bookingProperty();
		list($v, $error) = $this->bookingRequest($cal, $opt);
		if ($error !== '')
		{
			$this->respond(422, $error, array('available' => false), array('dates' => $error));
		}
		$p = $this->bookingPrice($cal, $opt, $v, $cid);
		unset($p['raw']);
		$p['available'] = true;
		$p['promo_code'] = $v['promo_code'];
		$p['promo_error'] = $v['promo_code'] !== '' && !$p['promo_valid'] ? self::lbl('front_invalid_promo_code') : '';
		$p['extras'] = (object) $v['qty'];
		$this->respond(200, 'OK', $p);
	}

	/**
	 * Saves the booking: the quote fields + the booking form fields (c_*), payment_method, terms (1).
	 * Data: reference, status, payment {method, online, pay_url, bank_account}, price
	 */
	public function pjActionBookingCreate()
	{
		$this->setAjax(true);
		$this->requirePost();
		list($cal, $t, $cid, $opt, $slug) = $this->bookingProperty();
		$ip = $this->throttleIp();
		$this->throttleCheck('booking', $ip, self::lbl('book_err_too_many'));
		if (pjBase::isBlockedIp($ip, $opt))
		{
			$this->respond(403, self::lbl('front_ip_address_blocked'));
		}
		if (class_exists('pjEntitlement') && pjEntitlement::frontSuspended($cid))
		{
			$this->respond(402, self::lbl('subFrontSuspended'));
		}
		list($v, $error) = $this->bookingRequest($cal, $opt);
		if ($error !== '')
		{
			$this->respond(422, $error, array(), array('dates' => $error));
		}
		$errors = array();
		$data = array();
		foreach (self::$bookingFields as $name => $f)
		{
			$mode = isset($opt[$f[0]]) ? (int) $opt[$f[0]] : 1;
			if ($mode !== 2 && $mode !== 3)
			{
				continue;
			}
			$value = trim((string) $this->param($name));
			if ($value === '')
			{
				if ($mode === 3)
				{
					$errors[$name] = self::lbl('book_err_required');
				}
				continue;
			}
			if ($name === 'c_email' && !pjValidation::pjActionEmail($value))
			{
				$errors[$name] = self::lbl('book_err_email');
				continue;
			}
			if ($name === 'c_country' && (int) pjBaseCountryModel::factory()->where('t1.id', (int) $value)->findCount()->getData() === 0)
			{
				$errors[$name] = self::lbl('book_err_required');
				continue;
			}
			$data[$name] = $name === 'c_country' ? (int) $value : self::cut($value, $name === 'c_notes' ? 2000 : 255);
		}
		if ((int) $opt['o_bf_terms'] === 3 && $this->param('terms') !== '1')
		{
			$errors['terms'] = self::lbl('book_err_terms');
		}
		$lid = self::websiteLocale($cid);
		$price = $this->bookingPrice($cal, $opt, $v, $cid);
		$methods = self::bookingPaymentMethods($cal['id'], $cid, $lid, isset($opt['o_currency']) ? $opt['o_currency'] : '');
		$method = (string) $this->param('payment_method');
		if ($price['deposit'] > 0 && !isset($methods[$method]))
		{
			$errors['payment_method'] = self::lbl('book_err_payment');
		} elseif (!isset($methods[$method])) {
			$method = '';
		}
		if (!empty($errors))
		{
			$this->respond(422, 'Validation failed', array(), $errors);
		}
		$p = $price['raw'];
		$data = array_merge($data, array(
			'calendar_id'     => (int) $cal['id'],
			'uuid'            => pjUtil::uuid(),
			'status'          => ucfirst((string) $opt['o_status_if_not_paid']),
			'locale_id'       => $lid,
			'ip'              => $ip,
			'date_from'       => $v['date_from'],
			'date_to'         => $v['date_to'],
			'price_based_on'  => $opt['o_price_based_on'],
			'c_adults'        => $v['adults'],
			'c_children'      => $v['children'],
			'payment_method'  => $method !== '' ? $method : ':NULL',
			'amount'          => $p['amount'],
			'extra_price'     => $p['extra_price'],
			'deposit'         => $p['deposit'],
			'tax'             => $p['tax'],
			'tourist_tax'     => $p['tourist_tax'],
			'security'        => $p['security'],
			'promo_amount'    => (int) $p['promo_valid'] === 1 ? $p['promo_amount'] : ':NULL',
			'promo_code'      => (int) $p['promo_valid'] === 1 ? $p['promo_code'] : ':NULL',
			'promo_type'      => (int) $p['promo_valid'] === 1 ? $p['promo_type'] : ':NULL',
			'discount_amount' => (int) $p['discount_valid'] === 1 ? $p['discount_amount'] : ':NULL',
		));
		$m = pjReservationModel::factory();
		if (!$m->validates($data))
		{
			$this->respond(422, self::lbl('book_err_dates'));
		}
		$id = (int) $m->setAttributes($data)->insert()->getInsertId();
		if ($id <= 0)
		{
			$this->respond(500, self::lbl('book_err_not_saved'));
		}
		$data['id'] = $id;
		self::throttleHit('booking', $ip);
		pjAppController::saveReservationIcal($data);
		foreach ($v['extras'] as $extra_id => $on)
		{
			pjReservationExtraModel::factory()->setAttributes(array('reservation_id' => $id, 'extra_id' => (int) $extra_id, 'qty' => (int) $v['qty'][$extra_id]))->insert();
		}
		// the booking e-mails / SMS of the property and the company, as with the old booking form
		if (!class_exists('pjFront'))
		{
			require_once dirname(__FILE__) . '/pjFront.controller.php';
		}
		// sent after the answer: the guest does not wait for the mail / SMS server
		self::afterResponse(function () use ($id, $opt, $lid) {
			pjFront::pjActionConfirmSend($id, $opt, $lid, 'confirmation');
		});

		$booking = pjReservationModel::factory()->find($id)->getData();
		$payment = array('method' => $method, 'online' => $method !== '' && !empty($methods[$method]['online']), 'pay_url' => null, 'bank_account' => '');
		if ($payment['online'])
		{
			$site = $this->signupSiteUrl();
			$return = ($site !== '' ? $site : rtrim(PJ_INSTALL_URL, '/')) . self::sitePropertyPath($site, $cid, $slug) . '/book/confirmed?ref=' . $booking['uuid'] . '&key=' . self::bookingKey($booking['uuid']);
			$payment['pay_url'] = PJ_INSTALL_URL . 'index.php?controller=pjFront&action=pjActionPayBooking&cid=' . (int) $cal['id']
				. '&ref=' . rawurlencode($booking['uuid']) . '&return=' . rawurlencode($return)
				. '&hash=' . sha1($booking['uuid'] . strtotime($booking['created']) . $return . PJ_SALT);
		} elseif ($method === 'bank') {
			$payment['bank_account'] = self::bankAccount($cal['id'], $lid);
		}
		unset($price['raw']);
		$this->respond(200, 'OK', array('reference' => $booking['uuid'], 'key' => self::bookingKey($booking['uuid']), 'status' => $booking['status'], 'payment' => $payment, 'price' => $price));
	}

	/** A booking made on the website (confirmation page). slug*, ref* (booking reference) */
	/**
	 * Secret key of a booking for its confirmation page: the reference alone (2 letters + a time) can be
	 * guessed, so the page also needs this key (sent with the booking and in the payment return link).
	 */
	private static function bookingKey($uuid)
	{
		return substr(hash_hmac('sha256', 'booking|' . (string) $uuid, PJ_SALT), 0, 32);
	}

	public function pjActionBookingStatus()
	{
		$this->setAjax(true);
		list($cal, $t, $cid, $opt) = $this->bookingProperty();
		$ref = (string) $this->param('ref');
		$key = (string) $this->param('key');
		$b = preg_match('/^[A-Za-z0-9-]{6,64}$/', $ref) && hash_equals(self::bookingKey($ref), $key)
			? pjReservationModel::factory()->where('t1.uuid', $ref)->where('t1.calendar_id', (int) $cal['id'])->limit(1)->findAll()->getDataIndex(0)
			: array();
		if (empty($b))
		{
			$this->respond(404, self::lbl('book_not_found'));
		}
		$lid = self::websiteLocale($cid);
		$extras = array();
		foreach (pjReservationExtraModel::factory()->select(sprintf("t1.qty, COALESCE(NULLIF(t2.content, ''), (SELECT ml.content FROM `%s` AS ml WHERE ml.model='pjExtra' AND ml.foreign_id=t1.extra_id AND ml.field='name' AND ml.content <> '' ORDER BY ml.locale ASC LIMIT 1)) AS name", pjMultiLangModel::factory()->getTable()))
			->join('pjMultiLang', sprintf("t2.model='pjExtra' AND t2.foreign_id=t1.extra_id AND t2.field='name' AND t2.locale='%u'", $lid), 'left outer')
			->where('t1.reservation_id', (int) $b['id'])->findAll()->getData() as $e)
		{
			$extras[] = array('name' => (string) $e['name'], 'qty' => (int) $e['qty']);
		}
		$names = __('payment_methods', true);
		$titles = pjObject::getPlugin('pjPayments') !== NULL ? pjPayments::getPaymentTitles($cal['id'], $lid) : array();
		$method = (string) $b['payment_method'];
		$total = (float) $b['amount'] + (float) $b['extra_price'] - (float) $b['promo_amount'] - (float) $b['discount_amount'] + (float) $b['tax'] + (float) $b['tourist_tax'];
		$this->respond(200, 'OK', array(
			'reference'      => $b['uuid'],
			'status'         => $b['status'],
			'paid'           => !empty($b['txn_id']),
			'date_from'      => $b['date_from'],
			'date_to'        => $b['date_to'],
			'adults'         => (int) $b['c_adults'],
			'children'       => (int) $b['c_children'],
			'email'          => (string) $b['c_email'],
			'extras'         => $extras,
			'payment_method' => $method,
			'payment_title'  => !empty($titles[$method]) ? $titles[$method] : (isset($names[$method]) ? $names[$method] : $method),
			'bank_account'   => $method === 'bank' ? self::bankAccount($cal['id'], $lid) : '',
			'total'          => round(max(0, $total), 2),
			'deposit'        => round((float) $b['deposit'], 2),
			'security'       => round((float) $b['security'], 2),
		));
	}
}
?>
