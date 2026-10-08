
START TRANSACTION;


CREATE TABLE IF NOT EXISTS `features` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` tinyint(3) unsigned DEFAULT NULL,
  `status` enum('T','F') NOT NULL DEFAULT 'T',
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `calendars_features` (
  `calendar_id` int(10) unsigned NOT NULL,
  `feature_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`calendar_id`, `feature_id`),
  KEY `calendar_id` (`calendar_id`),
  KEY `feature_id` (`feature_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'feature_categories_ARRAY_1', 'arrays', 'feature_categories_ARRAY_1', 'script', '2026-07-29 07:20:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'feature_categories_ARRAY_1' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Community features', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'feature_categories_ARRAY_2', 'arrays', 'feature_categories_ARRAY_2', 'script', '2026-07-29 07:20:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'feature_categories_ARRAY_2' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Property features', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'menuFeatures', 'backend', 'Label / Menu / Features', 'script', '2026-07-29 07:20:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'menuFeatures' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Features', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'infoFeaturesTitle', 'backend', 'Label / Features / list title', 'script', '2026-07-29 07:20:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'infoFeaturesTitle' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Features list', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'infoFeaturesDesc', 'backend', 'Label / Features / list desc', 'script', '2026-07-29 07:20:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'infoFeaturesDesc' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'You can set up and manage a list of pre-defined features. The list set here shows up on the Features tab while editing a property.', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'btnAddFeature', 'backend', 'Label / Features / add button', 'script', '2026-07-29 07:20:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'btnAddFeature' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Add feature', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblFeatureName', 'backend', 'Label / Features / name', 'script', '2026-07-29 07:20:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblFeatureName' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Feature name', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblFeatureCategory', 'backend', 'Label / Features / category', 'script', '2026-07-29 07:20:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblFeatureCategory' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Feature category', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'infoAddFeatureTitle', 'backend', 'Label / Features / add modal title', 'script', '2026-07-29 07:20:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'infoAddFeatureTitle' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Add feature', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'infoEditFeatureTitle', 'backend', 'Label / Features / edit modal title', 'script', '2026-07-29 07:20:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'infoEditFeatureTitle' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Edit feature', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'infoFeatureTabTitle', 'backend', 'Label / Features / property tab title', 'script', '2026-07-29 07:20:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'infoFeatureTabTitle' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Features', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'infoFeatureTabDesc', 'backend', 'Label / Features / property tab desc', 'script', '2026-07-29 07:20:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'infoFeatureTabDesc' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Use the pre-defined list of features below to describe this property. To manage the list, go to Options - Features.', 'script');

INSERT IGNORE INTO `features` (`id`, `category_id`, `status`) VALUES
(1, 2, 'T'),(2, 2, 'T'),(3, 2, 'T'),(4, 2, 'T'),(5, 2, 'T'),(6, 2, 'T'),(7, 1, 'T'),(8, 2, 'T'),(9, 2, 'T'),(10, 1, 'T'),(11, 1, 'T'),(12, 2, 'T'),(13, 2, 'T'),(14, 2, 'T'),(15, 1, 'T'),(16, 2, 'T'),(17, 2, 'T'),(18, 1, 'T'),(19, 1, 'T'),(20, 1, 'T'),(21, 1, 'T'),(22, 2, 'T'),(23, 1, 'T'),(24, 2, 'T'),(25, 2, 'T'),(26, 2, 'T'),(27, 2, 'T'),(28, 1, 'T'),(29, 2, 'T'),(30, 2, 'T'),(31, 1, 'T'),(32, 2, 'T'),(33, 2, 'T'),(34, 2, 'T'),(35, 2, 'T'),(36, 2, 'T'),(37, 1, 'T'),(38, 2, 'T'),(39, 2, 'T'),(40, 1, 'T'),(41, 1, 'T'),(42, 1, 'T'),(43, 2, 'T'),(44, 1, 'T'),(45, 2, 'T'),(46, 2, 'T'),(47, 1, 'T'),(48, 1, 'T'),(49, 2, 'T'),(50, 1, 'T'),(51, 2, 'T'),(52, 2, 'T'),(53, 1, 'T'),(54, 2, 'T');

INSERT IGNORE INTO `multi_lang` VALUES (NULL, 1, 'pjFeature', '::LOCALE::', 'name', 'Air conditioning', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 2, 'pjFeature', '::LOCALE::', 'name', 'Attic', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 3, 'pjFeature', '::LOCALE::', 'name', 'Balcony, Deck or Patio', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 4, 'pjFeature', '::LOCALE::', 'name', 'Basement', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 5, 'pjFeature', '::LOCALE::', 'name', 'Bonus / Rec room', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 6, 'pjFeature', '::LOCALE::', 'name', 'Breakfast nook', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 7, 'pjFeature', '::LOCALE::', 'name', 'Business center', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 8, 'pjFeature', '::LOCALE::', 'name', 'Cable- ready', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 9, 'pjFeature', '::LOCALE::', 'name', 'Central heat', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 10, 'pjFeature', '::LOCALE::', 'name', 'Clubhouse', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 11, 'pjFeature', '::LOCALE::', 'name', 'Covered parking', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 12, 'pjFeature', '::LOCALE::', 'name', 'Dining room', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 13, 'pjFeature', '::LOCALE::', 'name', 'Dishwasher', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 14, 'pjFeature', '::LOCALE::', 'name', 'Dryer', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 15, 'pjFeature', '::LOCALE::', 'name', 'Elevator', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 16, 'pjFeature', '::LOCALE::', 'name', 'Family room', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 17, 'pjFeature', '::LOCALE::', 'name', 'Fireplace', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 18, 'pjFeature', '::LOCALE::', 'name', 'Fitness center', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 19, 'pjFeature', '::LOCALE::', 'name', 'Garage parking', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 20, 'pjFeature', '::LOCALE::', 'name', 'Gated property', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 21, 'pjFeature', '::LOCALE::', 'name', 'Golf course', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 22, 'pjFeature', '::LOCALE::', 'name', 'Granite countertop', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 23, 'pjFeature', '::LOCALE::', 'name', 'Guest parking', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 24, 'pjFeature', '::LOCALE::', 'name', 'Hardwood floor', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 25, 'pjFeature', '::LOCALE::', 'name', 'High / Vaulted ceiling', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 26, 'pjFeature', '::LOCALE::', 'name', 'High- speed internet', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 27, 'pjFeature', '::LOCALE::', 'name', 'Jacuzzi / Whirlpool', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 28, 'pjFeature', '::LOCALE::', 'name', 'Lake', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 29, 'pjFeature', '::LOCALE::', 'name', 'Laundry area - garage', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 30, 'pjFeature', '::LOCALE::', 'name', 'Laundry area - inside', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 31, 'pjFeature', '::LOCALE::', 'name', 'Laundry on-site', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 32, 'pjFeature', '::LOCALE::', 'name', 'Living Room', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 33, 'pjFeature', '::LOCALE::', 'name', 'Loft layout', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 34, 'pjFeature', '::LOCALE::', 'name', 'Microwave', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 35, 'pjFeature', '::LOCALE::', 'name', 'New property (<5 years)', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 36, 'pjFeature', '::LOCALE::', 'name', 'Office / Den', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 37, 'pjFeature', '::LOCALE::', 'name', 'Playground', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 38, 'pjFeature', '::LOCALE::', 'name', 'Refrigerator', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 39, 'pjFeature', '::LOCALE::', 'name', 'Sauna', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 40, 'pjFeature', '::LOCALE::', 'name', 'Sauna / Spa', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 41, 'pjFeature', '::LOCALE::', 'name', 'Secured entry', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 42, 'pjFeature', '::LOCALE::', 'name', 'Small building', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 43, 'pjFeature', '::LOCALE::', 'name', 'Stainless steel appliances', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 44, 'pjFeature', '::LOCALE::', 'name', 'Storage space(s)', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 45, 'pjFeature', '::LOCALE::', 'name', 'Stove / Oven', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 46, 'pjFeature', '::LOCALE::', 'name', 'Swimming pool', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 47, 'pjFeature', '::LOCALE::', 'name', 'Swimming pool(s)', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 48, 'pjFeature', '::LOCALE::', 'name', 'Tennis court(s)', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 49, 'pjFeature', '::LOCALE::', 'name', 'Tile floor', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 50, 'pjFeature', '::LOCALE::', 'name', 'Vintage building', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 51, 'pjFeature', '::LOCALE::', 'name', 'Walk-in closet', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 52, 'pjFeature', '::LOCALE::', 'name', 'Washer', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 53, 'pjFeature', '::LOCALE::', 'name', 'Wheelchair access', 'script');
INSERT IGNORE INTO `multi_lang` VALUES (NULL, 54, 'pjFeature', '::LOCALE::', 'name', 'Yard', 'script');

COMMIT;
