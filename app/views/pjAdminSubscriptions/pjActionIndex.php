<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = 'sub_tab_companies';
$heading_desc = 'sub_index_desc';
include dirname(__FILE__) . '/elements/heading.php';
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
	<div class="ibox float-e-margins"><div class="ibox-content">
		<p class="text-muted"><?php __($tpl['unassigned_mode'] === 'restricted' ? 'sub_note_unassigned_restricted' : 'sub_note_unassigned_full'); ?></p>
		<div class="table-responsive">
		<table class="table table-striped sub-table">
			<thead><tr>
				<th><?php __('sub_col_company'); ?></th><th><?php __('sub_col_plan'); ?></th><th><?php __('sub_col_status'); ?></th>
				<th><?php __('sub_col_renewal'); ?></th><th><?php __('sub_col_properties'); ?></th><th><?php __('sub_col_users'); ?></th><th></th>
			</tr></thead>
			<tbody>
			<?php foreach ($tpl['rows'] as $r) { $s = $r['state']; ?>
				<tr>
					<td><strong><?php echo pjSanitize::html($r['company']['name']); ?></strong><?php echo $r['company']['status'] !== 'T' ? ' <small class="text-muted">(' . __('sub_company_inactive', true) . ')</small>' : ''; ?></td>
					<td><?php echo $s['plan'] ? pjSanitize::html($s['plan']['name']) : $subSource($s); ?>
						<?php if ($s['subscription'] && !empty($s['subscription']['pending_plan_id'])) { $pp = pjEntitlement::plan($s['subscription']['pending_plan_id']); ?><br><small class="text-muted"><?php __('sub_then'); ?> <?php echo $pp ? pjSanitize::html($pp['name']) : ''; ?></small><?php } ?></td>
					<td><?php echo $s['subscription'] ? $subStatus($s['status']) : '&mdash;'; ?>
						<?php if ($s['subscription'] && (int) $s['subscription']['cancel_at_period_end'] === 1) { ?><br><small class="text-muted"><?php __('sub_cancels_at_end'); ?></small><?php } ?></td>
					<td><?php echo $s['subscription'] ? $subDate($s['subscription']['current_period_end']) : '&mdash;'; ?></td>
					<td><?php echo (int) $r['usage']['properties']; ?> / <?php echo $subLimit($s['limits']['max_properties']); ?></td>
					<td><?php echo (int) $r['usage']['users']; ?> / <?php echo $subLimit($s['limits']['max_users']); ?></td>
					<td class="text-right"><a class="btn btn-primary btn-sm" href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionManage&amp;id=<?php echo $r['hash']; ?>"><?php __('sub_btn_manage'); ?></a></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		</div>
	</div></div>
</div>
