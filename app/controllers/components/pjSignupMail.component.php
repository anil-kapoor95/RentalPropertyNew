<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Sign-up emails, sent when a host creates an account (sign-up step 1). Edited by administrators in
 * Options > Notifications, like the booking emails:
 *   "Sign-up (new hosts)" tab > signup_host  - welcome email to the new host
 *   "Administrator" tab       > signup_admin - new sign-up email to the administrator
 * Platform notifications (company_id 0, foreign_id 0, is_general 1, transport email; created by
 * update_2026_10_30_10_00_00.sql), never copied to the companies. Texts in pjMultiLang (model pjNotification),
 * fields <variant>_subject_<tab> and <variant>_tokens_<tab>, HTML. Labels and tokens: Display > Languages.
 */
class pjSignupMail
{
	/** Value of the "Sign-up (new hosts)" tab (recipient radio) in Options > Notifications. */
	const RECIPIENT = 'signup';

	/** variant => tab (recipient radio) it is edited under */
	public static $variants = array(
		'signup_host'  => 'signup',
		'signup_admin' => 'admin',
	);

	public static function isVariant($variant)
	{
		return isset(self::$variants[(string) $variant]);
	}

	public static function subjectField($variant)
	{
		return $variant . '_subject_' . self::$variants[$variant];
	}

	public static function messageField($variant)
	{
		return $variant . '_tokens_' . self::$variants[$variant];
	}

	/** The notification rows: variant => row. */
	public static function rows()
	{
		return pjNotificationModel::factory()
			->where('t1.company_id', 0)->where('t1.foreign_id', 0)->where('t1.is_general', 1)->where('t1.transport', 'email')
			->whereIn('t1.variant', array_keys(self::$variants))
			->findAll()->getDataPair('variant');
	}

	/** Address of the admin email: the administrator login (user #1). */
	public static function adminAddress()
	{
		$admin = pjAuthUserModel::factory()->find(1)->getData();
		$to = !empty($admin['email']) ? (string) $admin['email'] : '';
		return pjValidation::pjActionEmail($to) ? $to : '';
	}

	/** Email settings of the platform (Options > Email settings). */
	private static function mailOptions()
	{
		return pjCompanyContext::basePairs(0);
	}

	/** Subject and message in the default language (another language with text otherwise), or null. */
	private static function texts($row)
	{
		$variant = $row['variant'];
		$i18n = pjBaseMultiLangModel::factory()->getMultiLang((int) $row['id'], 'pjNotification');
		$default = pjLocaleModel::factory()->where('t1.is_default', 1)->limit(1)->findAll()->getDataIndex(0);
		$order = array_keys($i18n);
		if (!empty($default))
		{
			array_unshift($order, (int) $default['id']);
		}
		foreach ($order as $lid)
		{
			$s = isset($i18n[$lid][self::subjectField($variant)]) ? trim((string) $i18n[$lid][self::subjectField($variant)]) : '';
			$msg = isset($i18n[$lid][self::messageField($variant)]) ? trim((string) $i18n[$lid][self::messageField($variant)]) : '';
			if ($s !== '' && $msg !== '')
			{
				return array('subject' => $s, 'message' => $msg);
			}
		}
		return null;
	}

	/** Token values for a host (array with name, email, phone). */
	private static function tokenValues($host, $site_url)
	{
		$opt = self::mailOptions();
		$site_url = (string) $site_url;
		return array(
			'{Name}'       => isset($host['name']) ? (string) $host['name'] : '',
			'{Email}'      => isset($host['email']) ? (string) $host['email'] : '',
			'{Phone}'      => isset($host['phone']) ? (string) $host['phone'] : '',
			'{Date}'       => date('Y-m-d H:i'),
			'{SignupLink}' => $site_url !== '' ? rtrim($site_url, '/') . '/setup' : '',
			'{SiteName}'   => isset($opt['o_sender_name']) ? (string) $opt['o_sender_name'] : '',
		);
	}

	/** Send one sign-up email. Returns true, or the reason it was not sent ('off': switched off). */
	public static function send($variant, $to, $host, $site_url = '')
	{
		if (!self::isVariant($variant))
		{
			return 'Unknown email.';
		}
		if (!pjValidation::pjActionEmail((string) $to))
		{
			return 'No valid recipient address.';
		}
		$rows = self::rows();
		if (empty($rows[$variant]))
		{
			return 'The notification is missing: run the update update_2026_10_30_10_00_00.sql.';
		}
		if ((int) $rows[$variant]['is_active'] !== 1)
		{
			return 'off';
		}
		$text = self::texts($rows[$variant]);
		if ($text === null)
		{
			return 'The notification has no subject or message.';
		}
		$tokens = self::tokenValues($host, $site_url);
		$subject = stripslashes(str_replace(array_keys($tokens), array_values($tokens), $text['subject']));
		// the host typed his name and phone: escaped in the HTML message
		$safe = array_map(function ($v) { return pjSanitize::html($v); }, $tokens);
		// the editor saves a link to {SignupLink} as "http://<back office>/{SignupLink}": keep only the token
		$message = preg_replace('~(href\s*=\s*["\'])[^"\']*?(\{SignupLink\})~i', '$1$2', stripslashes($text['message']));
		$body = str_replace(array_keys($safe), array_values($safe), $message);
		try {
			$mailer = pjAppController::getMailer(self::mailOptions());
			$ok = $mailer->setTo($to)->setSubject($subject)->send($body);
		} catch (Exception $e) {
			return 'The email could not be sent: ' . $e->getMessage();
		}
		if ($ok === false)
		{
			$why = trim((string) $mailer->getErrorMessage());
			return 'The email could not be sent' . ($why !== '' ? ': ' . $why : '') . '.';
		}
		return true;
	}

	/** After sign-up step 1: welcome email to the host and new sign-up email to the administrator. Never stops the sign-up. */
	public static function accountCreated($host, $site_url = '')
	{
		$result = array();
		foreach (array('signup_host' => isset($host['email']) ? $host['email'] : '', 'signup_admin' => self::adminAddress()) as $variant => $to)
		{
			$res = self::send($variant, $to, $host, $site_url);
			$result[$variant] = $res;
			if ($res !== true && $res !== 'off')
			{
				error_log('Sign-up email (' . $variant . '): ' . $res);
			}
		}
		return $result;
	}
}
?>
