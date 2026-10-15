-- Company login (4): set the permissions of its own "Company user" users
-- (the code limits the list to the permissions the company has itself).
-- Safe to re-run. Table names are unprefixed; the updater adds the prefix.

START TRANSACTION;

INSERT IGNORE INTO `plugin_auth_roles_permissions` (`role_id`, `permission_id`) SELECT 4, `id` FROM `plugin_auth_permissions` WHERE `key` IN ('pjBasePermissions_pjActionUserPermission', 'pjBasePermissions_pjActionAjaxSet');

INSERT IGNORE INTO `plugin_auth_users_permissions` (`user_id`, `permission_id`) SELECT x.`uid`, p.`id` FROM `plugin_auth_permissions` p, (SELECT DISTINCT `user_id` AS `uid` FROM `plugin_auth_users_permissions` WHERE `user_id` IN (SELECT `id` FROM `plugin_auth_users` WHERE `role_id` = 4)) x WHERE p.`key` IN ('pjBasePermissions_pjActionUserPermission', 'pjBasePermissions_pjActionAjaxSet');

COMMIT;
