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
			<td><?php echo $subMoney($t['amount'], $t['currency']); ?><?php if (isset($t['amount_usd']) && $t['amount_usd'] !== null && strtoupper((string) $t['currency']) !== 'USD') { ?>
				<br><small class="text-muted" title="<?php echo !empty($t['exchange_rate']) ? '1 USD = ' . pjSanitize::html(pjMoney::format($t['exchange_rate'], $t['currency'])) : ''; ?>">&asymp; <?php echo $subMoney($t['amount_usd'], 'USD'); ?></small><?php } ?><?php if (isset($t['discount_amount']) && (float) $t['discount_amount'] > 0) { ?>
				<br><small class="text-muted"><?php __('sub_discount'); ?>: <?php echo $subMoney($t['discount_amount'], $t['currency']); ?><?php echo !empty($t['coupon_code']) ? ' (' . pjSanitize::html($t['coupon_code']) . ')' : ''; ?></small><?php } ?></td>
			<td><?php echo pjSanitize::html(pjSubscriptionBilling::methodName($t['provider'])); ?></td>
			<td><span class="sub-badge sub-tx-<?php echo pjSanitize::html($t['status']); ?>"><?php __('sub_tx_' . $t['status']); ?></span><?php if (!empty($t['failure_reason'])) { ?><br><small class="text-muted"><?php echo pjSanitize::html($t['failure_reason']); ?></small><?php } ?></td>
			<td><?php echo $t['period_start'] ? $subDate($t['period_start']) . ' &ndash; ' . $subDate($t['period_end']) : '&mdash;'; ?><?php
				// A plan change credits the unused paid time of the old plan as extra days: say so, otherwise a monthly
				// plan seems to run for six weeks. Credit = stored end - one normal billing period from the start.
				if ($t['status'] === 'paid' && !empty($t['period_start']) && !empty($t['period_end']) && $t['kind'] !== 'renewal' && $t['provider'] !== 'manual'
					&& ($txPlan = pjEntitlement::plan($t['plan_id'])))
				{
					$txDays = (int) round((strtotime($t['period_end']) - strtotime(pjEntitlement::addPeriod($t['period_start'], $txPlan))) / 86400);
					if ($txDays >= 1)
					{
						?><br><small class="sub-credit-note" title="<?php __('sub_period_credit_hint', false, true); ?>"><i class="fa fa-plus-circle"></i> <?php echo pjSanitize::html(str_replace('{DAYS}', $txDays, __($txDays === 1 ? 'sub_period_credit_one' : 'sub_period_credit', true))); ?></small><?php
					}
				}
			?></td>
			<td><small><?php echo pjSanitize::html((string) $t['provider_ref']); ?></small></td>
		</tr>
	<?php } ?>
	<?php if (empty($tpl['transactions'])) { ?><tr><td colspan="8" class="text-muted"><?php __('sub_nothing'); ?></td></tr><?php } ?>
	</tbody>
</table>
</div>
