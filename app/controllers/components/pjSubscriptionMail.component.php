<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Subscription reminder e-mails to the companies (sent by the cron job "Subscriptions", pjCron::pjActionSubscriptions):
 *   sub_trial_ending - the free trial ends in N days        (setting reminder_trial_days, default 3)
 *   sub_renewal_due  - a paid plan has to be renewed in N days (setting reminder_renewal_days, default 7)
 *   sub_expired      - the plan has ended (sent once, within 30 days of the end)
 *
 * Platform notifications edited by the Super Admin in Options > Notifications > "Subscriptions (companies)":
 * notifications rows company_id 0, foreign_id 0, is_general 1, recipient 'client', transport email
 * (update_2026_11_13_09_30_00.sql), never copied to the companies (pjCompanyContext::ensureNotifications). Texts in pjMultiLang (model pjNotification), fields
 * <variant>_subject_subscription / <variant>_tokens_subscription.
 *
 * Every e-mail is sent once per company, reminder and period: it is logged in the subscription history
 * (event type "mail_<variant>", message with the period key) and that log is checked before sending.
 */
class pjSubscriptionMail
{
	/** Value of the "Subscriptions (companies)" tab in Options > Notifications (and of the text field names). */
	const RECIPIENT = 'subscription';

	/** recipient column of the notification rows (an enum: the rows are platform "client" e-mails). */
	const ROW_RECIPIENT = 'client';

	public static $variants = array('sub_trial_ending', 'sub_renewal_due', 'sub_expired');

	/** An expired plan older than this is not mailed any more (first run after an update, old data). */
	const EXPIRED_MAX_AGE = 2592000; // 30 days

	public static function subjectField($variant)
	{
		return $variant . '_subject_' . self::RECIPIENT;
	}

	public static function messageField($variant)
	{
		return $variant . '_tokens_' . self::RECIPIENT;
	}

	/** variant => notification row */
	public static function rows()
	{
		return pjNotificationModel::factory()
			->where('t1.company_id', 0)->where('t1.foreign_id', 0)->where('t1.is_general', 1)->where('t1.transport', 'email')
			->where('t1.recipient', self::ROW_RECIPIENT)->whereIn('t1.variant', self::$variants)
			->findAll()->getDataPair('variant');
	}

