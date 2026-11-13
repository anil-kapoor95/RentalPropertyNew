-- Subscription reminder e-mails (pjSubscriptionMail), edited in Options > Notifications > Subscriptions (companies).
-- Stored like the sign-up welcome e-mail: recipient 'client' of the platform (company 0), never copied to the companies.
INSERT IGNORE INTO `subscription_settings` (`key`, `value`) VALUES ('reminder_trial_days', '3'), ('reminder_renewal_days', '7');
INSERT INTO `notifications` (`foreign_id`, `company_id`, `recipient`, `transport`, `variant`, `is_active`, `is_general`)
SELECT 0, 0, 'client', 'email', v.`variant`, 1, 1 FROM (SELECT 'sub_trial_ending' AS `variant` UNION ALL SELECT 'sub_renewal_due' UNION ALL SELECT 'sub_expired') v
WHERE NOT EXISTS (SELECT 1 FROM `notifications` n WHERE n.`company_id` = 0 AND n.`foreign_id` = 0 AND n.`recipient` = 'client' AND n.`variant` = v.`variant`);

SET @n := (SELECT `id` FROM `notifications` WHERE `company_id` = 0 AND `foreign_id` = 0 AND `is_general` = 1 AND `recipient` = 'client' AND `variant` = 'sub_trial_ending' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjNotification' AND `foreign_id` = @n AND `field` IN ('sub_trial_ending_subject_subscription', 'sub_trial_ending_tokens_subscription');
INSERT INTO `plugin_base_multi_lang` (`id`, `foreign_id`, `model`, `locale`, `field`, `content`, `source`) VALUES (NULL, @n, 'pjNotification', '::LOCALE::', 'sub_trial_ending_subject_subscription', 'Your {Plan} trial ends in {DaysLeft} days', 'data');
INSERT INTO `plugin_base_multi_lang` (`id`, `foreign_id`, `model`, `locale`, `field`, `content`, `source`) VALUES (NULL, @n, 'pjNotification', '::LOCALE::', 'sub_trial_ending_tokens_subscription', '<p>Hi {Name},</p><p>The free trial of the <strong>{Plan}</strong> plan for {Company} ends on <strong>{EndDate}</strong> ({DaysLeft} days left).</p><p>Choose a plan now to keep all its features and your website taking bookings:<br><a href="{MyPlanLink}">{MyPlanLink}</a></p><p>The {SiteName} team</p>', 'data');

SET @n := (SELECT `id` FROM `notifications` WHERE `company_id` = 0 AND `foreign_id` = 0 AND `is_general` = 1 AND `recipient` = 'client' AND `variant` = 'sub_renewal_due' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjNotification' AND `foreign_id` = @n AND `field` IN ('sub_renewal_due_subject_subscription', 'sub_renewal_due_tokens_subscription');
INSERT INTO `plugin_base_multi_lang` (`id`, `foreign_id`, `model`, `locale`, `field`, `content`, `source`) VALUES (NULL, @n, 'pjNotification', '::LOCALE::', 'sub_renewal_due_subject_subscription', 'Your {Plan} plan renews on {EndDate}', 'data');
INSERT INTO `plugin_base_multi_lang` (`id`, `foreign_id`, `model`, `locale`, `field`, `content`, `source`) VALUES (NULL, @n, 'pjNotification', '::LOCALE::', 'sub_renewal_due_tokens_subscription', '<p>Hi {Name},</p><p>The <strong>{Plan}</strong> plan of {Company} has to be renewed by <strong>{EndDate}</strong> ({DaysLeft} days left). Price: {Price}.</p><p>Renew it on My plan so nothing stops:<br><a href="{MyPlanLink}">{MyPlanLink}</a></p><p>The {SiteName} team</p>', 'data');

SET @n := (SELECT `id` FROM `notifications` WHERE `company_id` = 0 AND `foreign_id` = 0 AND `is_general` = 1 AND `recipient` = 'client' AND `variant` = 'sub_expired' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjNotification' AND `foreign_id` = @n AND `field` IN ('sub_expired_subject_subscription', 'sub_expired_tokens_subscription');
INSERT INTO `plugin_base_multi_lang` (`id`, `foreign_id`, `model`, `locale`, `field`, `content`, `source`) VALUES (NULL, @n, 'pjNotification', '::LOCALE::', 'sub_expired_subject_subscription', 'Your {Plan} plan has ended', 'data');
INSERT INTO `plugin_base_multi_lang` (`id`, `foreign_id`, `model`, `locale`, `field`, `content`, `source`) VALUES (NULL, @n, 'pjNotification', '::LOCALE::', 'sub_expired_tokens_subscription', '<p>Hi {Name},</p><p>The <strong>{Plan}</strong> plan of {Company} ended on <strong>{EndDate}</strong>. Your back office is read-only and your website takes no new bookings.</p><p>Renew or choose a plan to switch everything on again:<br><a href="{MyPlanLink}">{MyPlanLink}</a></p><p>The {SiteName} team</p>', 'data');

