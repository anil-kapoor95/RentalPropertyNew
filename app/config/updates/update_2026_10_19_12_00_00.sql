-- Public sign-up: property, photos, AI description (Gemini) and theme steps.

ALTER TABLE `company_signups`
  ADD `calendar_id` int(10) unsigned DEFAULT NULL AFTER `company_id`,
  ADD `ai_generations` smallint(5) unsigned NOT NULL DEFAULT '0' AFTER `step`;

-- Gemini settings (Subscriptions > Settings, administrator only)
INSERT IGNORE INTO `subscription_settings` (`key`, `value`) VALUES ('gemini_api_key', ''), ('gemini_model', 'gemini-2.5-flash');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_ai_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_ai_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_ai_title','backend','Subscriptions / sub_ai_title','script','2026-10-19 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','AI property descriptions (sign-up)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gemini_key' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gemini_key';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gemini_key','backend','Subscriptions / sub_gemini_key','script','2026-10-19 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Google Gemini API key','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gemini_key_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gemini_key_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gemini_key_hint','backend','Subscriptions / sub_gemini_key_hint','script','2026-10-19 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Used to write the property description during sign-up. Leave empty to keep the saved key.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gemini_key_saved' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gemini_key_saved';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gemini_key_saved','backend','Subscriptions / sub_gemini_key_saved','script','2026-10-19 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','A key is saved.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gemini_key_clear' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gemini_key_clear';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gemini_key_clear','backend','Subscriptions / sub_gemini_key_clear','script','2026-10-19 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Remove the saved key','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gemini_model' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gemini_model';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gemini_model','backend','Subscriptions / sub_gemini_model','script','2026-10-19 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Gemini model','script');
