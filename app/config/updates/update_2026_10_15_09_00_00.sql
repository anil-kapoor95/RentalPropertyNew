-- Admin workspace: the administrator's own "company" (own properties, reservations,
-- options and settings), shown as "Admin" in Set company, for the Administrator only.
-- The row itself is created (and provisioned) by the application on first use.
-- Table names are unprefixed; the updater adds the prefix.

ALTER TABLE `companies` ADD `is_admin_ws` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `status`, ADD KEY `is_admin_ws` (`is_admin_ws`);
