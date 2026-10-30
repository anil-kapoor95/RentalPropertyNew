<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = 'sub_tab_plans';
$heading_desc = 'sub_plans_desc';
include dirname(__FILE__) . '/elements/heading.php';
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
	<div class="ibox float-e-margins"><div class="ibox-content">
		<a class="btn btn-primary m-b-md" href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionPlan"><i class="fa fa-plus m-r-xs"></i><?php __('sub_btn_add_plan'); ?></a>
		<div class="table-responsive">
		<table class="table table-striped sub-table">
			<thead><tr>
				<th><?php __('sub_col_plan'); ?></th><th><?php __('sub_col_price'); ?></th><th><?php __('sub_trial_days'); ?></th>
				<th><?php __('sub_max_properties'); ?></th><th><?php __('sub_max_users'); ?></th><th><?php __('sub_max_payment_gateways'); ?></th>
				<th><?php __('sub_col_companies'); ?></th><th><?php __('sub_col_status'); ?></th><th></th>
			</tr></thead>
			<tbody>
			<?php foreach ($tpl['plans'] as $p) { ?>
				<tr>
					<td><strong><?php echo pjSanitize::html($p['name']); ?></strong> <small class="text-muted"><?php echo pjSanitize::html($p['code']); ?></small><?php echo (int) $p['is_public'] !== 1 ? ' <small class="text-muted">(' . __('sub_hidden', true) . ')</small>' : ''; ?></td>
					<td><?php echo $subMoney($p['price'], $p['currency']); ?><?php if ($p['price'] !== null) { ?> <small class="text-muted">/ <?php echo $subInterval($p); ?></small><?php } ?></td>
					<td><?php echo (int) $p['trial_days']; ?></td>
					<td><?php echo $subLimit($p['max_properties']); ?></td>
					<td><?php echo $subLimit($p['max_users']); ?></td>
					<td><?php echo $subLimit($p['max_payment_gateways']); ?></td>
					<td><?php echo isset($tpl['counts'][$p['id']]) ? (int) $tpl['counts'][$p['id']] : 0; ?></td>
					<td><?php echo $p['status'] === 'T' ? '<span class="sub-badge sub-active">' . __('sub_active_plan', true) . '</span>' : '<span class="sub-badge sub-none">' . __('sub_inactive', true) . '</span>'; ?>
						<?php if (!pjEntitlement::isPurchasable($p)) { ?><br><small class="text-muted"><?php __('sub_not_purchasable'); ?></small><?php } ?></td>
					<td class="text-right"><a class="btn btn-primary btn-sm" href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionPlan&amp;id=<?php echo (int) $p['id']; ?>"><i class="fa fa-pencil"></i></a></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		</div>
	</div></div>
</div>
