-- Setup wizard + remove-demo labels (idempotent, delete-first).

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='lblRemoveDemoData' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='lblRemoveDemoData';
INSERT INTO `plugin_base_fields` VALUES (NULL,'lblRemoveDemoData','backend','Menu / Remove demo data','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Remove demo data','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupWelcomeTitle' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupWelcomeTitle';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupWelcomeTitle','backend','Setup / welcome title','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Welcome to your booking calendar','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupWelcomeSub' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupWelcomeSub';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupWelcomeSub','backend','Setup / welcome subtitle','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Would you like to start with sample properties, or with an empty calendar?','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupDemoTitle' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupDemoTitle';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupDemoTitle','backend','Setup / demo card title','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Load demo data','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupDemoDesc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupDemoDesc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupDemoDesc','backend','Setup / demo card desc','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Add six sample properties with photos, descriptions, features and US locations so you can explore the front-end and admin right away.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupBtnLoad' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupBtnLoad';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupBtnLoad','backend','Setup / load button','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Import demo data','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupEmptyTitle' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupEmptyTitle';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupEmptyTitle','backend','Setup / empty card title','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Start empty','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupEmptyDesc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupEmptyDesc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupEmptyDesc','backend','Setup / empty card desc','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Begin with a clean install and add your own properties from scratch.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupBtnSkip' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupBtnSkip';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupBtnSkip','backend','Setup / skip button','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Start empty','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupDemoActiveDesc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupDemoActiveDesc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupDemoActiveDesc','backend','Setup / demo active desc','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Demo properties are currently loaded. You can remove them at any time.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupBtnRemove' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupBtnRemove';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupBtnRemove','backend','Setup / remove button','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Remove demo data','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupBtnGoDashboard' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupBtnGoDashboard';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupBtnGoDashboard','backend','Setup / go to dashboard','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Go to dashboard','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupRemoveConfirm' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupRemoveConfirm';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupRemoveConfirm','backend','Setup / remove confirm','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Remove all demo properties? This cannot be undone.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupLoadingDemo' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupLoadingDemo';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupLoadingDemo','backend','Setup / loading','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Importing demo properties...','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupLoadedOk' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupLoadedOk';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupLoadedOk','backend','Setup / loaded','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Demo properties imported. Redirecting...','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupRemovingDemo' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupRemovingDemo';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupRemovingDemo','backend','Setup / removing','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Removing demo properties...','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='setupRemovedOk' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='setupRemovedOk';
INSERT INTO `plugin_base_fields` VALUES (NULL,'setupRemovedOk','backend','Setup / removed','script','2026-08-20 09:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Demo properties removed. Refreshing...','script');
