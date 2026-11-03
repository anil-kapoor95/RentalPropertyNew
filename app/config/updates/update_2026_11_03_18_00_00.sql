-- Texts of the property websites (localhost:3000/<property>): System Options > Languages, type Front end.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_nav_sections' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_nav_sections';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_nav_sections','frontend','Website page / site_nav_sections','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Sections','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_nav_overview' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_nav_overview';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_nav_overview','frontend','Website page / site_nav_overview','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Overview','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_nav_amenities' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_nav_amenities';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_nav_amenities','frontend','Website page / site_nav_amenities','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Amenities','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_nav_location' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_nav_location';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_nav_location','frontend','Website page / site_nav_location','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Location','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_nav_faq' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_nav_faq';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_nav_faq','frontend','Website page / site_nav_faq','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','FAQ','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_hosted_by' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_hosted_by';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_hosted_by','frontend','Website page / site_hosted_by','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Hosted by','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_see_all_stays' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_see_all_stays';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_see_all_stays','frontend','Website page / site_see_all_stays','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','See all {N} stays','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_book_direct' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_book_direct';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_book_direct','frontend','Website page / site_book_direct','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Book direct with the host','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_bedroom' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_bedroom';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_bedroom','frontend','Website page / site_bedroom','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','{N} bedroom','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_bedrooms' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_bedrooms';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_bedrooms','frontend','Website page / site_bedrooms','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','{N} bedrooms','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_bathroom' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_bathroom';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_bathroom','frontend','Website page / site_bathroom','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','{N} bathroom','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_bathrooms' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_bathrooms';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_bathrooms','frontend','Website page / site_bathrooms','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','{N} bathrooms','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_up_to_guest' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_up_to_guest';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_up_to_guest','frontend','Website page / site_up_to_guest','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Up to {N} guest','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_up_to_guests' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_up_to_guests';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_up_to_guests','frontend','Website page / site_up_to_guests','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Up to {N} guests','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_entire' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_entire';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_entire','frontend','Website page / site_entire','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Entire {TYPE}','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_type_villa' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_type_villa';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_type_villa','frontend','Website page / site_type_villa','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','villa','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_type_homestay' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_type_homestay';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_type_homestay','frontend','Website page / site_type_homestay','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','homestay','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_type_apartment' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_type_apartment';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_type_apartment','frontend','Website page / site_type_apartment','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','apartment','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_type_cottage' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_type_cottage';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_type_cottage','frontend','Website page / site_type_cottage','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','cottage','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_type_resort' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_type_resort';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_type_resort','frontend','Website page / site_type_resort','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','resort','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_about' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_about';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_about','frontend','Website page / site_about','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','About this stay','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_amenities' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_amenities';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_amenities','frontend','Website page / site_amenities','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Amenities','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_show_all_amenities' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_show_all_amenities';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_show_all_amenities','frontend','Website page / site_show_all_amenities','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Show all {N} amenities','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_show_less' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_show_less';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_show_less','frontend','Website page / site_show_less','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Show less','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_availability' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_availability';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_availability','frontend','Website page / site_availability','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Availability','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_good_to_know' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_good_to_know';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_good_to_know','frontend','Website page / site_good_to_know','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Good to know','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_guests' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_guests';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_guests','frontend','Website page / site_guests','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Guests','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_up_to' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_up_to';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_up_to','frontend','Website page / site_up_to','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Up to {N}','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_prices' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_prices';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_prices','frontend','Website page / site_prices','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Prices','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_per_night' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_per_night';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_per_night','frontend','Website page / site_per_night','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Per night','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_per_day' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_per_day';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_per_day','frontend','Website page / site_per_day','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Per day','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_per_night_varies' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_per_night_varies';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_per_night_varies','frontend','Website page / site_per_night_varies','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Per night, vary by day','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_per_day_varies' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_per_day_varies';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_per_day_varies','frontend','Website page / site_per_day_varies','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Per day, vary by day','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_host' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_host';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_host','frontend','Website page / site_host','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Host','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_location' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_location';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_location','frontend','Website page / site_location','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Location','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_map_of' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_map_of';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_map_of','frontend','Website page / site_map_of','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Map of {NAME}','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_faq' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_faq';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_faq','frontend','Website page / site_faq','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Questions guests ask','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_price_on_request' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_price_on_request';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_price_on_request','frontend','Website page / site_price_on_request','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Ask for a price','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_price_from' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_price_from';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_price_from','frontend','Website page / site_price_from','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','From — prices vary by day','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_night' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_night';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_night','frontend','Website page / site_night','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','night','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_nights' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_nights';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_nights','frontend','Website page / site_nights','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','nights','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_day' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_day';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_day','frontend','Website page / site_day','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','day','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_days' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_days';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_days','frontend','Website page / site_days','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','days','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_dates_free' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_dates_free';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_dates_free','frontend','Website page / site_dates_free','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Available for your dates','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_dates_taken' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_dates_taken';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_dates_taken','frontend','Website page / site_dates_taken','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Some of these dates are taken','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_whatsapp' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_whatsapp';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_whatsapp','frontend','Website page / site_whatsapp','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Ask on WhatsApp','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_new_tab' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_new_tab';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_new_tab','frontend','Website page / site_new_tab','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','(opens in a new tab)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_whatsapp_text' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_whatsapp_text';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_whatsapp_text','frontend','Website page / site_whatsapp_text','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Hi, I''m interested in {NAME}{DATES} for {N} {GUESTS}.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_whatsapp_dates' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_whatsapp_dates';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_whatsapp_dates','frontend','Website page / site_whatsapp_dates','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title',' from {FROM} to {TO}','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_guest_word' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_guest_word';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_guest_word','frontend','Website page / site_guest_word','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','guest','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_guests_word' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_guests_word';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_guests_word','frontend','Website page / site_guests_word','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','guests','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_total' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_total';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_total','frontend','Website page / site_total','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Total','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_estimate' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_estimate';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_estimate','frontend','Website page / site_estimate','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Estimate: the booking page shows the exact price.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_book_direct_owner' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_book_direct_owner';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_book_direct_owner','frontend','Website page / site_book_direct_owner','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Book directly with the owner','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_rights' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_rights';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_rights','frontend','Website page / site_rights','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','All Rights Reserved','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_photos_title' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_photos_title';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_photos_title','frontend','Website page / site_photos_title','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','{NAME} — photos','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_photo_prev' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_photo_prev';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_photo_prev','frontend','Website page / site_photo_prev','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Previous photo','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_photo_next' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_photo_next';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_photo_next','frontend','Website page / site_photo_next','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Next photo','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_photo_alt' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_photo_alt';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_photo_alt','frontend','Website page / site_photo_alt','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','{NAME}, photo {N}','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_open_photo' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_open_photo';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_open_photo','frontend','Website page / site_open_photo','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Open photo {N} of {T}','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_view_all_photos' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_view_all_photos';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_view_all_photos','frontend','Website page / site_view_all_photos','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','View all {N} photos','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_prev' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_prev';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_prev','frontend','Website page / site_cal_prev','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Previous month','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_next' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_next';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_next','frontend','Website page / site_cal_next','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Next month','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_weekdays' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_weekdays';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_weekdays','frontend','Website page / site_cal_weekdays','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','M T W T F S S','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_your_dates' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_your_dates';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_your_dates','frontend','Website page / site_cal_your_dates','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your dates','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_taken' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_taken';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_taken','frontend','Website page / site_cal_taken','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Taken','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_available' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_available';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_available','frontend','Website page / site_cal_available','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Available','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_pick_in' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_pick_in';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_pick_in','frontend','Website page / site_cal_pick_in','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose your check-in day','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_pick_out' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_pick_out';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_pick_out','frontend','Website page / site_cal_pick_out','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Check-in {DATE} · choose the check-out day','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_summary' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_summary';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_summary','frontend','Website page / site_cal_summary','script','2026-11-03 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','{N} {UNIT} · {FROM} – {TO}','script');

UPDATE `plugin_base_options` SET value = MD5(RAND()) WHERE `key`='o_fields_index';
