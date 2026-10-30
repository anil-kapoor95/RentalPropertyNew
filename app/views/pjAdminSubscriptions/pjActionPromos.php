<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = 'sub_tab_promos';
$heading_desc = 'sub_promos_desc';
include dirname(__FILE__) . '/elements/heading.php';
$kind = $tpl['kind'];
$url = $_SERVER['PHP_SELF'] . '?controller=pjAdminSubscriptions&amp;action=';
$promoValue = function ($p) {
	return $p['type'] === 'percent'
		? pjSanitize::html(rtrim(rtrim($p['value'], '0'), '.')) . '%'
		: pjSanitize::html(number_format((float) $p['value'], 2) . ' ' . $p['currency']);
};
$segments = array(
	'discount' => array('icon' => 'fa-percent', 'label' => 'sub_promo_tab_discounts'),
	'coupon' => array('icon' => 'fa-ticket', 'label' => 'sub_promo_tab_coupons'),
);
$add_label = $kind === 'coupon' ? 'sub_btn_add_coupon' : 'sub_btn_add_discount';
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
	<div class="ibox float-e-margins"><div class="ibox-content sub-promos">
		<div class="sub-promo-bar">
			<div class="sub-seg" role="tablist">
				<?php foreach ($segments as $k => $seg) { ?>
				<a class="sub-seg-item<?php echo $kind === $k ? ' active' : ''; ?>" role="tab" aria-selected="<?php echo $kind === $k ? 'true' : 'false'; ?>" href="<?php echo $url; ?>pjActionPromos&amp;kind=<?php echo $k; ?>">
					<i class="fa <?php echo $seg['icon']; ?>"></i> <?php __($seg['label']); ?>
					<span class="sub-seg-count"><?php echo (int) $tpl['counts'][$k]; ?></span>
				</a>
				<?php } ?>
			</div>
			<a class="btn btn-primary" href="<?php echo $url; ?>pjActionPromo&amp;kind=<?php echo $kind; ?>"><i class="fa fa-plus m-r-xs"></i><?php __($add_label); ?></a>
		</div>
		<p class="sub-promo-hint"><i class="fa fa-info-circle"></i> <?php __('sub_promo_stack_hint'); ?></p>

		<?php if (empty($tpl['promos'])) { ?>
		<div class="sub-empty">
			<div class="sub-empty-icon"><i class="fa <?php echo $segments[$kind]['icon']; ?>"></i></div>
			<p><?php __('sub_promo_empty'); ?></p>
			<a class="btn btn-primary btn-outline" href="<?php echo $url; ?>pjActionPromo&amp;kind=<?php echo $kind; ?>"><i class="fa fa-plus m-r-xs"></i><?php __($add_label); ?></a>
		</div>
		<?php } else { ?>
		<div class="table-responsive">
		<table class="table sub-table sub-promo-table">
			<thead><tr>
				<th><?php __('sub_promo_name'); ?></th>
				<th><?php __('sub_promo_value'); ?></th><th><?php __('sub_promo_plans'); ?></th><th><?php __('sub_promo_kinds'); ?></th>
				<th><?php __('sub_promo_validity'); ?></th><th><?php __('sub_promo_used'); ?></th><th><?php __('sub_col_status'); ?></th><th></th>
			</tr></thead>
			<tbody>
			<?php foreach ($tpl['promos'] as $p) {
				$plan_tags = array();
				foreach (pjSubscriptionPromos::listOf($p['plan_ids']) as $pid) { if (isset($tpl['plans'][(int) $pid])) { $plan_tags[] = pjSanitize::html($tpl['plans'][(int) $pid]); } }
				$kind_tags = array();
				foreach (pjSubscriptionPromos::listOf($p['kinds']) as $k) { $kind_tags[] = __('sub_kind_' . $k, true); }
				$used = isset($tpl['uses'][(int) $p['id']]) ? (int) $tpl['uses'][(int) $p['id']] : 0;
				$edit = $url . 'pjActionPromo&amp;id=' . (int) $p['id'];
				?>
				<tr class="<?php echo $p['status'] === 'T' ? '' : 'sub-row-off'; ?>">
					<td>
						<a class="sub-promo-name" href="<?php echo $edit; ?>"><?php echo pjSanitize::html($p['name']); ?></a>
						<?php if ($kind === 'coupon') { ?><br><span class="sub-code"><?php echo pjSanitize::html($p['code']); ?></span><?php } ?>
					</td>
					<td><span class="sub-off"><?php echo $promoValue($p); ?></span></td>
					<td><?php if (empty($plan_tags)) { ?><span class="sub-tag sub-tag-all"><?php __('sub_promo_all_plans'); ?></span><?php } else { foreach ($plan_tags as $t) { ?><span class="sub-tag"><?php echo $t; ?></span><?php } } ?></td>
					<td><?php if (empty($kind_tags)) { ?><span class="sub-tag sub-tag-all"><?php __('sub_promo_all_kinds'); ?></span><?php } else { foreach ($kind_tags as $t) { ?><span class="sub-tag"><?php echo pjSanitize::html($t); ?></span><?php } } ?></td>
					<td class="text-nowrap"><?php echo empty($p['date_from']) && empty($p['date_to']) ? '<span class="text-muted">' . __('sub_promo_any_time', true) . '</span>' : $subDate($p['date_from']) . ' &ndash; ' . $subDate($p['date_to']); ?></td>
					<td class="sub-usage-cell">
						<strong><?php echo $used; ?></strong><span class="text-muted"> / <?php echo $p['max_uses'] !== null ? (int) $p['max_uses'] : '&infin;'; ?></span>
						<?php if ($p['max_uses'] !== null && (int) $p['max_uses'] > 0) { ?><div class="sub-meter"><span style="width:<?php echo min(100, round($used * 100 / (int) $p['max_uses'])); ?>%"></span></div><?php } ?>
					</td>
					<td><?php echo $p['status'] === 'T' ? '<span class="sub-badge sub-active">' . __('sub_promo_active', true) . '</span>' : '<span class="sub-badge sub-none">' . __('sub_inactive', true) . '</span>'; ?></td>
					<td class="text-right"><a class="btn btn-white btn-sm sub-icon-btn" href="<?php echo $edit; ?>" title="<?php __('sub_btn_manage', false, true); ?>"><i class="fa fa-pencil"></i></a></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		</div>
		<?php } ?>
	</div></div>
</div>
