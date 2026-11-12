-- Website links: a unique stored link (slug) per property and company, and the company's own domain (Vercel).

ALTER TABLE `calendars` ADD COLUMN IF NOT EXISTS `slug` varchar(80) DEFAULT NULL;
ALTER TABLE `calendars` ADD UNIQUE INDEX IF NOT EXISTS `calendar_slug` (`slug`);
ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `slug` varchar(80) DEFAULT NULL;
ALTER TABLE `companies` ADD UNIQUE INDEX IF NOT EXISTS `company_slug` (`slug`);
ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `custom_domain` varchar(190) DEFAULT NULL;
ALTER TABLE `companies` ADD UNIQUE INDEX IF NOT EXISTS `company_domain` (`custom_domain`);
ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `domain_status` varchar(20) DEFAULT NULL;
ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `domain_message` varchar(255) DEFAULT NULL;
ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `domain_checked` datetime DEFAULT NULL;


SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkTitle' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkTitle';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkTitle','backend','lblSiteLinkTitle','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Website address','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDesc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDesc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkDesc','backend','lblSiteLinkDesc','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The link of your booking website. With the "Custom domain" feature of your plan you can also use your own domain.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDefault' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDefault';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkDefault','backend','lblSiteLinkDefault','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your link','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDomain' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDomain';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkDomain','backend','lblSiteLinkDomain','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your own domain','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDomainHint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDomainHint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkDomainHint','backend','lblSiteLinkDomainHint','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','For example www.myvilla.com (without https://). Leave empty to use only your link above.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkLocked' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkLocked';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkLocked','backend','lblSiteLinkLocked','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your own domain comes with a plan that includes "Custom domain".','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDns' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDns';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkDns','backend','lblSiteLinkDns','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','At your domain provider, add this DNS record:','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkCheck' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkCheck';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkCheck','backend','lblSiteLinkCheck','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Check now','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDnsType' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDnsType';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkDnsType','backend','lblSiteLinkDnsType','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Type','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDnsName' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDnsName';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkDnsName','backend','lblSiteLinkDnsName','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Name / Host','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDnsValue' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkDnsValue';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkDnsValue','backend','lblSiteLinkDnsValue','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Value / Points to','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkStatus_active' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkStatus_active';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkStatus_active','backend','lblSiteLinkStatus_active','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Connected','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkStatus_pending' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkStatus_pending';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkStatus_pending','backend','lblSiteLinkStatus_pending','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Waiting for the DNS record','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkStatus_error' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkStatus_error';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkStatus_error','backend','lblSiteLinkStatus_error','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Not connected','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkSaved' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkSaved';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkSaved','backend','lblSiteLinkSaved','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Domain saved.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkTaken' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkTaken';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkTaken','backend','lblSiteLinkTaken','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This domain is already used by another company.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkInvalid' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkInvalid';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkInvalid','backend','lblSiteLinkInvalid','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Enter a valid domain, e.g. www.myvilla.com','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkManual' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkManual';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkManual','backend','lblSiteLinkManual','script','2026-11-03 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','When the DNS record is in place, we connect the domain to your website. This can take up to a day.','script');

UPDATE `plugin_base_options` SET value = MD5(RAND()) WHERE `key`='o_fields_index';
