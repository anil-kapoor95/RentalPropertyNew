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

	/** Photos one property may have during the sign-up. */
	const MAX_PHOTOS = 20;

	/** AI suggestions one sign-up may generate (every tone change or rewrite is one). */
	const MAX_AI_GENERATIONS = 30;

	/** Writing tones of the AI suggestion (step 5). The first one is the default. */
	private static $tones = array(
		'warm'    => 'Warm: friendly and welcoming, as if the host were talking to the guest. Simple, natural words.',
		'premium' => 'Premium: polished, refined and understated, like a boutique hotel. Elegant but not flowery, no cliches.',
		'simple'  => 'Simple: short, plain and factual sentences that are easy to scan. No marketing words.',
	);

	/** Property types the front end offers (saved as the property text "property_type"). */
	private static $propertyTypes = array('villa', 'homestay', 'apartment', 'cottage', 'resort');

	/** Amenities one property may have, and the longest amenity name. */
	const MAX_AMENITIES = 100;
	const MAX_AMENITY_LENGTH = 80;

	/** Feature category of amenities created by the sign-up (Options > Features: 2 = "Property features"). */
	const AMENITY_CATEGORY = 2;

	/** Questions guests ask (step 5): most saved per property. */
	const MAX_FAQS = 8;

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
		$signup = self::normalizeStep($signup);
		$signup['user'] = $user;
		return $signup;
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
		return array_slice($faqs, 0, self::MAX_FAQS);
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
				$property['property_type'] = isset($t['property_type']) && in_array($t['property_type'], self::$propertyTypes, true) ? $t['property_type'] : null;
				$property['amenities'] = self::propertyAmenities($cal['id'], $signup['company_id']);
				$property['max_guests'] = isset($opt['o_max_people']) && (int) $opt['o_max_people'] > 0 ? (int) $opt['o_max_people'] : null;
				$photos = self::photoList($cal['id']);
				// stored as HTML for the back-office editor; the form gets plain text
				$description = isset($t['description']) && $t['description'] !== '' ? self::htmlToText($t['description']) : null;
				$headline = isset($t['headline']) && $t['headline'] !== '' ? $t['headline'] : null;
				$faqs = isset($t['faqs']) ? self::decodeFaqs($t['faqs']) : array();
				$tone = isset($t['content_tone']) && isset(self::$tones[$t['content_tone']]) ? $t['content_tone'] : null;
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
			'tones' => array_keys(self::$tones),
			// currency of the prices (Options of the account); the price itself is property.price
			'currency' => self::accountCurrency(!empty($signup['company_id']) ? (int) $signup['company_id'] : 0),
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
		$this->respond(200, 'Account created. Please continue with your company details.',
			array_merge(array('signup_token' => $token, 'resumed' => false), $this->signupState(array('id' => $signup_id))));
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
			if (self::tooLong($name, self::MAX_AMENITY_LENGTH))
			{
				return array(array(), sprintf('Amenity names can have at most %u characters', self::MAX_AMENITY_LENGTH));
			}
			$names[$key] = $name;
		}
		if (count($names) > self::MAX_AMENITIES)
		{
			return array(array(), sprintf('A property can have at most %u amenities', self::MAX_AMENITIES));
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
					'category_id' => self::AMENITY_CATEGORY,
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
			'name'       => self::cut($v['name'], 255),
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
				'name'           => self::cut($v['name'], 255),
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

		if ($amenities !== null)
		{
			self::saveAmenities($calendar_id, $company_id, $amenities);
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
		if ((int) $signup['ai_generations'] >= self::MAX_AI_GENERATIONS)
		{
			$this->respond(429, 'You have used all AI suggestions. Please edit the last text or write your own.');
		}
		$tone = strtolower($this->param('tone'));
		if ($tone === '')
		{
			$tone = key(self::$tones);
		}
		$field = strtolower($this->param('field'));
		if ($field === '')
		{
			$field = 'all';
		}
		$highlights = $this->param('highlights');
		$current = $this->param('current');
		$errors = array();
		if (!isset(self::$tones[$tone]))
		{
			$errors['tone'] = 'Choose one of: ' . implode(', ', array_keys(self::$tones));
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

		$facts = array_filter(array(
			'Property name' => isset($t['name']) ? $t['name'] : '',
			'Location' => implode(', ', array_filter(array(isset($t['address_city']) ? $t['address_city'] : '', $calendar['address_state'], $country))),
			'Property type' => isset($t['property_type']) && in_array($t['property_type'], self::$propertyTypes, true) ? $t['property_type'] : '',
			'Bedrooms' => $calendar['bedrooms'],
			'Bathrooms' => $calendar['bathrooms'],
			'Amenities' => implode(', ', array_slice(self::propertyAmenities($calendar['id'], $company_id), 0, 40)),
			'Host' => !empty($company['name']) ? $company['name'] : '',
			'Owner notes' => $highlights,
		), function ($x) { return $x !== null && $x !== ''; });
		$lines = array();
		foreach ($facts as $k => $val)
		{
			$lines[] = '- ' . $k . ': ' . str_replace(array("\r", "\n"), ' ', (string) $val);
		}

		$keys = array(
			'headline'    => '"headline": the page headline, one line, 4 to 10 words, at most 70 characters, no quotes, no full stop at the end.',
			'description' => '"description": 100 to 160 words in 2 or 3 short paragraphs separated by a blank line (\n\n). Plain text.',
			'faqs'        => '"faqs": an array of exactly 4 objects {"question": "...", "answer": "..."} with questions guests often ask before booking a place like this (check-in, guests, parking, things nearby...). Answers: 1 or 2 short sentences, based only on the facts; when the facts do not say, answer that guests can ask the host before booking.',
		);
		$wanted = $field === 'all' ? array_keys($keys) : array($field);
		$spec = array();
		foreach ($wanted as $k)
		{
			$spec[] = '- ' . $keys[$k];
		}
		$prompt = "You write the guest page of a holiday rental property on its own booking website.\n"
			. "LANGUAGE: write every word in English only, whatever the language of the facts. If a fact is written in another language or script, translate it (or transliterate a name) into English.\n"
			. "TONE: " . self::$tones[$tone] . "\n"
			. "Use only the facts below. Do not invent amenities, distances, ratings, awards or prices. No emojis, no markdown.\n"
			. "Facts:\n" . implode("\n", $lines) . "\n"
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
		$out['ai_generations_left'] = max(0, self::MAX_AI_GENERATIONS - (int) $signup['ai_generations'] - 1);
		$this->respond(200, 'Text generated. Check it and edit it before you publish.', $out);
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
				$out['faqs'] = array_slice($faqs, 0, self::MAX_FAQS);
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
		if ($tone !== '' && !isset(self::$tones[$tone]))
		{
			$errors['tone'] = 'Choose one of: ' . implode(', ', array_keys(self::$tones));
		}
		$faqs = array();
		if ($faqs_raw !== '' && $faqs_raw !== array())
		{
			if (!is_array($faqs_list))
			{
				$errors['faqs'] = 'Invalid questions';
			} elseif (count($faqs_list) > self::MAX_FAQS) {
				$errors['faqs'] = 'Maximum ' . self::MAX_FAQS . ' questions allowed';
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
	 * Data: themes[] {id, name, colors, free, locked}, default_id (the free theme), free_id, allowed (paid themes unlocked)
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
		$this->respond(200, 'OK', array('themes' => $themes, 'default_id' => $free_id, 'free_id' => $free_id, 'allowed' => $allowed));
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
	 * Gateways the front end can connect: payment_method => plugin and key formats.
	 * Saved in the same plugin_payment_options fields as Properties > Payments; card details never reach this API.
	 */
	private static $paymentGateways = array(
		'razorpay' => array(
			'plugin'  => 'pjRazorpay',
			'name'    => 'Razorpay',
			'public'  => '/^rzp_(live|test)_[A-Za-z0-9]{8,40}$/',
			'secret'  => '/^[A-Za-z0-9]{16,64}$/',
			'public_hint' => 'rzp_%s_',
		),
		'stripe' => array(
			'plugin'  => 'pjStripe',
			'name'    => 'Stripe',
			'public'  => '/^pk_(live|test)_[A-Za-z0-9]{10,247}$/',
			'secret'  => '/^(sk|rk)_(live|test)_[A-Za-z0-9]{10,247}$/',
			'public_hint' => 'pk_%s_',
		),
	);

	/** Saved settings of one gateway for the API answer (secret keys are only reported as saved / not saved). */
	private static function paymentMethodState($calendar_id, $payment_method)
	{
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
		return array(
			'payment_method'      => $payment_method,
			'is_active'           => !empty($row) && (int) $row['is_active'] === 1,
			'is_test_mode'        => !empty($row) && (int) $row['is_test_mode'] === 1,
			'label'               => (string) $label,
			'public_key'          => !empty($row['public_key']) ? $row['public_key'] : '',
			'test_public_key'     => !empty($row['test_public_key']) ? $row['test_public_key'] : '',
			'has_secret_key'      => !empty($row['private_key']),
			'has_test_secret_key' => !empty($row['test_private_key']),
		);
	}

	/**
	 * Saved settings of every gateway the front end can connect, to fill in its forms
	 * (secret keys are only reported as saved / not saved).
	 */
	public function pjActionGetPaymentMethods()
	{
		$this->setAjax(true);
		$this->requirePost();
		$this->requireSignupTable();

		$signup = $this->signupFromToken();
		$calendar = $this->signupProperty($signup);
		$calendar_id = (int) $calendar['id'];

		$methods = array();
		if (pjObject::getPlugin('pjPayments') !== NULL && class_exists('pjPaymentOptionModel'))
		{
			foreach (self::$paymentGateways as $payment_method => $gateway)
			{
				if (pjObject::getPlugin($gateway['plugin']) !== NULL)
				{
					$methods[$payment_method] = self::paymentMethodState($calendar_id, $payment_method);
				}
			}
		}
		$this->respond(200, 'OK', array(
			'payment_methods' => $methods,
			'active_gateways' => class_exists('pjEntitlement') ? pjEntitlement::activeGateways($calendar_id) : array(),
		));
	}

	/**
	 * Saves one online payment gateway of the sign-up property (Properties > Payments, plugin_payment_options).
	 *
	 * POST signup_token, payment_method (razorpay|stripe), is_active (0|1), is_test_mode (0|1),
	 *      label (optional), public_key, secret_key (optional when one is already saved for that mode).
	 * The keys are saved for the mode sent: test mode -> test_public_key / test_private_key.
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
		if (!isset(self::$paymentGateways[$payment_method]))
		{
			$this->respond(422, 'Validation failed', array(), array('payment_method' => 'Unknown payment method'));
		}
		$gateway = self::$paymentGateways[$payment_method];
		if (pjObject::getPlugin('pjPayments') === NULL || pjObject::getPlugin($gateway['plugin']) === NULL || !class_exists('pjPaymentOptionModel'))
		{
			$this->respond(503, sprintf('%s payments are not available on this system.', $gateway['name']));
		}

		$is_active = $this->param('is_active') === '1';
		$is_test_mode = $this->param('is_test_mode') === '1';
		$mode = $is_test_mode ? 'test' : 'live';
		$public_key = $this->param('public_key');
		$secret_key = $this->param('secret_key');
		$label = $this->has('label') ? trim(strip_tags($this->param('label'))) : '';

		$current = pjPaymentOptionModel::factory()->getOptions($calendar_id, $payment_method);
		$public_field = $is_test_mode ? 'test_public_key' : 'public_key';
		$secret_field = $is_test_mode ? 'test_private_key' : 'private_key';
		$has_saved_secret = !empty($current[$secret_field]);

		$errors = array();
		if ($public_key === '')
		{
			if ($is_active)
			{
				$errors['public_key'] = sprintf('Enter your %s %s', $gateway['name'], $payment_method === 'stripe' ? 'public key' : 'key ID');
			}
		} elseif (preg_match($gateway['secret'], $public_key)) {
			// A secret key pasted into the public field must not be saved where it is shown to guests.
			$errors['public_key'] = 'This is a secret key. Enter it in the secret key field.';
		} elseif (!preg_match($gateway['public'], $public_key, $m)) {
			$errors['public_key'] = sprintf('This is not a valid %s key. It should start with %s', $gateway['name'], sprintf($gateway['public_hint'], $mode));
		} elseif ($m[1] !== $mode) {
			$errors['public_key'] = sprintf('Test mode is %s, so use your %s key (%s...)', $is_test_mode ? 'on' : 'off', $mode, sprintf($gateway['public_hint'], $mode));
		}
		if ($secret_key === '')
		{
			if ($is_active && !$has_saved_secret)
			{
				$errors['secret_key'] = sprintf('Enter your %s secret key', $gateway['name']);
			}
		} elseif (preg_match($gateway['public'], $secret_key)) {
			$errors['secret_key'] = 'This is a public key. Enter your secret key here.';
		} elseif (ctype_digit($secret_key) || !preg_match($gateway['secret'], $secret_key, $m)) {
			// (digits only: never a gateway key, possibly a card number: not saved)
			$errors['secret_key'] = sprintf('This is not a valid %s secret key', $gateway['name']);
		} elseif (isset($m[2]) && $m[2] !== $mode) {
			$errors['secret_key'] = sprintf('Test mode is %s, so use your %s secret key', $is_test_mode ? 'on' : 'off', $mode);
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
		if ($is_active && class_exists('pjEntitlement'))
		{
			$max = pjEntitlement::limit($company_id, 'max_payment_gateways');
			$active = pjEntitlement::activeGateways($calendar_id);
			if ($max !== null && !in_array($payment_method, $active, true) && count($active) >= $max)
			{
				$this->respond(402, __('subGatewayLimit', true), array('active_gateways' => $active));
			}
		}

		$data = array(
			'is_active'    => $is_active ? 1 : 0,
			'is_test_mode' => $is_test_mode ? 1 : 0,
		);
		if ($public_key !== '')
		{
			$data[$public_field] = $public_key;
		}
		if ($secret_key !== '')
		{
			$data[$secret_field] = $secret_key;
		}
		pjPaymentOptionModel::factory()->saveOptions(array($payment_method => $data), $calendar_id);

		if ($label !== '')
		{
			$i18n = array();
			foreach (self::companyLocales($company_id) as $lid)
			{
				$i18n[$lid] = array($payment_method => $label);
			}
			pjMultiLangModel::factory()->updateMultiLang($i18n, $calendar_id, 'pjPayment', 'data');
		}

		$state = self::paymentMethodState($calendar_id, $payment_method);
		$state['active_gateways'] = class_exists('pjEntitlement') ? pjEntitlement::activeGateways($calendar_id) : array();
		$this->respond(200, sprintf('%s settings saved.', $gateway['name']), $state);
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
		$this->respond(200, 'OK', array('countries' => $countries));
	}
}
?>
