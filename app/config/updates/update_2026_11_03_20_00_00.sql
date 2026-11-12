-- The website address and Vercel settings are not used any more (the website is deployed to Vercel from git;
-- the website address is PJ_WEBSITE_URL in app/config/config.inc.php). Removes them from System Options > API Keys.

DELETE FROM `plugin_base_options` WHERE `foreign_id` = 1 AND `key` = 'o_website_url';
DELETE FROM `plugin_base_options` WHERE `foreign_id` = 1 AND `key` = 'o_vercel_token';
DELETE FROM `plugin_base_options` WHERE `foreign_id` = 1 AND `key` = 'o_vercel_project_id';
DELETE FROM `plugin_base_options` WHERE `foreign_id` = 1 AND `key` = 'o_vercel_team_id';

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_website_url' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_website_url';

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_website_url_text' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_website_url_text';

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_vercel_token' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_vercel_token';

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_vercel_token_text' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_vercel_token_text';

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_vercel_project_id' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_vercel_project_id';

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_vercel_project_id_text' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_vercel_project_id_text';

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_vercel_team_id' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_vercel_team_id';

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_vercel_team_id_text' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_vercel_team_id_text';

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblApiFieldValue' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblApiFieldValue';

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkNoVercel' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkNoVercel';

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkManual' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkManual';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkManual','backend','lblSiteLinkManual','script','2026-11-03 20:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','When the DNS record is in place, we connect the domain to your website. This can take up to a day.','script');

UPDATE `plugin_base_options` SET value = MD5(RAND()) WHERE `key`='o_fields_index';
