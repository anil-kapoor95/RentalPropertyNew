-- Dashboards: billing history, upcoming renewals / trial endings, quick actions (labels)

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bh_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bh_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bh_title','backend','Dashboard / dash_bh_title','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Billing History','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_upcoming' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_upcoming';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_upcoming','backend','Dashboard / dash_bill_upcoming','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coming up in the next 30 days','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_tag_renewal' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_tag_renewal';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_tag_renewal','backend','Dashboard / dash_bill_tag_renewal','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Renewals','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_tag_trial' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_tag_trial';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_tag_trial','backend','Dashboard / dash_bill_tag_trial','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Trials ending','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_renewals_tip' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_renewals_tip';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_renewals_tip','backend','Dashboard / dash_bill_renewals_tip','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Paying companies whose subscription period ends and is due to be paid again.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_trials_tip' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_trials_tip';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_trials_tip','backend','Dashboard / dash_bill_trials_tip','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Free trials that end. These companies pay only if they buy the plan.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_may_convert' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_may_convert';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_may_convert','backend','Dashboard / dash_bill_may_convert','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%s if they buy','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_trial_ends_on' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_trial_ends_on';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_trial_ends_on','backend','Dashboard / dash_bill_trial_ends_on','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','trial ends','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_renews_on' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_renews_on';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_renews_on','backend','Dashboard / dash_bill_renews_on','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','renews','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_if_bought' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_if_bought';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_if_bought','backend','Dashboard / dash_bill_if_bought','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','if bought','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_qa_reservation' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_qa_reservation';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_qa_reservation','backend','Dashboard / dash_qa_reservation','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','New reservation','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_qa_schedule' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_qa_schedule';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_qa_schedule','backend','Dashboard / dash_qa_schedule','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Schedule','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_qa_property' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_qa_property';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_qa_property','backend','Dashboard / dash_qa_property','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Add property','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_limit_reached' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_limit_reached';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_limit_reached','backend','Dashboard / dash_bill_limit_reached','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plan limit reached: %s.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bill_see_plans' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bill_see_plans';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bill_see_plans','backend','Dashboard / dash_bill_see_plans','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','See plans','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_ago_hour' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_ago_hour';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_ago_hour','backend','Dashboard / dash_ago_hour','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','1 hour ago','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_ago_day' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_ago_day';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_ago_day','backend','Dashboard / dash_ago_day','script','2026-11-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','1 day ago','script');
