-- INR plan payments: Razorpay only (Stripe no longer offered for INR), 2026-11-17. Run once.
UPDATE `plugin_base_multi_lang` SET `content` = 'No switched-on gateway can take INR. Switch on Razorpay below.' WHERE `model` = 'pjBaseField' AND `field` = 'title' AND `content` = 'No switched-on gateway can take INR. Switch on Razorpay (or Stripe) below.';
