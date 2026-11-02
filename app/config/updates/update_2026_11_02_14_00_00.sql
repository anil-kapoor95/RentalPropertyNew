-- Booking on the host's website (/property/<slug>/book): texts of the booking pages (Languages in the back office).

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_step_stay' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_step_stay';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_step_stay','frontend','Website booking / book_step_stay','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your stay','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_step_extras' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_step_extras';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_step_extras','frontend','Website booking / book_step_extras','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Extras','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_step_details' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_step_details';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_step_details','frontend','Website booking / book_step_details','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your details','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_step_payment' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_step_payment';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_step_payment','frontend','Website booking / book_step_payment','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payment','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_title_stay' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_title_stay';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_title_stay','frontend','Website booking / book_title_stay','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your stay','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_desc_stay' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_desc_stay';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_desc_stay','frontend','Website booking / book_desc_stay','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Check the dates and who''s coming.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_title_extras' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_title_extras';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_title_extras','frontend','Website booking / book_title_extras','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Make it special','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_desc_extras' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_desc_extras';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_desc_extras','frontend','Website booking / book_desc_extras','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Optional extras from the host.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_title_details' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_title_details';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_title_details','frontend','Website booking / book_title_details','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Who''s booking?','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_desc_details' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_desc_details';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_desc_details','frontend','Website booking / book_desc_details','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The host sends the confirmation to this e-mail.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_title_payment' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_title_payment';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_title_payment','frontend','Website booking / book_title_payment','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','How would you like to pay?','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_desc_payment' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_desc_payment';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_desc_payment','frontend','Website booking / book_desc_payment','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payment goes directly to the host.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_continue' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_continue';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_continue','frontend','Website booking / book_continue','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Continue','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_continue_payment' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_continue_payment';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_continue_payment','frontend','Website booking / book_continue_payment','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Continue to payment','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_back' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_back';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_back','frontend','Website booking / book_back','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Back','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_back_to_property' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_back_to_property';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_back_to_property','frontend','Website booking / book_back_to_property','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Back to the property','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_available' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_available';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_available','frontend','Website booking / book_available','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Available: {N} {UNIT}.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_checking' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_checking';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_checking','frontend','Website booking / book_checking','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Checking availability…','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_confirm_pay' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_confirm_pay';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_confirm_pay','frontend','Website booking / book_confirm_pay','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Confirm and pay {AMOUNT}','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_confirm' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_confirm';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_confirm','frontend','Website booking / book_confirm','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Confirm booking','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_pay_now' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_pay_now';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_pay_now','frontend','Website booking / book_pay_now','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Pay now','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_total' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_total';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_total','frontend','Website booking / book_total','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Total','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_coupon' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_coupon';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_coupon','frontend','Website booking / book_coupon','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coupon code','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_coupon_applied' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_coupon_applied';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_coupon_applied','frontend','Website booking / book_coupon_applied','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Coupon applied.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_add' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_add';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_add','frontend','Website booking / book_add','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Add','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_remove' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_remove';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_remove','frontend','Website booking / book_remove','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Remove','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_included' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_included';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_included','frontend','Website booking / book_included','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Included','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_required' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_required';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_required','frontend','Website booking / book_err_required','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This field is required.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_email' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_email';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_email','frontend','Website booking / book_err_email','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Enter a valid e-mail address.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_terms' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_terms';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_terms','frontend','Website booking / book_err_terms','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Please accept the booking terms.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_payment' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_payment';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_payment','frontend','Website booking / book_err_payment','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose how you would like to pay.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_choose_dates' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_choose_dates';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_choose_dates','frontend','Website booking / book_choose_dates','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose your check-in and check-out dates to continue.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_past' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_past';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_past','frontend','Website booking / book_err_past','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Dates in the past cannot be booked.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_dates' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_dates';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_dates','frontend','Website booking / book_err_dates','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose a check-out date after the check-in date.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_min_nights' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_min_nights';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_min_nights','frontend','Website booking / book_err_min_nights','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The minimum stay for these dates is {N}.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_max_nights' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_max_nights';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_max_nights','frontend','Website booking / book_err_max_nights','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The maximum stay for these dates is {N}.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_guests' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_guests';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_guests','frontend','Website booking / book_err_guests','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose the number of guests.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_taken' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_taken';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_taken','frontend','Website booking / book_err_taken','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','These dates are not available. Please choose other dates.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_not_saved' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_not_saved';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_not_saved','frontend','Website booking / book_err_not_saved','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The booking could not be saved. Please try again.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_too_many' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_too_many';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_too_many','frontend','Website booking / book_err_too_many','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Too many bookings from this device. Please wait a few minutes and try again.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_confirmed_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_confirmed_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_confirmed_title','frontend','Website booking / book_confirmed_title','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your booking is confirmed','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_pending_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_pending_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_pending_title','frontend','Website booking / book_pending_title','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Booking request sent','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_confirmed_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_confirmed_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_confirmed_desc','frontend','Website booking / book_confirmed_desc','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','We have sent the details to {EMAIL}.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_pending_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_pending_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_pending_desc','frontend','Website booking / book_pending_desc','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The host will confirm your booking. We have sent the details to {EMAIL}.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_reference' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_reference';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_reference','frontend','Website booking / book_reference','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Booking ID','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_paid_now' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_paid_now';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_paid_now','frontend','Website booking / book_paid_now','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Paid now','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_message_host' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_message_host';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_message_host','frontend','Website booking / book_message_host','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Message the host on WhatsApp','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_payment_failed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_payment_failed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_payment_failed','frontend','Website booking / book_payment_failed','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The payment was not completed. You can try again.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_redirecting' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_redirecting';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_redirecting','frontend','Website booking / book_redirecting','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Taking you to the secure payment page…','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_guests' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_guests';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_guests','frontend','Website booking / book_guests','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Guests','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_no_payment_needed' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_no_payment_needed';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_no_payment_needed','frontend','Website booking / book_no_payment_needed','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Nothing to pay now. You pay the host on arrival.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_adults_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_adults_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_adults_hint','frontend','Website booking / book_adults_hint','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Age 13 or above','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_children_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_children_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_children_hint','frontend','Website booking / book_children_hint','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Age 2–12','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_extras_none' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_extras_none';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_extras_none','frontend','Website booking / book_extras_none','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No extras for this property.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_dates' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_dates';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_dates','frontend','Website booking / book_dates','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Dates','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_extras_summary' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_extras_summary';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_extras_summary','frontend','Website booking / book_extras_summary','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Extras','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_payment_method' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_payment_method';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_payment_method','frontend','Website booking / book_payment_method','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Payment method','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_secure' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_secure';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_secure','frontend','Website booking / book_secure','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Book directly with the owner','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_book_now' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_book_now';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_book_now','frontend','Website booking / book_book_now','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Book now','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_add_date' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_add_date';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_add_date','frontend','Website booking / book_add_date','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Add date','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_check_in' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_check_in';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_check_in','frontend','Website booking / book_check_in','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Check-in','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_check_out' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_check_out';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_check_out','frontend','Website booking / book_check_out','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Check-out','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_not_found' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_not_found';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_not_found','frontend','Website booking / book_not_found','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This booking page was not found.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_bank_transfer_info' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_bank_transfer_info';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_bank_transfer_info','frontend','Website booking / book_bank_transfer_info','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Please transfer the amount to:','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_pay_on_arrival' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_pay_on_arrival';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_pay_on_arrival','frontend','Website booking / book_pay_on_arrival','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Pay on arrival','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_security_refundable' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_security_refundable';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_security_refundable','frontend','Website booking / book_security_refundable','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Security deposit (refundable)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_on_arrival' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_on_arrival';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_on_arrival','frontend','Website booking / book_on_arrival','script','2026-11-02 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','On arrival','script');
