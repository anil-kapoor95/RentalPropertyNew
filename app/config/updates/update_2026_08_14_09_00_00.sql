-- Modern front-end UI labels (properties listing + property detail page)
-- Idempotent: each label is removed first so this migration is safe to re-run.
-- Note: lblBedrooms / lblBathrooms are intentionally NOT seeded here — they already
-- exist in the base data (update_2026_08_07) and are reused by the views.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblAvailableNow' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblAvailableNow';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblAvailableNow', 'frontend', 'Listing / eyebrow', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Available now', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblBrowseProperties' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblBrowseProperties';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblBrowseProperties', 'frontend', 'Listing / subtitle', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Browse our properties and book your dates in a few clicks.', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblViewDetails' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblViewDetails';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblViewDetails', 'frontend', 'Listing / card button', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'View details', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblNoProperties' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblNoProperties';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblNoProperties', 'frontend', 'Listing / empty', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'No properties are available right now.', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblAllProperties' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblAllProperties';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblAllProperties', 'frontend', 'Detail / back link', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'All properties', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblAboutStay' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblAboutStay';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblAboutStay', 'frontend', 'Detail / accordion', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'About this stay', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblPropertyFeatures' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblPropertyFeatures';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblPropertyFeatures', 'frontend', 'Detail / accordion', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Property features', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblCommunityFeatures' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblCommunityFeatures';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblCommunityFeatures', 'frontend', 'Detail / accordion', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Community features', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblReadMore' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblReadMore';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblReadMore', 'frontend', 'Detail / read more', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Read more', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblShowLess' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblShowLess';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblShowLess', 'frontend', 'Detail / read less', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Show less', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblDetailsSoon' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblDetailsSoon';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblDetailsSoon', 'frontend', 'Detail / no details', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'More details coming soon.', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblCheckAvailability' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblCheckAvailability';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblCheckAvailability', 'frontend', 'Detail / CTA', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Check availability & book', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblChooseDates' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblChooseDates';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblChooseDates', 'frontend', 'Detail / booking heading', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Choose your dates', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblChooseDatesSub' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblChooseDatesSub';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblChooseDatesSub', 'frontend', 'Detail / booking subtitle', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Pick your arrival and departure to see the price and reserve.', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblWhereYoullBe' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblWhereYoullBe';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblWhereYoullBe', 'frontend', 'Detail / map heading', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Where you''ll be', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblPropertyFallback' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblPropertyFallback';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblPropertyFallback', 'frontend', 'Listing / unnamed property', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Property', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblNotFound' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblNotFound';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblNotFound', 'frontend', 'Detail / 404', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Property not found.', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblMapLocation' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblMapLocation';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblMapLocation', 'frontend', 'Detail / map fallback', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Map location', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblViewOnMap' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblViewOnMap';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblViewOnMap', 'frontend', 'Detail / map link', 'script', '2026-08-14 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'View on Google Maps', 'script');
