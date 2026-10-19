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
$btn = function ($do, $plan_id, $label, $class = 'btn-primary', $confirm = false) use ($action, $subCsrf, $methods) {
	if ($do === 'pay' && !empty($methods))
	{
		// choose the payment method in the popup first
		return '<button type="button" class="btn ' . $class . ' btn-sm sub-inline subPayBtn" data-plan="' . (int) $plan_id . '">' . __($label, true) . '</button>';
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
					<?php if ((int) $sub['cancel_at_period_end'] === 1) { echo $btn('undo_cancel', 0, 'sub_btn_undo_cancel', 'btn-white'); } else { echo $btn('cancel', 0, 'sub_btn_cancel_end', 'btn-white', true); } ?>
					<?php if (!empty($sub['pending_plan_id'])) { echo $btn('undo_downgrade', 0, 'sub_btn_undo_downgrade', 'btn-white'); } ?>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="hr-line-dashed"></div>
		<p class="m-b-xs"><strong><?php __('sub_included'); ?></strong></p>
		<ul class="sub-features">
			<?php foreach (pjEntitlement::$features as $f => $lbl) { $on = in_array($f, $s['features'], true); ?>
			<li class="<?php echo $on ? 'on' : 'off'; ?>"><i class="fa <?php echo $on ? 'fa-check' : 'fa-lock'; ?>"></i> <?php __($lbl); ?></li>
			<?php } ?>
		</ul>
	</div></div>

	<div class="row sub-plans">
	<?php foreach ($tpl['plans'] as $p) { if ((int) $p['is_public'] !== 1) { continue; }
		$is_cur = $cur && (int) $cur['id'] === (int) $p['id'];
		$buy = pjEntitlement::isPurchasable($p);
		?>
		<div class="col-md-4">
			<div class="ibox sub-plan<?php echo $is_cur ? ' current' : ''; ?>"><div class="ibox-content">
				<?php if ($is_cur) { ?><span class="sub-badge sub-active pull-right"><?php __('sub_current'); ?></span><?php } ?>
				<h3 class="m-t-none"><?php echo pjSanitize::html($p['name']); ?></h3>
				<div class="sub-price"><?php echo $subMoney($p['price'], $p['currency']); ?><?php if ($p['price'] !== null) { ?> <small>/ <?php echo $subInterval($p); ?></small><?php } ?></div>
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
					if ($tpl['self_trial'] && (int) $p['trial_days'] > 0 && !($sub && (int) $sub['used_trial'] === 1)) { echo $btn('trial', $p['id'], 'sub_btn_trial', 'btn-white'); }
				} elseif ((int) $p['sort'] > (int) $cur['sort']) {
					if ($buy) { echo $btn('pay', $p['id'], 'sub_btn_upgrade', 'btn-primary', true); }
				} else {
					echo $btn('downgrade', $p['id'], 'sub_btn_downgrade', 'btn-white', true);
				}
				if (!$buy && !$is_cur) { ?><p class="small text-muted m-t-xs"><?php __('sub_contact_admin'); ?></p><?php }
				?>
				</div>
			</div></div>
		</div>
	<?php } ?>
	</div>
	<?php if (empty($methods)) { ?><p class="text-muted small"><?php __('sub_manual_note'); ?></p><?php } ?>

	<?php if (!empty($methods)) { ?>
	<div class="modal inmodal fade" id="subPayModal" tabindex="-1" role="dialog" aria-hidden="true" style="display: none;">
		<div class="modal-dialog"><div class="modal-content">
			<form method="post" action="<?php echo $action; ?>" id="frmSubPay">
				<?php echo $subCsrf; ?><input type="hidden" name="do" value="pay"><input type="hidden" name="plan_id" value="0">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title"><?php __('sub_pay_choose'); ?></h4>
				</div>
				<div class="modal-body">
					<div class="sub-methods">
					<?php $first = true; foreach ($methods as $m => $name) { ?>
						<label class="sub-method">
							<input type="radio" name="method" value="<?php echo pjSanitize::html($m); ?>"<?php echo $first ? ' checked' : ''; ?>>
							<img src="<?php echo PJ_IMG_PATH; ?>backend/payments/<?php echo pjSanitize::html($m); ?>.png" alt="<?php echo pjSanitize::html($name); ?>">
							<span><?php echo pjSanitize::html($name); ?></span>
						</label>
					<?php $first = false; } ?>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-white" data-dismiss="modal"><?php __('btnCancel'); ?></button>
					<button type="submit" class="btn btn-primary"><?php __('sub_pay_btn'); ?></button>
				</div>
			</form>
		</div></div>
	</div>
	<?php } ?>

	<div class="ibox float-e-margins"><div class="ibox-content">
		<h4><?php __('sub_tab_billing'); ?></h4>
		<?php include dirname(__FILE__) . '/elements/transactions.php'; ?>
	</div></div>
</div>
