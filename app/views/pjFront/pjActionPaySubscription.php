<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex">
	<title><?php __('sub_pay_title'); ?></title>
	<link rel="stylesheet" href="<?php echo PJ_INSTALL_URL . PJ_THIRD_PARTY_PATH; ?>bootstrap/3.3.7/css/bootstrap.min.css">
	<style>
		body{background:#f6f4ef;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:#241f14}
		.pay-box{max-width:460px;margin:10vh auto;background:#fff;border-radius:18px;padding:32px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,.08)}
		.pay-spin{width:36px;height:36px;margin:0 auto 16px;border:4px solid #e5e1d8;border-top-color:#f97618;border-radius:50%;animation:s 1s linear infinite}
		.pay-plan{font-size:13px;color:#6b7280;margin:0 0 4px}
		.pay-amount{font-size:30px;font-weight:800;margin:0 0 14px}
		.pay-secure{font-size:12px;color:#6b7280;margin-top:14px}
		.pay-form{margin-top:10px}
		@keyframes s{to{transform:rotate(360deg)}}
	</style>
	<script src="<?php echo PJ_INSTALL_URL . PJ_THIRD_PARTY_PATH; ?>jquery/3.1.1/jquery.min.js"></script>
	<script src="<?php echo PJ_INSTALL_URL . PJ_THIRD_PARTY_PATH; ?>bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>
<body>
	<div class="pay-box">
		<?php if (!empty($tpl['error'])) { ?>
			<p><?php echo pjSanitize::html($tpl['error']); ?></p>
			<p><a href="<?php echo pjSanitize::html($tpl['return_url']); ?>"><?php __('book_back'); ?></a></p>
		<?php } else { $t = $tpl['txn']; ?>
			<div class="pay-spin" aria-hidden="true"></div>
			<p class="pay-plan"><?php echo $tpl['plan'] ? pjSanitize::html($tpl['plan']['name']) : ''; ?> &middot; <?php echo pjSanitize::html(pjSubscriptionBilling::methodName($t['provider'])); ?></p>
			<p class="pay-amount"><?php echo pjSanitize::html(pjMoney::format($t['amount'], $t['currency'])); ?></p>
			<p><?php __('sub_pay_redirect'); ?></p>
			<div class="pay-form">
			<?php
			if (!empty($tpl['params']['plugin']) && pjObject::getPlugin($tpl['params']['plugin']) !== NULL)
			{
				$controller->requestAction(array('controller' => $tpl['params']['plugin'], 'action' => 'pjActionForm', 'params' => $tpl['params']));
			}
			?>
			</div>
			<p class="pay-secure"><?php __('sub_pay_secure'); ?></p>
			<p><a href="<?php echo pjSanitize::html($tpl['return_url']); ?>"><?php __('book_back'); ?></a></p>
		<?php } ?>
	</div>
</body>
</html>
