-- Website branding line (System Options > Visual & Branding): text and link of the line shown on hosts' websites
-- when their plan shows it (Subscriptions > Plans > "Show 'Designed by PHPJabbers' on the website").

INSERT INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES (1, 'o_website_branding_text', 2, 'Designed by PHPJabbers', NULL, 'string', 30, 1, NULL) ON DUPLICATE KEY UPDATE tab_id = 2;
INSERT INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES (1, 'o_website_branding_footer', 2, 'Website by PHPJabbers', NULL, 'string', 31, 1, NULL) ON DUPLICATE KEY UPDATE tab_id = 2;
INSERT INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES (1, 'o_website_branding_url', 2, 'https://www.phpjabbers.com', NULL, 'string', 32, 1, NULL) ON DUPLICATE KEY UPDATE tab_id = 2;

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_website_branding_text' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_website_branding_text';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_opt_o_website_branding_text','backend','plugin_base_opt_o_website_branding_text','plugin','2026-11-03 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Websites: branding line (top of the page)','plugin');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_website_branding_footer' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_website_branding_footer';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_opt_o_website_branding_footer','backend','plugin_base_opt_o_website_branding_footer','plugin','2026-11-03 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Websites: branding text in the footer','plugin');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_website_branding_url' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_opt_o_website_branding_url';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_opt_o_website_branding_url','backend','plugin_base_opt_o_website_branding_url','plugin','2026-11-03 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Websites: branding link (optional)','plugin');

UPDATE `plugin_base_options` SET value = MD5(RAND()) WHERE `key`='o_fields_index';
