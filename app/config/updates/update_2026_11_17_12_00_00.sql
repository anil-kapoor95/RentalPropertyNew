-- Options > Payments: gateways for each currency, redesigned (labels), 2026-11-17. Run once.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gwc_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gwc_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gwc_title','backend','Subscriptions / sub_gwc_title','script','2026-11-17 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payment methods for plan purchases','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gwc_intro' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gwc_intro';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gwc_intro','backend','Subscriptions / sub_gwc_intro','script','2026-11-17 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose which gateways companies can pay their plan with, for each currency. Only gateways that are switched on below and can take that currency are listed. Companies see only the gateways you turn on here.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gwc_count' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gwc_count';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gwc_count','backend','Subscriptions / sub_gwc_count','script','2026-11-17 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','{N} of {T} available gateways turned on','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gwc_toggle' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gwc_toggle';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gwc_toggle','backend','Subscriptions / sub_gwc_toggle','script','2026-11-17 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Use {GW} for {CUR} payments','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gwc_empty' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gwc_empty';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gwc_empty','backend','Subscriptions / sub_gwc_empty','script','2026-11-17 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No switched-on gateway can take {CUR}. Switch on Razorpay, Stripe, PayPal or Authorize.Net below.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gwc_empty_inr' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gwc_empty_inr';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gwc_empty_inr','backend','Subscriptions / sub_gwc_empty_inr','script','2026-11-17 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No switched-on gateway can take INR. Switch on Razorpay below.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gwc_none_on' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gwc_none_on';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gwc_none_on','backend','Subscriptions / sub_gwc_none_on','script','2026-11-17 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No gateway is turned on for {CUR}: companies paying in {CUR} can only send a payment request.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gwc_unsupported' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gwc_unsupported';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gwc_unsupported','backend','Subscriptions / sub_gwc_unsupported','script','2026-11-17 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Not listed because they don\'t take {CUR}: {LIST}.','script');
