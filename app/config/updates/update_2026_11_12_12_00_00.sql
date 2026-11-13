-- Subscription audit fixes (2026-11-12)
-- * expired companies: website suspended + back office read-only (expired_mode 'suspend')
-- * plan picked on the pricing page is kept with the sign-up (its free trial starts with the company)

UPDATE `subscription_settings` SET `value` = 'suspend' WHERE `key` = 'expired_mode' AND `value` = 'read_only';
INSERT IGNORE INTO `subscription_settings` (`key`, `value`) VALUES ('expired_mode', 'suspend');

ALTER TABLE `company_signups` ADD COLUMN `plan_code` varchar(40) DEFAULT NULL AFTER `calendar_id`;

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subReadOnly' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subReadOnly';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subReadOnly','backend','Subscriptions / subReadOnly','script','2026-11-12 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your plan has expired: your account is read-only. Renew or choose a plan on My plan to make changes again.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subReadOnlyShort' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subReadOnlyShort';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subReadOnlyShort','backend','Subscriptions / subReadOnlyShort','script','2026-11-12 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plan expired: read-only, and your website takes no bookings.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subRenewNow' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subRenewNow';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subRenewNow','backend','Subscriptions / subRenewNow','script','2026-11-12 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Renew now','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subFreeSwitched' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subFreeSwitched';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subFreeSwitched','backend','Subscriptions / subFreeSwitched','script','2026-11-12 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','You are now on the free plan.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_coming_soon' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_coming_soon';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_coming_soon','backend','Subscriptions / sub_coming_soon','script','2026-11-12 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coming soon','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_choose_free' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_choose_free';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_choose_free','backend','Subscriptions / sub_btn_choose_free','script','2026-11-12 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Switch to this plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subFrontSuspended' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subFrontSuspended';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subFrontSuspended','backend','Subscriptions / subFrontSuspended','script','2026-11-12 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This property is not taking bookings at the moment. Please contact the host.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrCurrency' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrCurrency';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrCurrency','backend','Subscriptions / subErrCurrency','script','2026-11-12 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This plan cannot be paid online in its currency. Please contact us.','script');
