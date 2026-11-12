-- Social links of the website (System Options > Visual & Branding, administrators only).

INSERT INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES (1, 'o_social_instagram', 2, '', NULL, 'string', 50, 1, NULL) ON DUPLICATE KEY UPDATE tab_id = 2;
INSERT INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES (1, 'o_social_facebook', 2, '', NULL, 'string', 51, 1, NULL) ON DUPLICATE KEY UPDATE tab_id = 2;
INSERT INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES (1, 'o_social_linkedin', 2, '', NULL, 'string', 52, 1, NULL) ON DUPLICATE KEY UPDATE tab_id = 2;
INSERT INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES (1, 'o_social_x', 2, '', NULL, 'string', 53, 1, NULL) ON DUPLICATE KEY UPDATE tab_id = 2;
INSERT INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES (1, 'o_social_youtube', 2, '', NULL, 'string', 54, 1, NULL) ON DUPLICATE KEY UPDATE tab_id = 2;

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_social_instagram' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_social_instagram';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_opt_o_social_instagram','backend','plugin_base_opt_o_social_instagram','plugin','2026-11-03 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Instagram','plugin');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_social_facebook' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_social_facebook';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_opt_o_social_facebook','backend','plugin_base_opt_o_social_facebook','plugin','2026-11-03 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Facebook','plugin');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_social_linkedin' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_social_linkedin';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_opt_o_social_linkedin','backend','plugin_base_opt_o_social_linkedin','plugin','2026-11-03 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','LinkedIn','plugin');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_social_x' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_social_x';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_opt_o_social_x','backend','plugin_base_opt_o_social_x','plugin','2026-11-03 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','X (Twitter)','plugin');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_social_youtube' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_social_youtube';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_opt_o_social_youtube','backend','plugin_base_opt_o_social_youtube','plugin','2026-11-03 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','YouTube','plugin');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSocialTitle' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSocialTitle';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSocialTitle','backend','lblSocialTitle','plugin','2026-11-03 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Social links','plugin');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSocialDesc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSocialDesc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSocialDesc','backend','lblSocialDesc','plugin','2026-11-03 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Links to the social pages of the website. They are shown wherever the website shows social icons (Contact page, footer). Leave a link empty to hide its icon.','plugin');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSocialInvalid' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSocialInvalid';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSocialInvalid','backend','lblSocialInvalid','plugin','2026-11-03 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Enter a full link starting with https://','plugin');

UPDATE `plugin_base_options` SET value = MD5(RAND()) WHERE `key`='o_fields_index';
