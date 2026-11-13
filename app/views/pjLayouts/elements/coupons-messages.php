<?php
/** Messages of the company Coupons & Discounts pages (?msg=), same look as the subscription pages. */
$cd_msg = $controller->_get->toString('msg');
$cd_ok = array('cdSaved', 'cdDeleted');
$cd_err = array('cdErrFields', 'cdErrCode', 'cdErrRule', 'cdErrSave');
if (in_array($cd_msg, $cd_ok, true)) { ?>
	<div class="alert alert-success"><i class="fa fa-check m-r-xs"></i> <?php __($cd_msg); ?></div>
<?php } elseif (in_array($cd_msg, $cd_err, true)) { ?>
	<div class="alert alert-danger"><i class="fa fa-exclamation-triangle m-r-xs"></i> <?php __($cd_msg); ?></div>
<?php } ?>
