-- Cron jobs per company: own on/off switch, next run, last run and result per company.
-- Safe to re-run. Table names are unprefixed; the updater adds the prefix.
-- (The code also creates the table if it is missing.)

CREATE TABLE IF NOT EXISTS `companies_cron_jobs` (
  `company_id` int(10) unsigned NOT NULL,
  `cron_id` int(10) unsigned NOT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `next_run` datetime DEFAULT NULL,
  `last_run` datetime DEFAULT NULL,
  `status` text,
  PRIMARY KEY (`company_id`, `cron_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Banner on System Options > Cron jobs
SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblScopeCron' LIMIT 1);
UPDATE `plugin_base_multi_lang` SET `content`='The cron URL is the same for all companies. On/off, next run, last run and result are kept for this company only.' WHERE `model`='pjBaseField' AND `foreign_id`=@fid AND `field`='title';
