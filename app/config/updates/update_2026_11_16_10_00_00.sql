-- Dual-currency subscriptions (USD international / INR India), 2026-11-16.
-- Tables are unprefixed; the updater adds the prefix. Run once.
-- * plans: a USD and an INR price (INR may follow the exchange rate: inr_auto)
-- * company_subscriptions: billing currency + price / USD value / rate of the last payment
-- * billing_transactions: snapshot of both plan prices, the exchange rate and the USD value of every payment
-- * subscription_settings: inr_rate (INR per 1 USD)
-- Existing rows are converted at the default rate below (88 INR = 1 USD): edit the rate in Subscriptions > Plans
-- afterwards; it is never applied to payments already made.

INSERT IGNORE INTO `subscription_settings` (`key`, `value`) VALUES ('inr_rate', '88.0000'), ('country_currencies', 'IN:INR'), ('fallback_currency', 'USD'),
('gateway_map', '{"INR":["razorpay"],"USD":["razorpay","stripe"]}');

-- exchange rate audit trail: every saved rate with its source and time (never changed); payments keep the snapshot id
CREATE TABLE IF NOT EXISTS `exchange_rate_snapshots` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `base_currency` char(3) NOT NULL DEFAULT 'USD',
  `quote_currency` char(3) NOT NULL DEFAULT 'INR',
  `rate` decimal(14,6) NOT NULL,
  `source` varchar(40) NOT NULL DEFAULT 'manual',
  `user_id` int(10) unsigned DEFAULT NULL,
  `captured_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pair` (`base_currency`, `quote_currency`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `exchange_rate_snapshots` (`base_currency`, `quote_currency`, `rate`, `source`, `captured_at`) VALUES ('USD', 'INR', 88, 'migration default', NOW());
UPDATE `subscription_settings` SET `value` = 'USD' WHERE `key` = 'currency';

ALTER TABLE `plans` ADD COLUMN `price_usd` decimal(10,2) DEFAULT NULL AFTER `currency`, ADD COLUMN `price_inr` decimal(10,2) DEFAULT NULL AFTER `price_usd`, ADD COLUMN `inr_auto` tinyint(1) unsigned NOT NULL DEFAULT '1' AFTER `price_inr`;

-- plans priced in INR keep that INR price (set by hand) and get a USD price at the default rate
UPDATE `plans` SET `price_inr` = `price`, `price_usd` = ROUND(`price` / 88, 2), `inr_auto` = 0 WHERE `currency` = 'INR' AND `price` IS NOT NULL;
-- plans priced in USD (or another currency treated as USD) get a suggested INR price that follows the rate
UPDATE `plans` SET `price_usd` = `price`, `price_inr` = ROUND(`price` * 88, 0), `inr_auto` = 1 WHERE (`currency` IS NULL OR `currency` <> 'INR') AND `price` IS NOT NULL;
UPDATE `plans` SET `price_inr` = 0 WHERE `price_usd` = 0;
UPDATE `plans` SET `price` = `price_usd`, `currency` = 'USD';

ALTER TABLE `company_subscriptions` ADD COLUMN `billing_currency` char(3) DEFAULT NULL AFTER `used_trial`, ADD COLUMN `billing_country` char(2) DEFAULT NULL AFTER `billing_currency`, ADD COLUMN `price_amount` decimal(10,2) DEFAULT NULL AFTER `billing_currency`, ADD COLUMN `price_usd` decimal(10,2) DEFAULT NULL AFTER `price_amount`, ADD COLUMN `exchange_rate` decimal(12,6) DEFAULT NULL AFTER `price_usd`;

ALTER TABLE `billing_transactions` ADD COLUMN `price_usd` decimal(10,2) DEFAULT NULL AFTER `currency`, ADD COLUMN `price_inr` decimal(10,2) DEFAULT NULL AFTER `price_usd`, ADD COLUMN `exchange_rate` decimal(12,6) DEFAULT NULL AFTER `price_inr`, ADD COLUMN `amount_usd` decimal(10,2) DEFAULT NULL AFTER `exchange_rate`, ADD COLUMN `rate_snapshot_id` int(10) unsigned DEFAULT NULL AFTER `amount_usd`, ADD COLUMN `billing_country` char(2) DEFAULT NULL AFTER `rate_snapshot_id`, ADD KEY `status_paid` (`status`, `paid_at`);

-- history: USD value of earlier payments (no rate was stored with them: the default rate is used once, here)
SET @snap := (SELECT MAX(`id`) FROM `exchange_rate_snapshots`);
UPDATE `billing_transactions` SET `exchange_rate` = 88, `amount_usd` = `amount`, `rate_snapshot_id` = @snap WHERE `currency` = 'USD';
UPDATE `billing_transactions` SET `exchange_rate` = 88, `amount_usd` = ROUND(`amount` / 88, 2), `rate_snapshot_id` = @snap WHERE `currency` = 'INR';
UPDATE `billing_transactions` t SET t.`price_usd` = (SELECT p.`price_usd` FROM `plans` p WHERE p.`id` = t.`plan_id`), t.`price_inr` = (SELECT p.`price_inr` FROM `plans` p WHERE p.`id` = t.`plan_id`) WHERE t.`price_usd` IS NULL;

-- billing country (ISO alpha-2 of the company profile) on subscriptions and payments
UPDATE `company_subscriptions` s SET s.`billing_country` = (SELECT co.`alpha_2` FROM `plugin_base_countries` co WHERE co.`id` = (SELECT c.`country_id` FROM `companies` c WHERE c.`id` = s.`company_id`)) WHERE s.`billing_country` IS NULL;
UPDATE `billing_transactions` t SET t.`billing_country` = (SELECT co.`alpha_2` FROM `plugin_base_countries` co WHERE co.`id` = (SELECT c.`country_id` FROM `companies` c WHERE c.`id` = t.`company_id`)) WHERE t.`billing_country` IS NULL;

-- billing currency of each company: the currency of its last paid payment
UPDATE `company_subscriptions` s SET s.`billing_currency` = (SELECT t.`currency` FROM `billing_transactions` t WHERE t.`company_id` = s.`company_id` AND t.`status` = 'paid' AND t.`amount` > 0 AND t.`currency` IN ('USD', 'INR') ORDER BY t.`id` DESC LIMIT 1) WHERE s.`billing_currency` IS NULL;
UPDATE `company_subscriptions` s SET s.`price_amount` = (SELECT IF(t.`currency` = 'INR', t.`price_inr`, t.`price_usd`) FROM `billing_transactions` t WHERE t.`company_id` = s.`company_id` AND t.`status` = 'paid' AND t.`amount` > 0 AND t.`plan_id` = s.`plan_id` ORDER BY t.`id` DESC LIMIT 1), s.`price_usd` = (SELECT t.`price_usd` FROM `billing_transactions` t WHERE t.`company_id` = s.`company_id` AND t.`status` = 'paid' AND t.`amount` > 0 AND t.`plan_id` = s.`plan_id` ORDER BY t.`id` DESC LIMIT 1), s.`exchange_rate` = 88 WHERE s.`billing_currency` IS NOT NULL;

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_price_usd' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_price_usd';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_price_usd','backend','Subscriptions / sub_price_usd','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','USD price','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_price_usd_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_price_usd_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_price_usd_hint','backend','Subscriptions / sub_price_usd_hint','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','International customers. Required for a paid plan.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_price_inr' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_price_inr';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_price_inr','backend','Subscriptions / sub_price_inr','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','INR price','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_price_inr_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_price_inr_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_price_inr_hint','backend','Subscriptions / sub_price_inr_hint','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Customers in India. Paid through Razorpay.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_inr_auto' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_inr_auto';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_inr_auto','backend','Subscriptions / sub_inr_auto','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Follow the exchange rate','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_inr_auto_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_inr_auto_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_inr_auto_hint','backend','Subscriptions / sub_inr_auto_hint','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','INR price = USD price x the exchange rate, rounded to whole rupees. Updated when the rate changes. Switch off to set your own INR price.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_inr_auto_short' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_inr_auto_short';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_inr_auto_short','backend','Subscriptions / sub_inr_auto_short','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','auto','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_inr_suggested' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_inr_suggested';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_inr_suggested','backend','Subscriptions / sub_inr_suggested','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Suggested at the current rate:','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_inr_use_suggested' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_inr_use_suggested';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_inr_use_suggested','backend','Subscriptions / sub_inr_use_suggested','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Use suggested','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_inr_not_set' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_inr_not_set';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_inr_not_set','backend','Subscriptions / sub_inr_not_set','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','INR price not set','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_rate_line' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_rate_line';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_rate_line','backend','Subscriptions / sub_rate_line','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Exchange rate: 1 USD = {RATE}.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_rate_change' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_rate_change';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_rate_change','backend','Subscriptions / sub_rate_change','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Change the rate','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_rate_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_rate_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_rate_title','backend','Subscriptions / sub_rate_title','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Currency & exchange rate','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_rate_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_rate_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_rate_hint','backend','Subscriptions / sub_rate_hint','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plans have a USD price (international) and an INR price (India). The rate suggests INR prices and values INR payments in USD on the dashboard. Payments already made always keep the rate of their own day.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_rate_save' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_rate_save';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_rate_save','backend','Subscriptions / sub_rate_save','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Save rate','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_rate_confirm' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_rate_confirm';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_rate_confirm','backend','Subscriptions / sub_rate_confirm','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Save the new exchange rate? INR prices of plans that follow the rate are updated. Manual INR prices and payments already made stay as they are.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subRateSaved' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subRateSaved';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subRateSaved','backend','Subscriptions / subRateSaved','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The exchange rate has been saved.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrRate' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrRate';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrRate','backend','Subscriptions / subErrRate','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Please enter a valid exchange rate (1 USD = 1 to 100,000 INR).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrPlanUsd' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrPlanUsd';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrPlanUsd','backend','Subscriptions / subErrPlanUsd','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','A paid plan needs a USD price (the international price).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrCurrency' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrCurrency';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrCurrency','backend','Subscriptions / subErrCurrency','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This plan cannot be paid in the chosen currency.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_price_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_price_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_price_hint','backend','Subscriptions / sub_price_hint','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Leave the USD price empty for a plan on request (it cannot be bought online). 0 makes the plan free in both currencies.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_billing_currency' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_billing_currency';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_billing_currency','backend','Subscriptions / sub_billing_currency','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Billing currency','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_locked_price' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_locked_price';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_locked_price','backend','Subscriptions / sub_locked_price','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Last price paid','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_currency_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_currency_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_currency_title','backend','Subscriptions / sub_currency_title','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Billing currency','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_currency_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_currency_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_currency_hint','backend','Subscriptions / sub_currency_hint','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Currency of this company\'s next payments and renewals. Payments already made keep their own currency, amount and exchange rate.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gw_map_note' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gw_map_note';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gw_map_note','backend','Subscriptions / sub_gw_map_note','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose which gateways take each currency (default: INR through Razorpay, USD through Razorpay or Stripe). Companies only see the switched-on gateways of the currency they pay in.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_prices_in' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_prices_in';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_prices_in','backend','Subscriptions / sub_prices_in','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Prices in','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_cur_usd_short' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_cur_usd_short';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_cur_usd_short','backend','Subscriptions / sub_cur_usd_short','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','International','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_cur_inr_short' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_cur_inr_short';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_cur_inr_short','backend','Subscriptions / sub_cur_inr_short','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','India','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_cur_usd_name' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_cur_usd_name';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_cur_usd_name','backend','Subscriptions / sub_cur_usd_name','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','US Dollar, international','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_cur_inr_name' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_cur_inr_name';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_cur_inr_name','backend','Subscriptions / sub_cur_inr_name','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Indian Rupee, India','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_billed_in' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_billed_in';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_billed_in','backend','Subscriptions / sub_billed_in','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your account is billed in {CUR}. Renewals and plan changes stay in this currency.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_price_na_cur' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_price_na_cur';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_price_na_cur','backend','Subscriptions / sub_price_na_cur','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Not available in this currency','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_step_currency' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_step_currency';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_step_currency','backend','Subscriptions / sub_pay_step_currency','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose your currency','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_step_method' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_step_method';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_step_method','backend','Subscriptions / sub_pay_step_method','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose how you want to pay','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_locked' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_locked';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_locked','backend','Subscriptions / sub_pay_locked','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your account is billed in {CUR}: renewals and plan changes stay in this currency. To change it, please contact support.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_no_method' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_no_method';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_no_method','backend','Subscriptions / sub_pay_no_method','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No online payment method takes this currency yet. Continue to send a payment request; we will get in touch.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_have_coupon' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_have_coupon';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_have_coupon','backend','Subscriptions / sub_have_coupon','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Have a coupon code?','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_secure' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_secure';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_secure','backend','Subscriptions / sub_pay_secure','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Secure payment: you finish the payment on the payment provider\'s page. Your card details never reach this site.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_continue' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_continue';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_continue','backend','Subscriptions / sub_pay_continue','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Continue to payment','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_send_request' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_send_request';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_send_request','backend','Subscriptions / sub_pay_send_request','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Send payment request','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_you_pay' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_you_pay';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_you_pay','backend','Subscriptions / sub_you_pay','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Total to pay','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gw_is_on' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gw_is_on';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gw_is_on','backend','Subscriptions / sub_gw_is_on','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Switched on for subscriptions','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gw_is_off' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gw_is_off';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gw_is_off','backend','Subscriptions / sub_gw_is_off','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Not switched on yet: set it up below','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_gw_off_short' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_gw_off_short';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_gw_off_short','backend','Subscriptions / sub_gw_off_short','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','off','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_rate_audit' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_rate_audit';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_rate_audit','backend','Subscriptions / sub_rate_audit','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Rate saved {DATE} (source: {SOURCE}). Each payment keeps the rate snapshot it was valued with.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_inr_countries' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_inr_countries';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_inr_countries','backend','Subscriptions / sub_inr_countries','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','INR countries','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_inr_countries_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_inr_countries_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_inr_countries_hint','backend','Subscriptions / sub_inr_countries_hint','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Billing countries (ISO codes, e.g. IN) priced in INR. Every other country pays in USD.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_pay_price_locked' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_pay_price_locked';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_pay_price_locked','backend','Subscriptions / sub_pay_price_locked','script','2026-11-16 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Renewal at the price you agreed when you subscribed.','script');
