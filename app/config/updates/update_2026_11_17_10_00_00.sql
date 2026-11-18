-- Subscriptions > Plans: redesigned Currency & exchange rate card (labels), 2026-11-17. Run once.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_intro' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_intro';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_intro','backend','Subscriptions / sub_fx_intro','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plans have a USD price for international customers and an INR price for customers in India. Set the exchange rate and choose which billing countries pay in INR.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_saved' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_saved';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_saved','backend','Subscriptions / sub_fx_saved','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Last saved {DATE}','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_rate_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_rate_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_rate_title','backend','Subscriptions / sub_fx_rate_title','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Exchange rate','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_rate_help' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_rate_help';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_rate_help','backend','Subscriptions / sub_fx_rate_help','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','How many Indian rupees one US dollar is worth. Use today\'s rate from your bank or a site like Google ("1 USD to INR"). It is only changed when you save it here.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_examples' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_examples';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_examples','backend','Subscriptions / sub_fx_examples','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Examples:','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_rate_note' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_rate_note';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_rate_note','backend','Subscriptions / sub_fx_rate_note','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Saving updates the INR price of {N} plan(s) set to "Follow the exchange rate". INR prices you typed in yourself, and payments already made, never change. The dashboard values new INR payments in USD at this rate.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_countries_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_countries_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_countries_title','backend','Subscriptions / sub_fx_countries_title','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Countries that pay in INR','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_countries_help' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_countries_help';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_countries_help','backend','Subscriptions / sub_fx_countries_help','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','A company is charged in INR (through Razorpay) when the country in its profile (My company > Country) is in this list. Companies from every other country pay in USD (Razorpay or Stripe).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_none' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_none';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_none','backend','Subscriptions / sub_fx_none','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No country: every company pays in USD.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_add' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_add';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_add','backend','Subscriptions / sub_fx_add','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','+ Add a country','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_remove' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_remove';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_remove','backend','Subscriptions / sub_fx_remove','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Remove {NAME}','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_countries_tip' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_countries_tip';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_countries_tip','backend','Subscriptions / sub_fx_countries_tip','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Most platforms only need India here. Add another country only if you want its companies to see and pay INR prices too.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_foot' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_foot';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_foot','backend','Subscriptions / sub_fx_foot','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','A company that has already paid keeps its currency; you can change it on its subscription page.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_rate_confirm' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_rate_confirm';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_rate_confirm','backend','Subscriptions / sub_rate_confirm','script','2026-11-17 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Save the exchange rate and the INR countries? INR prices of plans that follow the rate are updated. Manual INR prices and payments already made stay as they are.','script');
