-- Subscriptions: a company without a plan has no full access; new companies get the "plan for new companies";
-- plans decide whether the website shows "Designed by PHPJabbers". System options > API Keys: Google client ID.
-- Options > Sign-up settings: limits, token life time and AI tones (platform options, hidden tab 99).

ALTER TABLE `plans` ADD COLUMN IF NOT EXISTS `show_branding` tinyint(1) unsigned NOT NULL DEFAULT '1' AFTER `is_public`;

INSERT INTO `subscription_settings` (`key`, `value`) VALUES ('unassigned_mode', 'restricted') ON DUPLICATE KEY UPDATE value = 'restricted';
DROP TABLE IF EXISTS pj_mig_plan;
CREATE TABLE pj_mig_plan AS SELECT `id` FROM `plans` WHERE `code` = 'starter' AND `status` = 'T' LIMIT 1;
INSERT INTO `subscription_settings` (`key`, `value`) SELECT 'default_plan_id', `id` FROM pj_mig_plan ON DUPLICATE KEY UPDATE value = value;
DROP TABLE pj_mig_plan;

INSERT IGNORE INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES
(1, 'o_google_client_id', 10, '11794092662-fdap1o3c1be99hdu17j64j5vgth9fg19.apps.googleusercontent.com', NULL, 'string', 2, 1, NULL),
(1, 'o_signup_max_photos', 99, '20', NULL, 'int', 20, 0, NULL),
(1, 'o_signup_max_ai_generations', 99, '30', NULL, 'int', 21, 0, NULL),
(1, 'o_signup_max_amenities', 99, '100', NULL, 'int', 22, 0, NULL),
(1, 'o_signup_max_amenity_length', 99, '80', NULL, 'int', 23, 0, NULL),
(1, 'o_signup_amenity_category', 99, '2', NULL, 'int', 24, 0, NULL),
(1, 'o_signup_max_faqs', 99, '8', NULL, 'int', 25, 0, NULL),
(1, 'o_signup_token_days', 99, '30', NULL, 'int', 26, 0, NULL),
(1, 'o_signup_tone_warm', 99, NULL, NULL, 'text', 27, 0, NULL),
(1, 'o_signup_tone_premium', 99, NULL, NULL, 'text', 28, 0, NULL),
(1, 'o_signup_tone_simple', 99, NULL, NULL, 'text', 29, 0, NULL);
DELETE FROM `plugin_base_options` WHERE `foreign_id` <> 1 AND (`key` = 'o_google_client_id' OR `key` LIKE 'o\_signup\_%');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_google_client_id' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_google_client_id';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_opt_o_google_client_id','backend','plugin_base_opt_o_google_client_id','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Google sign-in (client ID)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_google_client_id_text' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_google_client_id_text';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_opt_o_google_client_id_text','backend','plugin_base_opt_o_google_client_id_text','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','OAuth client ID of "Continue with Google" on the sign-up website (Google Cloud Console > APIs & Services > Credentials). Leave empty to hide the Google button.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_client_id' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_client_id';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_client_id','backend','plugin_base_client_id','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Client ID','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_default_plan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_default_plan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_default_plan','backend','sub_default_plan','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plan for new companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_default_plan_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_default_plan_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_default_plan_hint','backend','sub_default_plan_hint','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Every new company gets this plan (active, no end date); a company without a plan has no full access.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_default_plan_badge' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_default_plan_badge';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_default_plan_badge','backend','sub_default_plan_badge','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','New companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_show_branding' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_show_branding';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_show_branding','backend','sub_show_branding','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Show "Designed by PHPJabbers" on the website','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_show_branding_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_show_branding_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_show_branding_hint','backend','sub_show_branding_hint','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Untick to hide the line on the websites of companies with this plan.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='menuSignupSettings' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='menuSignupSettings';
INSERT INTO `plugin_base_fields` VALUES (NULL,'menuSignupSettings','backend','menuSignupSettings','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Sign-up settings','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_title','backend','signup_cfg_title','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Sign-up settings','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_desc','backend','signup_cfg_desc','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Limits of the sign-up on the website and how the AI writes the property texts. Empty = default value.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_limits' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_limits';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_limits','backend','signup_cfg_limits','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Limits','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_max_photos' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_max_photos';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_max_photos','backend','signup_cfg_max_photos','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Photos per property','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_max_ai_generations' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_max_ai_generations';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_max_ai_generations','backend','signup_cfg_max_ai_generations','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','AI texts per sign-up','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_max_amenities' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_max_amenities';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_max_amenities','backend','signup_cfg_max_amenities','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Amenities per property','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_max_amenity_length' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_max_amenity_length';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_max_amenity_length','backend','signup_cfg_max_amenity_length','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Longest amenity name (characters)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_amenity_category' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_amenity_category';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_amenity_category','backend','signup_cfg_amenity_category','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Feature category of new amenities','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_max_faqs' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_max_faqs';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_max_faqs','backend','signup_cfg_max_faqs','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Questions (FAQ) per property','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_token_days' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_token_days';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_token_days','backend','signup_cfg_token_days','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Sign-up link valid for (days)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_tones' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_tones';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_tones','backend','signup_cfg_tones','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','AI writing tones','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_tones_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_tones_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_tones_hint','backend','signup_cfg_tones_hint','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Instruction given to the AI for each tone the host can choose. Empty = default text.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_tone_warm' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_tone_warm';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_tone_warm','backend','signup_cfg_tone_warm','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Warm','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_tone_premium' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_tone_premium';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_tone_premium','backend','signup_cfg_tone_premium','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Premium','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_tone_simple' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_tone_simple';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_tone_simple','backend','signup_cfg_tone_simple','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Simple','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_default' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_default';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_default','backend','signup_cfg_default','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Default: %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_range' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_range';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_range','backend','signup_cfg_range','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Between %1$s and %2$s.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_saved' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_saved';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_saved','backend','signup_cfg_saved','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Sign-up settings saved.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='signup_cfg_fixed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='signup_cfg_fixed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'signup_cfg_fixed','backend','signup_cfg_fixed','script','2026-11-02 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The steps of the sign-up and the property types are part of the website design and are not set here.','script');
