-- Companies: login (password) created with the company, delete-logo icon, new messages.
-- Safe to re-run. Table names are unprefixed; the updater adds the prefix.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanySectionLogin' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanySectionLogin';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanySectionLogin','backend','Companies / login section','script','2026-10-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Company login','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginPassword' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginPassword';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyLoginPassword','backend','Companies / login password','script','2026-10-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Password','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginPasswordConfirm' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginPasswordConfirm';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyLoginPasswordConfirm','backend','Companies / login password confirm','script','2026-10-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Confirm password','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginNote' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginNote';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyLoginNote','backend','Companies / login note','script','2026-10-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','A user with the Company role is created with the company. It logs in with the company e-mail and this password.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyDeleteLogo' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyDeleteLogo';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyDeleteLogo','backend','Companies / delete logo','script','2026-10-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Delete logo','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyPasswordMismatch' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyPasswordMismatch';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyPasswordMismatch','backend','Companies / passwords do not match','script','2026-10-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The passwords do not match.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyPasswordWeak' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyPasswordWeak';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyPasswordWeak','backend','Companies / password too weak','script','2026-10-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','The password does not meet the password rules (System Options > Login & Protection).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='msgCompanyEmailTaken' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='msgCompanyEmailTaken';
INSERT INTO `plugin_base_fields` VALUES (NULL,'msgCompanyEmailTaken','backend','Companies / login e-mail taken','script','2026-10-14 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This e-mail address is already used by another user. Use another company e-mail.','script');
