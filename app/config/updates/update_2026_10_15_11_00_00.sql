-- Subscriptions: labels for "Remove subscription". Safe to re-run.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_remove' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_remove';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_remove','backend','Subscriptions / sub_btn_remove','script','2026-10-15 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Remove subscription','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_remove_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_remove_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_remove_hint','backend','Subscriptions / sub_remove_hint','script','2026-10-15 11:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Back to "no plan" (the settings for companies without a plan apply). History and payments are kept.','script');
