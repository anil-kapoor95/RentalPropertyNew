-- Roles: Super Admin (1, platform only) and the new Companies Admin (6, every company with the company switcher).
-- Permissions of both roles are decided in code (pjCompanyContext::pageScope / roleMayOpen), so role 6 needs no permission rows.
UPDATE `plugin_auth_roles` SET `role` = 'Super Admin' WHERE `id` = 1;
INSERT IGNORE INTO `plugin_auth_roles` (`id`, `role`, `is_backend`, `is_admin`, `status`) VALUES (6, 'Companies Admin', 'T', 'T', 'T');

