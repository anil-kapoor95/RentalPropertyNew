-- Company logins that have personal permissions (these replace the role's permissions)
-- also get the Users menu. Safe to re-run. Table names are unprefixed; the updater adds the prefix.

START TRANSACTION;

INSERT IGNORE INTO `plugin_auth_users_permissions` (`user_id`, `permission_id`) SELECT x.`uid`, p.`id` FROM `plugin_auth_permissions` p, (SELECT DISTINCT `user_id` AS `uid` FROM `plugin_auth_users_permissions` WHERE `user_id` IN (SELECT `id` FROM `plugin_auth_users` WHERE `role_id` = 4)) x WHERE p.`key` IN ('pjBaseUsers', 'pjBaseUsers_pjActionIndex', 'pjBaseUsers_pjActionCreate', 'pjBaseUsers_pjActionUpdate', 'pjBaseUsers_pjActionDeleteUser', 'pjBaseUsers_pjActionDeleteUserBulk', 'pjBaseUsers_pjActionStatusUser', 'pjBaseUsers_pjActionExportUser', 'pjBaseUsers_pjActionUnlockAccount', 'pjBaseUsers_pjActionProfile');

COMMIT;
