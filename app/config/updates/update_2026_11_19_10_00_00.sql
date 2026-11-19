-- Super Admin dashboard: reporting currency switch USD / INR (labels), 2026-11-19. Run once.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_cur_label' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_cur_label';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_cur_label','backend','Dashboard / dash_cur_label','script','2026-11-19 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Reporting currency','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_cur_charged' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_cur_charged';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_cur_charged','backend','Dashboard / dash_cur_charged','script','2026-11-19 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Charged: %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_cur_note_usd' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_cur_note_usd';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_cur_note_usd','backend','Dashboard / dash_cur_note_usd','script','2026-11-19 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Figures in US dollars. INR payments are counted at the USD value saved when they were paid (today: %s). Latest billing and activity show the amounts actually charged, in their own currency.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_cur_note_inr' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_cur_note_inr';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_cur_note_inr','backend','Dashboard / dash_cur_note_inr','script','2026-11-19 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Figures converted to Indian rupees at %s (Currency & exchange rate settings): reporting values only. Latest billing, activity and "Charged" show the amounts actually charged, in their own currency.','script');
