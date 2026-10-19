-- Options > AI settings: AI service + model + API key for the sign-up property description (pjAi).
-- Platform settings (shared options, foreign_id 1, hidden tab 99), administrators only.

-- Gemini key from Options > API Keys (update_2026_10_19_14/15) moves to this page; its value is kept.
INSERT IGNORE INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES
(1, 'o_gemini_api_key', 99, NULL, NULL, 'string', 1, 0, NULL),
(1, 'o_openai_api_key', 99, NULL, NULL, 'string', 2, 0, NULL),
(1, 'o_anthropic_api_key', 99, NULL, NULL, 'string', 3, 0, NULL),
(1, 'o_ai_provider', 99, 'gemini', NULL, 'string', 4, 0, NULL),
(1, 'o_ai_model_gemini', 99, 'gemini-2.5-flash', NULL, 'string', 5, 0, NULL),
(1, 'o_ai_model_openai', 99, NULL, NULL, 'string', 6, 0, NULL),
(1, 'o_ai_model_anthropic', 99, NULL, NULL, 'string', 7, 0, NULL);
UPDATE `plugin_base_options` SET `tab_id` = 99, `is_visible` = 0 WHERE `foreign_id` = 1 AND `key` = 'o_gemini_api_key';
DELETE FROM `plugin_base_options` WHERE `key` IN ('o_gemini_api_key', 'o_openai_api_key', 'o_anthropic_api_key', 'o_ai_provider', 'o_ai_model_gemini', 'o_ai_model_openai', 'o_ai_model_anthropic') AND `foreign_id` <> 1;
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id` IN (SELECT `id` FROM `plugin_base_fields` WHERE `key` IN ('plugin_base_opt_o_gemini_api_key', 'plugin_base_opt_o_gemini_api_key_text'));
DELETE FROM `plugin_base_fields` WHERE `key` IN ('plugin_base_opt_o_gemini_api_key', 'plugin_base_opt_o_gemini_api_key_text');

-- Permission (administrators)
DELETE FROM `plugin_auth_roles_permissions` WHERE `permission_id` IN (SELECT `id` FROM `plugin_auth_permissions` WHERE `key` = 'pjAdminOptions_pjActionAi');
DELETE FROM `plugin_auth_users_permissions` WHERE `permission_id` IN (SELECT `id` FROM `plugin_auth_permissions` WHERE `key` = 'pjAdminOptions_pjActionAi');
DELETE FROM `plugin_auth_permissions` WHERE `key` = 'pjAdminOptions_pjActionAi';
SET @parent := (SELECT `id` FROM `plugin_auth_permissions` WHERE `key` = 'pjAdminOptions' LIMIT 1);
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`, `is_shown`) VALUES (NULL, @parent, 'pjAdminOptions_pjActionAi', 'F');
INSERT IGNORE INTO `plugin_auth_roles_permissions` (`role_id`, `permission_id`) SELECT 1, `id` FROM `plugin_auth_permissions` WHERE `key` = 'pjAdminOptions_pjActionAi';
INSERT IGNORE INTO `plugin_auth_users_permissions` (`user_id`, `permission_id`) SELECT x.`uid`, p.`id` FROM `plugin_auth_permissions` p, (SELECT DISTINCT `user_id` AS `uid` FROM `plugin_auth_users_permissions` WHERE `user_id` IN (SELECT `id` FROM `plugin_auth_users` WHERE `role_id` = 1)) x WHERE p.`key` = 'pjAdminOptions_pjActionAi';

-- Labels
SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_menu' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_menu';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_menu','backend','AI settings / ai_menu','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','AI settings','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_title','backend','AI settings / ai_title','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','AI settings','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_desc','backend','AI settings / ai_desc','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The AI service and model that write the property description in the public sign-up. These are platform settings: only administrators see them and they apply to every sign-up.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_service' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_service';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_service','backend','AI settings / ai_service','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','AI service','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_service_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_service_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_service_hint','backend','AI settings / ai_service_hint','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The sign-up uses this service. Each service keeps its own key and model.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_api_key' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_api_key';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_api_key','backend','AI settings / ai_api_key','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','API key','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_key_saved' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_key_saved';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_key_saved','backend','AI settings / ai_key_saved','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','A key is saved. Leave the field empty to keep it.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_key_none' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_key_none';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_key_none','backend','AI settings / ai_key_none','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No key saved yet.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_get_key' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_get_key';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_get_key','backend','AI settings / ai_get_key','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Get a key','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_key_remove' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_key_remove';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_key_remove','backend','AI settings / ai_key_remove','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Remove the saved key','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_model' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_model';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_model','backend','AI settings / ai_model','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Model','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_model_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_model_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_model_hint','backend','AI settings / ai_model_hint','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Click Load models to choose from the models your key can use, or type a model id.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_btn_load' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_btn_load';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_btn_load','backend','AI settings / ai_btn_load','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Load models','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_btn_test' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_btn_test';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_btn_test','backend','AI settings / ai_btn_test','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Test','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_btn_save' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_btn_save';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_btn_save','backend','AI settings / ai_btn_save','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Save','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_saved' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_saved';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_saved','backend','AI settings / ai_saved','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','AI settings saved.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_err_model' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_err_model';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_err_model','backend','AI settings / ai_err_model','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','A model id may contain letters, digits and . _ - : / only. The other settings were saved.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_err_key' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_err_key';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_err_key','backend','AI settings / ai_err_key','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The API key looks invalid (no spaces, at least 10 characters). The other settings were saved.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_loading' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_loading';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_loading','backend','AI settings / ai_loading','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Loading models...','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_testing' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_testing';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_testing','backend','AI settings / ai_testing','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Testing, this can take up to a minute with slow models...','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_models_found' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_models_found';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_models_found','backend','AI settings / ai_models_found','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u models found. Click the Model field to choose one.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='ai_request_failed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='ai_request_failed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'ai_request_failed','backend','AI settings / ai_request_failed','script','2026-10-19 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The request failed. Please try again.','script');