	private static function days($key, $default)
	{
		$v = class_exists('pjEntitlement') ? pjEntitlement::setting($key) : null;
		$v = $v === null || $v === '' ? $default : (int) $v;
		return max(0, min(60, (int) $v));
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
			$m = isset($i18n[$lid][self::messageField($variant)]) ? trim((string) $i18n[$lid][self::messageField($variant)]) : '';
			if ($s !== '' && $m !== '')
			{
				return array('subject' => $s, 'message' => $m);
			}
		}
		return null;
	}

	/** Addresses of a company: its e-mail and its login's e-mail (when different). */
	public static function recipients($company)
	{
		$out = array();
		if (!empty($company['email']) && pjValidation::pjActionEmail((string) $company['email']))
		{
			$out[strtolower((string) $company['email'])] = (string) $company['email'];
		}
		if (!empty($company['user_id']))
		{
			$u = pjAuthUserModel::factory()->select('t1.email, t1.status')->find((int) $company['user_id'])->getData();
			if (!empty($u['email']) && $u['status'] === 'T' && pjValidation::pjActionEmail((string) $u['email']))
			{
				$out[strtolower((string) $u['email'])] = (string) $u['email'];
			}
		}
		return array_values($out);
	}

	private static function money($amount, $currency)
	{
		return $amount === null ? '' : (class_exists('pjMoney') ? pjMoney::format($amount, $currency) : number_format((float) $amount, 2) . ' ' . $currency);
	}

	/** Token values for one company / plan / date. */
	public static function tokens($company, $plan, $end, $site_name)
	{
		$end_ts = $end ? strtotime($end) : 0;
		$days = $end_ts ? (int) ceil(($end_ts - time()) / 86400) : 0;
		return array(
			'{Name}'       => !empty($company['contact_person']) ? (string) $company['contact_person'] : (string) $company['name'],
			'{Company}'    => (string) $company['name'],
			'{Plan}'       => $plan ? (string) $plan['name'] : '',
			'{Price}'      => $plan ? self::money($plan['price'], $plan['currency']) : '',
			'{EndDate}'    => $end_ts ? date('d.m.Y', $end_ts) : '',
			'{DaysLeft}'   => (string) max(0, $days),
			'{MyPlanLink}' => PJ_INSTALL_URL . 'index.php?controller=pjAdminSubscriptions&action=pjActionMyPlan',
			'{SiteName}'   => (string) $site_name,
		);
	}

	/** Already sent for this company / reminder / period? */
	private static function alreadySent($company_id, $variant, $key)
	{
		return (int) pjSubscriptionEventModel::factory()->where('t1.company_id', (int) $company_id)->where('t1.type', 'mail_' . $variant)
			->where('t1.message LIKE', '%[' . $key . ']%')->findCount()->getData() > 0;
	}

	/**
	 * Send one reminder to a company (all its addresses). Returns true, 'off', 'sent' (already sent) or the reason
	 * it was not sent. $force: send again even when it was sent for this period (test from the back office).
	 */
	public static function send($variant, $company, $plan, $end, $key, $force = false)
	{
		if (!in_array($variant, self::$variants, true))
		{
			return 'Unknown e-mail.';
		}
		if (!$force && self::alreadySent($company['id'], $variant, $key))
		{
			return 'sent';
		}
		$rows = self::rows();
		if (empty($rows[$variant]))
		{
			return 'The notification is missing: run the update update_2026_11_13_09_30_00.sql.';
		}
		if ((int) $rows[$variant]['is_active'] !== 1)
		{
			return 'off';
		}
		$to = self::recipients($company);
		if (empty($to))
		{
			return 'The company has no valid e-mail address.';
		}
		$text = self::texts($rows[$variant]);
		if ($text === null)
		{
			return 'The notification has no subject or message.';
		}
		$opt = pjCompanyContext::basePairs(0);
		$tokens = self::tokens($company, $plan, $end, isset($opt['o_sender_name']) ? $opt['o_sender_name'] : '');
		$subject = stripslashes(str_replace(array_keys($tokens), array_values($tokens), $text['subject']));
		$safe = array_map(function ($v) { return pjSanitize::html($v); }, $tokens);
		// the editor saves a link to {MyPlanLink} as "http://<back office>/{MyPlanLink}": keep only the token
		$message = preg_replace('~(href\s*=\s*["\'])[^"\']*?(\{MyPlanLink\})~i', '$1$2', stripslashes($text['message']));
		$body = str_replace(array_keys($safe), array_values($safe), $message);
		$errors = array();
		foreach ($to as $address)
		{
			try {
				$mailer = pjAppController::getMailer($opt);
				if ($mailer->setTo($address)->setSubject($subject)->send($body) === false)
				{
					$why = trim((string) $mailer->getErrorMessage());
					$errors[] = $address . ($why !== '' ? ': ' . $why : '');
				}
			} catch (Exception $e) {
				$errors[] = $address . ': ' . $e->getMessage();
			}
		}
		if (count($errors) === count($to))
		{
			return 'The e-mail could not be sent (' . implode('; ', $errors) . ').';
		}
		pjEntitlement::log($company['id'], 'mail_' . $variant, sprintf('Reminder e-mail "%s" sent to %s [%s]', $variant, implode(', ', $to), $key));
		return true;
	}

	/**
	 * The reminder a subscription is due for right now, or null: array(variant, end date, period key).
	 */
	public static function dueReminder($sub, $now = null)
	{
		$now = $now === null ? time() : (int) $now;
		$plan = pjEntitlement::plan($sub['plan_id']);
		if (!$plan)
		{
			return null;
		}
		list($status, $entitled) = pjEntitlement::effectiveStatus($sub, $now);
		$trial_end = !empty($sub['trial_ends_at']) ? strtotime($sub['trial_ends_at']) : 0;
		$period_end = !empty($sub['current_period_end']) ? strtotime($sub['current_period_end']) : 0;
		if ($status === 'trial' && $trial_end > $now)
		{
			// a scheduled change to a free plan, or cancellation, means nothing ends badly: still remind (the plan ends)
			if ($trial_end - $now <= self::days('reminder_trial_days', 3) * 86400)
			{
				return array('sub_trial_ending', $sub['trial_ends_at'], 'trial:' . date('Y-m-d', $trial_end));
			}
			return null;
		}
		if ($entitled && in_array($status, array('active', 'past_due'), true) && $period_end > $now)
		{
			if (pjEntitlement::isFree($plan) || (int) $sub['cancel_at_period_end'] === 1)
			{
				return null; // nothing to pay
			}
			$pending = !empty($sub['pending_plan_id']) ? pjEntitlement::plan($sub['pending_plan_id']) : false;
			if ($pending && pjEntitlement::isFree($pending))
			{
				return null; // moves to the free plan at the end
			}
			if ($period_end - $now <= self::days('reminder_renewal_days', 7) * 86400)
			{
				return array('sub_renewal_due', $sub['current_period_end'], 'renewal:' . date('Y-m-d', $period_end));
			}
			return null;
		}
		if (!$entitled && in_array($status, array('expired', 'cancelled'), true))
		{
			$end = $sub['status'] === 'trial' || ($trial_end && $trial_end >= $period_end) ? $trial_end : $period_end;
			if ($end <= 0)
			{
				// no end date (ended by the administrator): the day of the last change
				$end = !empty($sub['modified']) ? strtotime($sub['modified']) : $now;
			}
			if ($now - $end > self::EXPIRED_MAX_AGE)
			{
				return null;
			}
			// ended before its date (by the administrator): it ended "now"; the key stays the planned end
			$shown = min($end, $now);
			return array('sub_expired', date('Y-m-d H:i:s', $shown), 'expired:' . date('Y-m-d', $end));
		}
		return null;
	}

	/** Cron: send the reminders that are due. Returns a short report. */
	public static function run()
	{
		if (!class_exists('pjEntitlement') || !pjEntitlement::isReady())
		{
			return 'Subscription tables are missing.';
		}
		$count = array('sent' => 0, 'skipped' => 0, 'failed' => 0);
		$companies = pjCompanyModel::factory()->where('t1.status', 'T')->findAll()->getDataPair('id');
		foreach (pjCompanySubscriptionModel::factory()->findAll()->getData() as $sub)
		{
			if (!isset($companies[(int) $sub['company_id']]))
			{
				continue;
			}
			$sub = pjEntitlement::applyDueChange($sub);
			$due = self::dueReminder($sub);
			if ($due === null)
			{
				continue;
			}
			list($variant, $end, $key) = $due;
			$res = self::send($variant, $companies[(int) $sub['company_id']], pjEntitlement::plan($sub['plan_id']), $end, $key);
			if ($res === true)
			{
				$count['sent']++;
			} elseif ($res === 'sent' || $res === 'off') {
				$count['skipped']++;
			} else {
				$count['failed']++;
				error_log('Subscription reminder (' . $variant . ', company ' . (int) $sub['company_id'] . '): ' . $res);
			}
		}
		return sprintf('Reminder e-mails: %d sent, %d already sent / switched off, %d failed.', $count['sent'], $count['skipped'], $count['failed']);
	}
}
?>
