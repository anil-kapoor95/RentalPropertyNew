<?php
/**
 * "Company" field for the add / edit user forms (administrators only).
 * Expects $user_company_id (int|null) - the user's current company.
 */
if (class_exists('pjCompanyContext') && pjCompanyContext::isAdmin($controller->getRoleId(), $controller->getUserId()))
{
	$ucList = pjCompanyContext::allowedCompanies($controller->getRoleId(), $controller->getUserId());
	$ucSel = isset($user_company_id) && (int) $user_company_id > 0 ? (int) $user_company_id : (int) pjCompanyContext::activeId($controller->getRoleId(), $controller->getUserId());
	?>
	<div class="col-lg-3 col-md-4 col-sm-6 pj-user-company">
		<div class="form-group">
			<label class="control-label"><?php __('lblUserCompany'); ?></label>
			<select name="company" id="user_company" class="form-control">
				<?php foreach ($ucList as $ucId => $ucRow) { ?>
				<option value="<?php echo pjCompanyContext::hashOf($ucId); ?>"<?php echo (int) $ucId === $ucSel ? ' selected="selected"' : ''; ?>><?php echo pjSanitize::html($ucRow['name']); ?></option>
				<?php } ?>
			</select>
			<p class="help-block m-b-none pj-user-company-admin" style="display:none"><small><?php __('lblUserAllCompanies'); ?></small></p>
		</div>
	</div>
	<script type="text/javascript">
	(function () {
		var role = document.getElementById('role_id'), sel = document.getElementById('user_company');
		if (!role || !sel) { return; }
		var note = sel.parentNode.querySelector('.pj-user-company-admin');
		var sync = function () {
			var isAdmin = String(role.value) === '1';
			sel.disabled = isAdmin;
			sel.style.display = isAdmin ? 'none' : '';
			if (note) { note.style.display = isAdmin ? '' : 'none'; }
		};
		role.addEventListener('change', sync);
		sync();
	})();
	</script>
	<?php
}
