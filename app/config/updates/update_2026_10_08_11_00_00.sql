START TRANSACTION;

ALTER TABLE `calendars` ADD `company_id` int(10) unsigned DEFAULT NULL AFTER `user_id`, ADD KEY `company_id` (`company_id`);
ALTER TABLE `extras` ADD `company_id` int(10) unsigned DEFAULT NULL AFTER `id`, ADD KEY `company_id` (`company_id`);
ALTER TABLE `features` ADD `company_id` int(10) unsigned DEFAULT NULL AFTER `id`, ADD KEY `company_id` (`company_id`);
ALTER TABLE `discounts` ADD `company_id` int(10) unsigned DEFAULT NULL AFTER `id`, ADD KEY `company_id` (`company_id`);
ALTER TABLE `vouchers` ADD `company_id` int(10) unsigned DEFAULT NULL AFTER `id`, DROP INDEX `code`, ADD UNIQUE KEY `company_code` (`company_id`, `code`), ADD KEY `code` (`code`);
ALTER TABLE `notifications` ADD `company_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `foreign_id`, DROP INDEX `recipient`, ADD UNIQUE KEY `recipient` (`company_id`, `foreign_id`, `recipient`, `transport`, `variant`, `is_general`);

CREATE TABLE IF NOT EXISTS `companies_options` (
  `company_id` int(10) unsigned NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text,
  PRIMARY KEY (`company_id`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Default company for existing data
INSERT INTO `companies` (`name`, `status`, `created`) SELECT 'Default company', 'T', NOW() FROM DUAL WHERE NOT EXISTS (SELECT `id` FROM `companies`);
SET @cid := (SELECT MIN(`id`) FROM `companies`);
UPDATE `calendars` SET `company_id` = @cid WHERE `company_id` IS NULL;
UPDATE `extras` SET `company_id` = @cid WHERE `company_id` IS NULL;
UPDATE `features` SET `company_id` = @cid WHERE `company_id` IS NULL;
UPDATE `discounts` SET `company_id` = @cid WHERE `company_id` IS NULL;
UPDATE `vouchers` SET `company_id` = @cid WHERE `company_id` IS NULL;

-- Current theme colours become the default company's colours
INSERT IGNORE INTO `companies_options` (`company_id`, `key`, `value`) SELECT @cid, `key`, `value` FROM `plugin_base_options` WHERE `foreign_id` = 1 AND `key` IN ('o_brand_color','o_accent_color','o_text_color','o_background_color','o_price_color','o_page_background_color','o_font_family','o_color_palette','o_background_month','o_color_month','o_background_nav','o_color_available');

-- Permissions that were missing (Features, Theme colours) - delete-first
DELETE FROM `plugin_auth_roles_permissions` WHERE `permission_id` IN (SELECT `id` FROM `plugin_auth_permissions` WHERE `key` LIKE 'pjAdminFeatures%' OR `key` = 'pjAdminOptions_pjActionColors');
DELETE FROM `plugin_auth_users_permissions` WHERE `permission_id` IN (SELECT `id` FROM `plugin_auth_permissions` WHERE `key` LIKE 'pjAdminFeatures%' OR `key` = 'pjAdminOptions_pjActionColors');
DELETE FROM `plugin_auth_permissions` WHERE `key` LIKE 'pjAdminFeatures%' OR `key` = 'pjAdminOptions_pjActionColors';
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, NULL, 'pjAdminFeatures');
SET @level_1_id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, @level_1_id, 'pjAdminFeatures_pjActionIndex');
SET @level_2_id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, @level_2_id, 'pjAdminFeatures_pjActionCreate');
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, @level_2_id, 'pjAdminFeatures_pjActionUpdate');
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, @level_2_id, 'pjAdminFeatures_pjActionDeleteFeature');
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, @level_2_id, 'pjAdminFeatures_pjActionDeleteFeatureBulk');
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, NULL, 'pjAdminOptions_pjActionColors');

-- Administrators get them
INSERT IGNORE INTO `plugin_auth_roles_permissions` (`role_id`, `permission_id`) SELECT 1, `id` FROM `plugin_auth_permissions` WHERE `key` LIKE 'pjAdminFeatures%' OR `key` = 'pjAdminOptions_pjActionColors';
INSERT IGNORE INTO `plugin_auth_users_permissions` (`user_id`, `permission_id`) SELECT x.`uid`, p.`id` FROM `plugin_auth_permissions` p, (SELECT DISTINCT `user_id` AS `uid` FROM `plugin_auth_users_permissions` WHERE `user_id` IN (SELECT `id` FROM `plugin_auth_users` WHERE `role_id` = 1)) x WHERE p.`key` LIKE 'pjAdminFeatures%' OR p.`key` = 'pjAdminOptions_pjActionColors';

-- Company role (4) manages its own workspace
DELETE FROM `plugin_auth_roles_permissions` WHERE `role_id` = 4;
INSERT IGNORE INTO `plugin_auth_roles_permissions` (`role_id`, `permission_id`) SELECT 4, `id` FROM `plugin_auth_permissions` WHERE `key` IN ('pjAdmin_pjActionIndex', 'pjBaseUsers_pjActionProfile', 'pjAdminCompanies_pjActionProfile', 'pjAdminOptions_pjActionNotifications', 'pjAdminOptions_pjActionColors', 'pjAdminOptions_pjActionPreview', 'pjAdminOptions_pjActionInstall') OR `key` LIKE 'pjAdminCalendars%' OR `key` LIKE 'pjAdminReservations%' OR `key` LIKE 'pjAdminExtras%' OR `key` LIKE 'pjAdminFeatures%' OR `key` LIKE 'pjAdminDiscounts%' OR `key` LIKE 'pjAdminVouchers%';

-- Labels

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblActiveWorkspace' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblActiveWorkspace';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblActiveWorkspace','backend','Workspace / eyebrow','script','2026-10-08 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Active workspace','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSetCompany' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSetCompany';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSetCompany','backend','Workspace / switch','script','2026-10-08 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Set company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblNoCompanyYet' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblNoCompanyYet';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblNoCompanyYet','backend','Workspace / none','script','2026-10-08 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No company yet','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyHasData' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyHasData';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyHasData','backend','Companies / cannot delete','script','2026-10-08 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','A company that still has properties (or the last remaining company) cannot be deleted.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgNoCompanySelected' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgNoCompanySelected';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgNoCompanySelected','backend','Workspace / none selected','script','2026-10-08 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Please add or select a company first.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminFeatures' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminFeatures';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminFeatures','backend','pjAdminFeatures','script','2026-10-08 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Features menu','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminFeatures_pjActionIndex' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminFeatures_pjActionIndex';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminFeatures_pjActionIndex','backend','pjAdminFeatures_pjActionIndex','script','2026-10-08 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Features list','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminFeatures_pjActionCreate' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminFeatures_pjActionCreate';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminFeatures_pjActionCreate','backend','pjAdminFeatures_pjActionCreate','script','2026-10-08 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Add feature','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminFeatures_pjActionUpdate' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminFeatures_pjActionUpdate';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminFeatures_pjActionUpdate','backend','pjAdminFeatures_pjActionUpdate','script','2026-10-08 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Update feature','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminFeatures_pjActionDeleteFeature' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminFeatures_pjActionDeleteFeature';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminFeatures_pjActionDeleteFeature','backend','pjAdminFeatures_pjActionDeleteFeature','script','2026-10-08 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Delete single feature','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminFeatures_pjActionDeleteFeatureBulk' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminFeatures_pjActionDeleteFeatureBulk';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminFeatures_pjActionDeleteFeatureBulk','backend','pjAdminFeatures_pjActionDeleteFeatureBulk','script','2026-10-08 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Delete multiple features','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminOptions_pjActionColors' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminOptions_pjActionColors';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminOptions_pjActionColors','backend','pjAdminOptions_pjActionColors','script','2026-10-08 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Theme colours','script');


SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblBackupScope' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblBackupScope';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblBackupScope','backend','Backup / scope','script','2026-10-08 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Back up','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblBackupScopeCompany' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblBackupScopeCompany';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblBackupScopeCompany','backend','Backup / this company','script','2026-10-08 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblBackupScopeSystem' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblBackupScopeSystem';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblBackupScopeSystem','backend','Backup / whole system','script','2026-10-08 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Whole system (all companies)','script');

ALTER TABLE `companies` ADD `hash` varchar(32) DEFAULT NULL AFTER `user_id`, ADD UNIQUE KEY `hash` (`hash`);
UPDATE `companies` SET `hash` = MD5(CONCAT(`id`, '-', UUID(), '-', RAND())) WHERE `hash` IS NULL;

ALTER TABLE `plugin_auth_users` ADD `company_id` int(10) unsigned DEFAULT NULL AFTER `role_id`, ADD KEY `company_id` (`company_id`);

-- Company logins -> their company
UPDATE `plugin_auth_users` u SET u.`company_id` = (SELECT c.`id` FROM `companies` c WHERE c.`user_id` = u.`id` LIMIT 1) WHERE u.`role_id` = 4;
-- Owners / editors -> company of their properties
UPDATE `plugin_auth_users` u SET u.`company_id` = (SELECT MIN(k.`company_id`) FROM `calendars` k WHERE k.`user_id` = u.`id`) WHERE u.`role_id` IN (2, 3) AND u.`company_id` IS NULL;
-- Anyone else who is not an administrator -> first company
SET @cid := (SELECT MIN(`id`) FROM `companies`);
UPDATE `plugin_auth_users` SET `company_id` = @cid WHERE `role_id` <> 1 AND `company_id` IS NULL;
-- Administrators are global
UPDATE `plugin_auth_users` SET `company_id` = NULL WHERE `role_id` = 1;

COMMIT;