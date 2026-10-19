<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = 'sub_gw_title';
$heading_desc = 'sub_gw_desc';
include dirname(__FILE__) . '/elements/heading.php';
$all = $tpl['all_methods'];
$active = $tpl['active_methods'];
$offline = pjEntitlement::$offlineMethods;
$tile = function ($method, $name, $on) {
	return '<div class="col-lg-2 col-md-3 col-sm-4 col-xs-6"><a href="#" class="payment subGatewayLink' . ($on ? ' active' : '') . '" data-method="' . pjSanitize::html($method)
		. '" title="' . pjSanitize::html($name) . '"><img src="' . PJ_IMG_PATH . 'backend/payments/' . pjSanitize::html($method) . '.png" alt="' . pjSanitize::html($name) . '"></a></div>';
};
?>
<div class="wrapper wrapper-content animated fadeInRight" id="subGateways" data-url="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionGatewayOptions">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
	<div class="ibox float-e-margins"><div class="ibox-content">
		<p class="text-muted"><?php printf(__('sub_gw_currency_note', true), '<strong>' . pjSanitize::html($tpl['gateway_currency']) . '</strong>'); ?></p>
		<?php if (!empty($tpl['currency_mismatch'])) { ?>
		<div class="alert alert-warning m-b-sm"><i class="fa fa-exclamation-triangle m-r-xs"></i> <?php __('sub_gw_currency_mismatch'); ?> <?php echo pjSanitize::html(implode(', ', $tpl['currency_mismatch'])); ?></div>
		<?php } ?>
		<?php if (empty($active)) { ?>
		<div class="alert alert-info m-b-none"><i class="fa fa-info-circle m-r-xs"></i> <?php __('sub_gw_none'); ?></div>
		<?php } ?>
	</div></div>

	<?php if (!empty($active)) { ?>
	<div class="ibox float-e-margins">
		<div class="ibox-content ibox-heading"><h3><?php __('plugin_payments_active_payment_gateways'); ?></h3></div>
		<div class="ibox-content"><div class="row">
			<?php foreach ($active as $m => $name) { echo $tile($m, $name, true); } ?>
		</div></div>
	</div>
	<?php } ?>

	<?php $online_off = array_diff_key(array_diff_key($all, $active), array_flip($offline)); if (!empty($online_off)) { ?>
	<div class="ibox float-e-margins">
		<div class="ibox-content ibox-heading"><h3><?php __('plugin_payments_add_payment_gateways'); ?></h3></div>
		<div class="ibox-content"><div class="row">
			<?php foreach ($online_off as $m => $name) { echo $tile($m, $name, false); } ?>
		</div></div>
	</div>
	<?php } ?>

	<?php $offline_off = array_intersect_key(array_diff_key($all, $active), array_flip($offline)); if (!empty($offline_off)) { ?>
	<div class="ibox float-e-margins">
		<div class="ibox-content ibox-heading"><h3><?php __('script_offline_payment_methods'); ?></h3></div>
		<div class="ibox-content"><div class="row">
			<?php foreach ($offline_off as $m => $name) { echo $tile($m, $name, false); } ?>
		</div></div>
	</div>
	<?php } ?>

	<div class="modal inmodal fade" id="subGatewayModal" tabindex="-1" role="dialog" aria-hidden="true" style="display: none;">
		<div class="modal-dialog"><div id="subGatewayModalContent" class="modal-content"></div></div>
	</div>
</div>
