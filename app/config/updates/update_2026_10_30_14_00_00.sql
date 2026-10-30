-- System Options > General (platform defaults) and Company options > General (active company): note under the title.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblGeneralPlatformNote' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblGeneralPlatformNote';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblGeneralPlatformNote', 'backend', 'Options / General: platform note', 'script', '2026-10-30 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Platform defaults: a new company starts with these values. Each company changes its own in Company options > General.', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblGeneralCompanyNote' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblGeneralCompanyNote';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblGeneralCompanyNote', 'backend', 'Options / General: company note', 'script', '2026-10-30 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Settings of the active company (its prices, dates and currency).', 'script');
