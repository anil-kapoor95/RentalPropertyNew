-- Platform notifications (company 0, Options > Notifications) that have no subject / message yet get the texts
-- of the first company that wrote them (booking emails are only sent when a text exists). Texts already there are kept.

DROP TABLE IF EXISTS pj_mig_src;

CREATE TABLE pj_mig_src AS SELECT g.`id` AS gid, (SELECT a.`id` FROM `notifications` a WHERE a.`company_id` > 0 AND a.`foreign_id` = 0 AND a.`is_general` = 1 AND a.`recipient` = g.`recipient` AND a.`transport` = g.`transport` AND a.`variant` = g.`variant` AND a.`id` IN (SELECT ml.`foreign_id` FROM `plugin_base_multi_lang` ml WHERE ml.`model` = 'pjNotification' AND ml.`content` <> '') ORDER BY a.`id` LIMIT 1) AS aid FROM `notifications` g WHERE g.`company_id` = 0 AND g.`foreign_id` = 0 AND g.`is_general` = 1 AND g.`id` NOT IN (SELECT ml2.`foreign_id` FROM `plugin_base_multi_lang` ml2 WHERE ml2.`model` = 'pjNotification' AND ml2.`content` <> '');

INSERT IGNORE INTO `plugin_base_multi_lang` (`id`, `foreign_id`, `model`, `locale`, `field`, `content`, `source`) SELECT NULL, (SELECT s.gid FROM pj_mig_src s WHERE s.aid = x.`foreign_id` LIMIT 1), 'pjNotification', x.`locale`, x.`field`, x.`content`, 'data' FROM `plugin_base_multi_lang` x WHERE x.`model` = 'pjNotification' AND x.`foreign_id` IN (SELECT s2.aid FROM pj_mig_src s2 WHERE s2.aid IS NOT NULL);

DROP TABLE pj_mig_src;
