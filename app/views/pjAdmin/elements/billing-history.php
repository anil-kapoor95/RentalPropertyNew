<?php
/**
 * Dashboard section "Billing History": the latest billing transactions (pjPlatformDashboard::transactions).
 * Expects: $bh_rows (array), $bh_show_company (bool), $bh_view_all (URL or ''), $df (date format);
 * optional $bh_show_empty (bool: show the section with an empty message when $bh_rows is empty) and $bh_note (text
 * under the title, e.g. the period). Without rows and without $bh_show_empty nothing is shown.
 */
$bh_show_empty = !empty($bh_show_empty);
$bh_note = isset($bh_note) ? (string) $bh_note : '';
if (empty($bh_rows) && !$bh_show_empty)
{
	return;
}
$bh_status_class = array('paid' => 's-good', 'pending' => 's-warn', 'failed' => 's-bad', 'cancelled' => 's-bad', 'refunded' => '');
?>
<div class="pd-card" id="pdBillingHistory">
	<div class="pd-card-head">
		<h3><i class="fa fa-history"></i><?php __('dash_bh_title'); ?><?php if ($bh_note !== '') { ?> <small class="pd-head-note"><?php echo pjSanitize::html($bh_note); ?></small><?php } ?></h3>
		<?php if ($bh_view_all !== '') { ?>
			<a class="pd-head-link" href="<?php echo $bh_view_all; ?>"><?php __('dash_view_all'); ?> <i class="fa fa-arrow-right"></i></a>
		<?php } ?>
	</div>
	<div class="pd-card-body pd-tight">
		<?php if (empty($bh_rows)) { ?>
		<div class="pd-empty"><i class="fa fa-file-text-o"></i><?php __('dash_bh_empty'); ?></div>
		<?php } else { ?>
		<div class="pd-table-wrap">
			<table class="pd-table">
				<thead>
					<tr>
						<th><?php __('sub_col_date'); ?></th>
						<?php if ($bh_show_company) { ?><th><?php __('sub_col_company'); ?></th><?php } ?>
						<th><?php __('sub_col_plan'); ?></th>
						<th class="pd-hide-sm"><?php __('sub_col_method'); ?></th>
						<th class="text-right"><?php __('sub_col_amount'); ?></th>
						<th class="text-right"><?php __('sub_col_status'); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($bh_rows as $t) { ?>
					<tr>
						<td class="pd-nowrap"><?php echo pjSanitize::html(date($df, strtotime($t['date']))); ?></td>
						<?php if ($bh_show_company) { ?><td class="pd-strong"><?php echo pjSanitize::html($t['company']); ?></td><?php } ?>
						<td><?php echo pjSanitize::html($t['plan']); ?> <small class="pd-muted"><?php __('sub_kind_' . $t['kind']); ?></small>
							<?php if ($t['coupon'] !== '') { ?><small class="pd-muted">&middot; <i class="fa fa-ticket"></i> <?php echo pjSanitize::html($t['coupon']); ?></small><?php } ?></td>
						<td class="pd-hide-sm pd-muted"><?php echo pjSanitize::html($t['method']); ?></td>
						<td class="text-right pd-nowrap pd-strong"><?php echo pjSanitize::html($t['amount']); ?></td>
						<td class="text-right"><span class="pd-pill <?php echo isset($bh_status_class[$t['status']]) ? $bh_status_class[$t['status']] : ''; ?>"><?php __('sub_tx_' . $t['status']); ?></span></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		</div>
		<?php } ?>
	</div>
</div>
