
START TRANSACTION;


INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'lblIcalURL', 'backend', 'Label / iCal URL', 'script', '2026-08-07 15:10:16');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblIcalURL' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'iCal URL', 'script');


INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'icalExportNoteTitle', 'backend', 'Label / iCal export note title', 'script', '2026-08-07 15:10:16');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'icalExportNoteTitle' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Export your bookings to other platforms', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'icalExportNoteText', 'backend', 'Label / iCal export note text', 'script', '2026-08-07 15:10:16');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'icalExportNoteText' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'This is the iCal link for this property. Copy it and add it on <strong>Airbnb, Booking.com, Vrbo, Google Calendar</strong> and any other channel you use. Those platforms will read this link and <strong>block the dates that are already booked in this system</strong>, so you never get a double booking. This link is read-only &ndash; just copy it.', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'icalImportNoteTitle', 'backend', 'Label / iCal import note title', 'script', '2026-08-07 15:10:16');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'icalImportNoteTitle' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Import bookings from other platforms', 'script');

INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'icalImportNoteText', 'backend', 'Label / iCal import note text', 'script', '2026-08-07 15:10:16');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'icalImportNoteText' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Paste the iCal export links from the platforms where this property is also listed (<strong>Airbnb, Booking.com, Vrbo, Google Calendar</strong>&hellip;). This system will read those calendars and <strong>create matching bookings here</strong>, so any dates booked elsewhere are automatically blocked in your calendar. Add <strong>one link per row</strong> using the plus (+) button. Imported bookings are kept up to date automatically each time the sync runs.', 'script');


COMMIT;