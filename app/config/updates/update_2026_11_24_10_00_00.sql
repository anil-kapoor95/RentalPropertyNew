-- Plan restrictions, My plan, guest limits, calendar statuses, 2026-11-24. Run once (safe to run again).

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_mp_unlock' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_mp_unlock';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_mp_unlock','backend','Subscriptions / sub_mp_unlock','script','2026-11-24 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Upgrade to unlock','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_mp_plans' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_mp_plans';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_mp_plans','backend','Subscriptions / sub_mp_plans','script','2026-11-24 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plans','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_mp_usage' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_mp_usage';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_mp_usage','backend','Subscriptions / sub_mp_usage','script','2026-11-24 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Usage','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblSiteLinkInactivePlan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblSiteLinkInactivePlan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblSiteLinkInactivePlan','backend','Companies / lblSiteLinkInactivePlan','script','2026-11-24 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your current plan does not include "Custom domain", so this domain does not open your website. Upgrade to use it again, or clear it.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_pending' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_pending';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_pending','frontend','Website property page / site_cal_pending','script','2026-11-24 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Pending','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_booked' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_booked';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_booked','frontend','Website property page / site_cal_booked','script','2026-11-24 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Booked','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_unavailable' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_unavailable';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_unavailable','frontend','Website property page / site_cal_unavailable','script','2026-11-24 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Unavailable','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_cal_unavailable' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_cal_unavailable';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_cal_unavailable','frontend','Website booking / book_cal_unavailable','script','2026-11-24 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Unavailable','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_adults_max' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_adults_max';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_adults_max','frontend','Website booking / book_err_adults_max','script','2026-11-24 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','A booking can have up to {MAX} adults.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_err_children_max' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_err_children_max';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_err_children_max','frontend','Website booking / book_err_children_max','script','2026-11-24 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','A booking can have up to {MAX} children.','script');
SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_checkout_day' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_checkout_day';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_checkout_day','frontend','Website property page / site_cal_checkout_day','script','2026-11-24 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Check-out day','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='site_cal_checkin_day' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='site_cal_checkin_day';
INSERT INTO `plugin_base_fields` VALUES (NULL,'site_cal_checkin_day','frontend','Website property page / site_cal_checkin_day','script','2026-11-24 10:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Check-in day','script');

-- New e-mail tokens in the "Available tokens" help of the notification editor
UPDATE `plugin_base_multi_lang` m
JOIN `plugin_base_fields` f ON f.`id` = m.`foreign_id` AND m.`model` = 'pjBaseField'
SET m.`content` = CONCAT(m.`content`, '\r\n<div><small><strong>{PropertyAddress}</strong> - property address</small></div>\r\n<div><small><strong>{HostName}</strong> - host (company) name</small></div>\r\n<div><small><strong>{HostEmail}</strong> - host e-mail</small></div>\r\n<div><small><strong>{HostPhone}</strong> - host phone</small></div>\r\n<div><small><strong>{Nights}</strong> - number of nights (days)</small></div>\r\n<div><small><strong>{Status}</strong> - booking status</small></div>\r\n<div><small><strong>{AmountDue}</strong> - balance after the deposit</small></div>')
WHERE f.`key` = 'lblAvailableTokens' AND m.`field` = 'title' AND m.`content` NOT LIKE '%{PropertyAddress}%';
