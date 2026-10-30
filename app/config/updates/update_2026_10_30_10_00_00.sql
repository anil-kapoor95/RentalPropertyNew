-- Sign-up emails (sent after sign-up step 1), edited in Options > Notifications:
--   "Sign-up (new hosts)" > welcome email to the new host (variant signup_host)
--   "Administrator"       > new sign-up email (variant signup_admin)
-- Platform notifications: company_id 0, foreign_id 0, is_general 1 (never copied to the companies).

INSERT IGNORE INTO `notifications` (`foreign_id`, `company_id`, `recipient`, `transport`, `variant`, `is_active`, `is_general`) VALUES
(0, 0, 'client', 'email', 'signup_host', 1, 1),
(0, 0, 'admin', 'email', 'signup_admin', 1, 1);

-- texts of the admin email: field names follow the Administrator tab (<variant>_subject_admin / <variant>_tokens_admin)
UPDATE `plugin_base_multi_lang` SET `field` = 'signup_admin_subject_admin' WHERE `model` = 'pjNotification' AND `field` = 'signup_admin_subject_signup';
UPDATE `plugin_base_multi_lang` SET `field` = 'signup_admin_tokens_admin' WHERE `model` = 'pjNotification' AND `field` = 'signup_admin_tokens_signup';

-- default texts (kept when they already exist)
SET @nh := (SELECT `id` FROM `notifications` WHERE `company_id` = 0 AND `foreign_id` = 0 AND `is_general` = 1 AND `variant` = 'signup_host' LIMIT 1);
INSERT IGNORE INTO `plugin_base_multi_lang` (`id`, `foreign_id`, `model`, `locale`, `field`, `content`, `source`) VALUES (NULL, @nh, 'pjNotification', '::LOCALE::', 'signup_host_subject_signup', 'Welcome to {SiteName}, {Name}', 'data');
INSERT IGNORE INTO `plugin_base_multi_lang` (`id`, `foreign_id`, `model`, `locale`, `field`, `content`, `source`) VALUES (NULL, @nh, 'pjNotification', '::LOCALE::', 'signup_host_tokens_signup', '<p>Hi {Name},</p><p>Your account has been created with {Email}.</p><p>Continue setting up your property page here:<br><a href="{SignupLink}">{SignupLink}</a></p><p>You can log in again at any time with your email and password to pick up where you left off.</p><p>The {SiteName} team</p>', 'data');

SET @na := (SELECT `id` FROM `notifications` WHERE `company_id` = 0 AND `foreign_id` = 0 AND `is_general` = 1 AND `variant` = 'signup_admin' LIMIT 1);
INSERT IGNORE INTO `plugin_base_multi_lang` (`id`, `foreign_id`, `model`, `locale`, `field`, `content`, `source`) VALUES (NULL, @na, 'pjNotification', '::LOCALE::', 'signup_admin_subject_admin', 'New sign-up: {Name}', 'data');
INSERT IGNORE INTO `plugin_base_multi_lang` (`id`, `foreign_id`, `model`, `locale`, `field`, `content`, `source`) VALUES (NULL, @na, 'pjNotification', '::LOCALE::', 'signup_admin_tokens_admin', '<p>A new host has created an account.</p><p>Name: {Name}<br>Email: {Email}<br>Phone: {Phone}<br>Date: {Date}</p><p>The host still has to finish the sign-up steps (property, company, photos, description, theme).</p>', 'data');

-- labels (Display > Languages)
SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='recipients_ARRAY_signup' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='recipients_ARRAY_signup';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'recipients_ARRAY_signup', 'arrays', 'recipients_ARRAY_signup', 'script', '2026-10-30 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Sign-up (new hosts)', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_msg_to_signup' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_msg_to_signup';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'notifications_msg_to_signup', 'backend', 'notifications_msg_to_signup', 'script', '2026-10-30 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Messages sent to new hosts', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_ARRAY_client_email_signup_host' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_ARRAY_client_email_signup_host';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'notifications_ARRAY_client_email_signup_host', 'arrays', 'notifications_ARRAY_client_email_signup_host', 'script', '2026-10-30 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Send welcome email (account created)', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_titles_ARRAY_client_email_signup_host' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_titles_ARRAY_client_email_signup_host';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'notifications_titles_ARRAY_client_email_signup_host', 'arrays', 'notifications_titles_ARRAY_client_email_signup_host', 'script', '2026-10-30 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Welcome email sent to the new host', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_subtitles_ARRAY_client_email_signup_host' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_subtitles_ARRAY_client_email_signup_host';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'notifications_subtitles_ARRAY_client_email_signup_host', 'arrays', 'notifications_subtitles_ARRAY_client_email_signup_host', 'script', '2026-10-30 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Sent to the host when the account is created (sign-up step 1). The texts in the default language are used.', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_ARRAY_admin_email_signup_admin' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_ARRAY_admin_email_signup_admin';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'notifications_ARRAY_admin_email_signup_admin', 'arrays', 'notifications_ARRAY_admin_email_signup_admin', 'script', '2026-10-30 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Send new sign-up email', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_titles_ARRAY_admin_email_signup_admin' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_titles_ARRAY_admin_email_signup_admin';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'notifications_titles_ARRAY_admin_email_signup_admin', 'arrays', 'notifications_titles_ARRAY_admin_email_signup_admin', 'script', '2026-10-30 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'New sign-up email sent to Admin', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_subtitles_ARRAY_admin_email_signup_admin' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_subtitles_ARRAY_admin_email_signup_admin';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'notifications_subtitles_ARRAY_admin_email_signup_admin', 'arrays', 'notifications_subtitles_ARRAY_admin_email_signup_admin', 'script', '2026-10-30 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Sent to the administrator when a host creates an account (sign-up step 1).', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSignupTokens' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSignupTokens';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblSignupTokens', 'backend', 'Label / Sign-up email tokens', 'script', '2026-10-30 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', '<div><small><strong>{Name}</strong> - name of the host</small></div>\r\n<div><small><strong>{Email}</strong> - email of the host</small></div>\r\n<div><small><strong>{Phone}</strong> - phone of the host</small></div>\r\n<div><small><strong>{Date}</strong> - date and time of the sign-up</small></div>\r\n<div><small><strong>{SignupLink}</strong> - link to continue the sign-up</small></div>\r\n<div><small><strong>{SiteName}</strong> - sender name (Options &gt; Email settings)</small></div>', 'script');
