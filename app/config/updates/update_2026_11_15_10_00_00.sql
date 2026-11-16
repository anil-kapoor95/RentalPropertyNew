-- Super Admin dashboard redesign: labels (no data changes)

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_glance' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_glance';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_glance','backend','Dashboard / dash_glance','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Here is how the platform is doing','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_12m' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_12m';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_12m','backend','Dashboard / dash_12m','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','12M','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_q_this_month' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_q_this_month';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_q_this_month','backend','Dashboard / dash_q_this_month','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This month','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_q_last_month' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_q_last_month';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_q_last_month','backend','Dashboard / dash_q_last_month','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Last month','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_q_this_quarter' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_q_this_quarter';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_q_this_quarter','backend','Dashboard / dash_q_this_quarter','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This quarter','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_q_this_year' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_q_this_year';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_q_this_year','backend','Dashboard / dash_q_this_year','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This year','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_range_err_both' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_range_err_both';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_range_err_both','backend','Dashboard / dash_range_err_both','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose both a start and an end date.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_range_err_future' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_range_err_future';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_range_err_future','backend','Dashboard / dash_range_err_future','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Dates cannot be in the future.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_export' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_export';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_export','backend','Dashboard / dash_export','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Export','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_export_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_export_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_export_hint','backend','Dashboard / dash_export_hint','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Download the figures for this period as CSV','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_key_figures' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_key_figures';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_key_figures','backend','Dashboard / dash_key_figures','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Key figures','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_csv_period' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_csv_period';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_csv_period','backend','Dashboard / dash_csv_period','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Period','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_csv_metric' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_csv_metric';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_csv_metric','backend','Dashboard / dash_csv_metric','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Metric','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_csv_value' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_csv_value';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_csv_value','backend','Dashboard / dash_csv_value','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Value','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_loading' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_loading';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_loading','backend','Dashboard / dash_loading','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Loading…','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_load_error' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_load_error';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_load_error','backend','Dashboard / dash_load_error','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The dashboard could not be updated. Please try again.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_k_revenue' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_k_revenue';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_k_revenue','backend','Dashboard / dash_k_revenue','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Total revenue','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_k_companies' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_k_companies';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_k_companies','backend','Dashboard / dash_k_companies','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_k_subs' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_k_subs';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_k_subs','backend','Dashboard / dash_k_subs','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Active subscriptions','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_k_mrr' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_k_mrr';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_k_mrr','backend','Dashboard / dash_k_mrr','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Monthly recurring revenue','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_k_conv' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_k_conv';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_k_conv','backend','Dashboard / dash_k_conv','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Trial → paid','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_k_vs_prev' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_k_vs_prev';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_k_vs_prev','backend','Dashboard / dash_k_vs_prev','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','vs previous period','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_k_new_n' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_k_new_n';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_k_new_n','backend','Dashboard / dash_k_new_n','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u new','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_k_left_n' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_k_left_n';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_k_left_n','backend','Dashboard / dash_k_left_n','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u left','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_k_trial_n' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_k_trial_n';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_k_trial_n','backend','Dashboard / dash_k_trial_n','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u on trial','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_k_paying_n' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_k_paying_n';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_k_paying_n','backend','Dashboard / dash_k_paying_n','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u paying companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_k_conv_sub' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_k_conv_sub';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_k_conv_sub','backend','Dashboard / dash_k_conv_sub','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u of %u trials','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_k_no_trials' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_k_no_trials';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_k_no_trials','backend','Dashboard / dash_k_no_trials','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No trials in this period','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_rev_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_rev_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_rev_title','backend','Dashboard / dash_rev_title','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Revenue','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_rev_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_rev_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_rev_desc','backend','Dashboard / dash_rev_desc','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Paid subscription payments in the selected period,','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_rev_by_plan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_rev_by_plan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_rev_by_plan','backend','Dashboard / dash_rev_by_plan','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','By plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_rev_by_kind' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_rev_by_kind';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_rev_by_kind','backend','Dashboard / dash_rev_by_kind','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','New vs renewals','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_rev_average' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_rev_average';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_rev_average','backend','Dashboard / dash_rev_average','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Average %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_total' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_total';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_total','backend','Dashboard / dash_total','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Total','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_rev_other_currency' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_rev_other_currency';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_rev_other_currency','backend','Dashboard / dash_rev_other_currency','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payments in other currencies are not included in the chart:','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_rev_none' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_rev_none';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_rev_none','backend','Dashboard / dash_rev_none','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No paid payments in this period.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_kind_new' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_kind_new';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_kind_new','backend','Dashboard / dash_kind_new','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','New','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_kind_upgrade' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_kind_upgrade';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_kind_upgrade','backend','Dashboard / dash_kind_upgrade','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Upgrade','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_kind_renewal' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_kind_renewal';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_kind_renewal','backend','Dashboard / dash_kind_renewal','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Renewal','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_other' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_other';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_other','backend','Dashboard / dash_other','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Other','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_g_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_g_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_g_title','backend','Dashboard / dash_g_title','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company growth','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_g_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_g_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_g_desc','backend','Dashboard / dash_g_desc','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','New companies, companies that left and net change','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_g_left' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_g_left';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_g_left','backend','Dashboard / dash_g_left','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Left','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_g_net' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_g_net';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_g_net','backend','Dashboard / dash_g_net','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Net change','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mix_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mix_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mix_title','backend','Dashboard / dash_mix_title','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plan distribution','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mix_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mix_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mix_desc','backend','Dashboard / dash_mix_desc','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies on each plan at the end of the period','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mix_mrr' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mix_mrr';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mix_mrr','backend','Dashboard / dash_mix_mrr','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%s MRR','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mix_mrr_total' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mix_mrr_total';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mix_mrr_total','backend','Dashboard / dash_mix_mrr_total','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Total MRR','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mix_mrr_sub' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mix_mrr_sub';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mix_mrr_sub','backend','Dashboard / dash_mix_mrr_sub','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','From paid plans, trials excluded','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mix_on_trial' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mix_on_trial';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mix_on_trial','backend','Dashboard / dash_mix_on_trial','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u on trial','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mix_ended' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mix_ended';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mix_ended','backend','Dashboard / dash_mix_ended','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Expired / cancelled','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mix_ended_sub' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mix_ended_sub';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mix_ended_sub','backend','Dashboard / dash_mix_ended_sub','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Subscription no longer active','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_mix_no_plan_sub' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_mix_no_plan_sub';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_mix_no_plan_sub','backend','Dashboard / dash_mix_no_plan_sub','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Never chose a plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_f_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_f_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_f_title','backend','Dashboard / dash_f_title','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Subscription funnel','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_f_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_f_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_f_desc','backend','Dashboard / dash_f_desc','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies that signed up in this period and how far they got','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_f_signups' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_f_signups';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_f_signups','backend','Dashboard / dash_f_signups','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Signed up','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_f_trials' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_f_trials';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_f_trials','backend','Dashboard / dash_f_trials','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Chose a plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_f_paying' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_f_paying';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_f_paying','backend','Dashboard / dash_f_paying','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Became paying','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_f_renewed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_f_renewed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_f_renewed','backend','Dashboard / dash_f_renewed','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Renewed','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_f_of_prev' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_f_of_prev';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_f_of_prev','backend','Dashboard / dash_f_of_prev','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u% of prev.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_f_note' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_f_note';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_f_note','backend','Dashboard / dash_f_note','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u% of the new companies became paying customers.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_f_none' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_f_none';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_f_none','backend','Dashboard / dash_f_none','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No sign-ups in this period.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_pay_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_pay_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_pay_title','backend','Dashboard / dash_pay_title','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payment success','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_pay_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_pay_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_pay_desc','backend','Dashboard / dash_pay_desc','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Subscription payments created in this period','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_pay_successful' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_pay_successful';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_pay_successful','backend','Dashboard / dash_pay_successful','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','successful','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_pay_none_period' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_pay_none_period';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_pay_none_period','backend','Dashboard / dash_pay_none_period','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No payments in this period.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_paid_short' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_paid_short';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_paid_short','backend','Dashboard / dash_bill_paid_short','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Paid','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_failed_short' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_failed_short';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_failed_short','backend','Dashboard / dash_bill_failed_short','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Failed','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_open_short' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_open_short';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_open_short','backend','Dashboard / dash_bill_open_short','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Pending','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_up_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_up_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_up_title','backend','Dashboard / dash_up_title','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coming up (30 days)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_health_now' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_health_now';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_health_now','backend','Dashboard / dash_health_now','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Right now','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_per_day' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_per_day';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_per_day','backend','Dashboard / dash_per_day','script','2026-11-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','per day','script');
