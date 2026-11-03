<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Links of the hosts' websites.
 *
 * - Every property and company has its own stored, unique link ("slug"): <website>/casanova and
 *   <website>/stays/anil-kapoor. Two properties with the same name get "casanova" and "casanova-2".
 *   A link is made from the name once and then kept (renaming the property does not break shared links).
 * - A company may use its own domain (plan feature "custom_domain"). The domain is added to the website's
 *   Vercel project with the Vercel API (System Options > API Keys) and checked there; active domains are
 *   written to a list file that api.php reads to let these websites call the API.
 */
class pjSiteLinks
{
	/** First parts of the website's own pages: never a property link. */
	public static $reserved = array(
		'about', 'api', 'blog', 'book', 'contact', 'faq', 'help', 'how-it-works', 'login', 'logout', 'pricing',
		'privacy', 'properties', 'property', 'setup', 'stays', 'terms', 'themes', 'admin', 'dashboard', 'static',
		'assets', 'images', 'img', 'favicon-ico', 'robots-txt', 'sitemap-xml', 'icon-svg', 'not-found', 'www',
		'app', 'account', 'signup', 'register', 'search', 'checkout', 'pay', 'payment', 'confirmed', 'new',
	);

	/** Vercel DNS targets (https://vercel.com/docs/domains). */
	const VERCEL_CNAME = 'cname.vercel-dns.com';
	const VERCEL_A = '76.76.21.21';

	private static $columnsReady = null;

	/** Are the link columns there (update_2026_11_03_14_00_00.sql)? */
	public static function isReady()
	{
		if (self::$columnsReady === null)
		{
			$row = pjCalendarModel::factory()->select('t1.*')->limit(1)->findAll()->getDataIndex(0);
			$company = pjCompanyModel::factory()->select('t1.*')->limit(1)->findAll()->getDataIndex(0);
			self::$columnsReady = (empty($row) || array_key_exists('slug', $row)) && (empty($company) || array_key_exists('custom_domain', $company));
			if (self::$columnsReady)
			{
				self::refreshDomainList();
			}
		}
		return self::$columnsReady;
	}

