-- Website booking: availability calendar (labels)

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_cal_available' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_cal_available';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_cal_available','frontend','Website booking / book_cal_available','script','2026-11-02 20:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Available','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_cal_pending' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_cal_pending';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_cal_pending','frontend','Website booking / book_cal_pending','script','2026-11-02 20:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Pending','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_cal_booked' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_cal_booked';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_cal_booked','frontend','Website booking / book_cal_booked','script','2026-11-02 20:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Booked','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_cal_past' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_cal_past';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_cal_past','frontend','Website booking / book_cal_past','script','2026-11-02 20:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Past','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_cal_selected' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_cal_selected';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_cal_selected','frontend','Website booking / book_cal_selected','script','2026-11-02 20:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Your stay','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_cal_pick_in' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_cal_pick_in';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_cal_pick_in','frontend','Website booking / book_cal_pick_in','script','2026-11-02 20:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Choose your check-in day','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_cal_pick_out' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_cal_pick_out';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_cal_pick_out','frontend','Website booking / book_cal_pick_out','script','2026-11-02 20:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Now choose your check-out day','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_cal_taken' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_cal_taken';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_cal_taken','frontend','Website booking / book_cal_taken','script','2026-11-02 20:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Some days in between are booked. Choose other dates.','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_cal_prev' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_cal_prev';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_cal_prev','frontend','Website booking / book_cal_prev','script','2026-11-02 20:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Previous month','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_cal_next' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_cal_next';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_cal_next','frontend','Website booking / book_cal_next','script','2026-11-02 20:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Next month','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='book_cal_clear' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='book_cal_clear';
INSERT INTO `plugin_base_fields` VALUES (NULL,'book_cal_clear','frontend','Website booking / book_cal_clear','script','2026-11-02 20:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','Clear dates','script');

UPDATE `plugin_base_options` SET value = MD5(RAND()) WHERE `key`='o_fields_index';
