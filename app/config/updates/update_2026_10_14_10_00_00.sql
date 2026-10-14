-- SMS log per company: which company sent each logged SMS (rows of the shared
-- plugin_base_sms table are not changed). Safe to re-run. Table names are
-- unprefixed; the updater adds the prefix. (The code also creates it if missing.)

CREATE TABLE IF NOT EXISTS `companies_sms` (
  `sms_id` int(10) unsigned NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`sms_id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
