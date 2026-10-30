-- Undo update_2026_10_30_12_30_00.sql: the only texts it found (one company's admin booking-confirmation email)
-- were a test edit (page HTML), not a real email. The platform admin emails stay empty until they are written
-- in Options > Notifications > Administrator.

DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjNotification' AND `field` IN ('confirm_subject_admin', 'confirm_tokens_admin') AND `foreign_id` IN (SELECT n.`id` FROM `notifications` n WHERE n.`company_id` = 0 AND n.`foreign_id` = 0 AND n.`is_general` = 1 AND n.`recipient` = 'admin' AND n.`transport` = 'email' AND n.`variant` = 'confirmation');
