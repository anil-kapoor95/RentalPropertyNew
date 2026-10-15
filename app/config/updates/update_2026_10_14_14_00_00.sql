-- Companies: change the company login password when editing a company.
-- Safe to re-run. Table names are unprefixed; the updater adds the prefix.

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginNewPassword' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginNewPassword';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyLoginNewPassword','backend','Companies / new login password','script','2026-10-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','New password','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginNewPasswordConfirm' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginNewPasswordConfirm';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyLoginNewPasswordConfirm','backend','Companies / confirm new login password','script','2026-10-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Confirm new password','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginNoteEdit' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblCompanyLoginNoteEdit';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblCompanyLoginNoteEdit','backend','Companies / login note on edit','script','2026-10-14 14:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Leave both fields empty to keep the current password. The company signs in with its e-mail and this password.','script');
