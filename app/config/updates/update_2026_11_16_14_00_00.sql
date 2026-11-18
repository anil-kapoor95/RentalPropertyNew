-- Plans bought on the StayOwned website (2026-11-16): where a payment was started and where the browser returns.
-- Tables are unprefixed; the updater adds the prefix. Run once.
ALTER TABLE `billing_transactions` ADD COLUMN `channel` varchar(10) NOT NULL DEFAULT 'admin' AFTER `billing_country`, ADD COLUMN `return_url` varchar(255) DEFAULT NULL AFTER `channel`;
