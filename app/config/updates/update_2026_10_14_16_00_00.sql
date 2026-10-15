-- Company role: every System Options page (General, API Keys, Email Settings, SMS Settings,
-- Languages, Login & Protection, Captcha & Spam, Visual & Branding, Countries, Cron Jobs, Backup).
-- Each page saves for the company only; the shared login-screen keys and whole-system
-- backups stay admin-only in the code. Safe to re-run. Table names are unprefixed; the updater adds the prefix.

START TRANSACTION;

INSERT IGNORE INTO `plugin_auth_roles_permissions` (`role_id`, `permission_id`) SELECT 4, `id` FROM `plugin_auth_permissions` WHERE `key` LIKE 'pjBaseOptions%' OR `key` LIKE 'pjBaseSms%' OR `key` LIKE 'pjBaseLocale%' OR `key` LIKE 'pjBaseCountries%' OR `key` LIKE 'pjBaseCron%' OR `key` LIKE 'pjBaseBackup%';

INSERT IGNORE INTO `plugin_auth_users_permissions` (`user_id`, `permission_id`) SELECT x.`uid`, p.`id` FROM `plugin_auth_permissions` p, (SELECT DISTINCT `user_id` AS `uid` FROM `plugin_auth_users_permissions` WHERE `user_id` IN (SELECT `id` FROM `plugin_auth_users` WHERE `role_id` = 4)) x WHERE p.`key` LIKE 'pjBaseOptions%' OR p.`key` LIKE 'pjBaseSms%' OR p.`key` LIKE 'pjBaseLocale%' OR p.`key` LIKE 'pjBaseCountries%' OR p.`key` LIKE 'pjBaseCron%' OR p.`key` LIKE 'pjBaseBackup%';

COMMIT;
