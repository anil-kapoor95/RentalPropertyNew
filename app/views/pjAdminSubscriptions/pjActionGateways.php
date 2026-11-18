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
		<!-- Gateways for each currency: only the gateways switched on below that can take the currency -->
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionGateways" class="sub-gwc">
			<?php echo $subCsrf; ?><input type="hidden" name="do" value="gateway_map">
			<div class="sub-gwc-head">
				<div>
					<h3><?php __('sub_gwc_title'); ?></h3>
					<p><?php __('sub_gwc_intro'); ?></p>
				</div>
				<button class="btn btn-primary" type="submit"><i class="fa fa-check m-r-xs"></i><?php __('sub_btn_save'); ?></button>
			</div>
			<div class="sub-gwc-grid">
			<?php foreach (pjEntitlement::$currencies as $cur) {
				$opts = $tpl['gateway_options'][$cur];
				$picked = $tpl['currency_methods'][$cur];
				$n_on = count(array_intersect(array_keys($opts), $picked));
				?>
				<section class="sub-gwc-cur" aria-labelledby="gwc-<?php echo $cur; ?>">
					<header>
						<span class="sub-cur-flag sub-cur-flag-<?php echo strtolower($cur); ?>"><?php echo $cur === 'INR' ? '&#8377;' : '$'; ?></span>
						<div>
							<h4 id="gwc-<?php echo $cur; ?>"><?php echo $cur; ?> <small><?php __('sub_cur_' . strtolower($cur) . '_name'); ?></small></h4>
							<p class="sub-gwc-count" data-total="<?php echo count($opts); ?>"><?php echo str_replace(array('{N}', '{T}'), array('<b>' . $n_on . '</b>', count($opts)), __('sub_gwc_count', true)); ?></p>
						</div>
					</header>
					<?php if (empty($opts)) { ?>
					<div class="sub-gwc-empty">
						<i class="fa fa-plug"></i>
						<p><?php echo str_replace('{CUR}', $cur, __($cur === 'INR' ? 'sub_gwc_empty_inr' : 'sub_gwc_empty', true)); ?></p>
					</div>
					<?php } else { ?>
					<ul class="sub-gwc-list">
						<?php foreach ($opts as $m => $name) { $on = in_array($m, $picked, true); ?>
						<li>
							<label class="sub-gwc-item<?php echo $on ? ' on' : ''; ?>">
								<span class="sub-gwc-logo"><img src="<?php echo PJ_IMG_PATH; ?>backend/payments/<?php echo pjSanitize::html($m); ?>.png" alt=""></span>
								<span class="sub-gwc-name"><?php echo pjSanitize::html($name); ?></span>
								<span class="sub-switch"><input type="checkbox" name="map[<?php echo $cur; ?>][]" value="<?php echo pjSanitize::html($m); ?>"<?php echo $on ? ' checked' : ''; ?> aria-label="<?php echo pjSanitize::html(str_replace(array('{GW}', '{CUR}'), array($name, $cur), __('sub_gwc_toggle', true))); ?>"><i></i></span>
							</label>
						</li>
						<?php } ?>
					</ul>
					<p class="sub-gwc-warn" hidden><i class="fa fa-exclamation-triangle"></i> <?php echo str_replace('{CUR}', $cur, __('sub_gwc_none_on', true)); ?></p>
					<?php } ?>
					<?php if (!empty($tpl['gateway_unsupported'][$cur])) { ?>
					<p class="sub-gwc-note"><i class="fa fa-info-circle"></i> <?php echo str_replace(array('{LIST}', '{CUR}'), array(pjSanitize::html(implode(', ', $tpl['gateway_unsupported'][$cur])), $cur), __('sub_gwc_unsupported', true)); ?></p>
					<?php } ?>
				</section>
			<?php } ?>
			</div>
		</form>
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
