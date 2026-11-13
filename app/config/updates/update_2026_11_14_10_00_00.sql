-- Super Admin dashboard redesign (pjAdmin/pjActionPlatform.php): labels

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_super_admin' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_super_admin';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_super_admin','backend','Dashboard / dash_super_admin','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Super Admin','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_welcome' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_welcome';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_welcome','backend','Dashboard / dash_welcome','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Welcome back, %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_subtitle' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_subtitle';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_subtitle','backend','Dashboard / dash_subtitle','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Here is what is happening across the platform.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_status_ok' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_status_ok';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_status_ok','backend','Dashboard / dash_status_ok','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','All systems operational','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_status_warning' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_status_warning';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_status_warning','backend','Dashboard / dash_status_warning','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Some services need attention','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_status_error' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_status_error';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_status_error','backend','Dashboard / dash_status_error','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Service issue detected','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_range' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_range';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_range','backend','Dashboard / dash_range','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Date range','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_range_7' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_range_7';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_range_7','backend','Dashboard / dash_range_7','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Last 7 days','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_range_30' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_range_30';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_range_30','backend','Dashboard / dash_range_30','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Last 30 days','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_range_90' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_range_90';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_range_90','backend','Dashboard / dash_range_90','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Last 90 days','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_range_month' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_range_month';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_range_month','backend','Dashboard / dash_range_month','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This month','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_range_custom' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_range_custom';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_range_custom','backend','Dashboard / dash_range_custom','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Custom range','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_range_from' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_range_from';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_range_from','backend','Dashboard / dash_range_from','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','From','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_range_to' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_range_to';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_range_to','backend','Dashboard / dash_range_to','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','To','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_apply' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_apply';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_apply','backend','Dashboard / dash_apply','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Apply','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_range_showing' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_range_showing';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_range_showing','backend','Dashboard / dash_range_showing','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%s - %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_range_order' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_range_order';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_range_order','backend','Dashboard / dash_range_order','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The end date must be on or after the start date.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_not_ready' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_not_ready';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_not_ready','backend','Dashboard / dash_not_ready','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Subscriptions are not set up yet, so only company totals are shown.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_stat_companies' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_stat_companies';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_stat_companies','backend','Dashboard / dash_stat_companies','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Total Companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_stat_companies_sub' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_stat_companies_sub';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_stat_companies_sub','backend','Dashboard / dash_stat_companies_sub','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u active, %u new in this period','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_stat_subs' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_stat_subs';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_stat_subs','backend','Dashboard / dash_stat_subs','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Active Subscriptions','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_stat_subs_sub' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_stat_subs_sub';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_stat_subs_sub','backend','Dashboard / dash_stat_subs_sub','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u on trial, %u paid purchases in this period','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_stat_plans' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_stat_plans';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_stat_plans','backend','Dashboard / dash_stat_plans','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Available Plans','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_stat_plans_sub' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_stat_plans_sub';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_stat_plans_sub','backend','Dashboard / dash_stat_plans_sub','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Active and public, of %u plans','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_stat_coupons' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_stat_coupons';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_stat_coupons','backend','Dashboard / dash_stat_coupons','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Active Coupons','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_stat_coupons_sub' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_stat_coupons_sub';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_stat_coupons_sub','backend','Dashboard / dash_stat_coupons_sub','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u coupons in total, %u used in this period','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_overview_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_overview_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_overview_title','backend','Dashboard / dash_overview_title','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Subscription Overview','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_overview_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_overview_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_overview_desc','backend','Dashboard / dash_overview_desc','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies by current subscription status','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_overview_total' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_overview_total';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_overview_total','backend','Dashboard / dash_overview_total','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_status_no_plan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_status_no_plan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_status_no_plan','backend','Dashboard / dash_status_no_plan','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_overview_empty' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_overview_empty';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_overview_empty','backend','Dashboard / dash_overview_empty','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No companies yet.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_trends_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_trends_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_trends_title','backend','Dashboard / dash_trends_title','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Subscription Trends','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_trends_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_trends_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_trends_desc','backend','Dashboard / dash_trends_desc','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','New companies, trials started and paid purchases per day','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_series_companies' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_series_companies';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_series_companies','backend','Dashboard / dash_series_companies','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','New companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_series_trials' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_series_trials';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_series_trials','backend','Dashboard / dash_series_trials','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Trials started','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_series_purchases' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_series_purchases';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_series_purchases','backend','Dashboard / dash_series_purchases','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Paid purchases','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_7d' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_7d';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_7d','backend','Dashboard / dash_7d','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','7D','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_30d' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_30d';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_30d','backend','Dashboard / dash_30d','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','30D','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_90d' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_90d';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_90d','backend','Dashboard / dash_90d','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','90D','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_months_short' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_months_short';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_months_short','backend','Dashboard / dash_months_short','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mgmt_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mgmt_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mgmt_title','backend','Dashboard / dash_mgmt_title','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Platform Management','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mgmt_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mgmt_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mgmt_desc','backend','Dashboard / dash_mgmt_desc','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Shortcuts to the platform settings','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mgmt_plans' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mgmt_plans';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mgmt_plans','backend','Dashboard / dash_mgmt_plans','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plans','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mgmt_plans_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mgmt_plans_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mgmt_plans_desc','backend','Dashboard / dash_mgmt_plans_desc','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Create and edit subscription plans','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mgmt_promos' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mgmt_promos';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mgmt_promos','backend','Dashboard / dash_mgmt_promos','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coupons & Discounts','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mgmt_promos_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mgmt_promos_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mgmt_promos_desc','backend','Dashboard / dash_mgmt_promos_desc','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coupons and discounts on plan prices','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mgmt_settings' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mgmt_settings';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mgmt_settings','backend','Dashboard / dash_mgmt_settings','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','System Settings','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mgmt_settings_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mgmt_settings_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mgmt_settings_desc','backend','Dashboard / dash_mgmt_settings_desc','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','General options of the platform','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_activity_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_activity_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_activity_title','backend','Dashboard / dash_activity_title','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Recent Platform Activity','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_activity_all' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_activity_all';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_activity_all','backend','Dashboard / dash_activity_all','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Billing history','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_activity_empty' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_activity_empty';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_activity_empty','backend','Dashboard / dash_activity_empty','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No platform activity in this period.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_act_registration' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_act_registration';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_act_registration','backend','Dashboard / dash_act_registration','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%s registered','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_act_purchase' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_act_purchase';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_act_purchase','backend','Dashboard / dash_act_purchase','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%s purchased %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_act_upgrade' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_act_upgrade';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_act_upgrade','backend','Dashboard / dash_act_upgrade','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%s upgraded to %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_act_coupon' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_act_coupon';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_act_coupon','backend','Dashboard / dash_act_coupon','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%s used coupon %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_act_change' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_act_change';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_act_change','backend','Dashboard / dash_act_change','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%s changed plan to %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_act_change_noplan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_act_change_noplan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_act_change_noplan','backend','Dashboard / dash_act_change_noplan','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%s changed plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_health_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_health_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_health_title','backend','Dashboard / dash_health_title','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Platform Health','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_checked' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_checked';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_checked','backend','Dashboard / dash_h_checked','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Checked %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_gateway' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_gateway';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_gateway','backend','Dashboard / dash_h_gateway','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payment gateway','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_email' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_email';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_email','backend','Dashboard / dash_h_email','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Email service','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_tasks' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_tasks';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_tasks','backend','Dashboard / dash_h_tasks','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Scheduled tasks','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_jobs' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_jobs';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_jobs','backend','Dashboard / dash_h_jobs','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Background jobs','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_state_ok' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_state_ok';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_state_ok','backend','Dashboard / dash_state_ok','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Operational','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_state_warning' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_state_warning';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_state_warning','backend','Dashboard / dash_state_warning','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Attention','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_state_error' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_state_error';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_state_error','backend','Dashboard / dash_state_error','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Issue','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_state_unknown' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_state_unknown';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_state_unknown','backend','Dashboard / dash_state_unknown','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Not available','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_link_gateway' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_link_gateway';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_link_gateway','backend','Dashboard / dash_h_link_gateway','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payment settings','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_link_email' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_link_email';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_link_email','backend','Dashboard / dash_h_link_email','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Email settings','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_link_cron' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_link_cron';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_link_cron','backend','Dashboard / dash_h_link_cron','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Cron jobs','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_gateway_none_plugin' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_gateway_none_plugin';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_gateway_none_plugin','backend','Dashboard / dash_h_gateway_none_plugin','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The payments plugin is not installed, so subscription payments are not available.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_gateway_off' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_gateway_off';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_gateway_off','backend','Dashboard / dash_h_gateway_off','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No payment method is enabled for subscriptions.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_gateway_offline' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_gateway_offline';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_gateway_offline','backend','Dashboard / dash_h_gateway_offline','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Only offline methods are enabled: %s.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_gateway_test' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_gateway_test';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_gateway_test','backend','Dashboard / dash_h_gateway_test','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Test mode is on: %s.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_gateway_ok' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_gateway_ok';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_gateway_ok','backend','Dashboard / dash_h_gateway_ok','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Live: %s.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_email_nohost' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_email_nohost';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_email_nohost','backend','Dashboard / dash_h_email_nohost','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','SMTP is selected but no SMTP host is set.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_email_smtp' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_email_smtp';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_email_smtp','backend','Dashboard / dash_h_email_smtp','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','SMTP (%s).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_email_mail' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_email_mail';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_email_mail','backend','Dashboard / dash_h_email_mail','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','PHP mail().','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_email_failed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_email_failed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_email_failed','backend','Dashboard / dash_h_email_failed','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u reminder e-mail(s) failed on the last run.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_email_untested' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_email_untested';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_email_untested','backend','Dashboard / dash_h_email_untested','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No failures reported; delivery itself is not monitored.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_tasks_none' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_tasks_none';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_tasks_none','backend','Dashboard / dash_h_tasks_none','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No active cron jobs.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_tasks_late' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_tasks_late';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_tasks_late','backend','Dashboard / dash_h_tasks_late','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u of %u active jobs are overdue or never ran.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_tasks_ok' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_tasks_ok';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_tasks_ok','backend','Dashboard / dash_h_tasks_ok','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','All %u active jobs ran on time.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_jobs_never' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_jobs_never';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_jobs_never','backend','Dashboard / dash_h_jobs_never','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The server cron runner has never called the application.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_jobs_stale' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_jobs_stale';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_jobs_stale','backend','Dashboard / dash_h_jobs_stale','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The cron runner last ran %s.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_jobs_ok' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_jobs_ok';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_jobs_ok','backend','Dashboard / dash_h_jobs_ok','script','2026-11-14 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The cron runner last ran %s.','script');
