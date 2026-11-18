-- Country names: "Cocos array(Keeling) Islands" -> "Cocos (Keeling) Islands" (10 names in every language had a stray
-- "array" before the bracket), 2026-11-17. Run once; running it again changes nothing.
UPDATE `plugin_base_multi_lang` SET `content` = REPLACE(`content`, ' array(', ' (') WHERE `model` = 'pjBaseCountry' AND `field` = 'name' AND `content` LIKE '% array(%';
