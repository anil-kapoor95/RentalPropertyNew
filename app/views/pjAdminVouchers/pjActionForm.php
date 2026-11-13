<?php
/**
 * Add / edit a coupon of the active company (own page).
 * Same layout as the Super Admin's coupon form (views/pjAdminSubscriptions/pjActionPromo.php).
 */
include PJ_VIEWS_PATH . 'pjLayouts/elements/coupons-helpers.php';
$arr = $tpl['arr'];
$is_edit = !empty($arr);
$p = $is_edit ? $arr : array('id' => 0, 'code' => '', 'discount' => '', 'type' => 'percent', 'condition' => 'period', 'apply_on' => 'room',
	'used_count' => 1, 'date_from' => date('Y-m-d'), 'date_to' => date('Y-m-d', strtotime('+7 days')));
$v = function ($k) use ($p) { return $p[$k] === null ? '' : pjSanitize::html((string) $p[$k]); };
$sel = array_map('intval', (array) $tpl['calendar_id_arr']);
$url = $_SERVER['PHP_SELF'] . '?controller=pjAdminVouchers&amp;action=';
$discount_value = $is_edit ? rtrim(rtrim((string) $p['discount'], '0'), '.') : '';
?>
<div class="row wrapper border-bottom white-bg page-heading">
	<div class="col-sm-12">
		<h2><?php __($is_edit ? 'sub_promo_edit_coupon' : 'sub_btn_add_coupon'); ?></h2>
		<p class="m-b-none"><i class="fa fa-info-circle"></i> <?php __('cd_form_desc'); ?></p>
	</div>
</div>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include PJ_VIEWS_PATH . 'pjLayouts/elements/coupons-messages.php'; ?>
	<form method="post" action="<?php echo $url . ($is_edit ? 'pjActionUpdate' : 'pjActionCreate'); ?>" class="ibox float-e-margins" id="frmCdCoupon">
		<div class="ibox-content sub-promo-form">
			<?php echo $cdCsrf; ?><input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>">
			<div class="row">
				<div class="col-md-4 form-group"><label><?php __('sub_promo_code'); ?> *</label><input class="form-control" name="code" maxlength="100" required value="<?php echo $v('code'); ?>" autocomplete="off">
					<small class="text-muted"><?php __('cd_code_hint'); ?></small></div>
				<div class="col-md-2 form-group"><label><?php __('sub_promo_value'); ?> *</label><input class="form-control" name="discount" type="number" min="0.01" step="0.01" required value="<?php echo pjSanitize::html($discount_value); ?>"></div>
				<div class="col-md-2 form-group"><label><?php __('sub_promo_type'); ?></label>
					<select class="form-control cdType" name="type" data-currency="<?php echo pjSanitize::html($cdCurrency); ?>">
						<?php foreach ($cdTypes as $k => $label) { ?><option value="<?php echo pjSanitize::html($k); ?>"<?php echo $p['type'] === $k ? ' selected' : ''; ?>><?php echo pjSanitize::html($label); ?></option><?php } ?>
					</select></div>
				<div class="col-md-4 form-group"><label><?php __('cd_apply_on'); ?></label>
					<select class="form-control" name="apply_on">
						<?php foreach (pjUtil::sortArrayByArray($cdApplyOn, array('room', 'extra', 'both')) as $k => $label) { ?><option value="<?php echo pjSanitize::html($k); ?>"<?php echo $p['apply_on'] === $k ? ' selected' : ''; ?>><?php echo pjSanitize::html($label); ?></option><?php } ?>
					</select></div>
			</div>
			<div class="hr-line-dashed"></div>
			<div class="row">
				<div class="col-md-6 form-group"><label><?php __('cd_col_properties'); ?> *</label>
					<div>
					<?php foreach ($tpl['calendar_names'] as $cal_id => $cal_name) { ?>
						<label class="sub-chip"><input type="checkbox" name="calendar_id[]" value="<?php echo (int) $cal_id; ?>"<?php echo in_array((int) $cal_id, $sel, true) || (!$is_edit && count($tpl['calendar_names']) === 1) ? ' checked' : ''; ?>> <?php echo pjSanitize::html((string) $cal_name); ?></label>
					<?php } ?>
					</div>
				</div>
				<div class="col-md-6 form-group"><label><?php __('cd_condition'); ?></label>
					<div>
					<?php foreach (pjUtil::sortArrayByArray($cdConditions, array('period', 'made', 'both')) as $k => $label) { ?>
						<label class="sub-chip"><input type="radio" name="condition" value="<?php echo pjSanitize::html($k); ?>"<?php echo $p['condition'] === $k ? ' checked' : ''; ?>> <?php echo pjSanitize::html($label); ?></label>
					<?php } ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-3 form-group"><label><?php __('sub_promo_valid_from'); ?> *</label><input class="form-control" type="date" name="date_from" required value="<?php echo $v('date_from'); ?>"></div>
				<div class="col-md-3 form-group"><label><?php __('sub_promo_valid_to'); ?> *</label><input class="form-control" type="date" name="date_to" required value="<?php echo $v('date_to'); ?>"></div>
				<div class="col-md-3 form-group"><label><?php __('sub_promo_max_uses'); ?></label><input class="form-control" type="number" min="1" name="used_count" value="<?php echo (int) $p['used_count'] > 0 ? (int) $p['used_count'] : 1; ?>">
					<small class="text-muted"><?php __('cd_uses_hint'); ?></small></div>
			</div>
			<div class="hr-line-dashed"></div>
			<button class="btn btn-primary btn-lg" type="submit"><?php __('sub_btn_save'); ?></button>
			<a class="btn btn-white btn-lg pull-right" href="<?php echo $url; ?>pjActionIndex"><?php __('sub_btn_cancel'); ?></a>
		</div>
	</form>
	<?php if ($is_edit && !empty($tpl['has_delete'])) { ?>
	<form method="post" action="<?php echo $url; ?>pjActionUpdate" data-sub-confirm="<?php __('sub_confirm', false, true); ?>">
		<?php echo $cdCsrf; ?><input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>"><input type="hidden" name="do" value="delete">
		<button class="btn btn-white btn-sm text-danger" type="submit"><i class="fa fa-trash m-r-xs"></i><?php __('cd_btn_delete_coupon'); ?></button>
	</form>
	<?php } ?>
</div>
