-- Subscription plans & billing (platform level).
-- Tables are unprefixed; the updater adds the prefix. Safe to run once (CREATE IF NOT EXISTS / INSERT IGNORE).
-- No card data is ever stored: only provider references (e.g. Stripe Checkout session / payment intent ids).

CREATE TABLE IF NOT EXISTS `plans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) DEFAULT NULL,
  `currency` char(3) DEFAULT NULL,
  `billing_interval` enum('month','year') NOT NULL DEFAULT 'month',
  `interval_count` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `trial_days` smallint(5) unsigned NOT NULL DEFAULT '0',
  `max_properties` int(10) unsigned DEFAULT NULL,
  `max_users` int(10) unsigned DEFAULT NULL,
  `max_payment_gateways` int(10) unsigned DEFAULT NULL,
  `ai_monthly_messages` int(10) unsigned DEFAULT NULL,
  `features` text,
  `is_public` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `status` enum('T','F') NOT NULL DEFAULT 'T',
  `sort` smallint(5) unsigned NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `company_subscriptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `plan_id` int(10) unsigned NOT NULL,
  `status` enum('trial','active','past_due','cancelled','expired') NOT NULL DEFAULT 'trial',
  `started_at` datetime DEFAULT NULL,
  `trial_ends_at` datetime DEFAULT NULL,
  `current_period_start` datetime DEFAULT NULL,
  `current_period_end` datetime DEFAULT NULL,
  `cancel_at_period_end` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `cancelled_at` datetime DEFAULT NULL,
  `pending_plan_id` int(10) unsigned DEFAULT NULL,
  `used_trial` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `notes` text,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`),
  KEY `plan_id` (`plan_id`),
  KEY `status_end` (`status`,`current_period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `billing_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(32) NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `plan_id` int(10) unsigned NOT NULL,
  `kind` enum('new','renewal','upgrade') NOT NULL DEFAULT 'new',
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL,
  `status` enum('pending','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `provider` varchar(20) NOT NULL,
  `provider_session` varchar(255) DEFAULT NULL,
  `provider_ref` varchar(255) DEFAULT NULL,
  `checkout_url` text,
  `period_start` datetime DEFAULT NULL,
  `period_end` datetime DEFAULT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `provider_session` (`provider`,`provider_session`),
  UNIQUE KEY `provider_ref` (`provider`,`provider_ref`),
  KEY `company_status` (`company_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `billing_webhook_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(20) NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `result` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_event` (`provider`,`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `subscription_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `type` varchar(40) NOT NULL,
  `message` varchar(500) DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_created` (`company_id`,`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `subscription_settings` (
  `key` varchar(64) NOT NULL,
  `value` text,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- The three product plans. Prices are NOT set (product decision pending): a plan without a
-- price can be assigned by the administrator but cannot be bought online.
-- Limits left empty (NULL) mean "unlimited" and are editable in Subscriptions > Plans.
INSERT IGNORE INTO `plans` (`code`, `name`, `description`, `price`, `currency`, `billing_interval`, `interval_count`, `trial_days`, `max_properties`, `max_users`, `max_payment_gateways`, `ai_monthly_messages`, `features`, `is_public`, `status`, `sort`, `created`) VALUES
('starter', 'Starter', 'One property, basic website / theme, property and availability management, one payment gateway, Airbnb / iCal sync, social sharing and booking e-mail notifications.', NULL, NULL, 'month', 1, 14, 1, NULL, 1, NULL, 'website,properties,availability,ical_sync,social_sharing,email_notifications,payment_gateway', 1, 'T', 10, NOW()),
('pro', 'Pro', 'Everything in Starter plus more properties, custom domain, more themes and customisation, payment gateway choice, coupons, booking policies, customer management, basic analytics, SEO settings and calendar notes.', NULL, NULL, 'month', 1, 14, NULL, NULL, NULL, NULL, 'website,properties,availability,ical_sync,social_sharing,email_notifications,payment_gateway,custom_domain,themes,payment_gateway_choice,coupons,booking_policies,customers,analytics,seo,calendar_notes', 1, 'T', 20, NOW()),
('business', 'Business', 'Everything in Pro plus an AI booking assistant that uses your published website content and FAQs and checks live availability.', NULL, NULL, 'month', 1, 14, NULL, NULL, NULL, NULL, 'website,properties,availability,ical_sync,social_sharing,email_notifications,payment_gateway,custom_domain,themes,payment_gateway_choice,coupons,booking_policies,customers,analytics,seo,calendar_notes,ai_chatbot', 1, 'T', 30, NOW());

-- Defaults (all editable in Subscriptions > Billing settings).
INSERT IGNORE INTO `subscription_settings` (`key`, `value`) VALUES
('provider', 'manual'),
('currency', 'USD'),
('grace_days', '7'),
('unassigned_mode', 'full'),
('expired_mode', 'read_only'),
('self_trial', '0'),
('stripe_mode', 'test'),
('stripe_secret_test', ''),
('stripe_secret_live', ''),
('stripe_whsec_test', ''),
('stripe_whsec_live', '');

-- Renewals / expiry / scheduled downgrades (system run only).
INSERT IGNORE INTO `plugin_base_cron_jobs` (`name`, `controller`, `action`, `interval`, `period`, `is_active`) VALUES
('Subscriptions: renewals, overdue and expiry', 'pjCron', 'pjActionSubscriptions', 1, 'hour', 1);

-- Permissions: the whole module for administrators (role 1 has every permission anyway; the
-- controller also checks the role). "My plan" for company logins (role 4).
DELETE FROM `plugin_auth_roles_permissions` WHERE `permission_id` IN (SELECT `id` FROM `plugin_auth_permissions` WHERE `key` LIKE 'pjAdminSubscriptions%');
DELETE FROM `plugin_auth_users_permissions` WHERE `permission_id` IN (SELECT `id` FROM `plugin_auth_permissions` WHERE `key` LIKE 'pjAdminSubscriptions%');
DELETE FROM `plugin_auth_permissions` WHERE `key` LIKE 'pjAdminSubscriptions%';
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`, `is_shown`) VALUES (NULL, NULL, 'pjAdminSubscriptions', 'F');
SET @level_1_id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`, `is_shown`) VALUES (NULL, @level_1_id, 'pjAdminSubscriptions_pjActionIndex', 'F');
INSERT INTO `plugin_auth_permissions` (`id`, `parent_id`, `key`, `is_shown`) VALUES (NULL, @level_1_id, 'pjAdminSubscriptions_pjActionMyPlan', 'F');
INSERT IGNORE INTO `plugin_auth_roles_permissions` (`role_id`, `permission_id`) SELECT 1, `id` FROM `plugin_auth_permissions` WHERE `key` LIKE 'pjAdminSubscriptions%';
INSERT IGNORE INTO `plugin_auth_roles_permissions` (`role_id`, `permission_id`) SELECT 4, `id` FROM `plugin_auth_permissions` WHERE `key` = 'pjAdminSubscriptions_pjActionMyPlan';
INSERT IGNORE INTO `plugin_auth_users_permissions` (`user_id`, `permission_id`) SELECT x.`uid`, p.`id` FROM `plugin_auth_permissions` p, (SELECT DISTINCT `user_id` AS `uid` FROM `plugin_auth_users_permissions` WHERE `user_id` IN (SELECT `id` FROM `plugin_auth_users` WHERE `role_id` = 4)) x WHERE p.`key` = 'pjAdminSubscriptions_pjActionMyPlan';

-- Labels (backend). Generated list; each label is delete-first, so re-running is safe.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_menu' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_menu';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_menu','backend','Subscriptions / sub_menu','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Subscriptions','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_index_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_index_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_index_desc','backend','Subscriptions / sub_index_desc','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plans and subscriptions of every company. Only the administrator sees this section.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_tab_companies' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_tab_companies';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_tab_companies','backend','Subscriptions / sub_tab_companies','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company subscriptions','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_tab_plans' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_tab_plans';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_tab_plans','backend','Subscriptions / sub_tab_plans','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plans','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_tab_billing' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_tab_billing';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_tab_billing','backend','Subscriptions / sub_tab_billing','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Billing history','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_tab_settings' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_tab_settings';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_tab_settings','backend','Subscriptions / sub_tab_settings','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Billing settings','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_manage_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_manage_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_manage_title','backend','Subscriptions / sub_manage_title','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Manage subscription','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_manage_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_manage_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_manage_desc','backend','Subscriptions / sub_manage_desc','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Assign a plan, change the status or dates and record payments. Every change is logged.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plans_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plans_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plans_desc','backend','Subscriptions / sub_plans_desc','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The plans you sell. Prices, limits and features are editable; a plan without a price cannot be bought online.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_add' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_add';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_add','backend','Subscriptions / sub_plan_add','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Add plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_edit' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_edit';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_edit','backend','Subscriptions / sub_plan_edit','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Edit plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_desc','backend','Subscriptions / sub_plan_desc','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Name, price, limits and features of the plan. Empty limit = unlimited.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_billing_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_billing_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_billing_desc','backend','Subscriptions / sub_billing_desc','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','All payments of companies to the platform.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_settings_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_settings_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_settings_desc','backend','Subscriptions / sub_settings_desc','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','How companies pay you and what happens when a subscription runs out.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_myplan_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_myplan_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_myplan_title','backend','Subscriptions / sub_myplan_title','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','My plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_myplan_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_myplan_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_myplan_desc','backend','Subscriptions / sub_myplan_desc','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your subscription: current plan, included features, usage, renewal date and other plans.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_website' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_website';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_website','backend','Subscriptions / sub_f_website','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Booking website (basic theme)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_properties' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_properties';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_properties','backend','Subscriptions / sub_f_properties','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Property management','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_availability' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_availability';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_availability','backend','Subscriptions / sub_f_availability','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Availability calendar','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_ical_sync' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_ical_sync';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_ical_sync','backend','Subscriptions / sub_f_ical_sync','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Airbnb / iCal sync','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_social_sharing' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_social_sharing';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_social_sharing','backend','Subscriptions / sub_f_social_sharing','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Social sharing','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_email_notifications' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_email_notifications';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_email_notifications','backend','Subscriptions / sub_f_email_notifications','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Booking e-mail notifications','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_payment_gateway' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_payment_gateway';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_payment_gateway','backend','Subscriptions / sub_f_payment_gateway','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Online payment gateway','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_custom_domain' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_custom_domain';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_custom_domain','backend','Subscriptions / sub_f_custom_domain','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Custom domain','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_themes' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_themes';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_themes','backend','Subscriptions / sub_f_themes','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','More themes and customisation','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_payment_gateway_choice' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_payment_gateway_choice';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_payment_gateway_choice','backend','Subscriptions / sub_f_payment_gateway_choice','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choice of payment gateways','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_coupons' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_coupons';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_coupons','backend','Subscriptions / sub_f_coupons','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coupons and discounts','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_booking_policies' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_booking_policies';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_booking_policies','backend','Subscriptions / sub_f_booking_policies','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Booking policies (stay limits)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_customers' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_customers';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_customers','backend','Subscriptions / sub_f_customers','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Customer management','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_analytics' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_analytics';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_analytics','backend','Subscriptions / sub_f_analytics','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Basic analytics','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_seo' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_seo';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_seo','backend','Subscriptions / sub_f_seo','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','SEO settings','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_calendar_notes' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_calendar_notes';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_calendar_notes','backend','Subscriptions / sub_f_calendar_notes','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Calendar notes','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_f_ai_chatbot' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_f_ai_chatbot';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_f_ai_chatbot','backend','Subscriptions / sub_f_ai_chatbot','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','AI booking assistant','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_status_trial' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_status_trial';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_status_trial','backend','Subscriptions / sub_status_trial','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Trial','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_status_active' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_status_active';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_status_active','backend','Subscriptions / sub_status_active','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Active','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_status_past_due' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_status_past_due';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_status_past_due','backend','Subscriptions / sub_status_past_due','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Past due','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_status_cancelled' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_status_cancelled';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_status_cancelled','backend','Subscriptions / sub_status_cancelled','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Cancelled','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_status_expired' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_status_expired';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_status_expired','backend','Subscriptions / sub_status_expired','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Expired','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_status_none' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_status_none';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_status_none','backend','Subscriptions / sub_status_none','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No subscription','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_tx_pending' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_tx_pending';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_tx_pending','backend','Subscriptions / sub_tx_pending','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Pending','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_tx_paid' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_tx_paid';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_tx_paid','backend','Subscriptions / sub_tx_paid','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Paid','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_tx_failed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_tx_failed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_tx_failed','backend','Subscriptions / sub_tx_failed','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Failed','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_tx_cancelled' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_tx_cancelled';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_tx_cancelled','backend','Subscriptions / sub_tx_cancelled','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Cancelled','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_tx_refunded' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_tx_refunded';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_tx_refunded','backend','Subscriptions / sub_tx_refunded','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Refunded','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_kind_new' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_kind_new';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_kind_new','backend','Subscriptions / sub_kind_new','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','new','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_kind_renewal' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_kind_renewal';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_kind_renewal','backend','Subscriptions / sub_kind_renewal','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','renewal','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_kind_upgrade' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_kind_upgrade';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_kind_upgrade','backend','Subscriptions / sub_kind_upgrade','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','upgrade','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_provider_manual' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_provider_manual';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_provider_manual','backend','Subscriptions / sub_provider_manual','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Manual (bank transfer etc.)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_provider_stripe' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_provider_stripe';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_provider_stripe','backend','Subscriptions / sub_provider_stripe','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Stripe','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_col_company' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_col_company';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_col_company','backend','Subscriptions / sub_col_company','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_col_plan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_col_plan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_col_plan','backend','Subscriptions / sub_col_plan','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_col_status' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_col_status';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_col_status','backend','Subscriptions / sub_col_status','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Status','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_col_renewal' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_col_renewal';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_col_renewal','backend','Subscriptions / sub_col_renewal','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Renewal date','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_col_properties' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_col_properties';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_col_properties','backend','Subscriptions / sub_col_properties','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Properties','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_col_users' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_col_users';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_col_users','backend','Subscriptions / sub_col_users','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Users','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_col_price' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_col_price';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_col_price','backend','Subscriptions / sub_col_price','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Price','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_col_companies' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_col_companies';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_col_companies','backend','Subscriptions / sub_col_companies','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_col_date' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_col_date';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_col_date','backend','Subscriptions / sub_col_date','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Date','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_col_amount' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_col_amount';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_col_amount','backend','Subscriptions / sub_col_amount','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Amount','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_col_method' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_col_method';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_col_method','backend','Subscriptions / sub_col_method','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Method','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_col_period' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_col_period';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_col_period','backend','Subscriptions / sub_col_period','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Period','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_reference' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_reference';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_reference','backend','Subscriptions / sub_reference','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Reference','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_amount' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_amount';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_amount','backend','Subscriptions / sub_amount','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Amount','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_note' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_note';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_note','backend','Subscriptions / sub_note','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Note (optional)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_name' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_name';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_name','backend','Subscriptions / sub_plan_name','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plan name','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_code' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_code';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_code','backend','Subscriptions / sub_plan_code','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Code','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_currency' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_currency';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_currency','backend','Subscriptions / sub_currency','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Currency','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_billing_interval' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_billing_interval';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_billing_interval','backend','Subscriptions / sub_billing_interval','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Billed every','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_sort' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_sort';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_sort','backend','Subscriptions / sub_sort','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Order','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_description' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_description';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_description','backend','Subscriptions / sub_description','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Description','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_trial_days' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_trial_days';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_trial_days','backend','Subscriptions / sub_trial_days','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Trial days','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_max_properties' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_max_properties';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_max_properties','backend','Subscriptions / sub_max_properties','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Max. properties','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_max_users' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_max_users';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_max_users','backend','Subscriptions / sub_max_users','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Max. company users','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_max_payment_gateways' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_max_payment_gateways';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_max_payment_gateways','backend','Subscriptions / sub_max_payment_gateways','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Max. payment gateways','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_ai_monthly_messages' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_ai_monthly_messages';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_ai_monthly_messages','backend','Subscriptions / sub_ai_monthly_messages','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','AI messages / month','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_limits' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_limits';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_limits','backend','Subscriptions / sub_limits','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Limits','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_features' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_features';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_features','backend','Subscriptions / sub_features','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Features','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_public' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_public';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_public','backend','Subscriptions / sub_public','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Shown to companies','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_active_plan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_active_plan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_active_plan','backend','Subscriptions / sub_active_plan','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Active','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_inactive' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_inactive';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_inactive','backend','Subscriptions / sub_inactive','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Inactive','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_hidden' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_hidden';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_hidden','backend','Subscriptions / sub_hidden','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','hidden','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_month' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_month';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_month','backend','Subscriptions / sub_month','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','month','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_year' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_year';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_year','backend','Subscriptions / sub_year','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','year','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_unlimited' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_unlimited';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_unlimited','backend','Subscriptions / sub_unlimited','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Unlimited','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_price_not_set' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_price_not_set';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_price_not_set','backend','Subscriptions / sub_price_not_set','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Price not set','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_not_purchasable' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_not_purchasable';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_not_purchasable','backend','Subscriptions / sub_not_purchasable','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Not for sale online (no price)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_started' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_started';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_started','backend','Subscriptions / sub_started','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Started','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_trial_ends' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_trial_ends';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_trial_ends','backend','Subscriptions / sub_trial_ends','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Trial ends','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_grace_until' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_grace_until';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_grace_until','backend','Subscriptions / sub_grace_until','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Grace period until','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_scheduled_change' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_scheduled_change';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_scheduled_change','backend','Subscriptions / sub_scheduled_change','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plan change at period end','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_cancels_at_end' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_cancels_at_end';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_cancels_at_end','backend','Subscriptions / sub_cancels_at_end','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Cancels at the end of the period','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_then' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_then';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_then','backend','Subscriptions / sub_then','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','then','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_stored' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_stored';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_stored','backend','Subscriptions / sub_stored','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','saved','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_company_inactive' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_company_inactive';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_company_inactive','backend','Subscriptions / sub_company_inactive','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','inactive','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_history' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_history';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_history','backend','Subscriptions / sub_history','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','History','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_nothing' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_nothing';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_nothing','backend','Subscriptions / sub_nothing','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Nothing yet.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_per_property' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_per_property';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_per_property','backend','Subscriptions / sub_per_property','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','per property','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_current' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_current';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_current','backend','Subscriptions / sub_current','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Current','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_current_plan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_current_plan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_current_plan','backend','Subscriptions / sub_current_plan','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Current plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_no_plan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_no_plan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_no_plan','backend','Subscriptions / sub_no_plan','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_included' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_included';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_included','backend','Subscriptions / sub_included','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Included in your plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_assign_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_assign_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_assign_title','backend','Subscriptions / sub_assign_title','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Assign a plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_assign_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_assign_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_assign_hint','backend','Subscriptions / sub_assign_hint','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Explicit administrator action: the plan is switched on without a payment. Empty date = one billing period (trial: the plan''s trial length).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_period_end_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_period_end_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_period_end_hint','backend','Subscriptions / sub_period_end_hint','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','End date (optional)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_status_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_status_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_status_title','backend','Subscriptions / sub_status_title','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Change status','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_extend_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_extend_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_extend_title','backend','Subscriptions / sub_extend_title','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Renewal / end date','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_schedule_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_schedule_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_schedule_title','backend','Subscriptions / sub_schedule_title','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plan change at the end of the period','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_payment_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_payment_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_payment_title','backend','Subscriptions / sub_payment_title','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Record a payment','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_payment_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_payment_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_payment_hint','backend','Subscriptions / sub_payment_hint','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payment received outside the system (bank transfer...). Activates or renews the plan for one billing period. The reference must be unique.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_price_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_price_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_price_hint','backend','Subscriptions / sub_price_hint','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Leave the price empty until it is decided: the plan can still be assigned by you, but companies cannot buy it online.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_limits_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_limits_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_limits_hint','backend','Subscriptions / sub_limits_hint','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Empty = unlimited. Max. payment gateways counts online gateways switched on per property (cash and bank transfer are not counted).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_features_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_features_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_features_hint','backend','Subscriptions / sub_features_hint','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Features that are not built yet (custom domain, customer management, analytics, calendar notes, AI assistant) are switches only: they unlock the feature as soon as it exists.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_delete_plan_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_delete_plan_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_delete_plan_hint','backend','Subscriptions / sub_delete_plan_hint','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Only possible while no company uses the plan.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_note_unassigned_full' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_note_unassigned_full';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_note_unassigned_full','backend','Subscriptions / sub_note_unassigned_full','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies without a subscription keep full access (setting "Companies without a plan").','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_note_unassigned_restricted' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_note_unassigned_restricted';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_note_unassigned_restricted','backend','Subscriptions / sub_note_unassigned_restricted','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies without a subscription have the basic features only (setting "Companies without a plan").','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_unassigned_full' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_unassigned_full';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_unassigned_full','backend','Subscriptions / sub_unassigned_full','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No plan - full access','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_unassigned_restricted' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_unassigned_restricted';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_unassigned_restricted','backend','Subscriptions / sub_unassigned_restricted','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No plan - basic only','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_unassigned_full_msg' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_unassigned_full_msg';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_unassigned_full_msg','backend','Subscriptions / sub_unassigned_full_msg','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','You have no subscription yet; all features are available for now.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_unassigned_restricted_msg' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_unassigned_restricted_msg';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_unassigned_restricted_msg','backend','Subscriptions / sub_unassigned_restricted_msg','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','You have no subscription yet: only the basic features are available. Choose a plan below.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_past_due_msg' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_past_due_msg';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_past_due_msg','backend','Subscriptions / sub_past_due_msg','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your payment is overdue. The plan stays active until','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_expired_msg' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_expired_msg';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_expired_msg','backend','Subscriptions / sub_expired_msg','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your subscription has ended: only the basic features are available. Renew or choose a plan below.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_contact_admin' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_contact_admin';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_contact_admin','backend','Subscriptions / sub_contact_admin','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Contact us to switch to this plan.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_manual_note' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_manual_note';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_manual_note','backend','Subscriptions / sub_manual_note','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Online payment is not available yet: choosing a plan sends a payment request to the administrator.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_rules' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_rules';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_rules','backend','Subscriptions / sub_rules','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Rules','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_grace_days' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_grace_days';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_grace_days','backend','Subscriptions / sub_grace_days','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Grace days after a missed renewal','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_unassigned_mode' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_unassigned_mode';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_unassigned_mode','backend','Subscriptions / sub_unassigned_mode','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies without a plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_expired_mode' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_expired_mode';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_expired_mode','backend','Subscriptions / sub_expired_mode','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','When a subscription ends','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_expired_read_only' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_expired_read_only';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_expired_read_only','backend','Subscriptions / sub_expired_read_only','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Basic features only, bookings continue','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_expired_suspend' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_expired_suspend';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_expired_suspend','backend','Subscriptions / sub_expired_suspend','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Basic features only, new bookings refused','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_self_trial' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_self_trial';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_self_trial','backend','Subscriptions / sub_self_trial','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies may start a trial themselves','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_yes' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_yes';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_yes','backend','Subscriptions / sub_yes','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Yes','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_no' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_no';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_no','backend','Subscriptions / sub_no','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_rules_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_rules_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_rules_hint','backend','Subscriptions / sub_rules_hint','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','"Full access" keeps existing companies working until you assign them a plan.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_provider_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_provider_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_provider_title','backend','Subscriptions / sub_provider_title','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payment provider','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_provider' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_provider';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_provider','backend','Subscriptions / sub_provider','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Provider','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_stripe_mode' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_stripe_mode';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_stripe_mode','backend','Subscriptions / sub_stripe_mode','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Stripe mode','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_stripe_test' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_stripe_test';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_stripe_test','backend','Subscriptions / sub_stripe_test','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Test','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_stripe_live' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_stripe_live';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_stripe_live','backend','Subscriptions / sub_stripe_live','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Live','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_stripe_secret_test' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_stripe_secret_test';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_stripe_secret_test','backend','Subscriptions / sub_stripe_secret_test','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Stripe secret key (test)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_stripe_secret_live' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_stripe_secret_live';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_stripe_secret_live','backend','Subscriptions / sub_stripe_secret_live','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Stripe secret key (live)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_stripe_whsec_test' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_stripe_whsec_test';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_stripe_whsec_test','backend','Subscriptions / sub_stripe_whsec_test','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Webhook signing secret (test)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_stripe_whsec_live' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_stripe_whsec_live';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_stripe_whsec_live','backend','Subscriptions / sub_stripe_whsec_live','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Webhook signing secret (live)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_secret_set' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_secret_set';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_secret_set','backend','Subscriptions / sub_secret_set','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Saved - type to replace','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_secret_empty' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_secret_empty';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_secret_empty','backend','Subscriptions / sub_secret_empty','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Not set','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_secret_clear' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_secret_clear';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_secret_clear','backend','Subscriptions / sub_secret_clear','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Remove','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_webhook_url' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_webhook_url';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_webhook_url','backend','Subscriptions / sub_webhook_url','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Stripe webhook URL','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_webhook_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_webhook_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_webhook_hint','backend','Subscriptions / sub_webhook_hint','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','In Stripe > Developers > Webhooks add this URL with the events checkout.session.completed, checkout.session.async_payment_succeeded, checkout.session.async_payment_failed and checkout.session.expired, then paste its signing secret above.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_manage' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_manage';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_manage','backend','Subscriptions / sub_btn_manage','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Manage','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_assign' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_assign';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_assign','backend','Subscriptions / sub_btn_assign','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Assign','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_save' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_save';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_save','backend','Subscriptions / sub_btn_save','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Save','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_cancel' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_cancel';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_cancel','backend','Subscriptions / sub_btn_cancel','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Cancel','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_record' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_record';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_record','backend','Subscriptions / sub_btn_record','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Record payment','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_add_plan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_add_plan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_add_plan','backend','Subscriptions / sub_btn_add_plan','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Add plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_delete_plan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_delete_plan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_delete_plan','backend','Subscriptions / sub_btn_delete_plan','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Delete plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_cancel_end' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_cancel_end';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_cancel_end','backend','Subscriptions / sub_btn_cancel_end','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Cancel at period end','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_undo_cancel' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_undo_cancel';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_undo_cancel','backend','Subscriptions / sub_btn_undo_cancel','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Keep the subscription','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_undo_downgrade' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_undo_downgrade';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_undo_downgrade','backend','Subscriptions / sub_btn_undo_downgrade','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Keep the current plan','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_renew' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_renew';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_renew','backend','Subscriptions / sub_btn_renew','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Renew / pay','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_subscribe' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_subscribe';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_subscribe','backend','Subscriptions / sub_btn_subscribe','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Subscribe','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_trial' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_trial';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_trial','backend','Subscriptions / sub_btn_trial','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Start free trial','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_upgrade' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_upgrade';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_upgrade','backend','Subscriptions / sub_btn_upgrade','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Upgrade','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_downgrade' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_downgrade';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_downgrade','backend','Subscriptions / sub_btn_downgrade','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Downgrade at period end','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_see_plans' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_see_plans';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_see_plans','backend','Subscriptions / sub_btn_see_plans','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','See plans','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_confirm' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_confirm';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_confirm','backend','Subscriptions / sub_confirm','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Are you sure?','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subSaved' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subSaved';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subSaved','backend','Subscriptions / subSaved','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Saved.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subTrialStarted' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subTrialStarted';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subTrialStarted','backend','Subscriptions / subTrialStarted','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your trial has started.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subDowngradeScheduled' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subDowngradeScheduled';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subDowngradeScheduled','backend','Subscriptions / subDowngradeScheduled','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The new plan starts at the end of the current period.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subCancelScheduled' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subCancelScheduled';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subCancelScheduled','backend','Subscriptions / subCancelScheduled','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your subscription ends at the end of the current period.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subPaymentOk' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subPaymentOk';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subPaymentOk','backend','Subscriptions / subPaymentOk','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payment received - thank you! Your plan is active.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subRequestSent' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subRequestSent';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subRequestSent','backend','Subscriptions / subRequestSent','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your request was sent. The plan is switched on as soon as the payment is confirmed.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subPaymentPending' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subPaymentPending';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subPaymentPending','backend','Subscriptions / subPaymentPending','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','We have not received the payment confirmation yet. Your plan is switched on as soon as it arrives.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subPaymentCancelled' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subPaymentCancelled';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subPaymentCancelled','backend','Subscriptions / subPaymentCancelled','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The payment was cancelled. Nothing was charged.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrSave' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrSave';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrSave','backend','Subscriptions / subErrSave','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Nothing was changed.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrCsrf' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrCsrf';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrCsrf','backend','Subscriptions / subErrCsrf','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The form expired. Please try again.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrCompany' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrCompany';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrCompany','backend','Subscriptions / subErrCompany','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company not found.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrPlan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrPlan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrPlan','backend','Subscriptions / subErrPlan','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This plan cannot be chosen.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrTrial' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrTrial';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrTrial','backend','Subscriptions / subErrTrial','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','A trial is not available.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrCheckout' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrCheckout';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrCheckout','backend','Subscriptions / subErrCheckout','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The payment could not be started. Please contact us.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrPlanUsed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrPlanUsed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrPlanUsed','backend','Subscriptions / subErrPlanUsed','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This plan is used by companies or payments and cannot be deleted. Set it to inactive instead.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrPlanFields' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrPlanFields';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrPlanFields','backend','Subscriptions / subErrPlanFields','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Name and code (2-32 lowercase letters, digits, - or _) are required.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrPlanCode' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrPlanCode';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrPlanCode','backend','Subscriptions / subErrPlanCode','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This code is already used by another plan.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrKey' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrKey';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrKey','backend','Subscriptions / subErrKey','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The key does not look right (sk_test_ / sk_live_ / whsec_...). Nothing was changed for it.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrDuplicateRef' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrDuplicateRef';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrDuplicateRef','backend','Subscriptions / subErrDuplicateRef','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','A payment with this reference is already recorded.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrLimit' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrLimit';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrLimit','backend','Subscriptions / subErrLimit','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your plan does not allow more users. Upgrade to add more.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subLocked' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subLocked';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subLocked','backend','Subscriptions / subLocked','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This is not included in your subscription plan.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subLockedTab' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subLockedTab';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subLockedTab','backend','Subscriptions / subLockedTab','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This section is not included in your subscription plan. Saved settings stay, but they cannot be changed.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subLimitProperties' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subLimitProperties';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subLimitProperties','backend','Subscriptions / subLimitProperties','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your subscription plan does not allow more properties.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subGatewayLimit' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subGatewayLimit';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subGatewayLimit','backend','Subscriptions / subGatewayLimit','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your subscription plan does not allow more online payment gateways for this property.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subFrontSuspended' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subFrontSuspended';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subFrontSuspended','backend','Subscriptions / subFrontSuspended','script','2026-10-15 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Online booking is temporarily unavailable. Please contact us directly.','script');