	/** "Green Casa, Goa!" -> "green-casa-goa" (same rule as slugify() of the website). */
	public static function slugOf($name)
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
		return trim(substr($s, 0, 60), '-');
	}

	/** Name of a property in any of its languages (the first one that has it). */
	public static function calendarName($calendar_id)
	{
		foreach (pjMultiLangModel::factory()->select('t1.content')->where('t1.model', 'pjCalendar')->where('t1.foreign_id', (int) $calendar_id)
			->where('t1.field', 'name')->orderBy('t1.locale ASC')->findAll()->getData() as $r)
		{
			if (trim((string) $r['content']) !== '')
			{
				return (string) $r['content'];
			}
		}
		return '';
	}

	/** Is this link free for the given table (property links and company links are separate)? */
	private static function isFree($table, $slug, $except_id)
	{
		if ($slug === '' || ($table === 'calendar' && in_array($slug, self::$reserved, true)))
		{
			return false;
		}
		$m = $table === 'calendar' ? pjCalendarModel::factory() : pjCompanyModel::factory();
		return (int) $m->where('t1.slug', $slug)->where('t1.id !=', (int) $except_id)->findCount()->getData() === 0;
	}

	/** The free link for a name: "casanova", else "casanova-2", "casanova-3" ... */
	private static function freeSlug($table, $name, $except_id, $fallback)
	{
		$base = self::slugOf($name);
		if ($base === '')
		{
			$base = $fallback;
		}
		$base = substr($base, 0, 70);
		if (self::isFree($table, $base, $except_id))
		{
			return $base;
		}
		for ($i = 2; $i < 1000; $i++)
		{
			if (self::isFree($table, $base . '-' . $i, $except_id))
			{
				return $base . '-' . $i;
			}
		}
		return $base . '-' . (int) $except_id;
	}

	/** Stored link of a property; made from its name the first time. */
	public static function calendarSlug($calendar_id, $name = null)
	{
		$calendar_id = (int) $calendar_id;
		if ($calendar_id <= 0 || !self::isReady())
		{
			return self::slugOf($name !== null ? $name : self::calendarName($calendar_id));
		}
		$cal = pjCalendarModel::factory()->find($calendar_id)->getData();
		if (!empty($cal['slug']))
		{
			return (string) $cal['slug'];
		}
		$slug = self::freeSlug('calendar', $name !== null ? $name : self::calendarName($calendar_id), $calendar_id, 'property-' . $calendar_id);
		pjCalendarModel::factory()->set('id', $calendar_id)->modify(array('slug' => $slug));
		return $slug;
	}

	/** New link from the (new) name: used while the property is being set up and not public yet. */
	public static function renameCalendarSlug($calendar_id, $name)
	{
		if ((int) $calendar_id <= 0 || !self::isReady())
		{
			return self::slugOf($name);
		}
		$slug = self::freeSlug('calendar', $name, (int) $calendar_id, 'property-' . (int) $calendar_id);
		pjCalendarModel::factory()->set('id', (int) $calendar_id)->modify(array('slug' => $slug));
		return $slug;
	}

	/** Stored link of a company page (/stays/<slug>); made from its name the first time. */
	public static function companySlug($company_id, $name = null)
	{
		$company_id = (int) $company_id;
		if ($company_id <= 0 || !self::isReady())
		{
			return self::slugOf((string) $name);
		}
		$c = pjCompanyModel::factory()->find($company_id)->getData();
		if (!empty($c['slug']))
		{
			return (string) $c['slug'];
		}
		$slug = self::freeSlug('company', $name !== null ? $name : (isset($c['name']) ? $c['name'] : ''), $company_id, 'host-' . $company_id);
		pjCompanyModel::factory()->set('id', $company_id)->modify(array('slug' => $slug));
		return $slug;
	}

	/** Gives a link to every property / company that has none yet (oldest first, so existing links keep their name). */
	public static function ensureAll()
	{
		if (!self::isReady())
		{
			return;
		}
		foreach (pjCalendarModel::factory()->select('t1.id')->where("(t1.slug IS NULL OR t1.slug = '')")->orderBy('t1.id ASC')->findAll()->getData() as $c)
		{
			self::calendarSlug($c['id']);
		}
		foreach (pjCompanyModel::factory()->select('t1.id, t1.name')->where("(t1.slug IS NULL OR t1.slug = '')")->orderBy('t1.id ASC')->findAll()->getData() as $c)
		{
			self::companySlug($c['id'], $c['name']);
		}
	}

	/* ------------------------------------------------------------------
	 * Custom domains (Vercel)
	 * ------------------------------------------------------------------ */

	/** "https://WWW.MyVilla.com/" -> "www.myvilla.com"; '' when not a valid domain. */
	public static function normalizeDomain($value)
	{
		$d = strtolower(trim((string) $value));
		$d = preg_replace('~^[a-z]+://~', '', $d);
		$d = preg_replace('~[/?#].*$~', '', $d);
		$d = rtrim($d, '.');
		if (strlen($d) > 190 || !preg_match('/^(?=.{4,190}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $d))
		{
			return '';
		}
		return $d;
	}

	/** Platform option (System Options > API Keys). */
	public static function option($key, $default = '')
	{
		$row = pjBaseOptionModel::factory()->where('t1.foreign_id', 1)->where('t1.key', $key)->limit(1)->findAll()->getDataIndex(0);
		return !empty($row) && trim((string) $row['value']) !== '' ? trim((string) $row['value']) : $default;
	}

	/** Address of the guest websites, e.g. "https://www.stayowned.com" (no trailing slash). */
	public static function websiteUrl()
	{
		return rtrim(self::option('o_website_url', 'http://localhost:3000'), '/');
	}

	public static function vercelConfigured()
	{
		return self::option('o_vercel_token') !== '' && self::option('o_vercel_project_id') !== '';
	}

	/** DNS record the host has to add: CNAME for a subdomain (www.), A for the bare domain. */
	public static function dnsRecord($domain)
	{
		$parts = explode('.', $domain);
		$apex = count($parts) <= 2;
		return $apex
			? array('type' => 'A', 'name' => '@', 'value' => self::VERCEL_A)
			: array('type' => 'CNAME', 'name' => $parts[0], 'value' => self::VERCEL_CNAME);
	}

	/** Vercel REST call: array(http_code, decoded body). */
	private static function vercel($method, $path, $body = null)
	{
		$team = self::option('o_vercel_team_id');
		$url = 'https://api.vercel.com' . $path . ($team !== '' ? (strpos($path, '?') === false ? '?' : '&') . 'teamId=' . rawurlencode($team) : '');
		$ch = curl_init($url);
		$headers = array('Authorization: Bearer ' . self::option('o_vercel_token'), 'Content-Type: application/json');
		curl_setopt_array($ch, array(
			CURLOPT_CUSTOMREQUEST  => $method,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT        => 15,
		));
		if ($body !== null)
		{
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
		}
		$resp = curl_exec($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		$json = is_string($resp) ? json_decode($resp, true) : null;
		return array($code, is_array($json) ? $json : array());
	}

	/** Adds the domain to the website's Vercel project (already there: fine). array(ok, message) */
	public static function vercelAdd($domain)
	{
		if (!self::vercelConfigured())
		{
			return array(false, 'vercel_not_configured');
		}
		list($code, $json) = self::vercel('POST', '/v10/projects/' . rawurlencode(self::option('o_vercel_project_id')) . '/domains', array('name' => $domain));
		if ($code >= 200 && $code < 300)
		{
			return array(true, '');
		}
		$err = isset($json['error']['code']) ? (string) $json['error']['code'] : '';
		if (in_array($err, array('domain_already_in_use_by_project', 'domain_already_exists'), true))
		{
			return array(true, '');
		}
		return array(false, isset($json['error']['message']) ? (string) $json['error']['message'] : 'Vercel error ' . $code);
	}

	/** Removes the domain from the Vercel project (when the host changes or clears it). */
	public static function vercelRemove($domain)
	{
		if (!self::vercelConfigured() || $domain === '')
		{
			return;
		}
		self::vercel('DELETE', '/v9/projects/' . rawurlencode(self::option('o_vercel_project_id')) . '/domains/' . rawurlencode($domain));
	}

	/** Status of the domain at Vercel: array(status active|pending|error, message). */
	public static function vercelCheck($domain)
	{
		if (!self::vercelConfigured())
		{
			return array('pending', 'vercel_not_configured');
		}
		$project = rawurlencode(self::option('o_vercel_project_id'));
		list($code, $json) = self::vercel('GET', '/v9/projects/' . $project . '/domains/' . rawurlencode($domain));
		if ($code === 404)
		{
			list($ok, $msg) = self::vercelAdd($domain);
			if (!$ok)
			{
				return array('error', $msg);
			}
			list($code, $json) = self::vercel('GET', '/v9/projects/' . $project . '/domains/' . rawurlencode($domain));
		}
		if ($code < 200 || $code >= 300)
		{
			return array('error', isset($json['error']['message']) ? (string) $json['error']['message'] : 'Vercel error ' . $code);
		}
		if (empty($json['verified']))
		{
			return array('pending', 'Vercel is waiting for the domain to be verified.');
		}
		list($code2, $config) = self::vercel('GET', '/v6/domains/' . rawurlencode($domain) . '/config');
		if ($code2 >= 200 && $code2 < 300 && !empty($config['misconfigured']))
		{
			return array('pending', 'The DNS record is not there yet (it can take up to a few hours).');
		}
		return array('active', '');
	}

	/** Saves a company's domain (normalized, '' clears it) and checks it. array(ok, error label key) */
	public static function saveDomain($company_id, $value)
	{
		$company = pjCompanyModel::factory()->find((int) $company_id)->getData();
		if (empty($company))
		{
			return array(false, 'lblSiteLinkInvalid');
		}
		$old = isset($company['custom_domain']) ? (string) $company['custom_domain'] : '';
		$domain = trim((string) $value) === '' ? '' : self::normalizeDomain($value);
		if (trim((string) $value) !== '' && $domain === '')
		{
			return array(false, 'lblSiteLinkInvalid');
		}
		if ($domain !== '' && (int) pjCompanyModel::factory()->where('t1.custom_domain', $domain)->where('t1.id !=', (int) $company_id)->findCount()->getData() > 0)
		{
			return array(false, 'lblSiteLinkTaken');
		}
		if ($old !== '' && $old !== $domain)
		{
			self::vercelRemove($old);
		}
		$data = array('custom_domain' => $domain !== '' ? $domain : ':NULL', 'domain_status' => ':NULL', 'domain_message' => ':NULL', 'domain_checked' => ':NULL');
		pjCompanyModel::factory()->set('id', (int) $company_id)->modify($data);
		if ($domain !== '')
		{
			self::checkDomain($company_id);
		}
		self::writeDomainList();
		return array(true, '');
	}

	/** Checks the company's domain at Vercel and stores the result. */
	public static function checkDomain($company_id)
	{
		$company = pjCompanyModel::factory()->find((int) $company_id)->getData();
		$domain = !empty($company['custom_domain']) ? (string) $company['custom_domain'] : '';
		if ($domain === '')
		{
			return null;
		}
		if (self::vercelConfigured())
		{
			self::vercelAdd($domain);
		}
		list($status, $message) = self::vercelCheck($domain);
		pjCompanyModel::factory()->set('id', (int) $company_id)->modify(array(
			'domain_status'  => $status,
			'domain_message' => substr((string) $message, 0, 250),
			'domain_checked' => date('Y-m-d H:i:s'),
		));
		self::writeDomainList();
		return $status;
	}

	/** Company (and its only public property, if it has exactly one) of a website domain; null when unknown. */
	public static function companyOfDomain($host)
	{
		// local test of a domain: www.myvilla.com.localhost:3000 (browsers send *.localhost to this computer)
		$host = preg_replace('~(:\d+)$~', '', strtolower(trim((string) $host)));
		if (substr($host, -10) === '.localhost')
		{
			$host = substr($host, 0, -10);
		}
		$host = self::normalizeDomain($host);
		if ($host === '' || !self::isReady())
		{
			return null;
		}
		$alt = strpos($host, 'www.') === 0 ? substr($host, 4) : 'www.' . $host;
		foreach (array($host, $alt) as $d)
		{
			$c = pjCompanyModel::factory()->where('t1.status', 'T')->where('t1.custom_domain', $d)->limit(1)->findAll()->getDataIndex(0);
			if (!empty($c))
			{
				return $c;
			}
		}
		return null;
	}

	/** File with the active custom domains (read by api.php to let these websites call the API). */
	public static function domainListFile()
	{
		return PJ_UPLOAD_PATH . 'api_domains.json';
	}

	/** Rewrites the list when it is missing or older than 5 minutes (e.g. after the website address was changed). */
	public static function refreshDomainList()
	{
		$file = self::domainListFile();
		if (!is_file($file) || filemtime($file) < time() - 300)
		{
			self::writeDomainList();
		}
	}

	public static function writeDomainList()
	{
		if (!self::isReady())
		{
			return;
		}
		$list = array();
		// the website itself (System Options > API Keys > Website address)
		$p = parse_url(self::websiteUrl());
		if (!empty($p['scheme']) && !empty($p['host']))
		{
			$list[] = strtolower($p['scheme'] . '://' . $p['host'] . (!empty($p['port']) ? ':' . (int) $p['port'] : ''));
		}
		foreach (pjCompanyModel::factory()->select('t1.custom_domain')->where('t1.status', 'T')->where('t1.domain_status', 'active')
			->where('t1.custom_domain IS NOT NULL')->findAll()->getData() as $r)
		{
			$d = (string) $r['custom_domain'];
			$list[] = 'https://' . $d;
			$list[] = 'https://' . (strpos($d, 'www.') === 0 ? substr($d, 4) : 'www.' . $d);
		}
		@file_put_contents(self::domainListFile(), json_encode(array_values(array_unique($list))), LOCK_EX);
	}
}
?>
