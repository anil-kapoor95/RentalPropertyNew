<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = 'sub_pay_title';
$heading_desc = 'sub_myplan_desc';
include dirname(__FILE__) . '/elements/heading.php';
$t = $tpl['txn'];
$method = $t['provider'];
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<div class="ibox float-e-margins"><div class="ibox-content sub-pay">
		<div class="row">
			<div class="col-md-6">
				<table class="table sub-kv m-b-none">
					<tr><th><?php __('sub_col_plan'); ?></th><td><?php echo $tpl['plan'] ? pjSanitize::html($tpl['plan']['name']) : ''; ?> <small class="text-muted"><?php __('sub_kind_' . $t['kind']); ?></small></td></tr>
					<?php if (isset($t['discount_amount']) && (float) $t['discount_amount'] > 0) { ?>
					<tr><th><?php __('sub_list_price'); ?></th><td><?php echo $subMoney($t['list_amount'], $t['currency']); ?></td></tr>
					<tr><th><?php __('sub_discount'); ?></th><td>- <?php echo $subMoney($t['discount_amount'], $t['currency']); ?><?php echo !empty($t['coupon_code']) ? ' <small class="text-muted">(' . pjSanitize::html($t['coupon_code']) . ')</small>' : ''; ?></td></tr>
					<?php } ?>
					<tr><th><?php __('sub_col_amount'); ?></th><td><strong><?php echo $subMoney($t['amount'], $t['currency']); ?></strong></td></tr>
					<tr><th><?php __('sub_col_method'); ?></th><td><?php echo pjSanitize::html(pjSubscriptionBilling::methodName($method)); ?></td></tr>
					<tr><th><?php __('sub_pay_reference'); ?></th><td><code><?php echo pjSanitize::html(strtoupper(substr($t['uuid'], 0, 12))); ?></code></td></tr>
				</table>
			</div>
			<div class="col-md-6">
			<?php if ($tpl['params']) { ?>
				<p class="text-muted"><?php __('sub_pay_redirect'); ?></p>
				<div class="sub-pay-form">
					<?php $controller->requestAction(array('controller' => $tpl['params']['plugin'], 'action' => 'pjActionForm', 'params' => $tpl['params'])); ?>
				</div>
			<?php } elseif ($method === 'bank') { ?>
				<p><?php __('sub_pay_bank_info'); ?></p>
				<?php if ($tpl['bank_account'] !== '') { ?><div class="well m-b-none"><?php echo nl2br(pjSanitize::html($tpl['bank_account'])); ?></div><?php } ?>
			<?php } else { ?>
				<p><?php __('sub_pay_cash_info'); ?></p>
			<?php } ?>
			</div>
		</div>
		<div class="hr-line-dashed"></div>
		<a class="btn btn-white" href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionMyPlan"><?php __('sub_pay_back'); ?></a>
	</div></div>
</div>
