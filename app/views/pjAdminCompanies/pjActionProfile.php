<?php
$heading_title = 'infoMyCompanyTitle';
$heading_desc = 'infoMyCompanyDesc';
include dirname(__FILE__) . '/elements/heading.php';
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<div class="row">
		<div class="col-lg-12">
			<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
			<?php if (empty($tpl['arr'])) { ?>
				<div class="alert alert-warning"><i class="fa fa-info-circle m-r-xs"></i> <?php __('msgCompanyNoProfile'); ?></div>
			<?php } else { ?>
			<div class="ibox float-e-margins">
				<div class="ibox-content">
					<?php
					$form_action   = $_SERVER['PHP_SELF'] . '?controller=pjAdminCompanies&amp;action=pjActionProfile';
					$form_flag     = 'company_profile';
					$is_admin_form = false;
					$cancel_url    = null;
					include dirname(__FILE__) . '/elements/form.php';
					?>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
