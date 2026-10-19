-- Gemini API key moves to Options > API Keys (administrator only, Admin workspace).
-- Replaces the Subscriptions > Settings fields of update_2026_10_19_12_00_00.sql.

INSERT IGNORE INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES
(1, 'o_gemini_api_key', 10, NULL, NULL, 'string', 2, 1, NULL);

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_gemini_api_key' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_gemini_api_key';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_opt_o_gemini_api_key','backend','Options / API Keys / Gemini','script','2026-10-19 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Google Gemini (AI property descriptions)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_gemini_api_key_text' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_gemini_api_key_text';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_opt_o_gemini_api_key_text','backend','Options / API Keys / Gemini info','script','2026-10-19 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Used by the sign-up to write the property description. Get a key at Google AI Studio (aistudio.google.com). Only administrators see this key.','script');

-- The Subscriptions > Settings fields are no longer used
DELETE FROM `subscription_settings` WHERE `key` IN ('gemini_api_key', 'gemini_model');
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id` IN (SELECT `id` FROM `plugin_base_fields` WHERE `key` IN ('sub_ai_title','sub_gemini_key','sub_gemini_key_hint','sub_gemini_key_saved','sub_gemini_key_clear','sub_gemini_model'));
DELETE FROM `plugin_base_fields` WHERE `key` IN ('sub_ai_title','sub_gemini_key','sub_gemini_key_hint','sub_gemini_key_saved','sub_gemini_key_clear','sub_gemini_model');
