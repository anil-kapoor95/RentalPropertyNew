-- "Company user" role (5): users that a company creates for itself in its own Users menu.
-- They work inside that company only; administrators do not see or manage them.
-- Safe to re-run. Table names are unprefixed; the updater adds the prefix.

START TRANSACTION;

INSERT IGNORE INTO `plugin_auth_roles` (`id`, `role`, `is_backend`, `is_admin`, `status`) VALUES (5, 'Company user', 'T', 'T', 'T');

-- Company login (4): Users menu (list, add, edit, delete, status, export, unlock)
INSERT IGNORE INTO `plugin_auth_roles_permissions` (`role_id`, `permission_id`) SELECT 4, `id` FROM `plugin_auth_permissions` WHERE `key` IN ('pjBaseUsers', 'pjBaseUsers_pjActionIndex', 'pjBaseUsers_pjActionCreate', 'pjBaseUsers_pjActionUpdate', 'pjBaseUsers_pjActionDeleteUser', 'pjBaseUsers_pjActionDeleteUserBulk', 'pjBaseUsers_pjActionStatusUser', 'pjBaseUsers_pjActionExportUser', 'pjBaseUsers_pjActionUnlockAccount', 'pjBaseUsers_pjActionProfile');

-- Company user (5): the company's daily work (same as the company login), without
-- Users, My company and System Options. Only when the role has no permissions yet,
-- so changes the administrator makes later in Roles permissions are kept.
INSERT IGNORE INTO `plugin_auth_roles_permissions` (`role_id`, `permission_id`) SELECT 5, rp.`permission_id` FROM `plugin_auth_roles_permissions` rp WHERE rp.`role_id` = 4 AND NOT EXISTS (SELECT 1 FROM `plugin_auth_roles_permissions` x WHERE x.`role_id` = 5) AND rp.`permission_id` NOT IN (SELECT `id` FROM `plugin_auth_permissions` WHERE (`key` LIKE 'pjBaseUsers%' AND `key` <> 'pjBaseUsers_pjActionProfile') OR `key` = 'pjAdminCompanies_pjActionProfile' OR `key` LIKE 'pjBaseOptions%' OR `key` LIKE 'pjBaseSms%' OR `key` LIKE 'pjBaseLocale%' OR `key` LIKE 'pjBaseCountries%' OR `key` LIKE 'pjBaseCron%' OR `key` LIKE 'pjBaseBackup%');

COMMIT;

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_role_arr_ARRAY_5' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_role_arr_ARRAY_5';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_role_arr_ARRAY_5','arrays','Role / Company user','script','2026-10-14 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company user','script');
