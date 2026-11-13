-- Super Admin dashboard: Total Revenue card, one period for every figure (labels)

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_stat_revenue' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_stat_revenue';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_stat_revenue','backend','Dashboard / dash_stat_revenue','script','2026-11-14 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Total Revenue','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_stat_revenue_sub' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_stat_revenue_sub';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_stat_revenue_sub','backend','Dashboard / dash_stat_revenue_sub','script','2026-11-14 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u paid payments in this period; previous period: %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_compared_with' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_compared_with';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_compared_with','backend','Dashboard / dash_compared_with','script','2026-11-14 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Changes are compared with %s - %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_days_count' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_days_count';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_days_count','backend','Dashboard / dash_days_count','script','2026-11-14 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','%u days','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_range_invalid' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_range_invalid';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_range_invalid','backend','Dashboard / dash_range_invalid','script','2026-11-14 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The custom dates are not valid (the start must be on or before the end, and not in the future), so the last 30 days are shown. Please check the dates and apply again.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_as_of' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_as_of';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_as_of','backend','Dashboard / dash_as_of','script','2026-11-14 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','as of %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_week_of' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_week_of';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_week_of','backend','Dashboard / dash_week_of','script','2026-11-14 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Week of %s','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_per_week' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_per_week';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_per_week','backend','Dashboard / dash_per_week','script','2026-11-14 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','per week','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_per_month' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_per_month';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_per_month','backend','Dashboard / dash_per_month','script','2026-11-14 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','per month','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_from_today' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_from_today';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_from_today','backend','Dashboard / dash_from_today','script','2026-11-14 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','(from today)','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_bh_empty' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_bh_empty';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_bh_empty','backend','Dashboard / dash_bh_empty','script','2026-11-14 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No billing transactions in this period.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='dash_day_count' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='dash_day_count';
INSERT INTO `plugin_base_fields` VALUES (NULL,'dash_day_count','backend','Dashboard / dash_day_count','script','2026-11-14 16:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','1 day','script');
