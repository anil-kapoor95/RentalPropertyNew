-- Gemini API key is one shared (platform) option: Options > API Keys, administrators only.
-- Copies made per company by the previous build are removed; a key saved in a copy is kept.

INSERT IGNORE INTO `plugin_base_options` (`foreign_id`, `key`, `tab_id`, `value`, `label`, `type`, `order`, `is_visible`, `style`) VALUES
(1, 'o_gemini_api_key', 10, NULL, NULL, 'string', 2, 1, NULL);

UPDATE `plugin_base_options` AS o
  INNER JOIN (SELECT `value` FROM `plugin_base_options` WHERE `key` = 'o_gemini_api_key' AND `foreign_id` <> 1 AND `value` IS NOT NULL AND `value` <> '' ORDER BY `foreign_id` ASC LIMIT 1) AS c
  SET o.`value` = c.`value`
  WHERE o.`foreign_id` = 1 AND o.`key` = 'o_gemini_api_key' AND (o.`value` IS NULL OR o.`value` = '');

DELETE FROM `plugin_base_options` WHERE `key` = 'o_gemini_api_key' AND `foreign_id` <> 1;
