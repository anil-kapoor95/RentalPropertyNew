-- Billing list: note for days credited from the previous plan (labels), 2026-11-18. Run once.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_period_credit' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_period_credit';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_period_credit','backend','Subscriptions / sub_period_credit','script','2026-11-18 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','incl. {DAYS} days credit from the previous plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_period_credit_one' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_period_credit_one';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_period_credit_one','backend','Subscriptions / sub_period_credit_one','script','2026-11-18 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','incl. 1 day credit from the previous plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_period_credit_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_period_credit_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_period_credit_hint','backend','Subscriptions / sub_period_credit_hint','script','2026-11-18 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The unused paid time of the previous plan was not lost: its value was added as extra days on this plan. After this period the plan renews at its normal price and billing interval.','script');
