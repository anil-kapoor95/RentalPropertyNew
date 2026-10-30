-- Admin workspace ("Admin" company) removed. The administrator's settings that were kept in it become the
-- platform settings (used by every company): System Options (all tabs except General), their texts, labels,
-- notifications and countries. Only Properties, Reservations and Company options (Extras, Features, General,
-- Coupons & Discounts) follow the active company now. Helper tables pj_mig_* are dropped at the end.

SET @aws := (SELECT `id` FROM `companies` WHERE `is_admin_ws` = 1 ORDER BY `id` LIMIT 1);
SET @fid := 1000 + @aws;

DROP TABLE IF EXISTS pj_mig_opt;
DROP TABLE IF EXISTS pj_mig_ml;
DROP TABLE IF EXISTS pj_mig_lbl;
DROP TABLE IF EXISTS pj_mig_ntf;
DROP TABLE IF EXISTS pj_mig_ntxt;

-- System options of the admin workspace (not General) -> platform options
CREATE TABLE pj_mig_opt AS SELECT `key`, `value` FROM `plugin_base_options` WHERE `foreign_id` = @fid AND (`tab_id` IS NULL OR `tab_id` <> 1);
UPDATE `plugin_base_options` g SET g.`value` = (SELECT t.`value` FROM pj_mig_opt t WHERE t.`key` = g.`key` LIMIT 1) WHERE g.`foreign_id` = 1 AND g.`key` IN (SELECT t2.`key` FROM pj_mig_opt t2);
DROP TABLE pj_mig_opt;

-- texts of those options (login / captcha messages)
CREATE TABLE pj_mig_ml AS SELECT `locale`, `field`, `content` FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseOption' AND `foreign_id` = @fid;
UPDATE `plugin_base_multi_lang` g SET g.`content` = (SELECT t.`content` FROM pj_mig_ml t WHERE t.`locale` = g.`locale` AND t.`field` = g.`field` LIMIT 1) WHERE g.`model` = 'pjBaseOption' AND g.`foreign_id` = 1 AND EXISTS (SELECT 1 FROM pj_mig_ml t2 WHERE t2.`locale` = g.`locale` AND t2.`field` = g.`field`);
DROP TABLE pj_mig_ml;

-- labels changed in the admin workspace -> platform labels
CREATE TABLE pj_mig_lbl AS SELECT `foreign_id`, `locale`, `content` FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `field` = CONCAT('title_c', @aws);
UPDATE `plugin_base_multi_lang` g SET g.`content` = (SELECT t.`content` FROM pj_mig_lbl t WHERE t.`foreign_id` = g.`foreign_id` AND t.`locale` = g.`locale` LIMIT 1) WHERE g.`model` = 'pjBaseField' AND g.`field` = 'title' AND EXISTS (SELECT 1 FROM pj_mig_lbl t2 WHERE t2.`foreign_id` = g.`foreign_id` AND t2.`locale` = g.`locale`);
DROP TABLE pj_mig_lbl;

-- notifications of the admin workspace (on / off and texts) -> platform notifications (company 0)
CREATE TABLE pj_mig_ntf AS SELECT a.`id` AS aid, (SELECT g.`id` FROM `notifications` g WHERE g.`company_id` = 0 AND g.`foreign_id` = 0 AND g.`is_general` = 1 AND g.`recipient` = a.`recipient` AND g.`transport` = a.`transport` AND g.`variant` = a.`variant` LIMIT 1) AS gid, a.`is_active` AS is_active FROM `notifications` a WHERE a.`company_id` = @aws AND a.`foreign_id` = 0 AND a.`is_general` = 1;
UPDATE `notifications` g SET g.`is_active` = (SELECT m.is_active FROM pj_mig_ntf m WHERE m.gid = g.`id` LIMIT 1) WHERE g.`id` IN (SELECT m2.gid FROM pj_mig_ntf m2 WHERE m2.gid IS NOT NULL);
CREATE TABLE pj_mig_ntxt AS SELECT (SELECT m.gid FROM pj_mig_ntf m WHERE m.aid = x.`foreign_id` LIMIT 1) AS gid, x.`locale` AS locale, x.`field` AS field, x.`content` AS content FROM `plugin_base_multi_lang` x WHERE x.`model` = 'pjNotification' AND x.`foreign_id` IN (SELECT m3.aid FROM pj_mig_ntf m3);
UPDATE `plugin_base_multi_lang` g SET g.`content` = (SELECT t.content FROM pj_mig_ntxt t WHERE t.gid = g.`foreign_id` AND t.locale = g.`locale` AND t.field = g.`field` LIMIT 1) WHERE g.`model` = 'pjNotification' AND EXISTS (SELECT 1 FROM pj_mig_ntxt t2 WHERE t2.gid = g.`foreign_id` AND t2.locale = g.`locale` AND t2.field = g.`field`);
DROP TABLE pj_mig_ntxt;
DROP TABLE pj_mig_ntf;

-- countries: statuses set in the admin workspace -> platform statuses; its own countries become shared
UPDATE `plugin_base_countries` b SET b.`status` = (SELECT cc.`status` FROM `companies_countries` cc WHERE cc.`company_id` = @aws AND cc.`country_id` = b.`id` LIMIT 1) WHERE b.`id` IN (SELECT cc2.`country_id` FROM `companies_countries` cc2 WHERE cc2.`company_id` = @aws);
UPDATE `plugin_base_countries` SET `company_id` = NULL WHERE `company_id` = @aws;

-- the Admin company itself (no properties or reservations)
DELETE FROM `companies` WHERE `is_admin_ws` = 1;

-- label of the new left menu
SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='menuCompanyOptions' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='menuCompanyOptions';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'menuCompanyOptions', 'backend', 'Menu / Company options', 'script', '2026-10-30 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Company options', 'script');
