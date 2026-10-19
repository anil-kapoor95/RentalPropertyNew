<?php
/** Expects $tpl['transactions'], helpers from common.php. $show_company (bool) */
?>
<div class="table-responsive">
<table class="table table-striped sub-table">
	<thead><tr>
		<th><?php __('sub_col_date'); ?></th>
		<?php if (!empty($show_company)) { ?><th><?php __('sub_col_company'); ?></th><?php } ?>
		<th><?php __('sub_col_plan'); ?></th><th><?php __('sub_col_amount'); ?></th><th><?php __('sub_col_method'); ?></th>
		<th><?php __('sub_col_status'); ?></th><th><?php __('sub_col_period'); ?></th><th><?php __('sub_reference'); ?></th>
	</tr></thead>
	<tbody>
	<?php foreach ($tpl['transactions'] as $t) { ?>
		<tr>
			<td><?php echo $subDate($t['created']); ?></td>
			<?php if (!empty($show_company)) { ?><td><?php echo pjSanitize::html((string) $t['company_name']); ?></td><?php } ?>
			<td><?php echo pjSanitize::html((string) $t['plan_name']); ?> <small class="text-muted"><?php __('sub_kind_' . $t['kind']); ?></small></td>
			<td><?php echo $subMoney($t['amount'], $t['currency']); ?></td>
			<td><?php echo pjSanitize::html(pjSubscriptionBilling::methodName($t['provider'])); ?></td>
			<td><span class="sub-badge sub-tx-<?php echo pjSanitize::html($t['status']); ?>"><?php __('sub_tx_' . $t['status']); ?></span><?php if (!empty($t['failure_reason'])) { ?><br><small class="text-muted"><?php echo pjSanitize::html($t['failure_reason']); ?></small><?php } ?></td>
			<td><?php echo $t['period_start'] ? $subDate($t['period_start']) . ' &ndash; ' . $subDate($t['period_end']) : '&mdash;'; ?></td>
			<td><small><?php echo pjSanitize::html((string) $t['provider_ref']); ?></small></td>
		</tr>
	<?php } ?>
	<?php if (empty($tpl['transactions'])) { ?><tr><td colspan="8" class="text-muted"><?php __('sub_nothing'); ?></td></tr><?php } ?>
	</tbody>
</table>
</div>
