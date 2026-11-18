<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = 'sub_myplan_title';
$heading_desc = 'sub_myplan_desc';
include dirname(__FILE__) . '/elements/heading.php';
$s = $tpl['state'];
$sub = $s['subscription'];
$cur = $s['plan'];
$action = $_SERVER['PHP_SELF'] . '?controller=pjAdminSubscriptions&amp;action=pjActionMyPlan';
$methods = $tpl['methods'];
$method_cur = $tpl['method_currencies'];
$billing = $tpl['billing_currency'];
$btn = function ($do, $plan_id, $label, $class = 'btn-primary', $confirm = false) use ($action, $subCsrf, $methods) {
	if ($do === 'pay')
	{
		// popup first: coupon code and (when switched on) the payment method
		return '<button type="button" class="btn ' . $class . ' btn-sm sub-inline subPayBtn" data-plan="' . (int) $plan_id . '" data-kind-label="' . pjSanitize::html(__($label, true)) . '">' . __($label, true) . '</button>';
	}
	return '<form method="post" action="' . $action . '" class="sub-inline"' . ($confirm ? ' data-sub-confirm="' . pjSanitize::html(__('sub_confirm', true)) . '"' : '') . '>' . $subCsrf
		. '<input type="hidden" name="do" value="' . $do . '"><input type="hidden" name="plan_id" value="' . (int) $plan_id . '">'
		. '<button class="btn ' . $class . ' btn-sm" type="submit">' . __($label, true) . '</button></form>';
};
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
	<div class="ibox float-e-margins"><div class="ibox-content">
		<div class="row">
			<div class="col-md-6">
				<p class="text-muted m-b-xs"><?php __('sub_current_plan'); ?></p>
				<h2 class="m-t-none"><?php echo $cur ? pjSanitize::html($cur['name']) : __('sub_no_plan', true); ?>
					<?php echo $sub ? $subStatus($s['status']) : ''; ?></h2>
				<?php if ($sub) { ?>
					<p><?php __($s['status'] === 'trial' ? 'sub_trial_ends' : 'sub_col_renewal'); ?>: <strong><?php echo $subDate($sub['current_period_end']); ?></strong>
					<?php if ((int) $sub['cancel_at_period_end'] === 1) { ?><br><span class="text-danger"><?php __('sub_cancels_at_end'); ?></span><?php } ?>
					<?php if (!empty($sub['pending_plan_id'])) { $pp = pjEntitlement::plan($sub['pending_plan_id']); ?><br><?php __('sub_scheduled_change'); ?>: <strong><?php echo $pp ? pjSanitize::html($pp['name']) : ''; ?></strong><?php } ?>
					<?php if ($s['status'] === 'past_due') { ?><br><span class="text-danger"><?php __('sub_past_due_msg'); ?> <?php echo $subDate($s['grace_ends_at']); ?></span><?php } ?>
					<?php if (!$s['entitled']) { ?><br><span class="text-danger"><?php __('sub_expired_msg'); ?></span><?php } ?>
					</p>
				<?php } elseif ($s['source'] === 'unassigned') { ?>
					<p class="text-muted"><?php __($s['entitled'] ? 'sub_unassigned_full_msg' : 'sub_unassigned_restricted_msg'); ?></p>
				<?php } ?>
			</div>
			<div class="col-md-6">
				<div class="sub-usage">
					<?php foreach (array('properties' => 'max_properties', 'users' => 'max_users') as $u => $l) { $max = $s['limits'][$l]; $used = (int) $tpl['usage'][$u]; $pct = $max === null ? 0 : ($max > 0 ? min(100, round($used * 100 / $max)) : 100); ?>
					<div class="m-b-sm"><strong><?php __('sub_col_' . $u); ?></strong> <span class="text-muted"><?php echo $used; ?> / <?php echo $subLimit($max); ?></span>
						<?php if ($max !== null) { ?><div class="progress progress-mini"><div class="progress-bar" style="width:<?php echo $pct; ?>%"></div></div><?php } ?></div>
					<?php } ?>
					<div><strong><?php __('sub_max_payment_gateways'); ?></strong> <span class="text-muted"><?php echo $subLimit($s['limits']['max_payment_gateways']); ?> <?php __('sub_per_property'); ?></span></div>
				</div>
				<?php if ($sub && $s['entitled']) { ?>
				<div class="m-t-md">
					<?php if ((int) $sub['cancel_at_period_end'] === 1) { echo $btn('undo_cancel', 0, 'sub_btn_undo_cancel', 'btn-white'); } elseif (!empty($sub['current_period_end'])) { echo $btn('cancel', 0, 'sub_btn_cancel_end', 'btn-white', true); } ?>
					<?php if (!empty($sub['pending_plan_id'])) { echo $btn('undo_downgrade', 0, 'sub_btn_undo_downgrade', 'btn-white'); } ?>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="hr-line-dashed"></div>
		<p class="m-b-xs"><strong><?php __('sub_included'); ?></strong></p>
		<ul class="sub-features">
			<?php foreach (pjEntitlement::$features as $f => $lbl) { $on = in_array($f, $s['features'], true); ?>
			<li class="<?php echo $on ? 'on' : 'off'; ?>"><i class="fa <?php echo $on ? 'fa-check' : 'fa-lock'; ?>"></i> <?php __($lbl); ?><?php if (pjEntitlement::isComingSoon($f)) { ?> <span class="label label-default"><?php __('sub_coming_soon'); ?></span><?php } ?></li>
			<?php } ?>
		</ul>
	</div></div>

	<div class="sub-cur-bar">
		<span class="sub-cur-bar-label"><i class="fa fa-globe"></i> <?php __('sub_prices_in'); ?></span>
		<div class="sub-seg" role="radiogroup" aria-label="<?php __('sub_prices_in', false, true); ?>" id="subCurSwitch">
			<?php foreach (pjEntitlement::$currencies as $c) { ?>
			<button type="button" class="sub-seg-btn<?php echo $c === $billing ? ' active' : ''; ?>" role="radio" aria-checked="<?php echo $c === $billing ? 'true' : 'false'; ?>" data-cur="<?php echo $c; ?>">
				<strong><?php echo $c; ?></strong> <small><?php __('sub_cur_' . strtolower($c) . '_short'); ?></small>
			</button>
			<?php } ?>
		</div>
		<?php if ($tpl['currency_locked']) { ?><span class="sub-cur-bar-note"><i class="fa fa-lock"></i> <?php echo str_replace('{CUR}', '<strong>' . pjSanitize::html($billing) . '</strong>', __('sub_billed_in', true)); ?></span><?php } ?>
	</div>

	<div class="row sub-plans">
	<?php foreach ($tpl['plans'] as $p) { if ((int) $p['is_public'] !== 1) { continue; }
		$is_cur = $cur && (int) $cur['id'] === (int) $p['id'];
		$buy = pjEntitlement::isPurchasable($p);
		$free = pjEntitlement::isFree($p);
		?>
		<div class="col-md-4">
			<div class="ibox sub-plan<?php echo $is_cur ? ' current' : ''; ?>" data-plan="<?php echo (int) $p['id']; ?>" data-interval="<?php echo pjSanitize::html(strip_tags(html_entity_decode($subInterval($p)))); ?>"
				data-currencies="<?php echo pjSanitize::html(implode(',', pjEntitlement::currenciesOf($p))); ?>"
				data-prices="<?php echo pjSanitize::html(json_encode(array('USD' => pjEntitlement::priceIn($p, 'USD') !== null ? pjMoney::format(pjEntitlement::priceIn($p, 'USD'), 'USD') : '', 'INR' => pjEntitlement::priceIn($p, 'INR') !== null ? pjMoney::format(pjEntitlement::priceIn($p, 'INR'), 'INR') : ''))); ?>"><div class="ibox-content">
				<?php if ($is_cur) { ?><span class="sub-badge sub-active pull-right"><?php __('sub_current'); ?></span><?php } ?>
				<h3 class="m-t-none"><?php echo pjSanitize::html($p['name']); ?></h3>
				<?php
				// one price per currency (USD / INR); the currency switch above the plans shows one of them
				$qs = isset($tpl['quotes'][(int) $p['id']]) ? $tpl['quotes'][(int) $p['id']] : array();
				foreach (pjEntitlement::$currencies as $c) {
					$cp = pjEntitlement::priceIn($p, $c);
					$q = isset($qs[$c]) ? $qs[$c] : null;
					?>
				<div class="sub-price" data-cur="<?php echo $c; ?>"<?php echo $c !== $billing ? ' hidden' : ''; ?>><?php
					if ($p['price'] === null) { echo $subMoney(null, $c); }
					elseif ($cp === null) { ?><span class="sub-price-na"><?php __('sub_price_na_cur'); ?></span><?php }
					elseif ($q && $q['discount'] > 0) { ?><del class="sub-price-old"><?php echo $subMoney($q['list'], $c); ?></del> <?php echo $subMoney($q['amount'], $c); ?><?php }
					else { echo $subMoney($q ? $q['amount'] : $cp, $c); /* a renewal shows the agreed (locked) price */ }
					if ($p['price'] !== null && $cp !== null) { ?> <small>/ <?php echo $subInterval($p); ?></small><?php } ?></div>
				<?php } ?>
				<p class="text-muted small"><?php echo pjSanitize::html((string) $p['description']); ?></p>
				<ul class="sub-limits">
					<li><?php __('sub_max_properties'); ?>: <?php echo $subLimit($p['max_properties']); ?></li>
					<li><?php __('sub_max_users'); ?>: <?php echo $subLimit($p['max_users']); ?></li>
				</ul>
				<div class="sub-actions">
				<?php
				if ($is_cur && $s['entitled'])
				{
					if ($buy) { echo $btn('pay', $p['id'], 'sub_btn_renew', 'btn-primary'); }
				} elseif ($is_cur || !$cur || !$s['entitled']) {
					if ($buy) { echo $btn('pay', $p['id'], 'sub_btn_subscribe', 'btn-primary'); }
					if ($free) { echo $btn('downgrade', $p['id'], 'sub_btn_choose_free', 'btn-primary', true); }
					if ($tpl['self_trial'] && (int) $p['trial_days'] > 0 && !($sub && (int) $sub['used_trial'] === 1)) { echo $btn('trial', $p['id'], 'sub_btn_trial', 'btn-white'); }
				} elseif ((int) $p['sort'] > (int) $cur['sort']) {
					if ($buy) { echo $btn('pay', $p['id'], 'sub_btn_upgrade', 'btn-primary', true); }
				} else {
					echo $btn('downgrade', $p['id'], 'sub_btn_downgrade', 'btn-white', true);
				}
				if (!$buy && !$free && !$is_cur) { ?><p class="small text-muted m-t-xs"><?php __('sub_contact_admin'); ?></p><?php }
				?>
				</div>
			</div></div>
		</div>
	<?php } ?>
	</div>
	<?php if (empty($methods)) { ?><p class="text-muted small"><?php __('sub_manual_note'); ?></p><?php } ?>

	<!-- Choose how to pay: currency -> payment method -> coupon -> summary -->
	<div class="modal inmodal fade sub-pay-modal" id="subPayModal" tabindex="-1" role="dialog" aria-labelledby="subPayTitle" aria-hidden="true" style="display: none;">
		<div class="modal-dialog"><div class="modal-content">
			<form method="post" action="<?php echo $action; ?>" id="frmSubPay" data-check="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionCheckPromo"
				data-billing="<?php echo pjSanitize::html($billing); ?>" data-btn-pay="<?php __('sub_pay_continue', false, true); ?>" data-btn-request="<?php __('sub_pay_send_request', false, true); ?>"
				data-locked="<?php __('sub_pay_locked', false, true); ?>" data-price-locked="<?php __('sub_pay_price_locked', false, true); ?>">
				<?php echo $subCsrf; ?><input type="hidden" name="do" value="pay"><input type="hidden" name="plan_id" value="0">
				<div class="sub-pay-head">
					<button type="button" class="close" data-dismiss="modal" aria-label="<?php __('btnCancel', false, true); ?>"><span aria-hidden="true">&times;</span></button>
					<span class="sub-pay-kind" data-pv="kind"></span>
					<h4 class="sub-pay-title" id="subPayTitle"></h4>
					<p class="sub-pay-sub"><i class="fa fa-refresh"></i> <span data-pv="interval"></span></p>
				</div>
				<div class="sub-pay-body">
					<!-- 1. Currency -->
					<fieldset class="sub-pay-step">
						<legend><span class="sub-step-no">1</span><?php __('sub_pay_step_currency'); ?></legend>
						<div class="sub-cur-options">
							<?php foreach (pjEntitlement::$currencies as $c) { ?>
							<label class="sub-cur-option" data-cur="<?php echo $c; ?>">
								<input type="radio" name="currency" value="<?php echo $c; ?>">
								<span class="sub-cur-flag"><?php echo $c === 'INR' ? '&#8377;' : '$'; ?></span>
								<span class="sub-cur-meta"><strong><?php echo $c; ?></strong><small><?php __('sub_cur_' . strtolower($c) . '_name'); ?></small></span>
								<span class="sub-cur-price" data-pv="price"></span>
							</label>
							<?php } ?>
						</div>
						<p class="sub-pay-locked" hidden><i class="fa fa-lock"></i> <span></span></p>
					</fieldset>

					<!-- 2. Payment method (only the gateways of the chosen currency) -->
					<fieldset class="sub-pay-step">
						<legend><span class="sub-step-no">2</span><?php __('sub_pay_step_method'); ?></legend>
						<div class="sub-methods">
						<?php foreach ($methods as $m => $name) { if (!isset($method_cur[$m])) { continue; } ?>
							<label class="sub-method" data-currencies="<?php echo pjSanitize::html(implode(',', $method_cur[$m])); ?>">
								<input type="radio" name="method" value="<?php echo pjSanitize::html($m); ?>">
								<span class="sub-method-check"><i class="fa fa-check"></i></span>
								<img src="<?php echo PJ_IMG_PATH; ?>backend/payments/<?php echo pjSanitize::html($m); ?>.png" alt="">
								<span class="sub-method-name"><?php echo pjSanitize::html($name); ?></span>
							</label>
						<?php } ?>
						</div>
						<p class="sub-pay-nomethod" hidden><i class="fa fa-info-circle"></i> <?php __('sub_pay_no_method'); ?></p>
					</fieldset>

					<!-- Coupon (optional) -->
					<div class="sub-coupon">
						<button type="button" class="sub-coupon-toggle" aria-expanded="false" aria-controls="subCouponBox"><i class="fa fa-tag"></i> <?php __('sub_have_coupon'); ?></button>
						<div class="sub-coupon-box" id="subCouponBox" hidden>
							<label class="sr-only" for="subCouponInput"><?php __('sub_coupon_code'); ?></label>
							<div class="input-group">
								<input type="text" class="form-control text-uppercase" id="subCouponInput" name="coupon_code" maxlength="30" autocomplete="off" placeholder="<?php __('sub_coupon_hint', false, true); ?>">
								<span class="input-group-btn"><button type="button" class="btn btn-white subCouponApply"><?php __('sub_coupon_apply'); ?></button></span>
							</div>
							<small class="subCouponMsg" role="status" aria-live="polite"></small>
						</div>
					</div>

					<!-- Summary -->
					<div class="sub-summary" aria-live="polite">
						<div class="sub-summary-row"><span><?php __('sub_list_price'); ?></span><span class="subQuoteList"></span></div>
						<div class="sub-summary-row sub-summary-discount subQuoteDiscountRow"><span><?php __('sub_discount'); ?></span><span class="subQuoteDiscount"></span></div>
						<div class="sub-summary-total"><span><?php __('sub_you_pay'); ?></span><strong class="subQuoteAmount"></strong></div>
						<p class="sub-summary-usd" hidden></p>
						<p class="sub-summary-lock" hidden><i class="fa fa-lock"></i> <span></span></p>
					</div>
					<p class="sub-pay-secure"><i class="fa fa-shield"></i> <?php __('sub_pay_secure'); ?></p>
				</div>
				<div class="sub-pay-foot">
					<button type="button" class="btn btn-white" data-dismiss="modal"><?php __('btnCancel'); ?></button>
					<button type="submit" class="btn btn-primary sub-pay-submit"><i class="fa fa-lock m-r-xs"></i><span><?php __('sub_pay_continue'); ?></span></button>
				</div>
			</form>
		</div></div>
	</div>

	<div class="ibox float-e-margins"><div class="ibox-content">
		<h4><?php __('sub_tab_billing'); ?></h4>
		<?php include dirname(__FILE__) . '/elements/transactions.php'; ?>
	</div></div>
</div>
