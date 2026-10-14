-- Companies created before 2026-10-14 got a copy of the platform's SMTP login and
-- front-end captcha secret. Clear those copies where they are still identical to the
-- platform value; while a company keeps the platform's SMTP host / site key, the
-- platform secret is used for it at run time (pjCompanyContext::basePairs), so e-mail
-- keeps working. Values a company entered itself are not touched. Safe to re-run.
-- Table names are unprefixed; the updater adds the prefix.

UPDATE `plugin_base_options` SET `value` = '' WHERE `foreign_id` > 1000 AND `key` = 'o_smtp_user' AND `value` <> '' AND `value` = (SELECT x.v FROM (SELECT `value` AS v FROM `plugin_base_options` WHERE `foreign_id` = 1 AND `key` = 'o_smtp_user' LIMIT 1) AS x);
UPDATE `plugin_base_options` SET `value` = '' WHERE `foreign_id` > 1000 AND `key` = 'o_smtp_pass' AND `value` <> '' AND `value` = (SELECT x.v FROM (SELECT `value` AS v FROM `plugin_base_options` WHERE `foreign_id` = 1 AND `key` = 'o_smtp_pass' LIMIT 1) AS x);
UPDATE `plugin_base_options` SET `value` = '' WHERE `foreign_id` > 1000 AND `key` = 'o_captcha_secret_key_front' AND `value` <> '' AND `value` = (SELECT x.v FROM (SELECT `value` AS v FROM `plugin_base_options` WHERE `foreign_id` = 1 AND `key` = 'o_captcha_secret_key_front' LIMIT 1) AS x);
