-- Labels: roles (Super Admin, Companies Admin), Super Admin dashboard, reminder e-mails (Options > Notifications)
SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_role_arr_ARRAY_1' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_role_arr_ARRAY_1';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_role_arr_ARRAY_1','arrays','plugin_base_role_arr_ARRAY_1','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Super Admin','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='plugin_base_role_arr_ARRAY_6' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='plugin_base_role_arr_ARRAY_6';
INSERT INTO `plugin_base_fields` VALUES (NULL,'plugin_base_role_arr_ARRAY_6','arrays','plugin_base_role_arr_ARRAY_6','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies Admin','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblUserPlatformOnly' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblUserPlatformOnly';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblUserPlatformOnly','backend','lblUserPlatformOnly','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Platform only (no company data)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSuperAdminArea' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSuperAdminArea';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSuperAdminArea','backend','lblSuperAdminArea','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Super Admin','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblPlatformSettings' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblPlatformSettings';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblPlatformSettings','backend','lblPlatformSettings','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Platform settings','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_platform_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_platform_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_platform_title','backend','dash_platform_title','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Platform overview','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_platform_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_platform_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_platform_desc','backend','dash_platform_desc','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Totals of the whole platform. Company data (properties, reservations, guests, payments) is managed by the Companies Admin.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_platform_companies' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_platform_companies';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_platform_companies','backend','dash_platform_companies','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_platform_companies_active' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_platform_companies_active';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_platform_companies_active','backend','dash_platform_companies_active','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Active companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_platform_paying' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_platform_paying';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_platform_paying','backend','dash_platform_paying','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies with plan access','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_platform_no_plan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_platform_no_plan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_platform_no_plan','backend','dash_platform_no_plan','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies without a plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_platform_per_plan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_platform_per_plan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_platform_per_plan','backend','dash_platform_per_plan','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies per plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_platform_with_access' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_platform_with_access';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_platform_with_access','backend','dash_platform_with_access','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','With access','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_platform_per_status' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_platform_per_status';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_platform_per_status','backend','dash_platform_per_status','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Subscriptions per status','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_platform_users' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_platform_users';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_platform_users','backend','dash_platform_users','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Users per role','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='recipients_ARRAY_subscription' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='recipients_ARRAY_subscription';
INSERT INTO `plugin_base_fields` VALUES (NULL,'recipients_ARRAY_subscription','arrays','recipients_ARRAY_subscription','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Subscriptions (companies)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_msg_to_subscription' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_msg_to_subscription';
INSERT INTO `plugin_base_fields` VALUES (NULL,'notifications_msg_to_subscription','backend','notifications_msg_to_subscription','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Reminder e-mails sent to the companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_ARRAY_client_email_sub_trial_ending' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_ARRAY_client_email_sub_trial_ending';
INSERT INTO `plugin_base_fields` VALUES (NULL,'notifications_ARRAY_client_email_sub_trial_ending','arrays','notifications_ARRAY_client_email_sub_trial_ending','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Send "trial ends soon" e-mail','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_ARRAY_client_email_sub_renewal_due' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_ARRAY_client_email_sub_renewal_due';
INSERT INTO `plugin_base_fields` VALUES (NULL,'notifications_ARRAY_client_email_sub_renewal_due','arrays','notifications_ARRAY_client_email_sub_renewal_due','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Send "renewal due" e-mail','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_ARRAY_client_email_sub_expired' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_ARRAY_client_email_sub_expired';
INSERT INTO `plugin_base_fields` VALUES (NULL,'notifications_ARRAY_client_email_sub_expired','arrays','notifications_ARRAY_client_email_sub_expired','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Send "plan expired" e-mail','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_titles_ARRAY_client_email_sub_trial_ending' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_titles_ARRAY_client_email_sub_trial_ending';
INSERT INTO `plugin_base_fields` VALUES (NULL,'notifications_titles_ARRAY_client_email_sub_trial_ending','arrays','notifications_titles_ARRAY_client_email_sub_trial_ending','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Trial ends soon','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_titles_ARRAY_client_email_sub_renewal_due' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_titles_ARRAY_client_email_sub_renewal_due';
INSERT INTO `plugin_base_fields` VALUES (NULL,'notifications_titles_ARRAY_client_email_sub_renewal_due','arrays','notifications_titles_ARRAY_client_email_sub_renewal_due','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Renewal due','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_titles_ARRAY_client_email_sub_expired' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_titles_ARRAY_client_email_sub_expired';
INSERT INTO `plugin_base_fields` VALUES (NULL,'notifications_titles_ARRAY_client_email_sub_expired','arrays','notifications_titles_ARRAY_client_email_sub_expired','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plan expired','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_subtitles_ARRAY_client_email_sub_trial_ending' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_subtitles_ARRAY_client_email_sub_trial_ending';
INSERT INTO `plugin_base_fields` VALUES (NULL,'notifications_subtitles_ARRAY_client_email_sub_trial_ending','arrays','notifications_subtitles_ARRAY_client_email_sub_trial_ending','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Sent to the company 3 days before its free trial ends (once per trial).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_subtitles_ARRAY_client_email_sub_renewal_due' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_subtitles_ARRAY_client_email_sub_renewal_due';
INSERT INTO `plugin_base_fields` VALUES (NULL,'notifications_subtitles_ARRAY_client_email_sub_renewal_due','arrays','notifications_subtitles_ARRAY_client_email_sub_renewal_due','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Sent to the company 7 days before a paid plan has to be renewed (once per period; not when it moves to a free plan or cancels).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='notifications_subtitles_ARRAY_client_email_sub_expired' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='notifications_subtitles_ARRAY_client_email_sub_expired';
INSERT INTO `plugin_base_fields` VALUES (NULL,'notifications_subtitles_ARRAY_client_email_sub_expired','arrays','notifications_subtitles_ARRAY_client_email_sub_expired','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Sent to the company once when its plan has ended (trial or paid period).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSubscriptionTokens' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSubscriptionTokens';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSubscriptionTokens','backend','lblSubscriptionTokens','script','2026-11-13 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','<p>{Name} - contact person (or company name)<br>{Company} - company name<br>{Plan} - plan name<br>{Price} - plan price<br>{EndDate} - end / renewal date<br>{DaysLeft} - days left<br>{MyPlanLink} - link to My plan<br>{SiteName} - sender name</p>','script');

