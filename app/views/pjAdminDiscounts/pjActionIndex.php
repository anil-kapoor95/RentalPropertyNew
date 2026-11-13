<?php
/**
 * Discounts of the active company (Company options > Coupons & Discounts).
 * Same layout as the Super Admin's Coupons & Discounts (views/pjAdminSubscriptions/pjActionPromos.php).
 */
include PJ_VIEWS_PATH . 'pjLayouts/elements/coupons-helpers.php';
$cd_add = !empty($tpl['has_create']);
include PJ_VIEWS_PATH . 'pjLayouts/elements/coupons-bar.php';
$edit_url = $_SERVER['PHP_SELF'] . '?controller=pjAdminDiscounts&amp;action=pjActionUpdate&amp;id=';
$cdRule = function ($row) use ($cdOptions) {
	$label = isset($cdOptions[$row['options']]) ? $cdOptions[$row['options']] : '';
	switch ($row['options'])
	{
		case 'early':
			$detail = sprintf(__('cd_rule_early', true), (int) $row['early_days']);
			break;
		case 'persons':
			$detail = sprintf(__('cd_rule_persons', true), (int) $row['min_persons'], (int) $row['max_persons']);
			break;
		case 'duration':
			$detail = sprintf(__('cd_rule_duration', true), (int) $row['min_duration'], (int) $row['max_duration']);
			break;
		default:
			$detail = '';
	}
	return '<span class="sub-tag sub-tag-all">' . pjSanitize::html($label) . '</span>' . ($detail !== '' ? '<span class="sub-tag">' . pjSanitize::html($detail) . '</span>' : '');
};
?>
		<?php if (empty($tpl['rows'])) { ?>
		<div class="sub-empty">
			<div class="sub-empty-icon"><i class="fa fa-percent"></i></div>
			<p><?php __('sub_promo_empty'); ?></p>
			<?php if ($cd_add) { ?><a class="btn btn-primary btn-outline" href="<?php echo $cd_add_url; ?>"><i class="fa fa-plus m-r-xs"></i><?php __($cd_add_label); ?></a><?php } ?>
		</div>
		<?php } else { ?>
		<div class="table-responsive">
		<table class="table sub-table sub-promo-table">
			<thead><tr>
				<th><?php __('sub_promo_name'); ?></th>
				<th><?php __('sub_promo_value'); ?></th><th><?php __('cd_col_properties'); ?></th><th><?php __('cd_col_rule'); ?></th>
				<th><?php __('sub_promo_validity'); ?></th><th><?php __('cd_col_conditions'); ?></th><th><?php __('sub_col_status'); ?></th><th></th>
			</tr></thead>
			<tbody>
			<?php foreach ($tpl['rows'] as $row) {
				$rid = (int) $row['id'];
				$status = pjAppController::cdStatus($row['date_from'], $row['date_to']);
				$name = trim((string) $row['name']) !== '' ? (string) $row['name'] : '#' . $rid;
				?>
				<tr class="<?php echo $status === 'expired' ? 'sub-row-off' : ''; ?>">
					<td>
						<?php if (!empty($tpl['has_update'])) { ?><a class="sub-promo-name" href="<?php echo $edit_url . $rid; ?>"><?php echo pjSanitize::html($name); ?></a><?php } else { ?><span class="sub-promo-name"><?php echo pjSanitize::html($name); ?></span><?php } ?>
					</td>
					<td class="text-nowrap"><span class="sub-off"><?php echo $cdValue($row); ?></span></td>
					<td><?php echo $cdPropertyTags(isset($tpl['links'][$rid]) ? $tpl['links'][$rid] : array()); ?></td>
					<td><?php echo $cdRule($row); ?></td>
					<td class="text-nowrap"><?php echo $cdDate($row['date_from']) . ' &ndash; ' . $cdDate($row['date_to']); ?></td>
					<td><?php if (isset($cdConditions[$row['condition']])) { ?><span class="sub-tag"><?php echo pjSanitize::html($cdConditions[$row['condition']]); ?></span><?php } ?></td>
					<td><?php echo $cdStatus($row); ?></td>
					<td class="text-right"><?php if (!empty($tpl['has_update'])) { ?><a class="btn btn-white btn-sm sub-icon-btn" href="<?php echo $edit_url . $rid; ?>" title="<?php __('sub_btn_manage', false, true); ?>"><i class="fa fa-pencil"></i></a><?php } ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		</div>
		<?php if (!empty($tpl['has_export'])) { ?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminDiscounts&amp;action=pjActionExportDiscount" class="m-t-sm">
			<?php echo $cdCsrf; foreach ($tpl['rows'] as $row) { ?><input type="hidden" name="record[]" value="<?php echo (int) $row['id']; ?>"><?php } ?>
			<button type="submit" class="btn btn-white btn-sm"><i class="fa fa-download m-r-xs"></i><?php __('cd_btn_export'); ?></button>
		</form>
		<?php } ?>
		<?php } ?>
	</div></div>
</div>
