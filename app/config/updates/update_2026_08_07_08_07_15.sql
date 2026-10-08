
START TRANSACTION;


INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'propertyICalFeed', 'backend', 'Label / Property Ical Feed', 'script', '2026-08-07 15:10:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'propertyICalFeed' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Property Ical Feed', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'infoAddICalFeeds', 'backend', 'Label / Add Ical Feeds', 'script', '2026-08-07 15:10:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'infoAddICalFeeds' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Add Ical Feeds', 'script');


DELETE FROM `plugin_base_cron_jobs` WHERE `controller` = 'pjCron' AND `action` = 'pjActionSyncIcalUrls';
INSERT INTO `plugin_base_cron_jobs` (`name`, `controller`, `action`, `interval`, `period`, `is_active`) VALUES
('Import reservations from ical urls for properties', 'pjCron', 'pjActionSyncIcalUrls', 1, 'minute', 1);

COMMIT;