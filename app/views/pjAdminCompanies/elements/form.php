<?php
/**
 * Shared company form.
 * Expects: $form_action, $form_flag (company_create|company_update|company_profile),
 *          $is_admin_form (bool), $cancel_url (string|null), $tpl['arr'] (optional).
 */
$arr = isset($tpl['arr']) && is_array($tpl['arr']) ? $tpl['arr'] : array();
$v = function ($key) use ($arr) {
	return isset($arr[$key]) ? pjSanitize::html($arr[$key]) : '';
};
$status_on = !isset($arr['status']) || $arr['status'] == 'T';
?>
<form action="<?php echo $form_action; ?>" method="post" id="frmCompany" class="form pj-form" enctype="multipart/form-data" autocomplete="off">
	<input type="hidden" name="<?php echo $form_flag; ?>" value="1" />
	<input type="hidden" name="csrf_token" value="<?php echo pjAppController::getCsrfToken(); ?>" />
	<?php if (!empty($arr['id'])) { ?>
	<input type="hidden" name="id" value="<?php echo pjCompanyContext::hashOf($arr['id']); ?>" />
	<?php } ?>

	<!-- Company details -->
	<h3 class="m-b-md"><?php __('lblCompanySectionGeneral'); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-6 col-sm-6">
			<div class="form-group">
				<label class="control-label" for="company_name"><?php __('lblCompanyName'); ?> *</label>
				<input type="text" name="name" id="company_name" class="form-control required" maxlength="255" value="<?php echo $v('name'); ?>" data-msg-required="<?php __('pj_field_required', false, true); ?>">
			</div>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-6">
			<div class="form-group">
				<label class="control-label" for="legal_name"><?php __('lblCompanyLegalName'); ?></label>
				<input type="text" name="legal_name" id="legal_name" class="form-control" maxlength="255" value="<?php echo $v('legal_name'); ?>">
			</div>
		</div>
		<div class="col-lg-2 col-md-6 col-sm-6">
			<div class="form-group">
				<label class="control-label" for="registration_no"><?php __('lblCompanyRegNo'); ?></label>
				<input type="text" name="registration_no" id="registration_no" class="form-control" maxlength="100" value="<?php echo $v('registration_no'); ?>">
			</div>
		</div>
		<div class="col-lg-2 col-md-6 col-sm-6">
			<div class="form-group">
				<label class="control-label" for="tax_no"><?php __('lblCompanyTaxNo'); ?></label>
				<input type="text" name="tax_no" id="tax_no" class="form-control" maxlength="100" value="<?php echo $v('tax_no'); ?>">
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-8 col-md-12">
			<div class="form-group">
				<label class="control-label" for="description"><?php __('lblCompanyDescription'); ?></label>
				<textarea name="description" id="description" rows="5" class="form-control"><?php echo $v('description'); ?></textarea>
			</div>
		</div>
		<div class="col-lg-4 col-md-12">
			<div class="form-group">
				<label class="control-label" for="logo"><?php __('lblCompanyLogo'); ?></label>
				<?php if (!empty($arr['logo']) && is_file($arr['logo'])) { ?>
				<div class="m-b-sm">
					<img src="<?php echo PJ_INSTALL_URL . pjSanitize::html($arr['logo']); ?>" alt="" class="pj-company-logo" style="max-width: 160px; max-height: 90px; border: 1px solid #e7eaec; padding: 4px; background: #fff;">
				</div>
				<div class="checkbox m-t-none m-b-sm">
					<label><input type="checkbox" name="remove_logo" value="1"> <?php __('lblCompanyRemoveLogo'); ?></label>
				</div>
				<?php } ?>
				<input type="file" name="logo" id="logo" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
				<p class="help-block m-b-none"><small><?php __('lblCompanyLogoHint'); ?></small></p>
			</div>
		</div>
	</div>

	<div class="hr-line-dashed"></div>

	<!-- Contact -->
	<h3 class="m-b-md"><?php __('lblCompanySectionContact'); ?></h3>
	<div class="row">
		<div class="col-lg-3 col-md-6 col-sm-6">
			<div class="form-group">
				<label class="control-label" for="contact_person"><?php __('lblCompanyContact'); ?> *</label>
				<input type="text" name="contact_person" id="contact_person" class="form-control required" maxlength="255" value="<?php echo $v('contact_person'); ?>" data-msg-required="<?php __('pj_field_required', false, true); ?>">
			</div>
		</div>
		<div class="col-lg-3 col-md-6 col-sm-6">
			<div class="form-group">
				<label class="control-label" for="company_email"><?php __('lblCompanyEmail'); ?> *</label>
				<input type="email" name="email" id="company_email" class="form-control required email" maxlength="255" value="<?php echo $v('email'); ?>" data-msg-required="<?php __('pj_field_required', false, true); ?>" data-msg-email="<?php __('plugin_base_email_invalid', false, true); ?>">
			</div>
		</div>
		<div class="col-lg-3 col-md-6 col-sm-6">
			<div class="form-group">
				<label class="control-label" for="company_phone"><?php __('lblCompanyPhone'); ?> *</label>
				<input type="text" name="phone" id="company_phone" class="form-control required" maxlength="50" value="<?php echo $v('phone'); ?>" data-msg-required="<?php __('pj_field_required', false, true); ?>">
			</div>
		</div>
		<div class="col-lg-3 col-md-6 col-sm-6">
			<div class="form-group">
				<label class="control-label" for="website"><?php __('lblCompanyWebsite'); ?></label>
				<input type="text" name="website" id="website" class="form-control" maxlength="255" placeholder="https://" value="<?php echo $v('website'); ?>">
			</div>
		</div>
	</div>

	<div class="hr-line-dashed"></div>

	<!-- Address -->
	<h3 class="m-b-md"><?php __('lblCompanySectionAddress'); ?></h3>
	<div class="row">
		<div class="col-lg-6 col-md-6">
			<div class="form-group">
				<label class="control-label" for="address_1"><?php __('lblCompanyAddress1'); ?> *</label>
				<input type="text" name="address_1" id="address_1" class="form-control required" maxlength="255" value="<?php echo $v('address_1'); ?>" data-msg-required="<?php __('pj_field_required', false, true); ?>">
			</div>
		</div>
		<div class="col-lg-6 col-md-6">
			<div class="form-group">
				<label class="control-label" for="address_2"><?php __('lblCompanyAddress2'); ?></label>
				<input type="text" name="address_2" id="address_2" class="form-control" maxlength="255" value="<?php echo $v('address_2'); ?>">
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-3 col-md-6 col-sm-6">
			<div class="form-group">
				<label class="control-label" for="city"><?php __('lblCompanyCity'); ?> *</label>
				<input type="text" name="city" id="city" class="form-control required" maxlength="100" value="<?php echo $v('city'); ?>" data-msg-required="<?php __('pj_field_required', false, true); ?>">
			</div>
		</div>
		<div class="col-lg-3 col-md-6 col-sm-6">
			<div class="form-group">
				<label class="control-label" for="state"><?php __('lblCompanyState'); ?></label>
				<input type="text" name="state" id="state" class="form-control" maxlength="100" value="<?php echo $v('state'); ?>">
			</div>
		</div>
		<div class="col-lg-2 col-md-6 col-sm-6">
			<div class="form-group">
				<label class="control-label" for="zip"><?php __('lblCompanyZip'); ?></label>
				<input type="text" name="zip" id="zip" class="form-control" maxlength="20" value="<?php echo $v('zip'); ?>">
			</div>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-6">
			<div class="form-group">
				<label class="control-label" for="country_id"><?php __('lblCompanyCountry'); ?> *</label>
				<select name="country_id" id="country_id" class="form-control select-item required" data-msg-required="<?php __('pj_field_required', false, true); ?>" data-placeholder="-- <?php __('lblChoose'); ?> --">
					<option value="">-- <?php __('lblChoose'); ?> --</option>
					<?php foreach ($tpl['country_arr'] as $c) { ?>
					<option value="<?php echo (int) $c['id']; ?>"<?php echo isset($arr['country_id']) && (int) $arr['country_id'] === (int) $c['id'] ? ' selected="selected"' : ''; ?>><?php echo pjSanitize::html($c['name']); ?></option>
					<?php } ?>
				</select>
			</div>
		</div>
	</div>

	<?php if ($is_admin_form) { ?>
	<div class="hr-line-dashed"></div>

	<!-- Administration (admin only) -->
	<h3 class="m-b-md"><?php __('lblCompanySectionAdmin'); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-6 col-sm-6">
			<div class="form-group">
				<label class="control-label" for="user_id"><?php __('lblCompanyUser'); ?></label>
				<select name="user_id" id="user_id" class="form-control select-item" data-placeholder="<?php __('lblCompanyNoUser', false, true); ?>">
					<option value=""><?php __('lblCompanyNoUser'); ?></option>
					<?php foreach ($tpl['user_arr'] as $u) { ?>
					<option value="<?php echo (int) $u['id']; ?>"<?php echo isset($arr['user_id']) && (int) $arr['user_id'] === (int) $u['id'] ? ' selected="selected"' : ''; ?>><?php echo pjSanitize::html($u['name'] . ' (' . $u['email'] . ')'); ?></option>
					<?php } ?>
				</select>
				<p class="help-block m-b-none"><small><?php __('lblCompanyUserNote'); ?></small></p>
			</div>
		</div>
		<div class="col-lg-2 col-md-3 col-sm-3">
			<div class="form-group">
				<label class="control-label" for="commission"><?php __('lblCompanyCommission'); ?></label>
				<input type="number" name="commission" id="commission" class="form-control" min="0" max="100" step="0.01" value="<?php echo isset($arr['commission']) ? (float) $arr['commission'] : '0'; ?>">
			</div>
		</div>
		<div class="col-lg-2 col-md-3 col-sm-3">
			<div class="form-group">
				<label class="control-label"><?php __('lblCompanyStatus'); ?></label>
				<div class="clearfix">
					<div class="switch onoffswitch-data pull-left">
						<div class="onoffswitch">
							<input class="onoffswitch-checkbox" id="status" name="status" type="checkbox"<?php echo $status_on ? ' checked="checked"' : ''; ?>>
							<label class="onoffswitch-label" for="status">
								<span class="onoffswitch-inner" data-on="<?php __('filter_ARRAY_active'); ?>" data-off="<?php __('filter_ARRAY_inactive'); ?>"></span>
								<span class="onoffswitch-switch"></span>
							</label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-4 col-md-12">
			<div class="form-group">
				<label class="control-label" for="notes"><?php __('lblCompanyNotes'); ?></label>
				<textarea name="notes" id="notes" rows="3" class="form-control"><?php echo $v('notes'); ?></textarea>
			</div>
		</div>
	</div>
	<?php } ?>

	<div class="hr-line-dashed"></div>

	<div class="clearfix">
		<button type="submit" class="ladda-button btn btn-primary btn-lg btn-phpjabbers-loader pull-left" data-style="zoom-in" style="margin-right: 15px;">
			<span class="ladda-label"><?php __('btnSave'); ?></span>
			<?php include $controller->getConstant('pjBase', 'PLUGIN_VIEWS_PATH') . 'pjLayouts/elements/button-animation.php'; ?>
		</button>
		<?php if (!empty($cancel_url)) { ?>
		<a class="btn btn-white btn-lg pull-right" href="<?php echo $cancel_url; ?>"><?php __('btnCancel'); ?></a>
		<?php } ?>
	</div>
</form>
