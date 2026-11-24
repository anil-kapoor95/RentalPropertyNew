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
// One currency only: the company's (India INR, every other country USD; once billed, the currency it pays in).
$billing = $tpl['billing_currency'];
$btn = function ($do, $plan_id, $label, $class = 'btn-primary', $confirm = false) use ($action, $subCsrf, $methods) {
	if ($do === 'pay')
	{
		// popup first: coupon code and (when switched on) the payment method
		return '<button type="button" class="btn ' . $class . ' sub-inline subPayBtn" data-plan="' . (int) $plan_id . '" data-kind-label="' . pjSanitize::html(__($label, true)) . '">' . __($label, true) . '</button>';
	}
	return '<form method="post" action="' . $action . '" class="sub-inline"' . ($confirm ? ' data-sub-confirm="' . pjSanitize::html(__('sub_confirm', true)) . '"' : '') . '>' . $subCsrf
		. '<input type="hidden" name="do" value="' . $do . '"><input type="hidden" name="plan_id" value="' . (int) $plan_id . '">'
		. '<button class="btn ' . $class . '" type="submit">' . __($label, true) . '</button></form>';
};
$included = array();
$locked = array();
foreach (pjEntitlement::$features as $f => $lbl)
{
	if (in_array($f, $s['features'], true)) { $included[$f] = $lbl; } else { $locked[$f] = $lbl; }
}
$featureItem = function ($f, $lbl, $on) {
	return '<li class="' . ($on ? 'on' : 'off') . '"><i class="fa ' . ($on ? 'fa-check-circle' : 'fa-lock') . '" aria-hidden="true"></i> <span>' . __($lbl, true) . '</span>'
		. (pjEntitlement::isComingSoon($f) ? ' <span class="sub-mp-soon">' . __('sub_coming_soon', true) . '</span>' : '') . '</li>';
};
?>
<div class="wrapper wrapper-content animated fadeInRight sub-mp">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>

	<!-- Current plan -->
	<section class="sub-mp-card sub-mp-hero" aria-labelledby="subMpCurrent">
		<div class="sub-mp-hero-main">
			<p class="sub-mp-eyebrow"><?php __('sub_current_plan'); ?></p>
			<h2 class="sub-mp-name" id="subMpCurrent"><?php echo $cur ? pjSanitize::html($cur['name']) : __('sub_no_plan', true); ?>
				<?php echo $sub ? $subStatus($s['status']) : ''; ?></h2>
			<?php if ($sub) { ?>
			<dl class="sub-mp-meta">
				<?php if (!empty($sub['current_period_end'])) { ?>
				<div><dt><?php __($s['status'] === 'trial' ? 'sub_trial_ends' : 'sub_col_renewal'); ?></dt><dd><?php echo $subDate($sub['current_period_end']); ?></dd></div>
				<?php } ?>
				<?php if (!empty($sub['pending_plan_id'])) { $pp = pjEntitlement::plan($sub['pending_plan_id']); ?>
				<div><dt><?php __('sub_scheduled_change'); ?></dt><dd><?php echo $pp ? pjSanitize::html($pp['name']) : ''; ?></dd></div>
				<?php } ?>
			</dl>
			<?php if ((int) $sub['cancel_at_period_end'] === 1) { ?><p class="sub-mp-alert"><i class="fa fa-info-circle"></i> <?php __('sub_cancels_at_end'); ?></p><?php } ?>
			<?php if ($s['status'] === 'past_due') { ?><p class="sub-mp-alert"><i class="fa fa-exclamation-triangle"></i> <?php __('sub_past_due_msg'); ?> <?php echo $subDate($s['grace_ends_at']); ?></p><?php } ?>
			<?php if (!$s['entitled']) { ?><p class="sub-mp-alert"><i class="fa fa-exclamation-triangle"></i> <?php __('sub_expired_msg'); ?></p><?php } ?>
			<?php } elseif ($s['source'] === 'unassigned') { ?>
			<p class="text-muted"><?php __($s['entitled'] ? 'sub_unassigned_full_msg' : 'sub_unassigned_restricted_msg'); ?></p>
			<?php } ?>
			<?php if ($sub && $s['entitled']) { ?>
			<div class="sub-mp-hero-actions">
				<?php if ((int) $sub['cancel_at_period_end'] === 1) { echo $btn('undo_cancel', 0, 'sub_btn_undo_cancel', 'btn-white'); } elseif (!empty($sub['current_period_end']) && $s['status'] !== 'trial') { echo $btn('cancel', 0, 'sub_btn_cancel_end', 'btn-white', true); } ?>
				<?php if (!empty($sub['pending_plan_id'])) { echo $btn('undo_downgrade', 0, 'sub_btn_undo_downgrade', 'btn-white'); } ?>
			</div>
			<?php } ?>
		</div>
		<div class="sub-mp-usage" aria-label="<?php __('sub_mp_usage', false, true); ?>">
			<?php foreach (array('properties' => 'max_properties', 'users' => 'max_users') as $u => $l) { $max = $s['limits'][$l]; $used = (int) $tpl['usage'][$u]; $pct = $max === null ? 0 : ($max > 0 ? min(100, round($used * 100 / $max)) : 100); ?>
			<div class="sub-mp-tile">
				<span class="sub-mp-tile-label"><?php __('sub_col_' . $u); ?></span>
				<strong class="sub-mp-tile-value"><?php echo $used; ?> <small>/ <?php echo $subLimit($max); ?></small></strong>
				<?php if ($max !== null) { ?><span class="sub-mp-bar<?php echo $pct >= 100 ? ' full' : ''; ?>" role="progressbar" aria-valuenow="<?php echo $used; ?>" aria-valuemin="0" aria-valuemax="<?php echo (int) $max; ?>"><span style="width:<?php echo $pct; ?>%"></span></span><?php } ?>
			</div>
			<?php } ?>
			<div class="sub-mp-tile">
				<span class="sub-mp-tile-label"><?php __('sub_max_payment_gateways'); ?></span>
				<strong class="sub-mp-tile-value"><?php echo $subLimit($s['limits']['max_payment_gateways']); ?> <small><?php __('sub_per_property'); ?></small></strong>
			</div>
		</div>
	</section>

	<!-- Features: what the plan has, and what an upgrade unlocks -->
	<section class="sub-mp-card">
		<div class="sub-mp-feature-cols">
			<div>
				<h3 class="sub-mp-h"><?php __('sub_included'); ?></h3>
				<ul class="sub-mp-features"><?php foreach ($included as $f => $lbl) { echo $featureItem($f, $lbl, true); } ?></ul>
			</div>
			<?php if (!empty($locked)) { ?>
			<div>
				<h3 class="sub-mp-h"><?php __('sub_mp_unlock'); ?></h3>
				<ul class="sub-mp-features"><?php foreach ($locked as $f => $lbl) { echo $featureItem($f, $lbl, false); } ?></ul>
			</div>
			<?php } ?>
		</div>
	</section>

	<!-- Plans: prices in the company's currency only -->
	<div class="sub-mp-plans-head">
		<h3 class="sub-mp-h"><?php __('sub_mp_plans'); ?></h3>
		<span class="sub-mp-cur"><span class="sub-mp-cur-sign"><?php echo $billing === 'INR' ? '&#8377;' : '$'; ?></span> <?php __('sub_prices_in'); ?> <strong><?php echo pjSanitize::html($billing); ?></strong> &middot; <?php __('sub_cur_' . strtolower($billing) . '_name'); ?></span>
	</div>
	<div class="sub-plans sub-mp-plans">
	<?php foreach ($tpl['plans'] as $p) { if ((int) $p['is_public'] !== 1) { continue; }
		$is_cur = $cur && (int) $cur['id'] === (int) $p['id'];
		$buy = pjEntitlement::isPurchasable($p);
		$free = pjEntitlement::isFree($p);
		$cp = pjEntitlement::priceIn($p, $billing);
		$qs = isset($tpl['quotes'][(int) $p['id']]) ? $tpl['quotes'][(int) $p['id']] : array();
		$q = isset($qs[$billing]) ? $qs[$billing] : null;
		$pf = (isset($p['feature_list']) ? (array) $p['feature_list'] : array());
		?>
		<div class="sub-plan sub-mp-plan<?php echo $is_cur ? ' current' : ''; ?>" data-plan="<?php echo (int) $p['id']; ?>" data-interval="<?php echo pjSanitize::html(strip_tags(html_entity_decode($subInterval($p)))); ?>"
			data-currencies="<?php echo pjSanitize::html(implode(',', array_values(array_intersect(pjEntitlement::currenciesOf($p), array($billing))))); ?>"
			data-prices="<?php echo pjSanitize::html(json_encode(array($billing => $cp !== null ? pjMoney::format($cp, $billing) : ''))); ?>">
			<div class="sub-mp-plan-top">
				<h4><?php echo pjSanitize::html($p['name']); ?></h4>
				<?php if ($is_cur) { ?><span class="sub-badge sub-active"><?php __('sub_current'); ?></span><?php } ?>
			</div>
			<div class="sub-price"><?php
				if ($p['price'] === null) { echo $subMoney(null, $billing); }
				elseif ($cp === null) { ?><span class="sub-price-na"><?php __('sub_price_na_cur'); ?></span><?php }
				elseif ($q && $q['discount'] > 0) { ?><del class="sub-price-old"><?php echo $subMoney($q['list'], $billing); ?></del> <?php echo $subMoney($q['amount'], $billing); ?><?php }
				else { echo $subMoney($q ? $q['amount'] : $cp, $billing); /* a renewal shows the agreed (locked) price */ }
				if ($p['price'] !== null && $cp !== null) { ?> <small>/ <?php echo $subInterval($p); ?></small><?php } ?></div>
			<?php if (trim((string) $p['description']) !== '') { ?><p class="sub-mp-plan-desc"><?php echo pjSanitize::html((string) $p['description']); ?></p><?php } ?>
			<ul class="sub-mp-plan-list">
				<li><i class="fa fa-home" aria-hidden="true"></i> <?php __('sub_max_properties'); ?>: <strong><?php echo $subLimit($p['max_properties']); ?></strong></li>
				<li><i class="fa fa-user" aria-hidden="true"></i> <?php __('sub_max_users'); ?>: <strong><?php echo $subLimit($p['max_users']); ?></strong></li>
				<?php foreach (pjEntitlement::$features as $f => $lbl) { if (!in_array($f, $pf, true) || pjEntitlement::isComingSoon($f)) { continue; } ?>
				<li><i class="fa fa-check" aria-hidden="true"></i> <?php __($lbl); ?></li>
				<?php } ?>
			</ul>
			<div class="sub-actions">
			<?php
			if ($is_cur && $s['entitled'])
			{
				if ($buy) { echo $btn('pay', $p['id'], 'sub_btn_renew', 'btn-primary btn-block'); }
			} elseif ($is_cur || !$cur || !$s['entitled']) {
				if ($buy) { echo $btn('pay', $p['id'], 'sub_btn_subscribe', 'btn-primary btn-block'); }
				if ($free) { echo $btn('downgrade', $p['id'], 'sub_btn_choose_free', 'btn-primary btn-block', true); }
				if ($tpl['self_trial'] && (int) $p['trial_days'] > 0 && !($sub && (int) $sub['used_trial'] === 1)) { echo $btn('trial', $p['id'], 'sub_btn_trial', 'btn-white btn-block'); }
			} elseif ((int) $p['sort'] > (int) $cur['sort']) {
				if ($buy) { echo $btn('pay', $p['id'], 'sub_btn_upgrade', 'btn-primary btn-block', true); }
			} else {
				echo $btn('downgrade', $p['id'], 'sub_btn_downgrade', 'btn-white btn-block', true);
			}
			if (!$buy && !$free && !$is_cur) { ?><p class="small text-muted m-t-xs"><?php __('sub_contact_admin'); ?></p><?php }
			?>
			</div>
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
					<!-- Currency: only the company's own (India INR, every other country USD); not a choice -->
					<div class="sub-cur-options sub-mp-cur-fixed" style="display:none">
						<label class="sub-cur-option" data-cur="<?php echo pjSanitize::html($billing); ?>">
							<input type="radio" name="currency" value="<?php echo pjSanitize::html($billing); ?>" checked>
							<span class="sub-cur-price" data-pv="price"></span>
						</label>
					</div>
					<p class="sub-pay-locked" hidden><i class="fa fa-lock"></i> <span></span></p>

					<!-- 2. Payment method (only the gateways of the chosen currency) -->
					<fieldset class="sub-pay-step">
						<legend><span class="sub-step-no">1</span><?php __('sub_pay_step_method'); ?></legend>
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
