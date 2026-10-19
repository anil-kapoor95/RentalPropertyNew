-- Options > AI settings: model list (loaded from the service) and custom models per service; clearer saved-key texts.

INSERT IGNORE INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES
(1, 'o_ai_models_gemini', 99, NULL, NULL, 'text', 8, 0, NULL),
(1, 'o_ai_custom_models_gemini', 99, NULL, NULL, 'text', 11, 0, NULL),
(1, 'o_ai_models_openai', 99, NULL, NULL, 'text', 9, 0, NULL),
(1, 'o_ai_custom_models_openai', 99, NULL, NULL, 'text', 12, 0, NULL),
(1, 'o_ai_models_anthropic', 99, NULL, NULL, 'text', 10, 0, NULL),
(1, 'o_ai_custom_models_anthropic', 99, NULL, NULL, 'text', 13, 0, NULL);

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_saved_key' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_saved_key';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_saved_key','backend','AI settings / ai_saved_key','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','API key saved for %s: %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_err_db' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_err_db';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_err_db','backend','AI settings / ai_err_db','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%s could not be saved to the database. Check that the update update_2026_10_19_16_00_00.sql has run.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_key_placeholder' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_key_placeholder';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_key_placeholder','backend','AI settings / ai_key_placeholder','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Paste the API key','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_key_placeholder_saved' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_key_placeholder_saved';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_key_placeholder_saved','backend','AI settings / ai_key_placeholder_saved','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Saved: %s. Paste a new key only to replace it.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_key_saved_masked' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_key_saved_masked';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_key_saved_masked','backend','AI settings / ai_key_saved_masked','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Saved key: %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_choose_model' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_choose_model';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_choose_model','backend','AI settings / ai_choose_model','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose a model','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_custom_model' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_custom_model';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_custom_model','backend','AI settings / ai_custom_model','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Custom model...','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_custom_tag' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_custom_tag';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_custom_tag','backend','AI settings / ai_custom_tag','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','(custom)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_custom_placeholder' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_custom_placeholder';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_custom_placeholder','backend','AI settings / ai_custom_placeholder','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Type the model id, e.g. gemini-2.5-flash','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_models_none' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_models_none';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_models_none','backend','AI settings / ai_models_none','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No model list loaded yet: click Load models.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_models_listed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_models_listed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_models_listed','backend','AI settings / ai_models_listed','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u models from the service.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_custom_list' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_custom_list';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_custom_list','backend','AI settings / ai_custom_list','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Custom models:','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_remove_custom' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_remove_custom';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_remove_custom','backend','AI settings / ai_remove_custom','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','remove %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_model_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_model_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_model_hint','backend','AI settings / ai_model_hint','script','2026-10-19 17:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Pick one, or choose Custom model... to type any model id. Custom models stay in the list.','script');
