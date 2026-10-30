<?php
include dirname(__FILE__) . '/elements/common.php';
$kind = $tpl['kind'];
$heading_title = $tpl['promo'] ? ($kind === 'coupon' ? 'sub_promo_edit_coupon' : 'sub_promo_edit_discount') : ($kind === 'coupon' ? 'sub_btn_add_coupon' : 'sub_btn_add_discount');
$heading_desc = 'sub_promo_form_desc';
include dirname(__FILE__) . '/elements/heading.php';
$p = $tpl['promo'] ? $tpl['promo'] : array('id' => 0, 'name' => '', 'code' => '', 'type' => 'percent', 'value' => '', 'currency' => '',
	'plan_ids' => '', 'kinds' => '', 'date_from' => null, 'date_to' => null, 'max_uses' => null, 'max_per_company' => null, 'status' => 'T');
$v = function ($k) use ($p) { return $p[$k] === null ? '' : pjSanitize::html((string) $p[$k]); };
$sel_plans = pjSubscriptionPromos::listOf($p['plan_ids']);
$sel_kinds = pjSubscriptionPromos::listOf($p['kinds']);
$url = $_SERVER['PHP_SELF'] . '?controller=pjAdminSubscriptions&amp;action=';
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
	<form method="post" action="<?php echo $url; ?>pjActionPromo" class="ibox float-e-margins" id="frmSubPromo">
		<div class="ibox-content sub-promo-form">
			<?php echo $subCsrf; ?><input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>"><input type="hidden" name="kind" value="<?php echo $kind; ?>">
			<div class="row">
				<div class="col-md-4 form-group"><label><?php __('sub_promo_name'); ?> *</label><input class="form-control" name="name" maxlength="100" required value="<?php echo $v('name'); ?>"></div>
				<?php if ($kind === 'coupon') { ?>
				<div class="col-md-3 form-group"><label><?php __('sub_promo_code'); ?> *</label><input class="form-control text-uppercase" name="code" maxlength="30" required pattern="[A-Za-z0-9_\-]{3,30}" value="<?php echo $v('code'); ?>">
					<small class="text-muted"><?php __('sub_promo_code_hint'); ?></small></div>
				<?php } ?>
				<div class="col-md-2 form-group"><label><?php __('sub_promo_value'); ?> *</label><input class="form-control" name="value" type="number" min="0.01" step="0.01" required value="<?php echo $v('value'); ?>"></div>
				<div class="col-md-2 form-group"><label><?php __('sub_promo_type'); ?></label>
					<select class="form-control" name="type" id="subPromoType">
						<option value="percent"<?php echo $p['type'] === 'percent' ? ' selected' : ''; ?>><?php __('sub_promo_type_percent'); ?></option>
						<option value="amount"<?php echo $p['type'] === 'amount' ? ' selected' : ''; ?>><?php __('sub_promo_type_amount'); ?></option>
					</select></div>
				<div class="col-md-1 form-group subPromoCurrency"><label><?php __('sub_currency'); ?></label><input class="form-control text-uppercase" name="currency" maxlength="3" value="<?php echo $v('currency'); ?>" placeholder="<?php echo pjSanitize::html((string) pjEntitlement::setting('currency')); ?>"></div>
			</div>
			<p class="text-muted small subPromoCurrency"><?php __('sub_promo_currency_hint'); ?></p>
			<div class="hr-line-dashed"></div>
			<div class="row">
				<div class="col-md-6 form-group"><label><?php __('sub_promo_plans'); ?></label>
					<div>
					<?php foreach ($tpl['plans'] as $pl) { ?>
						<label class="sub-chip"><input type="checkbox" name="plan_ids[]" value="<?php echo (int) $pl['id']; ?>"<?php echo in_array((string) (int) $pl['id'], $sel_plans, true) ? ' checked' : ''; ?>> <?php echo pjSanitize::html($pl['name']); ?></label>
					<?php } ?>
					</div>
				</div>
				<div class="col-md-6 form-group"><label><?php __('sub_promo_kinds'); ?></label>
					<div>
					<?php foreach (pjSubscriptionPromos::$kinds as $k) { ?>
						<label class="sub-chip"><input type="checkbox" name="kinds[]" value="<?php echo $k; ?>"<?php echo in_array($k, $sel_kinds, true) ? ' checked' : ''; ?>> <?php __('sub_kind_' . $k); ?></label>
					<?php } ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-3 form-group"><label><?php __('sub_promo_valid_from'); ?></label><input class="form-control" type="date" name="date_from" value="<?php echo $v('date_from'); ?>"></div>
				<div class="col-md-3 form-group"><label><?php __('sub_promo_valid_to'); ?></label><input class="form-control" type="date" name="date_to" value="<?php echo $v('date_to'); ?>"></div>
				<div class="col-md-3 form-group"><label><?php __('sub_promo_max_uses'); ?></label><input class="form-control" type="number" min="0" name="max_uses" value="<?php echo $v('max_uses'); ?>" placeholder="<?php __('sub_unlimited', false, true); ?>"></div>
				<div class="col-md-3 form-group"><label><?php __('sub_promo_max_per_company'); ?></label><input class="form-control" type="number" min="0" name="max_per_company" value="<?php echo $v('max_per_company'); ?>" placeholder="<?php __('sub_unlimited', false, true); ?>"></div>
			</div>
			<div class="hr-line-dashed"></div>
			<label class="sub-chip"><input type="checkbox" name="status" value="T"<?php echo $p['status'] === 'T' ? ' checked' : ''; ?>> <?php __('sub_promo_active'); ?></label>
			<div class="hr-line-dashed"></div>
			<button class="btn btn-primary btn-lg" type="submit"><?php __('sub_btn_save'); ?></button>
			<a class="btn btn-white btn-lg pull-right" href="<?php echo $url; ?>pjActionPromos&amp;kind=<?php echo $kind; ?>"><?php __('sub_btn_cancel'); ?></a>
		</div>
	</form>
	<?php if ($tpl['promo']) { ?>
	<form method="post" action="<?php echo $url; ?>pjActionPromo" data-sub-confirm="<?php __('sub_confirm', false, true); ?>">
		<?php echo $subCsrf; ?><input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>"><input type="hidden" name="do" value="delete">
		<button class="btn btn-white btn-sm text-danger" type="submit"><i class="fa fa-trash m-r-xs"></i><?php __('sub_btn_delete_promo'); ?></button>
	</form>
	<?php } ?>
</div>
