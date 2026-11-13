-- Billing Overview redesign: tags of the upcoming list

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_trial_ends_on' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_trial_ends_on';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_trial_ends_on','backend','Dashboard / dash_bill_trial_ends_on','script','2026-11-14 22:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Trial ends','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_renews_on' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_renews_on';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_renews_on','backend','Dashboard / dash_bill_renews_on','script','2026-11-14 22:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Renews','script');
