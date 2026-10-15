-- Company role: System Options pages that work per company
-- (General, API keys, E-mail settings, SMS settings, Languages, Captcha & spam).
-- Login protection, Visual & branding, Countries, Cron jobs and Backup stay admin-only.
-- My company: labels for changing the company's own password.
-- Safe to re-run. Table names are unprefixed; the updater adds the prefix.

START TRANSACTION;

INSERT IGNORE INTO `plugin_auth_roles_permissions` (`role_id`, `permission_id`) SELECT 4, `id` FROM `plugin_auth_permissions` WHERE `key` IN ('pjBaseOptions', 'pjBaseOptions_pjActionIndex', 'pjBaseOptions_pjActionApiKeys', 'pjBaseOptions_pjActionEmailSettings') OR `key` LIKE 'pjBaseOptions_pjActionCaptchaSpam%' OR `key` LIKE 'pjBaseSms%' OR (`key` LIKE 'pjBaseLocale%' AND `key` <> 'pjBaseLocale_pjActionLabels_showIds');

-- Company users that have personal permissions (these replace the role's) get them too
INSERT IGNORE INTO `plugin_auth_users_permissions` (`user_id`, `permission_id`) SELECT x.`uid`, p.`id` FROM `plugin_auth_permissions` p, (SELECT DISTINCT `user_id` AS `uid` FROM `plugin_auth_users_permissions` WHERE `user_id` IN (SELECT `id` FROM `plugin_auth_users` WHERE `role_id` = 4)) x WHERE p.`key` IN ('pjBaseOptions', 'pjBaseOptions_pjActionIndex', 'pjBaseOptions_pjActionApiKeys', 'pjBaseOptions_pjActionEmailSettings') OR p.`key` LIKE 'pjBaseOptions_pjActionCaptchaSpam%' OR p.`key` LIKE 'pjBaseSms%' OR (p.`key` LIKE 'pjBaseLocale%' AND p.`key` <> 'pjBaseLocale_pjActionLabels_showIds');

COMMIT;

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanySectionPassword' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanySectionPassword';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanySectionPassword','backend','Companies / my company password section','script','2026-10-14 15:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Change password','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginCurrentPassword' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginCurrentPassword';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyLoginCurrentPassword','backend','Companies / current password','script','2026-10-14 15:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Current password','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginNoteOwn' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginNoteOwn';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyLoginNoteOwn','backend','Companies / my company password note','script','2026-10-14 15:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','To change your password, enter your current password and the new password twice. Leave all three empty to keep it.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyCurrentPasswordWrong' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyCurrentPasswordWrong';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyCurrentPasswordWrong','backend','Companies / current password wrong','script','2026-10-14 15:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The current password is not correct. Nothing was saved.','script');
