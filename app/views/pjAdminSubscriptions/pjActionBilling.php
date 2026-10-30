<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = 'sub_tab_billing';
$heading_desc = 'sub_billing_desc';
include dirname(__FILE__) . '/elements/heading.php';
$show_company = true;
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
	<div class="ibox float-e-margins"><div class="ibox-content">
		<?php include dirname(__FILE__) . '/elements/transactions.php'; ?>
	</div></div>
</div>
