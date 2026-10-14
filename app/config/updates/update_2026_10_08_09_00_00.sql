START TRANSACTION;

CREATE TABLE IF NOT EXISTS `companies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `registration_no` varchar(100) DEFAULT NULL,
  `tax_no` varchar(100) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address_1` varchar(255) DEFAULT NULL,
  `address_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `country_id` int(10) unsigned DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text,
  `commission` decimal(5,2) NOT NULL DEFAULT '0.00',
  `notes` text,
  `status` enum('T','F') NOT NULL DEFAULT 'T',
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Role 4 = Company (backend login allowed)
INSERT IGNORE INTO `plugin_auth_roles` (`id`, `role`, `is_backend`, `is_admin`, `status`) VALUES (4, 'Company', 'T', 'T', 'T');

-- Permission tree (delete-first; also clears grants that pointed at old ids)
DELETE FROM `plugin_auth_roles_permissions` WHERE `permission_id` IN (SELECT `id` FROM `plugin_auth_permissions` WHERE `key` LIKE 'pjAdminCompanies%');
DELETE FROM `plugin_auth_users_permissions` WHERE `permission_id` IN (SELECT `id` FROM `plugin_auth_permissions` WHERE `key` LIKE 'pjAdminCompanies%');
DELETE FROM `plugin_auth_permissions` WHERE `key` LIKE 'pjAdminCompanies%';

INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, NULL, 'pjAdminCompanies');
SET @level_1_id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, @level_1_id, 'pjAdminCompanies_pjActionIndex');
SET @level_2_id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, @level_2_id, 'pjAdminCompanies_pjActionCreate');
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, @level_2_id, 'pjAdminCompanies_pjActionUpdate');
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, @level_2_id, 'pjAdminCompanies_pjActionDeleteCompany');
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, @level_2_id, 'pjAdminCompanies_pjActionDeleteCompanyBulk');
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`) VALUES (NULL, NULL, 'pjAdminCompanies_pjActionProfile');

-- Administrator role (1): full Companies management (not the company-only profile page)
INSERT IGNORE INTO `plugin_auth_roles_permissions` (`role_id`, `permission_id`) SELECT 1, `id` FROM `plugin_auth_permissions` WHERE `key` LIKE 'pjAdminCompanies%' AND `key` <> 'pjAdminCompanies_pjActionProfile';
-- Administrators that have user-level permission overrides get the same
-- (every table name follows FROM so the updater can prefix it)
INSERT IGNORE INTO `plugin_auth_users_permissions` (`user_id`, `permission_id`) SELECT x.`uid`, p.`id` FROM `plugin_auth_permissions` p, (SELECT DISTINCT `user_id` AS `uid` FROM `plugin_auth_users_permissions` WHERE `user_id` IN (SELECT `id` FROM `plugin_auth_users` WHERE `role_id` = 1)) x WHERE p.`key` LIKE 'pjAdminCompanies%' AND p.`key` <> 'pjAdminCompanies_pjActionProfile';

-- Company role (4): dashboard, own user profile, own company profile
-- (re-run safe: role 4 permissions set by the administrator are kept; it starts empty, so INSERT IGNORE is enough)
INSERT IGNORE INTO `plugin_auth_roles_permissions` (`role_id`, `permission_id`) SELECT 4, `id` FROM `plugin_auth_permissions` WHERE `key` IN ('pjAdmin_pjActionIndex', 'pjBaseUsers_pjActionProfile', 'pjAdminCompanies_pjActionProfile');

