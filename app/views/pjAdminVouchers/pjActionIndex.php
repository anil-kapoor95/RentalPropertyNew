<?php
/**
 * Coupons of the active company (Company options > Coupons & Discounts).
 * Same layout as the Super Admin's Coupons & Discounts (views/pjAdminSubscriptions/pjActionPromos.php).
 */
include PJ_VIEWS_PATH . 'pjLayouts/elements/coupons-helpers.php';
$cd_add = !empty($tpl['has_create']);
include PJ_VIEWS_PATH . 'pjLayouts/elements/coupons-bar.php';
$edit_url = $_SERVER['PHP_SELF'] . '?controller=pjAdminVouchers&amp;action=pjActionUpdate&amp;id=';
?>
		<?php if (empty($tpl['rows'])) { ?>
		<div class="sub-empty">
			<div class="sub-empty-icon"><i class="fa fa-ticket"></i></div>
			<p><?php __('sub_promo_empty'); ?></p>
			<?php if ($cd_add) { ?><a class="btn btn-primary btn-outline" href="<?php echo $cd_add_url; ?>"><i class="fa fa-plus m-r-xs"></i><?php __($cd_add_label); ?></a><?php } ?>
		</div>
		<?php } else { ?>
		<div class="table-responsive">
		<table class="table sub-table sub-promo-table">
			<thead><tr>
				<th><?php __('sub_promo_code'); ?></th>
				<th><?php __('sub_promo_value'); ?></th><th><?php __('cd_col_properties'); ?></th><th><?php __('cd_col_conditions'); ?></th>
				<th><?php __('sub_promo_validity'); ?></th><th><?php __('sub_promo_used'); ?></th><th><?php __('sub_col_status'); ?></th><th></th>
			</tr></thead>
			<tbody>
			<?php foreach ($tpl['rows'] as $row) {
				$rid = (int) $row['id'];
				$used = isset($tpl['uses'][strtolower((string) $row['code'])]) ? (int) $tpl['uses'][strtolower((string) $row['code'])] : 0;
				$limit = (int) $row['used_count'];
				$status = pjAppController::cdStatus($row['date_from'], $row['date_to']);
				?>
				<tr class="<?php echo $status === 'expired' ? 'sub-row-off' : ''; ?>">
					<td>
						<?php if (!empty($tpl['has_update'])) { ?><a class="sub-code" href="<?php echo $edit_url . $rid; ?>"><?php echo pjSanitize::html($row['code']); ?></a><?php } else { ?><span class="sub-code"><?php echo pjSanitize::html($row['code']); ?></span><?php } ?>
					</td>
					<td class="text-nowrap"><span class="sub-off"><?php echo $cdValue($row); ?></span></td>
					<td><?php echo $cdPropertyTags(isset($tpl['links'][$rid]) ? $tpl['links'][$rid] : array()); ?></td>
					<td>
						<?php if (isset($cdConditions[$row['condition']])) { ?><span class="sub-tag"><?php echo pjSanitize::html($cdConditions[$row['condition']]); ?></span><?php } ?>
						<?php if (isset($cdApplyOn[$row['apply_on']])) { ?><span class="sub-tag sub-tag-all"><?php echo pjSanitize::html($cdApplyOn[$row['apply_on']]); ?></span><?php } ?>
					</td>
					<td class="text-nowrap"><?php echo $cdDate($row['date_from']) . ' &ndash; ' . $cdDate($row['date_to']); ?></td>
					<td class="sub-usage-cell">
						<strong><?php echo $used; ?></strong><span class="text-muted"> / <?php echo $limit > 0 ? $limit : '&infin;'; ?></span>
						<?php if ($limit > 0) { ?><div class="sub-meter"><span style="width:<?php echo min(100, round($used * 100 / $limit)); ?>%"></span></div><?php } ?>
					</td>
					<td><?php echo $cdStatus($row); ?></td>
					<td class="text-right"><?php if (!empty($tpl['has_update'])) { ?><a class="btn btn-white btn-sm sub-icon-btn" href="<?php echo $edit_url . $rid; ?>" title="<?php __('sub_btn_manage', false, true); ?>"><i class="fa fa-pencil"></i></a><?php } ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		</div>
		<?php if (!empty($tpl['has_export'])) { ?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminVouchers&amp;action=pjActionExportVoucher" class="m-t-sm">
			<?php echo $cdCsrf; foreach ($tpl['rows'] as $row) { ?><input type="hidden" name="record[]" value="<?php echo (int) $row['id']; ?>"><?php } ?>
			<button type="submit" class="btn btn-white btn-sm"><i class="fa fa-download m-r-xs"></i><?php __('cd_btn_export'); ?></button>
		</form>
		<?php } ?>
		<?php } ?>
	</div></div>
</div>
