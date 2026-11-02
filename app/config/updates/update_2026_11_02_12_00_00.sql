-- Properties > Payments: payment methods by plan (PayPal and cash always; the others need "Choice of payment gateways").

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subPaymentMethodLocked' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subPaymentMethodLocked';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subPaymentMethodLocked','backend','subPaymentMethodLocked','script','2026-11-02 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','This payment method is not included in your plan. PayPal and cash can always be used; the other gateways come with plans that include "Choice of payment gateways".','script');

SET @fid := (SELECT `id` FROM `plugin_base_fields` WHERE `key`='subMoreGatewaysOnPlan' LIMIT 1);
DELETE FROM `plugin_base_multi_lang` WHERE `model`='pjBaseField' AND `foreign_id`=@fid;
DELETE FROM `plugin_base_fields` WHERE `key`='subMoreGatewaysOnPlan';
INSERT INTO `plugin_base_fields` VALUES (NULL,'subMoreGatewaysOnPlan','backend','subMoreGatewaysOnPlan','script','2026-11-02 12:00:00');
SET @id := (SELECT LAST_INSERT_ID());
INSERT INTO `plugin_base_multi_lang` VALUES (NULL,@id,'pjBaseField','::LOCALE::','title','More payment gateways (Stripe, Razorpay, Authorize.Net and others) come with plans that include "Choice of payment gateways".','script');
