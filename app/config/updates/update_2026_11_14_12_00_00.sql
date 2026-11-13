-- Dashboards redesign (billing overview, change badges) and permission limits in Users: labels

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_vs_previous' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_vs_previous';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_vs_previous','backend','Dashboard / dash_vs_previous','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Compared with the previous period of the same length','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_vs_last_month' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_vs_last_month';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_vs_last_month','backend','Dashboard / dash_vs_last_month','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Compared with last month up to the same day','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_badge_new' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_badge_new';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_badge_new','backend','Dashboard / dash_badge_new','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','New','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_ago_now' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_ago_now';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_ago_now','backend','Dashboard / dash_ago_now','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','just now','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_ago_minutes' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_ago_minutes';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_ago_minutes','backend','Dashboard / dash_ago_minutes','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u min ago','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_ago_hours' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_ago_hours';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_ago_hours','backend','Dashboard / dash_ago_hours','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u hours ago','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_ago_days' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_ago_days';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_ago_days','backend','Dashboard / dash_ago_days','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u days ago','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_act_t_registration' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_act_t_registration';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_act_t_registration','backend','Dashboard / dash_act_t_registration','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','New company registered','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_act_t_purchase' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_act_t_purchase';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_act_t_purchase','backend','Dashboard / dash_act_t_purchase','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','New subscription purchased','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_act_t_upgrade' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_act_t_upgrade';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_act_t_upgrade','backend','Dashboard / dash_act_t_upgrade','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plan upgraded','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_act_t_coupon' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_act_t_coupon';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_act_t_coupon','backend','Dashboard / dash_act_t_coupon','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coupon used','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_act_t_change' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_act_t_change';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_act_t_change','backend','Dashboard / dash_act_t_change','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plan changed','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_act_s_coupon' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_act_s_coupon';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_act_s_coupon','backend','Dashboard / dash_act_s_coupon','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%s by %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_ok_gateway' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_ok_gateway';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_ok_gateway','backend','Dashboard / dash_h_ok_gateway','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Online','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_ok_email' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_ok_email';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_ok_email','backend','Dashboard / dash_h_ok_email','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Online','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_ok_tasks' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_ok_tasks';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_ok_tasks','backend','Dashboard / dash_h_ok_tasks','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Running','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_h_ok_jobs' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_h_ok_jobs';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_h_ok_jobs','backend','Dashboard / dash_h_ok_jobs','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Healthy','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_billing_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_billing_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_billing_title','backend','Dashboard / dash_billing_title','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Billing Overview','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_revenue' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_revenue';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_revenue','backend','Dashboard / dash_bill_revenue','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Revenue in this period','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_paid' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_paid';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_paid','backend','Dashboard / dash_bill_paid','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Paid payments','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_open_failed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_open_failed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_open_failed','backend','Dashboard / dash_bill_open_failed','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Open / failed payments','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_past_due' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_past_due';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_past_due','backend','Dashboard / dash_bill_past_due','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies past due','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_renewals' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_renewals';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_renewals','backend','Dashboard / dash_bill_renewals','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Renewals in the next 30 days: %u','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_no_renewals' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_no_renewals';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_no_renewals','backend','Dashboard / dash_bill_no_renewals','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No renewals in the next 30 days.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_manage_plan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_manage_plan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_manage_plan','backend','Dashboard / dash_manage_plan','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Manage plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_admin_workspace' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_admin_workspace';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_admin_workspace','backend','Dashboard / dash_bill_admin_workspace','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This workspace belongs to the platform and has no subscription.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_pay_status' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_pay_status';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_pay_status','backend','Dashboard / dash_pay_status','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payment status','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_pay_paid' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_pay_paid';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_pay_paid','backend','Dashboard / dash_pay_paid','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Paid','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_pay_free' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_pay_free';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_pay_free','backend','Dashboard / dash_pay_free','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Free','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_pay_trial' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_pay_trial';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_pay_trial','backend','Dashboard / dash_pay_trial','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Trial, nothing to pay yet','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_pay_pending' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_pay_pending';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_pay_pending','backend','Dashboard / dash_pay_pending','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payment pending','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_pay_failed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_pay_failed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_pay_failed','backend','Dashboard / dash_pay_failed','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Last payment failed','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_pay_overdue' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_pay_overdue';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_pay_overdue','backend','Dashboard / dash_pay_overdue','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Overdue','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_pay_ended' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_pay_ended';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_pay_ended','backend','Dashboard / dash_pay_ended','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Ended','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_pay_none' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_pay_none';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_pay_none','backend','Dashboard / dash_pay_none','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No subscription','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_next' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_next';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_next','backend','Dashboard / dash_bill_next','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Next billing date','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_ends' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_ends';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_ends','backend','Dashboard / dash_bill_ends','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Ends on','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_price' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_price';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_price','backend','Dashboard / dash_bill_price','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Price','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_last_payment' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_last_payment';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_last_payment','backend','Dashboard / dash_bill_last_payment','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Last payment','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_every_month' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_every_month';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_every_month','backend','Dashboard / dash_bill_every_month','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','every %u months','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_every_year' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_every_year';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_every_year','backend','Dashboard / dash_bill_every_year','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','every %u years','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_scheduled' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_scheduled';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_scheduled','backend','Dashboard / dash_bill_scheduled','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Changes to %s on %s.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_cancels' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_cancels';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_cancels','backend','Dashboard / dash_bill_cancels','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The subscription ends on %s and will not renew.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_overdue' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_overdue';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_overdue','backend','Dashboard / dash_bill_overdue','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The payment is overdue. Access continues until %s.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_failed_msg' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_failed_msg';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_failed_msg','backend','Dashboard / dash_bill_failed_msg','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The last payment did not go through. Please try again from My plan.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_company' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_company';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_company','backend','Dashboard / dash_company','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_company_subtitle' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_company_subtitle';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_company_subtitle','backend','Dashboard / dash_company_subtitle','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Here is an overview of your business today, %s.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_today_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_today_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_today_title','backend','Dashboard / dash_today_title','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Today','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_today_none' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_today_none';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_today_none','backend','Dashboard / dash_today_none','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No arrivals or departures today.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_arrives' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_arrives';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_arrives','backend','Dashboard / dash_arrives','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Arrives','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_leaves' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_leaves';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_leaves','backend','Dashboard / dash_leaves','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Leaves','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_and_more' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_and_more';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_and_more','backend','Dashboard / dash_and_more','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','and %u more','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_user_more_rights' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_user_more_rights';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_user_more_rights','backend','Users / plugin_base_user_more_rights','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This user has permissions that you do not have, so you cannot change this user.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_user_perm_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_user_perm_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_user_perm_hint','backend','Users / plugin_base_user_perm_hint','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The new user gets the permissions of this role that you have yourself. You can narrow them down later.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_user_perm_own_only' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_user_perm_own_only';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_user_perm_own_only','backend','Users / plugin_base_user_perm_own_only','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Only permissions that you have yourself are listed and can be given.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_error_titles_ARRAY_PU09' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_error_titles_ARRAY_PU09';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_error_titles_ARRAY_PU09','arrays','plugin_base_error_titles_ARRAY_PU09','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Not allowed','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_error_bodies_ARRAY_PU09' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_error_bodies_ARRAY_PU09';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_error_bodies_ARRAY_PU09','arrays','plugin_base_error_bodies_ARRAY_PU09','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This user has permissions that you do not have, so you cannot change this user.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_not_needed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_not_needed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_not_needed','backend','Dashboard / dash_bill_not_needed','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Not needed','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_per_month' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_per_month';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_per_month','backend','Dashboard / dash_bill_per_month','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','per month','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_per_year' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_per_year';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_per_year','backend','Dashboard / dash_bill_per_year','script','2026-11-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','per year','script');
