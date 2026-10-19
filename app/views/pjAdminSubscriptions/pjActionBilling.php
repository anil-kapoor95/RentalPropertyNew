<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = 'sub_menu';
$heading_desc = 'sub_billing_desc';
$heading_tabs = array('pjActionIndex' => 'sub_tab_companies', 'pjActionPlans' => 'sub_tab_plans', 'pjActionBilling' => 'sub_tab_billing', 'pjActionSettings' => 'sub_tab_settings');
include dirname(__FILE__) . '/elements/heading.php';
$show_company = true;
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
	<div class="ibox float-e-margins"><div class="ibox-content">
		<?php include dirname(__FILE__) . '/elements/transactions.php'; ?>
	</div></div>
</div>
