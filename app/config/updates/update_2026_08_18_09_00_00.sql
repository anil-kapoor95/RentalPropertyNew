-- Install-page (properties listing) labels. Idempotent (delete-first).

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblListingInstall' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblListingInstall';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblListingInstall', 'backend', 'Install / listing title', 'script', '2026-08-18 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Properties listing — Install codes (all properties)', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblListingInstallNote' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblListingInstallNote';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblListingInstallNote', 'backend', 'Install / listing note', 'script', '2026-08-18 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'These codes embed the full properties listing (every active property) and follow your theme colours. They are not affected by the property selection above.', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblInstallCodeFull' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblInstallCodeFull';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblInstallCodeFull', 'backend', 'Install / full code', 'script', '2026-08-18 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Install code', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblInstallCodeFullNote' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblInstallCodeFullNote';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblInstallCodeFullNote', 'backend', 'Install / full code note', 'script', '2026-08-18 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Copy this into any page where you want the properties to appear. It renders directly into your page (no iframe).', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblCopy' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblCopy';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblCopy', 'backend', 'Install / copy button', 'script', '2026-08-18 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Copy', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblShorterVersion' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblShorterVersion';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblShorterVersion', 'backend', 'Install / shorter title', 'script', '2026-08-18 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Shorter version', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblShorterVersionNote' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblShorterVersionNote';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblShorterVersionNote', 'backend', 'Install / shorter note', 'script', '2026-08-18 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'The script injects the stylesheets automatically, so this compact version works too.', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblDirectLink' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblDirectLink';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblDirectLink', 'backend', 'Install / direct link title', 'script', '2026-08-18 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Direct link', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblDirectLinkNote' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblDirectLinkNote';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblDirectLinkNote', 'backend', 'Install / direct link note', 'script', '2026-08-18 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Open the standalone properties page directly.', 'script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key` = 'lblOpenPropertiesPage' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model` = 'pjBaseField' AND `foreign_id` = @fid;
DELETE FROM `plugin_base_fields` WHERE `key` = 'lblOpenPropertiesPage';
INSERT INTO `plugin_base_fields` VALUES (NULL, 'lblOpenPropertiesPage', 'backend', 'Install / open button', 'script', '2026-08-18 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL, @id, 'pjBaseField', '::LOCALE::', 'title', 'Open properties page', 'script');
