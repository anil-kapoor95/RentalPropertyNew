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
				<div class="m-b-sm m-t-sm pj-company-logo-box" style="position: relative; display: inline-block; min-width: 60px; min-height: 40px;">
					<img src="<?php echo PJ_INSTALL_URL . pjSanitize::html($arr['logo']); ?>" alt="" class="pj-company-logo" style="max-width: 160px; max-height: 90px; border: 1px solid #e7eaec; padding: 4px; background: #fff;">
					<a href="#" class="btn btn-danger btn-xs pj-company-logo-delete" data-id="<?php echo pjCompanyContext::hashOf($arr['id']); ?>" title="<?php __('lblCompanyDeleteLogo', false, true); ?>" style="position: absolute; top: -8px; right: -8px; border-radius: 50%; width: 26px; height: 26px; padding: 3px 0; text-align: center;"><i class="fa fa-trash"></i></a>
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

	<?php if (($is_admin_form && in_array($form_flag, array('company_create', 'company_update'), true)) || $form_flag === 'company_profile') {
		// Add company: password required. Edit company / My company: optional, only to change it.
		$pw_new = $form_flag === 'company_create';
		$pw_req = $pw_new ? ' required' : '';
		$pw_own = $form_flag === 'company_profile'; // the company changes its own password: current password needed
		$pw_col = $pw_own ? 'col-lg-3 col-md-6 col-sm-6' : 'col-lg-4 col-md-6 col-sm-6';
	?>
	<div class="hr-line-dashed"></div>

	<!-- Login of the company (a user with the Company role is created with the company) -->
	<h3 class="m-b-md"><?php __($pw_own ? 'lblCompanySectionPassword' : 'lblCompanySectionLogin'); ?></h3>
	<div class="row">
		<?php if ($pw_own) { ?>
		<div class="<?php echo $pw_col; ?>">
			<div class="form-group">
				<label class="control-label" for="login_password_current"><?php __('lblCompanyLoginCurrentPassword'); ?></label>
				<div class="input-group">
					<input type="password" name="login_password_current" id="login_password_current" class="form-control" autocomplete="current-password" data-msg-required="<?php __('pj_field_required', false, true); ?>">
					<span class="input-group-btn"><button type="button" class="btn btn-default pj-toggle-password" data-target="#login_password_current" tabindex="-1"><i class="fa fa-eye"></i></button></span>
				</div>
			</div>
		</div>
		<?php } ?>
		<div class="<?php echo $pw_col; ?>">
			<div class="form-group">
				<label class="control-label" for="login_password"><?php __($pw_new ? 'lblCompanyLoginPassword' : 'lblCompanyLoginNewPassword'); echo $pw_new ? ' *' : ''; ?></label>
				<div class="input-group">
					<input type="password" name="login_password" id="login_password" class="form-control<?php echo $pw_req; ?>" autocomplete="new-password" data-msg-required="<?php __('pj_field_required', false, true); ?>">
					<span class="input-group-btn"><button type="button" class="btn btn-default pj-toggle-password" data-target="#login_password" tabindex="-1"><i class="fa fa-eye"></i></button></span>
				</div>
			</div>
		</div>
		<div class="<?php echo $pw_col; ?>">
			<div class="form-group">
				<label class="control-label" for="login_password_confirm"><?php __($pw_new ? 'lblCompanyLoginPasswordConfirm' : 'lblCompanyLoginNewPasswordConfirm'); echo $pw_new ? ' *' : ''; ?></label>
				<div class="input-group">
					<input type="password" name="login_password_confirm" id="login_password_confirm" class="form-control<?php echo $pw_req; ?>" autocomplete="new-password" data-msg-required="<?php __('pj_field_required', false, true); ?>" data-msg-equal-to="<?php __('msgCompanyPasswordMismatch', false, true); ?>">
					<span class="input-group-btn"><button type="button" class="btn btn-default pj-toggle-password" data-target="#login_password_confirm" tabindex="-1"><i class="fa fa-eye"></i></button></span>
				</div>
			</div>
		</div>
		<div class="<?php echo $pw_own ? 'col-lg-3' : 'col-lg-4'; ?> col-md-12">
			<p class="help-block m-t-lg"><small><?php __($pw_new ? 'lblCompanyLoginNote' : ($pw_own ? 'lblCompanyLoginNoteOwn' : 'lblCompanyLoginNoteEdit')); ?></small></p>
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
