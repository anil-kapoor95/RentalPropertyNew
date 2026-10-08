-- Rename the "Preview" menu item to "Calendar preview".
SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'menuPreview' LIMIT 1);
UPDATE `multi_lang` SET `content` = 'Calendar preview' WHERE `model` = 'pjBaseField' AND `field` = 'title' AND `foreign_id` = @fid;
