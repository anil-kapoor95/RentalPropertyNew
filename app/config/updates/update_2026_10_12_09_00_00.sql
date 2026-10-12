-- Languages per company: which system languages each company uses, its order and its default.
-- Safe to re-run. Table names are unprefixed; the updater adds the prefix.
-- Existing companies are filled automatically with all current languages on first use.

CREATE TABLE IF NOT EXISTS `companies_locales` (
  `company_id` int(10) unsigned NOT NULL,
  `locale_id` int(10) unsigned NOT NULL,
  `sort` int(10) unsigned DEFAULT NULL,
  `is_default` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`company_id`, `locale_id`),
  KEY `locale_id` (`locale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Banner on System Options > Languages
SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblScopeLanguages' LIMIT 1);
UPDATE `plugin_base_multi_lang` SET `content`='Languages, their order, the default language and label texts are saved for this company only.' WHERE `model`='pjBaseField' AND `foreign_id`=@fid AND `field`='title';
