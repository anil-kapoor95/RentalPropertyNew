<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = $tpl['plan'] ? 'sub_plan_edit' : 'sub_plan_add';
$heading_desc = 'sub_plan_desc';
$heading_tabs = array('pjActionIndex' => 'sub_tab_companies', 'pjActionPlans' => 'sub_tab_plans', 'pjActionBilling' => 'sub_tab_billing', 'pjActionSettings' => 'sub_tab_settings');
$heading_active = 'pjActionPlans';
include dirname(__FILE__) . '/elements/heading.php';
$p = $tpl['plan'] ? $tpl['plan'] : array('id' => 0, 'code' => '', 'name' => '', 'description' => '', 'price' => null, 'currency' => '', 'billing_interval' => 'month',
	'interval_count' => 1, 'trial_days' => 0, 'max_properties' => null, 'max_users' => null, 'max_payment_gateways' => null, 'ai_monthly_messages' => null,
	'feature_list' => pjEntitlement::$baseFeatures, 'is_public' => 1, 'status' => 'T', 'sort' => 0);
$v = function ($k) use ($p) { return $p[$k] === null ? '' : pjSanitize::html((string) $p[$k]); };
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionPlan" class="ibox float-e-margins">
		<div class="ibox-content">
			<?php echo $subCsrf; ?><input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>">
			<div class="row">
				<div class="col-md-4 form-group"><label><?php __('sub_plan_name'); ?> *</label><input class="form-control required" name="name" maxlength="100" required value="<?php echo $v('name'); ?>"></div>
				<div class="col-md-2 form-group"><label><?php __('sub_plan_code'); ?> *</label><input class="form-control required" name="code" maxlength="32" required pattern="[a-z0-9_\-]{2,32}" value="<?php echo $v('code'); ?>"></div>
				<div class="col-md-2 form-group"><label><?php __('sub_col_price'); ?></label><input class="form-control" name="price" type="number" min="0" step="0.01" value="<?php echo $v('price'); ?>" placeholder="<?php __('sub_price_not_set', false, true); ?>"></div>
				<div class="col-md-1 form-group"><label><?php __('sub_currency'); ?></label><input class="form-control" name="currency" maxlength="3" value="<?php echo $tpl['plan'] && $tpl['plan']['currency'] ? pjSanitize::html($tpl['plan']['currency']) : ''; ?>" placeholder="<?php echo pjSanitize::html(pjEntitlement::setting('currency')); ?>"></div>
				<div class="col-md-2 form-group"><label><?php __('sub_billing_interval'); ?></label>
					<div class="input-group"><input class="form-control" name="interval_count" type="number" min="1" max="12" value="<?php echo (int) $p['interval_count']; ?>" style="max-width:70px">
					<select class="form-control" name="billing_interval"><option value="month"<?php echo $p['billing_interval'] === 'month' ? ' selected' : ''; ?>><?php __('sub_month'); ?></option><option value="year"<?php echo $p['billing_interval'] === 'year' ? ' selected' : ''; ?>><?php __('sub_year'); ?></option></select></div></div>
				<div class="col-md-1 form-group"><label><?php __('sub_sort'); ?></label><input class="form-control" name="sort" type="number" min="0" value="<?php echo (int) $p['sort']; ?>"></div>
			</div>
			<div class="form-group"><label><?php __('sub_description'); ?></label><textarea class="form-control" name="description" rows="2"><?php echo $v('description'); ?></textarea></div>
			<p class="text-muted small"><?php __('sub_price_hint'); ?></p>
			<div class="hr-line-dashed"></div>
			<h4><?php __('sub_limits'); ?></h4>
			<p class="text-muted small"><?php __('sub_limits_hint'); ?></p>
			<div class="row">
				<div class="col-md-2 form-group"><label><?php __('sub_trial_days'); ?></label><input class="form-control" name="trial_days" type="number" min="0" max="365" value="<?php echo (int) $p['trial_days']; ?>"></div>
				<?php foreach (array('max_properties', 'max_users', 'max_payment_gateways', 'ai_monthly_messages') as $k) { ?>
				<div class="col-md-2 form-group"><label><?php __('sub_' . $k); ?></label><input class="form-control" name="<?php echo $k; ?>" type="number" min="0" value="<?php echo $v($k); ?>" placeholder="<?php __('sub_unlimited', false, true); ?>"></div>
				<?php } ?>
			</div>
			<div class="hr-line-dashed"></div>
			<h4><?php __('sub_features'); ?></h4>
			<div class="row sub-feature-grid">
				<?php foreach (pjEntitlement::$features as $f => $lbl) { ?>
				<div class="col-md-4 col-sm-6"><label class="sub-check"><input type="checkbox" name="features[]" value="<?php echo $f; ?>"<?php echo in_array($f, $p['feature_list'], true) ? ' checked' : ''; ?>> <?php __($lbl); ?></label></div>
				<?php } ?>
			</div>
			<p class="text-muted small m-t-sm"><?php __('sub_features_hint'); ?></p>
			<div class="hr-line-dashed"></div>
			<label class="sub-check m-r-lg"><input type="checkbox" name="status" value="T"<?php echo $p['status'] === 'T' ? ' checked' : ''; ?>> <?php __('sub_active_plan'); ?></label>
			<label class="sub-check"><input type="checkbox" name="is_public" value="1"<?php echo (int) $p['is_public'] === 1 ? ' checked' : ''; ?>> <?php __('sub_public'); ?></label>
			<div class="hr-line-dashed"></div>
			<button class="btn btn-primary btn-lg" type="submit"><?php __('sub_btn_save'); ?></button>
			<a class="btn btn-white btn-lg pull-right" href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionPlans"><?php __('sub_btn_cancel'); ?></a>
		</div>
	</form>
	<?php if ($tpl['plan']) { ?>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionPlan" data-sub-confirm="<?php __('sub_confirm', false, true); ?>">
		<?php echo $subCsrf; ?><input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>"><input type="hidden" name="do" value="delete">
		<button class="btn btn-white btn-sm text-danger" type="submit"><i class="fa fa-trash m-r-xs"></i><?php __('sub_btn_delete_plan'); ?></button>
		<small class="text-muted m-l-sm"><?php __('sub_delete_plan_hint'); ?></small>
	</form>
	<?php } ?>
</div>
