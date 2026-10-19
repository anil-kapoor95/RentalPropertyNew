<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = 'sub_menu';
$heading_desc = 'sub_settings_desc';
$heading_tabs = array('pjActionIndex' => 'sub_tab_companies', 'pjActionPlans' => 'sub_tab_plans', 'pjActionBilling' => 'sub_tab_billing', 'pjActionSettings' => 'sub_tab_settings');
include dirname(__FILE__) . '/elements/heading.php';
$s = $tpl['settings'];
$sel = function ($name, $options) use ($s) {
	$h = '<select class="form-control" name="' . $name . '">';
	foreach ($options as $val => $lbl)
	{
		$h .= '<option value="' . $val . '"' . ((string) $s[$name] === (string) $val ? ' selected' : '') . '>' . __($lbl, true) . '</option>';
	}
	return $h . '</select>';
};
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionSettings" class="ibox float-e-margins" autocomplete="off">
		<div class="ibox-content">
			<?php echo $subCsrf; ?>
			<h4><?php __('sub_rules'); ?></h4>
			<div class="row">
				<div class="col-md-3 form-group"><label><?php __('sub_currency'); ?></label><input class="form-control" name="currency" maxlength="3" value="<?php echo pjSanitize::html($s['currency']); ?>"></div>
				<div class="col-md-3 form-group"><label><?php __('sub_grace_days'); ?></label><input class="form-control" type="number" min="0" max="90" name="grace_days" value="<?php echo (int) $s['grace_days']; ?>"></div>
				<div class="col-md-3 form-group"><label><?php __('sub_unassigned_mode'); ?></label><?php echo $sel('unassigned_mode', array('full' => 'sub_unassigned_full', 'restricted' => 'sub_unassigned_restricted')); ?></div>
				<div class="col-md-3 form-group"><label><?php __('sub_expired_mode'); ?></label><?php echo $sel('expired_mode', array('read_only' => 'sub_expired_read_only', 'suspend' => 'sub_expired_suspend')); ?></div>
				<div class="col-md-3 form-group"><label><?php __('sub_self_trial'); ?></label><?php echo $sel('self_trial', array('0' => 'sub_no', '1' => 'sub_yes')); ?></div>
			</div>
			<p class="text-muted small"><?php __('sub_rules_hint'); ?></p>
			<div class="hr-line-dashed"></div>
			<h4><?php __('sub_provider_title'); ?></h4>
			<p class="m-b-xs"><?php __('sub_gw_settings_hint'); ?>
				<a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionGateways"><?php __('sub_gw_title'); ?> <i class="fa fa-arrow-right"></i></a></p>
			<p class="text-muted small"><?php echo !empty($tpl['methods']) ? pjSanitize::html(implode(', ', $tpl['methods'])) : __('sub_gw_none', true); ?></p>
			<div class="hr-line-dashed"></div>
			<button class="btn btn-primary btn-lg" type="submit"><?php __('sub_btn_save'); ?></button>
		</div>
	</form>
</div>
