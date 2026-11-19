-- Brand: BookbyHost (Figma "Book by host"), 2026-11-19. Run once.
-- Product name shown in the back office (browser title, login page) and the e-mail sender name / {SiteName} token.

UPDATE `plugin_base_multi_lang` m
JOIN `plugin_base_fields` f ON f.`id` = m.`foreign_id` AND m.`model` = 'pjBaseField'
SET m.`content` = 'BookbyHost'
WHERE f.`key` = 'script_name' AND m.`field` = 'title';

UPDATE `plugin_base_options` SET `value` = 'BookbyHost' WHERE `key` = 'o_sender_name' AND `value` IN ('stayOwned', 'StayOwned', 'Stay Owned', '');
