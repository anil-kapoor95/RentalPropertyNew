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
 * - A company may use its own domain (plan feature "custom_domain"). The host points the domain to the website
 *   (DNS record shown in My company) and the DNS is checked here; active domains are written to a list file that
 *   api.php reads (CORS). Not tied to any hosting: the DNS targets are the website's own address (PJ_WEBSITE_URL)
 *   unless PJ_DOMAIN_CNAME_TARGET / PJ_DOMAIN_A_TARGET say otherwise (app/config/config.inc.php), and a domain
 *   behind a proxy / CDN (Cloudflare ...) is recognised by asking the website itself (/api/site-check).
 *   A web server that issues certificates on demand (Caddy, Traefik, nginx + a hook) can ask
 *   api.php?action=pjActionDomainAllowed&domain=... whether a domain belongs to a host (see DEPLOYMENT.md).
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

	/** What the website's /api/site-check answers (Next.js: src/app/api/site-check/route.ts). */
	const SITE_CHECK_APP = 'bookbyhost';

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
	 * Custom domains (any hosting)
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

	/**
	 * Address of the guest websites, e.g. "https://www.stayowned.com" (no trailing slash): PJ_WEBSITE_URL of
	 * app/config/config.inc.php (set it once when the website goes live), else http://localhost:3000.
	 */
	public static function websiteUrl()
	{
		$url = defined('PJ_WEBSITE_URL') ? trim((string) PJ_WEBSITE_URL) : '';
		return rtrim($url !== '' ? $url : 'http://localhost:3000', '/');
	}

	/**
	 * Where the hosts' domains must point: array(cname => host name, ips => IPv4 addresses).
	 *   PJ_DOMAIN_CNAME_TARGET: host name for CNAME records (default: the host of PJ_WEBSITE_URL, e.g. bookbyhost.com)
	 *   PJ_DOMAIN_A_TARGET:     IP address(es) for the bare domain, comma separated (default: the addresses the
	 *                           CNAME target resolves to)
	 * Works with any hosting: a VPS, a PaaS, a load balancer, a CDN or Vercel (set its documented targets here).
	 */
	public static function dnsTargets()
	{
		static $targets = null;
		if ($targets !== null)
		{
			return $targets;
		}
		$cname = defined('PJ_DOMAIN_CNAME_TARGET') ? strtolower(trim((string) PJ_DOMAIN_CNAME_TARGET)) : '';
		if ($cname === '')
		{
			$cname = strtolower((string) parse_url(self::websiteUrl(), PHP_URL_HOST));
		}
		$cname = rtrim($cname, '.');
		$ips = array();
		$list = defined('PJ_DOMAIN_A_TARGET') ? trim((string) PJ_DOMAIN_A_TARGET) : '';
		foreach (preg_split('/[\s,;]+/', $list) as $ip)
		{
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
			{
				$ips[] = $ip;
			}
		}
		if (empty($ips) && $cname !== '' && $cname !== 'localhost' && function_exists('gethostbynamel'))
		{
			$found = @gethostbynamel($cname);
			if (is_array($found))
			{
				$ips = array_values(array_unique($found));
			}
		}
		return $targets = array('cname' => $cname, 'ips' => $ips);
	}

	/** DNS record the host has to add: CNAME for a subdomain (www.), A for the bare domain (ALIAS when no IP is known). */
	public static function dnsRecord($domain)
	{
		$t = self::dnsTargets();
		$parts = explode('.', $domain);
		$apex = count($parts) <= 2;
		if ($apex)
		{
			return !empty($t['ips'])
				? array('type' => 'A', 'name' => '@', 'value' => $t['ips'][0])
				: array('type' => 'ALIAS / ANAME', 'name' => '@', 'value' => $t['cname']);
		}
		return array('type' => 'CNAME', 'name' => $parts[0], 'value' => $t['cname']);
	}

	/**
	 * Does the domain reach the website? array(status active|pending, message). Active when one of these is true:
	 *  1. its CNAME is the website's CNAME target,
	 *  2. it resolves to one of the website's addresses,
	 *  3. the website answers on it (/api/site-check) - for domains behind a proxy / CDN that hides the addresses.
	 */
	public static function dnsCheck($domain)
	{
		$t = self::dnsTargets();
		$records = function_exists('dns_get_record') ? @dns_get_record($domain, DNS_A | DNS_CNAME) : false;
		if (is_array($records))
		{
			foreach ($records as $r)
			{
				if ($t['cname'] !== '' && isset($r['target']) && rtrim(strtolower((string) $r['target']), '.') === $t['cname'])
				{
					return array('active', '');
				}
				if (!empty($t['ips']) && isset($r['ip']) && in_array((string) $r['ip'], $t['ips'], true))
				{
					return array('active', '');
				}
			}
		}
		if (self::siteAnswersOn($domain))
		{
			return array('active', '');
		}
		return array('pending', 'The DNS record is not there yet (it can take up to a few hours).');
	}

	/** Does the website itself answer on this domain (GET http(s)://<domain>/api/site-check)? */
	public static function siteAnswersOn($domain)
	{
		foreach (array('https://', 'http://') as $scheme)
		{
			$body = self::httpGet($scheme . $domain . '/api/site-check');
			if ($body === '')
			{
				continue;
			}
			$json = json_decode($body, true);
			if (is_array($json) && isset($json['app']) && $json['app'] === self::SITE_CHECK_APP)
			{
				return true;
			}
		}
		return false;
	}

	/** Small GET with a short timeout (redirects followed); '' on any problem. */
	private static function httpGet($url)
	{
		if (function_exists('curl_init'))
		{
			$ch = curl_init($url);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3,
				CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 6, CURLOPT_USERAGENT => 'BookbyHost-domain-check/1.0',
				// a certificate may not be issued yet (on-demand TLS): the answer itself is what is checked
				CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
			));
			$body = curl_exec($ch);
			$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			return is_string($body) && $code >= 200 && $code < 300 ? substr($body, 0, 2000) : '';
		}
		$ctx = stream_context_create(array('http' => array('timeout' => 6, 'follow_location' => 1, 'max_redirects' => 3),
			'ssl' => array('verify_peer' => false, 'verify_peer_name' => false)));
		$body = @file_get_contents($url, false, $ctx, 0, 2000);
		return is_string($body) ? $body : '';
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
		$data = array('custom_domain' => $domain !== '' ? $domain : ':NULL', 'domain_status' => ':NULL', 'domain_message' => ':NULL', 'domain_checked' => ':NULL');
		pjCompanyModel::factory()->set('id', (int) $company_id)->modify($data);
		if ($domain !== '')
		{
			self::checkDomain($company_id);
		}
		self::writeDomainList();
		return array(true, '');
	}

	/** Checks the DNS of the company's domain and stores the result. */
	public static function checkDomain($company_id)
	{
		$company = pjCompanyModel::factory()->find((int) $company_id)->getData();
		$domain = !empty($company['custom_domain']) ? (string) $company['custom_domain'] : '';
		if ($domain === '')
		{
			return null;
		}
		list($status, $message) = self::dnsCheck($domain);
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
			// "Your own domain" is a plan feature: a domain of a company whose plan lacks it opens no website
			if (!empty($c) && self::domainAllowed((int) $c['id']))
			{
				return $c;
			}
		}
		return null;
	}

	/** True when the company's active plan includes "Your own domain" (custom_domain). */
	public static function domainAllowed($company_id)
	{
		return !class_exists('pjEntitlement') || !pjEntitlement::isReady() || pjEntitlement::allows((int) $company_id, 'custom_domain');
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
		foreach (pjCompanyModel::factory()->select('t1.id, t1.custom_domain')->where('t1.status', 'T')->where('t1.domain_status', 'active')
			->where('t1.custom_domain IS NOT NULL')->findAll()->getData() as $r)
		{
			if (!self::domainAllowed((int) $r['id']))
			{
				continue;
			}
			$d = (string) $r['custom_domain'];
			$list[] = 'https://' . $d;
			$list[] = 'https://' . (strpos($d, 'www.') === 0 ? substr($d, 4) : 'www.' . $d);
		}
		@file_put_contents(self::domainListFile(), json_encode(array_values(array_unique($list))), LOCK_EX);
	}
}
?>
