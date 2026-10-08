<?php
$heading_title = 'infoAddCompanyTitle';
$heading_desc = 'infoAddCompanyDesc';
include dirname(__FILE__) . '/elements/heading.php';
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<div class="row">
		<div class="col-lg-12">
			<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
			<div class="ibox float-e-margins">
				<div class="ibox-content">
					<?php
					$form_action   = $_SERVER['PHP_SELF'] . '?controller=pjAdminCompanies&amp;action=pjActionCreate';
					$form_flag     = 'company_create';
					$is_admin_form = true;
					$cancel_url    = PJ_INSTALL_URL . 'index.php?controller=pjAdminCompanies&amp;action=pjActionIndex';
					include dirname(__FILE__) . '/elements/form.php';
					?>
				</div>
			</div>
		</div>
	</div>
</div>
