
START TRANSACTION;


ALTER TABLE `calendars`
	ADD `bedrooms` varchar(10) DEFAULT NULL,
	ADD `bathrooms` varchar(10) DEFAULT NULL;


INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblBedrooms', 'backend', 'Label / Bedrooms', 'script', '2026-08-07 15:10:17');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblBedrooms' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Bedrooms', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblBathrooms', 'backend', 'Label / Bathrooms', 'script', '2026-08-07 15:10:17');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblBathrooms' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Bathrooms', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblDescription', 'backend', 'Label / Description', 'script', '2026-08-07 15:10:17');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblDescription' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Description', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblNotAvailable', 'backend', 'Label / Not available', 'script', '2026-08-07 15:10:17');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblNotAvailable' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Not available', 'script');


COMMIT;