-- Subscriptions > Coupons & Discounts: the administrator's discounts (automatic) and coupons (with a code) on plan prices.
-- Billing transactions keep the plan price, the discount and the coupon that were used.

CREATE TABLE IF NOT EXISTS `subscription_promos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kind` enum('discount','coupon') NOT NULL DEFAULT 'discount',
  `name` varchar(100) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `type` enum('percent','amount') NOT NULL DEFAULT 'percent',
  `value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `currency` char(3) DEFAULT NULL,
  `plan_ids` varchar(255) DEFAULT NULL,
  `kinds` varchar(50) DEFAULT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `max_uses` int(10) unsigned DEFAULT NULL,
  `max_per_company` int(10) unsigned DEFAULT NULL,
  `status` enum('T','F') NOT NULL DEFAULT 'T',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `kind_status` (`kind`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `billing_transactions` ADD COLUMN `list_amount` decimal(10,2) DEFAULT NULL AFTER `amount`, ADD COLUMN `discount_amount` decimal(10,2) DEFAULT NULL AFTER `list_amount`, ADD COLUMN `promo_ids` varchar(255) DEFAULT NULL AFTER `discount_amount`, ADD COLUMN `coupon_code` varchar(30) DEFAULT NULL AFTER `promo_ids`;

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_tab_promos' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_tab_promos';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_tab_promos','backend','Subscriptions / sub_tab_promos','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coupons & Discounts','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promos_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promos_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promos_desc','backend','Subscriptions / sub_promos_desc','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Discounts lower plan prices automatically. Coupons lower them only when the company enters the code while paying for a plan (My plan).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_tab_discounts' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_tab_discounts';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_tab_discounts','backend','Subscriptions / sub_promo_tab_discounts','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Discounts','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_tab_coupons' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_tab_coupons';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_tab_coupons','backend','Subscriptions / sub_promo_tab_coupons','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coupons','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_add_discount' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_add_discount';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_add_discount','backend','Subscriptions / sub_btn_add_discount','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Add discount','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_add_coupon' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_add_coupon';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_add_coupon','backend','Subscriptions / sub_btn_add_coupon','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Add coupon','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_edit_discount' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_edit_discount';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_edit_discount','backend','Subscriptions / sub_promo_edit_discount','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Edit discount','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_edit_coupon' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_edit_coupon';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_edit_coupon','backend','Subscriptions / sub_promo_edit_coupon','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Edit coupon','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_form_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_form_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_form_desc','backend','Subscriptions / sub_promo_form_desc','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Leave Plans and Applies to empty to use it for every plan and every payment.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_name' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_name';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_name','backend','Subscriptions / sub_promo_name','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Name','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_code' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_code';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_code','backend','Subscriptions / sub_promo_code','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coupon code','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_code_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_code_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_code_hint','backend','Subscriptions / sub_promo_code_hint','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','3 to 30 letters, digits, - or _. Companies can type it in small or capital letters.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_value' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_value';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_value','backend','Subscriptions / sub_promo_value','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Discount','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_type' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_type';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_type','backend','Subscriptions / sub_promo_type','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Type','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_type_percent' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_type_percent';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_type_percent','backend','Subscriptions / sub_promo_type_percent','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Percent (%)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_type_amount' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_type_amount';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_type_amount','backend','Subscriptions / sub_promo_type_amount','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Fixed amount','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_currency_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_currency_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_currency_hint','backend','Subscriptions / sub_promo_currency_hint','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Fixed amounts only apply to plans priced in this currency.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_plans' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_plans';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_plans','backend','Subscriptions / sub_promo_plans','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plans','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_all_plans' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_all_plans';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_all_plans','backend','Subscriptions / sub_promo_all_plans','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','All plans','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_kinds' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_kinds';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_kinds','backend','Subscriptions / sub_promo_kinds','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Applies to','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_all_kinds' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_all_kinds';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_all_kinds','backend','Subscriptions / sub_promo_all_kinds','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Every payment','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_valid_from' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_valid_from';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_valid_from','backend','Subscriptions / sub_promo_valid_from','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Valid from','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_valid_to' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_valid_to';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_valid_to','backend','Subscriptions / sub_promo_valid_to','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Valid to','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_validity' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_validity';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_validity','backend','Subscriptions / sub_promo_validity','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Validity','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_any_time' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_any_time';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_any_time','backend','Subscriptions / sub_promo_any_time','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Any time','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_max_uses' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_max_uses';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_max_uses','backend','Subscriptions / sub_promo_max_uses','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Total uses','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_max_per_company' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_max_per_company';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_max_per_company','backend','Subscriptions / sub_promo_max_per_company','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Uses per company','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_used' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_used';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_used','backend','Subscriptions / sub_promo_used','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Used','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_active' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_active';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_active','backend','Subscriptions / sub_promo_active','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Active','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_empty' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_empty';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_empty','backend','Subscriptions / sub_promo_empty','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Nothing added yet.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_btn_delete_promo' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_btn_delete_promo';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_btn_delete_promo','backend','Subscriptions / sub_btn_delete_promo','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Delete','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_promo_stack_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_promo_stack_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_promo_stack_hint','backend','Subscriptions / sub_promo_stack_hint','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','If several discounts fit a plan, the biggest one is used. A coupon is taken off the price after the discount.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_coupon_code' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_coupon_code';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_coupon_code','backend','Subscriptions / sub_coupon_code','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coupon code','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_coupon_apply' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_coupon_apply';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_coupon_apply','backend','Subscriptions / sub_coupon_apply','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Apply','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_coupon_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_coupon_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_coupon_hint','backend','Subscriptions / sub_coupon_hint','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Have a coupon? Enter it to see your price.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_coupon_ok' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_coupon_ok';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_coupon_ok','backend','Subscriptions / sub_coupon_ok','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coupon applied.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_list_price' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_list_price';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_list_price','backend','Subscriptions / sub_list_price','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plan price','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_discount' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_discount';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_discount','backend','Subscriptions / sub_discount','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Discount','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_you_pay' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_you_pay';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_you_pay','backend','Subscriptions / sub_you_pay','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','You pay','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_provider_promo' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_provider_promo';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_provider_promo','backend','Subscriptions / sub_provider_promo','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coupon / discount (nothing to pay)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrPromoFields' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrPromoFields';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrPromoFields','backend','Subscriptions / subErrPromoFields','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Fill in the name and the discount. A coupon also needs a code of 3 to 30 letters, digits, - or _.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrPromoCode' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrPromoCode';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrPromoCode','backend','Subscriptions / subErrPromoCode','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This coupon code is already used.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subErrCoupon' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subErrCoupon';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subErrCoupon','backend','Subscriptions / subErrCoupon','script','2026-10-30 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This coupon code is not valid for this plan.','script');
