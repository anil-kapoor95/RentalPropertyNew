-- Subscription payments: every plan is charged and verified in its own currency.
SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gw_currency_note' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gw_currency_note';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gw_currency_note','backend','Subscriptions / sub_gw_currency_note','script','2026-11-12 13:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Each plan is charged and verified by the gateway in its own currency (%s). The gateway account must accept that currency.','script');