-- Labels

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_role_arr_ARRAY_4' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_role_arr_ARRAY_4';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_role_arr_ARRAY_4','arrays','Role / Company','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='menuCompanies' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='menuCompanies';
INSERT INTO `plugin_base_fields` VALUES (NULL,'menuCompanies','backend','Menu / Companies','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='menuMyCompany' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='menuMyCompany';
INSERT INTO `plugin_base_fields` VALUES (NULL,'menuMyCompany','backend','Menu / My company','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','My company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='infoCompaniesTitle' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='infoCompaniesTitle';
INSERT INTO `plugin_base_fields` VALUES (NULL,'infoCompaniesTitle','backend','Companies / list title','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='infoCompaniesDesc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='infoCompaniesDesc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'infoCompaniesDesc','backend','Companies / list desc','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Manage the companies on your platform. Link each company to a user account with the Company role so it can log in and manage its own profile.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='infoAddCompanyTitle' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='infoAddCompanyTitle';
INSERT INTO `plugin_base_fields` VALUES (NULL,'infoAddCompanyTitle','backend','Companies / add title','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Add company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='infoAddCompanyDesc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='infoAddCompanyDesc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'infoAddCompanyDesc','backend','Companies / add desc','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Fill in the company details below. Only the company name is required.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='infoUpdateCompanyTitle' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='infoUpdateCompanyTitle';
INSERT INTO `plugin_base_fields` VALUES (NULL,'infoUpdateCompanyTitle','backend','Companies / update title','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Update company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='infoUpdateCompanyDesc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='infoUpdateCompanyDesc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'infoUpdateCompanyDesc','backend','Companies / update desc','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Change the company details and click Save.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='infoMyCompanyTitle' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='infoMyCompanyTitle';
INSERT INTO `plugin_base_fields` VALUES (NULL,'infoMyCompanyTitle','backend','Companies / profile title','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','My company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='infoMyCompanyDesc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='infoMyCompanyDesc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'infoMyCompanyDesc','backend','Companies / profile desc','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Keep your company details up to date. Fields managed by the administrator are not shown here.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='btnAddCompany' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='btnAddCompany';
INSERT INTO `plugin_base_fields` VALUES (NULL,'btnAddCompany','backend','Companies / add button','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Add company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanySectionGeneral' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanySectionGeneral';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanySectionGeneral','backend','Companies / section general','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company details','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanySectionContact' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanySectionContact';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanySectionContact','backend','Companies / section contact','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Contact','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanySectionAddress' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanySectionAddress';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanySectionAddress','backend','Companies / section address','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Address','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanySectionAdmin' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanySectionAdmin';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanySectionAdmin','backend','Companies / section admin','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Administration','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyName' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyName';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyName','backend','Companies / name','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company name','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyLegalName' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyLegalName';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyLegalName','backend','Companies / legal name','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Legal name','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyRegNo' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyRegNo';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyRegNo','backend','Companies / registration no','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Registration / licence no.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyTaxNo' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyTaxNo';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyTaxNo','backend','Companies / tax no','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Tax / VAT no.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyContact' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyContact';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyContact','backend','Companies / contact person','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Contact person','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyEmail' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyEmail';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyEmail','backend','Companies / email','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Email','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyPhone' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyPhone';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyPhone','backend','Companies / phone','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Phone','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyWebsite' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyWebsite';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyWebsite','backend','Companies / website','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Website','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyAddress1' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyAddress1';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyAddress1','backend','Companies / address 1','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Address line 1','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyAddress2' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyAddress2';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyAddress2','backend','Companies / address 2','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Address line 2','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyCity' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyCity';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyCity','backend','Companies / city','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','City','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyState' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyState';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyState','backend','Companies / state','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','State / region','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyZip' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyZip';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyZip','backend','Companies / zip','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Postcode / ZIP','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyCountry' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyCountry';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyCountry','backend','Companies / country','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Country','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyLogo' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyLogo';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyLogo','backend','Companies / logo','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Logo','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyLogoHint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyLogoHint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyLogoHint','backend','Companies / logo hint','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','JPG, PNG, GIF or WEBP.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyRemoveLogo' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyRemoveLogo';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyRemoveLogo','backend','Companies / remove logo','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Remove current logo','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyDescription' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyDescription';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyDescription','backend','Companies / description','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','About the company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyCommission' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyCommission';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyCommission','backend','Companies / commission','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Commission (%)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyNotes' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyNotes';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyNotes','backend','Companies / internal notes','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Internal notes (admin only)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyUser' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyUser';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyUser','backend','Companies / login account','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Login account','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyUserNote' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyUserNote';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyUserNote','backend','Companies / login account note','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Only users with the Company role that are not linked to another company are listed.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyNoUser' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyNoUser';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyNoUser','backend','Companies / no login','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','-- Not linked --','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyStatus' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyStatus';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyStatus','backend','Companies / status','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Status','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyAdded' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyAdded';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyAdded','backend','Companies / msg added','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company has been added.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanySaved' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanySaved';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanySaved','backend','Companies / msg saved','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company has been saved.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyError' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyError';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyError','backend','Companies / msg error','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company could not be saved. Please check the form and try again.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyNotFound' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyNotFound';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyNotFound','backend','Companies / msg not found','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company was not found.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyNoProfile' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyNoProfile';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyNoProfile','backend','Companies / msg no profile','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No company is linked to your account yet. Please contact the administrator.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyUserTaken' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyUserTaken';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyUserTaken','backend','Companies / msg user taken','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The selected login account is already linked to another company, or it does not have the Company role.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyInvalidLogo' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyInvalidLogo';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyInvalidLogo','backend','Companies / msg invalid logo','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The logo could not be uploaded. Use a JPG, PNG, GIF or WEBP image.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminCompanies','backend','pjAdminCompanies','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies menu','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies_pjActionIndex' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies_pjActionIndex';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminCompanies_pjActionIndex','backend','pjAdminCompanies_pjActionIndex','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies list','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies_pjActionCreate' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies_pjActionCreate';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminCompanies_pjActionCreate','backend','pjAdminCompanies_pjActionCreate','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Add company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies_pjActionUpdate' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies_pjActionUpdate';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminCompanies_pjActionUpdate','backend','pjAdminCompanies_pjActionUpdate','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Update company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies_pjActionDeleteCompany' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies_pjActionDeleteCompany';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminCompanies_pjActionDeleteCompany','backend','pjAdminCompanies_pjActionDeleteCompany','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Delete single company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies_pjActionDeleteCompanyBulk' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies_pjActionDeleteCompanyBulk';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminCompanies_pjActionDeleteCompanyBulk','backend','pjAdminCompanies_pjActionDeleteCompanyBulk','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Delete multiple companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies_pjActionProfile' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='pjAdminCompanies_pjActionProfile';
INSERT INTO `plugin_base_fields` VALUES (NULL,'pjAdminCompanies_pjActionProfile','backend','pjAdminCompanies_pjActionProfile','script','2026-10-08 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','My company (Company role)','script');


SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyRequired' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyRequired';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyRequired','backend','Companies / msg required','script','2026-10-08 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Please fill in all required fields (marked with *).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='infoAddCompanyDesc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='infoAddCompanyDesc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'infoAddCompanyDesc','backend','Companies / add desc','script','2026-10-08 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Fill in the company details below. Fields marked with * are required.','script');

COMMIT;
