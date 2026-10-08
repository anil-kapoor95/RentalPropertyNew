
START TRANSACTION;


INSERT IGNORE INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES
(1, 'o_brand_color',           99, '#b45309', NULL, 'string', NULL, 0, NULL),
(1, 'o_accent_color',          99, '#f3f4f6', NULL, 'string', NULL, 0, NULL),
(1, 'o_text_color',            99, '#111827', NULL, 'string', NULL, 0, NULL),
(1, 'o_background_color',      99, '#ffffff', NULL, 'string', NULL, 0, NULL),
(1, 'o_price_color',           99, '#b45309', NULL, 'string', NULL, 0, NULL),
(1, 'o_page_background_color', 99, '#ffffff', NULL, 'string', NULL, 0, NULL),
(1, 'o_font_family',           99, '',        NULL, 'string', NULL, 0, NULL),
(1, 'o_color_palette',         99, '0',       NULL, 'string', NULL, 0, NULL);


INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'menuDisplay', 'backend', 'Menu / Display', 'script', '2026-08-08 09:00:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'menuDisplay' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Display', 'script');


INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'infoThemeColorsTitle', 'backend', 'Display / Colours title', 'script', '2026-08-08 09:00:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'infoThemeColorsTitle' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Theme colours', 'script');


INSERT IGNORE INTO `plugin_base_fields` VALUES (NULL, 'infoThemeColorsDesc', 'backend', 'Display / Colours desc', 'script', '2026-08-08 09:00:00');

SET @id := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'infoThemeColorsDesc' LIMIT 1);

INSERT IGNORE INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Pick a palette or set your own colours. These drive the dynamic theme used by the new front-end page and embed.', 'script');


COMMIT;
