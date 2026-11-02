-- Subscriptions > Plans: redesigned list and form (labels)

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plans_count' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plans_count';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plans_count','backend','sub_plans_count','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plans: {N}','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plans_empty' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plans_empty';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plans_empty','backend','sub_plans_empty','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','No plans yet','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plans_empty_desc' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plans_empty_desc';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plans_empty_desc','backend','sub_plans_empty_desc','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Create your first plan: a price, limits and the features companies get.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_free' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_free';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_free','backend','sub_plan_free','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Free','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_trial' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_trial';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_trial','backend','sub_plan_trial','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','{N}-day free trial','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_features_count' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_features_count';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_features_count','backend','sub_features_count','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','{N} of {T} features','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_base_included' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_base_included';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_base_included','backend','sub_plan_base_included','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','All basic features','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_more' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_more';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_more','backend','sub_more','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','+{N} more','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_companies' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_companies';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_companies','backend','sub_plan_companies','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies: {N}','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_btn_edit' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_btn_edit';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_btn_edit','backend','sub_plan_btn_edit','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Edit','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_private' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_private';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_private','backend','sub_plan_private','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Hidden','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plans_compare' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plans_compare';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plans_compare','backend','sub_plans_compare','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Compare plans','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_sec_details' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_sec_details';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_sec_details','backend','sub_plan_sec_details','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plan details','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_sec_pricing' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_sec_pricing';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_sec_pricing','backend','sub_plan_sec_pricing','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Pricing','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_sec_visibility' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_sec_visibility';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_sec_visibility','backend','sub_plan_sec_visibility','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Visibility and access','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_code_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_code_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_code_hint','backend','sub_plan_code_hint','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Lowercase letters, numbers, - and _ (2-32).','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_select_all' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_select_all';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_select_all','backend','sub_select_all','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Select all','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_select_base' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_select_base';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_select_base','backend','sub_select_base','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Basic only','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_active_plan_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_active_plan_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_active_plan_hint','backend','sub_active_plan_hint','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies can get this plan. Untick to retire it: companies already on it keep it.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_public_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_public_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_public_hint','backend','sub_public_hint','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Companies see it on My plan and can buy it (needs a price above 0). Untick for a plan only you assign.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_sort_hint' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_sort_hint';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_sort_hint','backend','sub_sort_hint','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Plans are listed from the lowest number.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='sub_plan_preview' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='sub_plan_preview';
INSERT INTO `plugin_base_fields` VALUES (NULL,'sub_plan_preview','backend','sub_plan_preview','script','2026-11-02 18:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Summary','script');

-- refresh the cached labels of signed-in users
UPDATE `plugin_base_options` SET value = MD5(RAND()) WHERE `key`='o_fields_index';
