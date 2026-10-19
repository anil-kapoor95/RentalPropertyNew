-- Public sign-up (Next.js front end): one row per self-registering company account.
-- Keeps the step reached so an unfinished sign-up can be resumed. Safe to re-run.

CREATE TABLE IF NOT EXISTS `company_signups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `company_id` int(10) unsigned DEFAULT NULL,
  `step` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `token_hash` char(64) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `token_hash` (`token_hash`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
