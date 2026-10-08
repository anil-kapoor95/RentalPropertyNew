
START TRANSACTION;


ALTER TABLE `calendars`
	ADD `address_country` int(11) DEFAULT NULL,
	ADD `address_state` varchar(255) DEFAULT NULL,
	ADD `address_zip` varchar(255) DEFAULT NULL,
	ADD `lat` varchar(50) DEFAULT NULL,
	ADD `lng` varchar(50) DEFAULT NULL,
	ADD `show_googlemap` enum('T','F') DEFAULT 'T';


CREATE TABLE IF NOT EXISTS `calendar_photos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `calendar_id` int(10) unsigned DEFAULT NULL,
  `hash` varchar(50) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `small_path` varchar(255) DEFAULT NULL,
  `large_path` varchar(255) DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_id` (`calendar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'menuPhotos', 'backend', 'Label / Menu / Photos', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'menuPhotos' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Photos', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'menuAddress', 'backend', 'Label / Menu / Address', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'menuAddress' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Address', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'menuSeo', 'backend', 'Label / Menu / SEO', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'menuSeo' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'SEO', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblPhotosInfo', 'backend', 'Label / Photos / info note', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblPhotosInfo' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Upload photos of this property. The first photo is used as the main image. Accepted formats: JPG, PNG, GIF.', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblUploadPhotos', 'backend', 'Label / Photos / upload field', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblUploadPhotos' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Upload photos', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'btnUploadPhotos', 'backend', 'Label / Photos / upload button', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'btnUploadPhotos' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Upload', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblNoPhotosYet', 'backend', 'Label / Photos / empty list', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblNoPhotosYet' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'No photos have been uploaded for this property yet.', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblDeletePhoto', 'backend', 'Label / Photos / delete button', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblDeletePhoto' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Delete', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblPhotoDeleteConfirm', 'backend', 'Label / Photos / delete confirmation', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblPhotoDeleteConfirm' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Are you sure you want to delete this photo?', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblAddressInfo', 'backend', 'Label / Address / info note', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblAddressInfo' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Set the location of this property. The coordinates are used to show the property on a map.', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblShowOnMap', 'backend', 'Label / Address / show on map', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblShowOnMap' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Show on map', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblGoogleMapAddress', 'backend', 'Label / Address / google map address', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblGoogleMapAddress' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Google map address', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblAddress', 'backend', 'Label / Address / address', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblAddress' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Address', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblZip', 'backend', 'Label / Address / zip', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblZip' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Zip code', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblCountry', 'backend', 'Label / Address / country', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblCountry' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Country', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblState', 'backend', 'Label / Address / state', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblState' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'State', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblCity', 'backend', 'Label / Address / city', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblCity' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'City', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblLatitude', 'backend', 'Label / Address / latitude', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblLatitude' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Latitude', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblLongitude', 'backend', 'Label / Address / longitude', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblLongitude' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Longitude', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblGMapNote', 'backend', 'Label / Address / map note', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblGMapNote' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Enter the latitude and longitude to position the property precisely on the map.', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblSeoInfo', 'backend', 'Label / SEO / info note', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblSeoInfo' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'These fields are used by search engines. Keep the meta title short and write a clear meta description.', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblMetaTitle', 'backend', 'Label / SEO / meta title', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblMetaTitle' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Meta title', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblMetaKeywords', 'backend', 'Label / SEO / meta keywords', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblMetaKeywords' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Meta keywords', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblMetaDesc', 'backend', 'Label / SEO / meta description', 'script', '2026-08-07 15:10:18');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblMetaDesc' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Meta description', 'script');


COMMIT;