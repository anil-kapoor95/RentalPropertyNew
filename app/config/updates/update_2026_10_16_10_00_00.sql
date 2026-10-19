-- Subscriptions: payment methods (Options > Payments) + popups. Safe to re-run.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_menu_payments' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_menu_payments';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_menu_payments','backend','Subscriptions / sub_menu_payments','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payments','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gw_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gw_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gw_title','backend','Subscriptions / sub_gw_title','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Subscription payments','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gw_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gw_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gw_desc','backend','Subscriptions / sub_gw_desc','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose the payment methods companies use to pay for their plan. These settings are only for subscriptions; the payment settings of properties are not affected.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gw_currency_note' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gw_currency_note';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gw_currency_note','backend','Subscriptions / sub_gw_currency_note','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Online gateways check the payment in %s (the currency of the main settings). Plan prices paid online must use the same currency.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gw_currency_mismatch' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gw_currency_mismatch';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gw_currency_mismatch','backend','Subscriptions / sub_gw_currency_mismatch','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','These plans use another currency and cannot be paid online:','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gw_none' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gw_none';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gw_none','backend','Subscriptions / sub_gw_none','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No payment method is switched on yet. Companies can only send a payment request.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gw_settings_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gw_settings_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gw_settings_hint','backend','Subscriptions / sub_gw_settings_hint','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The payment methods for subscriptions are set in Options > Payments.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_provider_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_provider_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_provider_title','backend','Subscriptions / sub_provider_title','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payment methods','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_provider_manual' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_provider_manual';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_provider_manual','backend','Subscriptions / sub_provider_manual','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Manual (recorded by the administrator)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_title','backend','Subscriptions / sub_pay_title','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Pay for your plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_choose' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_choose';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_choose','backend','Subscriptions / sub_pay_choose','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose how you want to pay','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_btn' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_btn';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_btn','backend','Subscriptions / sub_pay_btn','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Continue','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_redirect' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_redirect';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_redirect','backend','Subscriptions / sub_pay_redirect','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','You are taken to the secure payment page. Your plan is switched on as soon as the payment is confirmed.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_bank_info' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_bank_info';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_bank_info','backend','Subscriptions / sub_pay_bank_info','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Transfer the amount to the account below and mention the reference. The plan is switched on when the payment has arrived.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_cash_info' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_cash_info';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_cash_info','backend','Subscriptions / sub_pay_cash_info','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Pay the amount to the administrator and mention the reference. The plan is switched on when the payment is recorded.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_reference' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_reference';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_reference','backend','Subscriptions / sub_pay_reference','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Reference','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_back' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_back';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_back','backend','Subscriptions / sub_pay_back','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Back to My plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_waiting_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_waiting_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_waiting_title','backend','Subscriptions / sub_waiting_title','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payments waiting for confirmation','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_waiting_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_waiting_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_waiting_hint','backend','Subscriptions / sub_waiting_hint','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Bank transfers, cash payments and payment requests of this company. Confirm one when the money has arrived (enter your own reference, e.g. the bank statement number).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_confirm_paid' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_confirm_paid';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_confirm_paid','backend','Subscriptions / sub_btn_confirm_paid','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Confirm payment','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_cancel_txn' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_cancel_txn';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_cancel_txn','backend','Subscriptions / sub_btn_cancel_txn','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Cancel','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrMethod' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrMethod';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrMethod','backend','Subscriptions / subErrMethod','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Please choose an available payment method.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrCurrency' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrCurrency';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrCurrency','backend','Subscriptions / subErrCurrency','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This plan cannot be paid online in its currency. Please choose another payment method or contact us.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subPaymentFailed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subPaymentFailed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subPaymentFailed','backend','Subscriptions / subPaymentFailed','script','2026-10-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The payment was not completed. Please try again or choose another payment method.','script');

-- The direct Stripe keys / provider of the first version are replaced by Options > Payments (empty keys only).
DELETE FROM `subscription_settings` WHERE `key` IN ('provider','stripe_mode') OR (`key` IN ('stripe_secret_test','stripe_secret_live','stripe_whsec_test','stripe_whsec_live') AND (`value` IS NULL OR `value` = ''));
