-- Companies list: new page description (the company login is created with the company).
-- Only the system text is changed; texts a company changed itself are kept. Safe to re-run.
-- Table names are unprefixed; the updater adds the prefix.

UPDATE `plugin_base_multi_lang` SET `content` = 'Manage the companies on your platform. When you add a company, its login is created too: the company signs in with its e-mail and the password you set.' WHERE `model` = 'pjBaseField' AND `field` = 'title' AND `foreign_id` = (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'infoCompaniesDesc' LIMIT 1);
