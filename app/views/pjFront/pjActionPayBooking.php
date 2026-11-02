<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php __('book_redirecting'); ?></title>
	<link rel="stylesheet" href="<?php echo PJ_INSTALL_URL . PJ_THIRD_PARTY_PATH; ?>bootstrap/3.3.7/css/bootstrap.min.css">
	<style>
		body{background:#f6f4ef;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:#241f14}
		.pay-box{max-width:460px;margin:12vh auto;background:#fff;border-radius:16px;padding:32px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,.08)}
		.pay-spin{width:36px;height:36px;margin:0 auto 16px;border:4px solid #e5e1d8;border-top-color:#3f7d8a;border-radius:50%;animation:s 1s linear infinite}
		@keyframes s{to{transform:rotate(360deg)}}
	</style>
	<script src="<?php echo PJ_INSTALL_URL . PJ_THIRD_PARTY_PATH; ?>jquery/3.1.1/jquery.min.js"></script>
	<script src="<?php echo PJ_INSTALL_URL . PJ_THIRD_PARTY_PATH; ?>bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>
<body>
	<div class="pay-box">
		<?php if (!empty($tpl['error'])) { ?>
			<p><?php echo pjSanitize::html($tpl['error']); ?></p>
		<?php } else { ?>
			<div class="pay-spin"></div>
			<p><?php __('book_redirecting'); ?></p>
			<p><a href="<?php echo pjSanitize::html($tpl['return_url']); ?>"><?php __('book_back'); ?></a></p>
			<?php
			if (!empty($tpl['params']['plugin']) && pjObject::getPlugin($tpl['params']['plugin']) !== NULL)
			{
				$controller->requestAction(array('controller' => $tpl['params']['plugin'], 'action' => 'pjActionForm', 'params' => $tpl['params']));
			}
			?>
		<?php } ?>
	</div>
</body>
</html>
