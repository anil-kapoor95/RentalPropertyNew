-- Automatic daily exchange rate USD -> INR (labels + cron job), 2026-11-18. Run once.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_auto_label' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_auto_label';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_auto_label','backend','Subscriptions / sub_fx_auto_label','script','2026-11-18 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Update automatically every day','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_auto_help' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_auto_help';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_auto_help','backend','Subscriptions / sub_fx_auto_help','script','2026-11-18 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Today\'s rate is fetched once a day from free central-bank reference rates (Frankfurter, or ExchangeRate-API as a backup) and saved with its date. A change of more than 15% in one day is not applied without you. Plan prices are never changed automatically.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_auto_ok' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_auto_ok';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_auto_ok','backend','Subscriptions / sub_fx_auto_ok','script','2026-11-18 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Last automatic update: {DATE} · source {SOURCE} · rate of {RATEDATE}','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_auto_failed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_auto_failed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_auto_failed','backend','Subscriptions / sub_fx_auto_failed','script','2026-11-18 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Last automatic update failed ({DATE}): {ERROR} The saved rate is still used.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_auto_never' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_auto_never';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_auto_never','backend','Subscriptions / sub_fx_auto_never','script','2026-11-18 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Not updated automatically yet.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_auto_off' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_auto_off';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_auto_off','backend','Subscriptions / sub_fx_auto_off','script','2026-11-18 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Automatic update is off: the rate above is used until you change it.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_update_now' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_update_now';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_update_now','backend','Subscriptions / sub_fx_update_now','script','2026-11-18 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Update now','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_history' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_history';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_history','backend','Subscriptions / sub_fx_history','script','2026-11-18 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Recent rates','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_history_manual' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_history_manual';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_history_manual','backend','Subscriptions / sub_fx_history_manual','script','2026-11-18 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','set by hand','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subRateUpdated' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subRateUpdated';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subRateUpdated','backend','Subscriptions / subRateUpdated','script','2026-11-18 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Exchange rate updated to today\'s rate.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subRateFetchFailed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subRateFetchFailed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subRateFetchFailed','backend','Subscriptions / subRateFetchFailed','script','2026-11-18 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Today\'s exchange rate could not be fetched or was not applied (see the message on the page). The saved rate is still used.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_fx_rate_help' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_fx_rate_help';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_fx_rate_help','backend','Subscriptions / sub_fx_rate_help','script','2026-11-18 19:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','How many Indian rupees one US dollar is worth. With the daily automatic update it follows the market by itself; you can still type a rate and save it (the next daily update replaces it, unless you switch the automatic update off).','script');

-- Daily job: fetch and save today's rate (system run only)
INSERT INTO `plugin_base_cron_jobs` (`name`, `controller`, `action`, `interval`, `period`, `is_active`)
SELECT 'Exchange rate: USD to INR (daily)', 'pjCron', 'pjActionExchangeRate', 1, 'day', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `plugin_base_cron_jobs` WHERE `controller`='pjCron' AND `action`='pjActionExchangeRate');

-- Automatic update on by default
INSERT IGNORE INTO `subscription_settings` (`key`, `value`) VALUES ('rate_auto', '1');
