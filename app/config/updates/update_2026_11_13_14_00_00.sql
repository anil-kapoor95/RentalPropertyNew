-- Company Coupons & Discounts: same layout as the Super Admin's Coupons & Discounts (labels of the new pages)

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_desc','backend','Coupons / cd_desc','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Discounts lower booking prices automatically. Coupons lower them only when the guest enters the code while booking.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_hint','backend','Coupons / cd_hint','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Each discount and coupon works only on the properties, dates and conditions you choose. A coupon can be used together with a discount.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_form_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_form_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_form_desc','backend','Coupons / cd_form_desc','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose the properties, the dates and when it applies. Bookings that match get the discount.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_code_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_code_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_code_hint','backend','Coupons / cd_code_hint','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The code guests type when they book (unique in your company).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_uses_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_uses_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_uses_hint','backend','Coupons / cd_uses_hint','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','How many confirmed bookings can use the code.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_col_properties' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_col_properties';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_col_properties','backend','Coupons / cd_col_properties','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Properties','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_col_conditions' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_col_conditions';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_col_conditions','backend','Coupons / cd_col_conditions','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Conditions','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_col_rule' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_col_rule';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_col_rule','backend','Coupons / cd_col_rule','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Rule','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_apply_on' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_apply_on';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_apply_on','backend','Coupons / cd_apply_on','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Applies to','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_condition' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_condition';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_condition','backend','Coupons / cd_condition','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Valid for','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_status_active' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_status_active';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_status_active','backend','Coupons / cd_status_active','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Active','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_status_scheduled' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_status_scheduled';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_status_scheduled','backend','Coupons / cd_status_scheduled','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Scheduled','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_status_expired' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_status_expired';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_status_expired','backend','Coupons / cd_status_expired','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Expired','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_rule_early' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_rule_early';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_rule_early','backend','Coupons / cd_rule_early','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u+ days ahead','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_rule_persons' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_rule_persons';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_rule_persons','backend','Coupons / cd_rule_persons','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u-%u guests','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_rule_duration' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_rule_duration';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_rule_duration','backend','Coupons / cd_rule_duration','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u-%u nights','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_rule_early_days' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_rule_early_days';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_rule_early_days','backend','Coupons / cd_rule_early_days','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Days before arrival','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_rule_guests' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_rule_guests';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_rule_guests','backend','Coupons / cd_rule_guests','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Guests','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_rule_nights' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_rule_nights';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_rule_nights','backend','Coupons / cd_rule_nights','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Nights','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_btn_export' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_btn_export';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_btn_export','backend','Coupons / cd_btn_export','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Export CSV','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_btn_delete_coupon' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_btn_delete_coupon';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_btn_delete_coupon','backend','Coupons / cd_btn_delete_coupon','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Delete coupon','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cd_btn_delete_discount' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cd_btn_delete_discount';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cd_btn_delete_discount','backend','Coupons / cd_btn_delete_discount','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Delete discount','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cdSaved' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cdSaved';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cdSaved','backend','Coupons / cdSaved','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Saved.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cdDeleted' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cdDeleted';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cdDeleted','backend','Coupons / cdDeleted','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Deleted.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cdErrFields' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cdErrFields';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cdErrFields','backend','Coupons / cdErrFields','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Fill in the required fields: a discount above 0 (at most 100%), valid dates (from not after to) and at least one property.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cdErrCode' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cdErrCode';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cdErrCode','backend','Coupons / cdErrCode','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Enter a coupon code that is not used yet (up to 100 characters).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cdErrRule' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cdErrRule';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cdErrRule','backend','Coupons / cdErrRule','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Fill in the rule: a minimum above 0 and a maximum not below the minimum.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='cdErrSave' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='cdErrSave';
INSERT INTO `plugin_base_fields` VALUES (NULL,'cdErrSave','backend','Coupons / cdErrSave','script','2026-11-13 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','It could not be saved. Please try again.','script');
