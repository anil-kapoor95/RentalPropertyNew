START TRANSACTION;

-- System Options per company: countries per company + scope labels.
-- RUN ONCE (it alters a table). Table names are unprefixed; the updater adds the prefix.

-- ALTER TABLE `plugin_base_countries` ADD `company_id` int(10) unsigned DEFAULT NULL, ADD KEY `company_id` (`company_id`);

-- CREATE TABLE IF NOT EXISTS `companies_countries` (
--   `company_id` int(10) unsigned NOT NULL,
--   `country_id` int(10) unsigned NOT NULL,
--   `status` enum('T','F') NOT NULL DEFAULT 'T',
--   PRIMARY KEY (`company_id`, `country_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -- Labels

-- SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblScopeCompany' LIMIT 1);
-- DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
-- DELETE FROM `plugin_base_fields` WHERE `key`='lblScopeCompany';
-- INSERT INTO `plugin_base_fields` VALUES (NULL,'lblScopeCompany','backend','System options / scope','script','2026-10-08 14:00:00');
-- SET @id := (SELECT LAST_INSERT_ID());
-- INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','These settings apply to company:','script');

-- SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblScopeLanguages' LIMIT 1);
-- DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
-- DELETE FROM `plugin_base_fields` WHERE `key`='lblScopeLanguages';
-- INSERT INTO `plugin_base_fields` VALUES (NULL,'lblScopeLanguages','backend','System options / scope languages','script','2026-10-08 14:00:00');
-- SET @id := (SELECT LAST_INSERT_ID());
-- INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Label texts you change here are saved for this company only. The list of languages is shared by all companies.','script');

-- SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblScopeLoginCaptcha' LIMIT 1);
-- DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
-- DELETE FROM `plugin_base_fields` WHERE `key`='lblScopeLoginCaptcha';
-- INSERT INTO `plugin_base_fields` VALUES (NULL,'lblScopeLoginCaptcha','backend','System options / scope login captcha','script','2026-10-08 14:00:00');
-- SET @id := (SELECT LAST_INSERT_ID());
-- INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The captcha on the admin login screen is shared by all companies.','script');

-- SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblScopeCron' LIMIT 1);
-- DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
-- DELETE FROM `plugin_base_fields` WHERE `key`='lblScopeCron';
-- INSERT INTO `plugin_base_fields` VALUES (NULL,'lblScopeCron','backend','System options / scope cron','script','2026-10-08 14:00:00');
-- SET @id := (SELECT LAST_INSERT_ID());
-- INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Cron jobs are shared by all companies: every job processes all companies (e.g. automatic back-ups are made per company).','script');

-- SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblUserCompany' LIMIT 1);
-- DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
-- DELETE FROM `plugin_base_fields` WHERE `key`='lblUserCompany';
-- INSERT INTO `plugin_base_fields` VALUES (NULL,'lblUserCompany','backend','Users / company','script','2026-10-08 15:00:00');
-- SET @id := (SELECT LAST_INSERT_ID());
-- INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company','script');

-- SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblUserAllCompanies' LIMIT 1);
-- DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
-- DELETE FROM `plugin_base_fields` WHERE `key`='lblUserAllCompanies';
-- INSERT INTO `plugin_base_fields` VALUES (NULL,'lblUserAllCompanies','backend','Users / administrator scope','script','2026-10-08 15:00:00');
-- SET @id := (SELECT LAST_INSERT_ID());
-- INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','All companies (administrator)','script');

-- SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblFilterAllCompanies' LIMIT 1);
-- DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
-- DELETE FROM `plugin_base_fields` WHERE `key`='lblFilterAllCompanies';
-- INSERT INTO `plugin_base_fields` VALUES (NULL,'lblFilterAllCompanies','backend','Users / filter all','script','2026-10-08 15:00:00');
-- SET @id := (SELECT LAST_INSERT_ID());
-- INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','All companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyLogoTooBig' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyLogoTooBig';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyLogoTooBig','backend','Companies / logo too big','script','2026-10-08 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The logo is larger than the server allows (PHP upload_max_filesize). Please use a smaller image.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyLogoFolder' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyLogoFolder';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyLogoFolder','backend','Companies / logo folder','script','2026-10-08 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The logo could not be saved: the folder app/web/upload/ is not writable. Please set its permissions to 777.','script');

COMMIT;