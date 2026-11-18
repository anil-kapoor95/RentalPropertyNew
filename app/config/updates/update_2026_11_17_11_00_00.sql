-- Options > Currency & exchange rate: own page (labels), 2026-11-17. Run once.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_menu_currency' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_menu_currency';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_menu_currency','backend','Subscriptions / sub_menu_currency','script','2026-11-17 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Currency & exchange rate','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_currency_page_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_currency_page_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_currency_page_desc','backend','Subscriptions / sub_currency_page_desc','script','2026-11-17 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The exchange rate between US dollars and Indian rupees, and the billing countries whose companies pay in INR.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_strip' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_strip';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_strip','backend','Subscriptions / sub_fx_strip','script','2026-11-17 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Exchange rate: 1 USD = {RATE} · Countries paying in INR: {COUNTRIES}','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_strip_link' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_strip_link';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_strip_link','backend','Subscriptions / sub_fx_strip_link','script','2026-11-17 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Change in Options','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_save' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_save';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_save','backend','Subscriptions / sub_fx_save','script','2026-11-17 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Save changes','script');
