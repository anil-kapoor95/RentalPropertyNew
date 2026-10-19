<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = 'sub_manage_title';
$heading_desc = 'sub_manage_desc';
$heading_tabs = array('pjActionIndex' => 'sub_tab_companies', 'pjActionPlans' => 'sub_tab_plans', 'pjActionBilling' => 'sub_tab_billing', 'pjActionSettings' => 'sub_tab_settings');
$heading_active = 'pjActionIndex';
include dirname(__FILE__) . '/elements/heading.php';
$s = $tpl['state'];
$sub = $s['subscription'];
$action = $_SERVER['PHP_SELF'] . '?controller=pjAdminSubscriptions&amp;action=pjActionManage';
$hidden = $subCsrf . '<input type="hidden" name="company" value="' . $tpl['hash'] . '">';
$planOptions = function ($selected = 0, $empty = false) use ($tpl) {
	$h = $empty ? '<option value="0">&mdash;</option>' : '';
	foreach ($tpl['plans'] as $p)
	{
		$h .= '<option value="' . (int) $p['id'] . '"' . ((int) $p['id'] === (int) $selected ? ' selected' : '') . '>' . pjSanitize::html($p['name']) . ($p['status'] !== 'T' ? ' (' . __('sub_inactive', true) . ')' : '') . '</option>';
	}
	return $h;
};
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
	<div class="row">
		<div class="col-lg-5">
			<div class="ibox float-e-margins"><div class="ibox-content">
				<h3 class="m-t-none"><?php echo pjSanitize::html($tpl['company']['name']); ?></h3>
				<table class="table sub-kv">
					<tr><th><?php __('sub_col_plan'); ?></th><td><?php echo $s['plan'] ? pjSanitize::html($s['plan']['name']) : $subSource($s); ?></td></tr>
					<tr><th><?php __('sub_col_status'); ?></th><td><?php echo $sub ? $subStatus($s['status']) : '&mdash;'; ?><?php echo $sub && $sub['status'] !== $s['status'] ? ' <small class="text-muted">(' . __('sub_stored', true) . ': ' . __('sub_status_' . $sub['status'], true) . ')</small>' : ''; ?></td></tr>
					<?php if ($sub) { ?>
					<tr><th><?php __('sub_started'); ?></th><td><?php echo $subDate($sub['started_at']); ?></td></tr>
					<tr><th><?php __($sub['status'] === 'trial' ? 'sub_trial_ends' : 'sub_col_renewal'); ?></th><td><?php echo $subDate($sub['current_period_end']); ?></td></tr>
					<?php if ($s['grace_ends_at'] && $s['status'] === 'past_due') { ?><tr><th><?php __('sub_grace_until'); ?></th><td><?php echo $subDate($s['grace_ends_at']); ?></td></tr><?php } ?>
					<?php if (!empty($sub['pending_plan_id'])) { $pp = pjEntitlement::plan($sub['pending_plan_id']); ?><tr><th><?php __('sub_scheduled_change'); ?></th><td><?php echo $pp ? pjSanitize::html($pp['name']) : ''; ?></td></tr><?php } ?>
					<?php if ((int) $sub['cancel_at_period_end'] === 1) { ?><tr><th></th><td><?php __('sub_cancels_at_end'); ?></td></tr><?php } ?>
					<?php } ?>
					<tr><th><?php __('sub_col_properties'); ?></th><td><?php echo (int) $tpl['usage']['properties']; ?> / <?php echo $subLimit($s['limits']['max_properties']); ?></td></tr>
					<tr><th><?php __('sub_col_users'); ?></th><td><?php echo (int) $tpl['usage']['users']; ?> / <?php echo $subLimit($s['limits']['max_users']); ?></td></tr>
				</table>
				<p class="m-b-xs"><strong><?php __('sub_features'); ?></strong></p>
				<ul class="sub-features">
				<?php foreach (pjEntitlement::$features as $f => $lbl) { $on = in_array($f, $s['features'], true); ?>
					<li class="<?php echo $on ? 'on' : 'off'; ?>"><i class="fa <?php echo $on ? 'fa-check' : 'fa-times'; ?>"></i> <?php __($lbl); ?></li>
				<?php } ?>
				</ul>
			</div></div>
		</div>
		<div class="col-lg-7">
			<div class="ibox float-e-margins"><div class="ibox-content">
				<h4><?php __('sub_assign_title'); ?></h4>
				<p class="text-muted small"><?php __('sub_assign_hint'); ?></p>
				<form method="post" action="<?php echo $action; ?>" class="form-inline sub-form" data-sub-confirm="<?php __('sub_confirm', false, true); ?>">
					<?php echo $hidden; ?><input type="hidden" name="do" value="assign">
					<select name="plan_id" class="form-control"><?php echo $planOptions($sub ? $sub['plan_id'] : 0); ?></select>
					<select name="status" class="form-control"><option value="active"><?php __('sub_status_active'); ?></option><option value="trial"><?php __('sub_status_trial'); ?></option></select>
					<input type="date" name="period_end" class="form-control" title="<?php __('sub_period_end_hint', false, true); ?>">
					<input type="text" name="note" class="form-control" placeholder="<?php __('sub_note', false, true); ?>" maxlength="200">
					<button class="btn btn-primary" type="submit"><?php __('sub_btn_assign'); ?></button>
				</form>
				<?php if ($sub) { ?>
				<div class="hr-line-dashed"></div>
				<h4><?php __('sub_status_title'); ?></h4>
				<form method="post" action="<?php echo $action; ?>" class="form-inline sub-form" data-sub-confirm="<?php __('sub_confirm', false, true); ?>">
					<?php echo $hidden; ?><input type="hidden" name="do" value="status">
					<select name="status" class="form-control"><?php foreach (pjEntitlement::$statuses as $st) { ?><option value="<?php echo $st; ?>"<?php echo $sub['status'] === $st ? ' selected' : ''; ?>><?php __('sub_status_' . $st); ?></option><?php } ?></select>
					<input type="text" name="note" class="form-control" placeholder="<?php __('sub_note', false, true); ?>" maxlength="200">
					<button class="btn btn-default" type="submit"><?php __('sub_btn_save'); ?></button>
				</form>
				<div class="hr-line-dashed"></div>
				<h4><?php __('sub_extend_title'); ?></h4>
				<form method="post" action="<?php echo $action; ?>" class="form-inline sub-form">
					<?php echo $hidden; ?><input type="hidden" name="do" value="extend">
					<input type="date" name="period_end" class="form-control" required value="<?php echo !empty($sub['current_period_end']) ? date('Y-m-d', strtotime($sub['current_period_end'])) : ''; ?>">
					<input type="text" name="note" class="form-control" placeholder="<?php __('sub_note', false, true); ?>" maxlength="200">
					<button class="btn btn-default" type="submit"><?php __('sub_btn_save'); ?></button>
				</form>
				<div class="hr-line-dashed"></div>
				<h4><?php __('sub_schedule_title'); ?></h4>
				<form method="post" action="<?php echo $action; ?>" class="form-inline sub-form">
					<?php echo $hidden; ?><input type="hidden" name="do" value="schedule">
					<select name="plan_id" class="form-control"><?php echo $planOptions(!empty($sub['pending_plan_id']) ? $sub['pending_plan_id'] : 0, true); ?></select>
					<button class="btn btn-default" type="submit"><?php __('sub_btn_save'); ?></button>
				</form>
				<form method="post" action="<?php echo $action; ?>" class="m-t-sm">
					<?php echo $hidden; ?><input type="hidden" name="do" value="cancel_end"><input type="hidden" name="flag" value="<?php echo (int) $sub['cancel_at_period_end'] === 1 ? 0 : 1; ?>">
					<button class="btn btn-white btn-sm" type="submit"><?php __((int) $sub['cancel_at_period_end'] === 1 ? 'sub_btn_undo_cancel' : 'sub_btn_cancel_end'); ?></button>
				</form>
				<form method="post" action="<?php echo $action; ?>" class="m-t-sm" data-sub-confirm="<?php __('sub_confirm', false, true); ?>">
					<?php echo $hidden; ?><input type="hidden" name="do" value="remove">
					<button class="btn btn-white btn-sm text-danger" type="submit"><?php __('sub_btn_remove'); ?></button>
					<small class="text-muted m-l-sm"><?php __('sub_remove_hint'); ?></small>
				</form>
				<?php } ?>
				<?php
				$waiting = array();
				foreach ($tpl['transactions'] as $t) { if ($t['status'] === 'pending' && !pjSubscriptionBilling::isOnline($t['provider'])) { $waiting[] = $t; } }
				if (!empty($waiting)) { ?>
				<div class="hr-line-dashed"></div>
				<h4><?php __('sub_waiting_title'); ?></h4>
				<p class="text-muted small"><?php __('sub_waiting_hint'); ?></p>
				<?php foreach ($waiting as $t) { ?>
				<div class="sub-waiting">
					<div class="m-b-xs"><strong><?php echo pjSanitize::html((string) $t['plan_name']); ?></strong> &middot; <?php echo $subMoney($t['amount'], $t['currency']); ?>
						&middot; <?php echo pjSanitize::html(pjSubscriptionBilling::methodName($t['provider'])); ?>
						&middot; <?php __('sub_pay_reference'); ?> <code><?php echo pjSanitize::html(strtoupper(substr($t['uuid'], 0, 12))); ?></code>
						&middot; <span class="text-muted"><?php echo $subDate($t['created']); ?></span></div>
					<form method="post" action="<?php echo $action; ?>" class="form-inline sub-form sub-inline" data-sub-confirm="<?php __('sub_confirm', false, true); ?>">
						<?php echo $hidden; ?><input type="hidden" name="do" value="confirm_txn"><input type="hidden" name="txn" value="<?php echo pjSanitize::html($t['uuid']); ?>">
						<input type="text" name="reference" class="form-control" required maxlength="120" placeholder="<?php __('sub_reference', false, true); ?>">
						<button class="btn btn-primary btn-sm" type="submit"><?php __('sub_btn_confirm_paid'); ?></button>
					</form>
					<form method="post" action="<?php echo $action; ?>" class="sub-inline" data-sub-confirm="<?php __('sub_confirm', false, true); ?>">
						<?php echo $hidden; ?><input type="hidden" name="do" value="cancel_txn"><input type="hidden" name="txn" value="<?php echo pjSanitize::html($t['uuid']); ?>">
						<button class="btn btn-white btn-sm" type="submit"><?php __('sub_btn_cancel_txn'); ?></button>
					</form>
				</div>
				<?php } } ?>
				<div class="hr-line-dashed"></div>
				<h4><?php __('sub_payment_title'); ?></h4>
				<p class="text-muted small"><?php __('sub_payment_hint'); ?></p>
				<form method="post" action="<?php echo $action; ?>" class="form-inline sub-form" data-sub-confirm="<?php __('sub_confirm', false, true); ?>">
					<?php echo $hidden; ?><input type="hidden" name="do" value="payment">
					<select name="plan_id" class="form-control"><?php echo $planOptions($sub ? $sub['plan_id'] : 0); ?></select>
					<input type="number" name="amount" class="form-control" min="0.01" step="0.01" required placeholder="<?php __('sub_amount', false, true); ?>">
					<input type="text" name="reference" class="form-control" required maxlength="120" placeholder="<?php __('sub_reference', false, true); ?>">
					<button class="btn btn-primary" type="submit"><?php __('sub_btn_record'); ?></button>
				</form>
			</div></div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-7">
			<div class="ibox float-e-margins"><div class="ibox-content">
				<h4><?php __('sub_tab_billing'); ?></h4>
				<?php include dirname(__FILE__) . '/elements/transactions.php'; ?>
			</div></div>
		</div>
		<div class="col-lg-5">
			<div class="ibox float-e-margins"><div class="ibox-content">
				<h4><?php __('sub_history'); ?></h4>
				<ul class="sub-events">
				<?php foreach ($tpl['events'] as $e) { ?>
					<li><small class="text-muted"><?php echo pjSanitize::html(date($tpl['option_arr']['o_date_format'] . ' H:i', strtotime($e['created']))); ?></small> <?php echo pjSanitize::html($e['message']); ?></li>
				<?php } ?>
				<?php if (empty($tpl['events'])) { ?><li class="text-muted"><?php __('sub_nothing'); ?></li><?php } ?>
				</ul>
			</div></div>
		</div>
	</div>
</div>
