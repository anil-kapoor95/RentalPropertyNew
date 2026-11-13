-- Booking page: message when the coupon code is empty

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_coupon_required' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_coupon_required';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_coupon_required','frontend','Booking / book_coupon_required','script','2026-11-14 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Please enter a coupon code.','script');
