<?php
$sub_msg = $controller->_get->toString('msg');
$sub_ok = array('subSaved', 'subTrialStarted', 'subDowngradeScheduled', 'subCancelScheduled', 'subPaymentOk', 'subRequestSent');
$sub_info = array('subPaymentPending', 'subPaymentCancelled', 'subLocked');
$sub_err = array('subErrSave', 'subErrCsrf', 'subErrCompany', 'subErrPlan', 'subErrTrial', 'subErrCheckout', 'subErrPlanUsed',
	'subErrPlanFields', 'subErrPlanCode', 'subErrKey', 'subErrDuplicateRef', 'subErrLimit', 'subErrMethod', 'subErrCurrency', 'subPaymentFailed');
if (in_array($sub_msg, $sub_ok, true)) { ?>
	<div class="alert alert-success"><i class="fa fa-check m-r-xs"></i> <?php __($sub_msg); ?></div>
<?php } elseif (in_array($sub_msg, $sub_info, true)) { ?>
	<div class="alert alert-info"><i class="fa fa-info-circle m-r-xs"></i> <?php __($sub_msg); ?></div>
<?php } elseif (in_array($sub_msg, $sub_err, true)) { ?>
	<div class="alert alert-danger"><i class="fa fa-exclamation-triangle m-r-xs"></i> <?php __($sub_msg); ?></div>
<?php } ?>
