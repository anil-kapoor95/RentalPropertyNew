
START TRANSACTION;

DROP TABLE IF EXISTS `ical_urls`;
CREATE TABLE IF NOT EXISTS `ical_urls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `calendar_id` int(10) DEFAULT NULL,
  `provider_id` int(10) DEFAULT NULL,
  `ical_urls` Text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

COMMIT;