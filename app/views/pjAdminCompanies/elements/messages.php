<?php
// Flash message after redirect (?msg=<label key>). Only known keys are rendered.
$company_msg = $controller->_get->toString('msg');
$company_ok  = array('msgCompanyAdded', 'msgCompanySaved');
$company_err = array('msgCompanyError', 'msgCompanyRequired', 'msgCompanyNotFound', 'msgCompanyUserTaken', 'msgCompanyInvalidLogo', 'msgCompanyLogoTooBig', 'msgCompanyLogoFolder');
if (in_array($company_msg, $company_ok, true)) { ?>
	<div class="alert alert-success"><i class="fa fa-check m-r-xs"></i> <?php __($company_msg); ?></div>
<?php } elseif (in_array($company_msg, $company_err, true)) { ?>
	<div class="alert alert-danger"><i class="fa fa-exclamation-triangle m-r-xs"></i> <?php __($company_msg); ?></div>
<?php } ?>
