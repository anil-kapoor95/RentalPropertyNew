<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = 'sub_tab_plans';
$heading_desc = 'sub_plans_desc';
include dirname(__FILE__) . '/elements/heading.php';

$self = $_SERVER['PHP_SELF'] . '?controller=pjAdminSubscriptions&amp;action=';
$all_features = pjEntitlement::$features;
$base = pjEntitlement::$baseFeatures;
$limits = array(
	'max_properties'       => 'fa-home',
	'max_users'            => 'fa-users',
	'max_payment_gateways' => 'fa-credit-card',
	'ai_monthly_messages'  => 'fa-comments-o',
);
$t = function ($key, $tokens = array()) {
	return pjSanitize::html(str_replace(array_keys($tokens), array_values($tokens), __($key, true)));
};
/** Price of a plan card: "Free", "Price not set" or "1,200.00 USD / month". */
$price = function ($p) use ($subInterval, $t) {
	if ($p['price'] === null)
	{
		return '<span class="sub-pc-price sub-pc-price-none">' . $t('sub_price_not_set') . '</span>';
	}
	if ((float) $p['price'] <= 0)
	{
		return '<span class="sub-pc-price">' . $t('sub_plan_free') . '</span>';
	}
	// big number, small currency symbol on the side the settings put it (pjMoney)
	list($sign, $number, $place) = pjMoney::parts($p['price'], $p['currency']);
	$sign_html = '<small>' . pjSanitize::html($sign) . '</small>';
	return '<span class="sub-pc-price">' . ($place === 'front' ? $sign_html . ' ' . pjSanitize::html($number) : pjSanitize::html($number) . ' ' . $sign_html) . '</span>'
		. '<span class="sub-pc-per">/ ' . $subInterval($p) . '</span>';
};
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>

	<div class="sub-plans-bar">
		<p class="sub-plans-count"><?php echo $t('sub_plans_count', array('{N}' => count($tpl['plans']))); ?></p>
		<a class="btn btn-primary" href="<?php echo $self; ?>pjActionPlan"><i class="fa fa-plus m-r-xs"></i><?php __('sub_btn_add_plan'); ?></a>
	</div>

	<?php if (empty($tpl['plans'])) { ?>
	<div class="ibox"><div class="ibox-content sub-empty">
		<div class="sub-empty-icon"><i class="fa fa-cubes"></i></div>
		<h3><?php __('sub_plans_empty'); ?></h3>
		<p><?php __('sub_plans_empty_desc'); ?></p>
		<a class="btn btn-primary m-t-sm" href="<?php echo $self; ?>pjActionPlan"><i class="fa fa-plus m-r-xs"></i><?php __('sub_btn_add_plan'); ?></a>
	</div></div>
	<?php } else { ?>

	<div class="sub-plan-grid">
		<?php foreach ($tpl['plans'] as $p) {
			$active = $p['status'] === 'T';
			$is_default = (int) $p['id'] === (int) $tpl['default_plan_id'];
			$extras = array_values(array_diff($p['feature_list'], $base));
			$missing_base = array_values(array_diff($base, $p['feature_list']));
			$companies = isset($tpl['counts'][$p['id']]) ? (int) $tpl['counts'][$p['id']] : 0;
			$edit = $self . 'pjActionPlan&amp;id=' . (int) $p['id'];
			?>
		<article class="sub-pc<?php echo $is_default ? ' sub-pc-default' : ''; ?><?php echo $active ? '' : ' sub-pc-off'; ?>">
			<header class="sub-pc-head">
				<div class="sub-pc-badges">
					<?php echo $active ? '<span class="sub-badge sub-active">' . $t('sub_active_plan') . '</span>' : '<span class="sub-badge sub-none">' . $t('sub_inactive') . '</span>'; ?>
					<?php if ($is_default) { ?><span class="sub-badge sub-trial"><i class="fa fa-star m-r-xs"></i><?php echo $t('sub_default_plan_badge'); ?></span><?php } ?>
					<?php if ((int) $p['is_public'] !== 1) { ?><span class="sub-badge sub-none"><i class="fa fa-eye-slash m-r-xs"></i><?php echo $t('sub_plan_private'); ?></span><?php } ?>
				</div>
				<h3 class="sub-pc-name"><a href="<?php echo $edit; ?>"><?php echo pjSanitize::html($p['name']); ?></a></h3>
				<span class="sub-code"><?php echo pjSanitize::html($p['code']); ?></span>
				<div class="sub-pc-pricing"><?php echo $price($p); ?></div>
				<?php if ((int) $p['trial_days'] > 0) { ?>
				<p class="sub-pc-trial"><i class="fa fa-gift m-r-xs"></i><?php echo $t('sub_plan_trial', array('{N}' => (int) $p['trial_days'])); ?></p>
				<?php } ?>
				<?php if ($active && !pjEntitlement::isPurchasable($p)) { ?>
				<p class="sub-pc-note"><i class="fa fa-info-circle m-r-xs"></i><?php echo $t('sub_not_purchasable'); ?></p>
				<?php } ?>
			</header>

			<ul class="sub-pc-limits">
				<?php foreach ($limits as $k => $icon) { ?>
				<li><span><i class="fa <?php echo $icon; ?>"></i><?php echo $t('sub_' . $k); ?></span><strong><?php echo $p[$k] === null ? $t('sub_unlimited') : (int) $p[$k]; ?></strong></li>
				<?php } ?>
			</ul>

			<div class="sub-pc-features">
				<p class="sub-pc-label"><?php echo $t('sub_features_count', array('{N}' => count($p['feature_list']), '{T}' => count($all_features))); ?></p>
				<?php if (empty($missing_base)) { ?>
				<p class="sub-pc-base"><i class="fa fa-check"></i><?php echo $t('sub_plan_base_included'); ?></p>
				<?php } ?>
				<ul>
					<?php
					$show = empty($missing_base) ? $extras : $p['feature_list'];
					foreach (array_slice($show, 0, 5) as $f) { ?>
					<li><i class="fa fa-check"></i><?php echo $t($all_features[$f]); ?></li>
					<?php } ?>
				</ul>
				<?php if (count($show) > 5) { ?>
				<a class="sub-pc-more" href="#subCompare"><?php echo $t('sub_more', array('{N}' => count($show) - 5)); ?></a>
				<?php } ?>
			</div>

			<footer class="sub-pc-foot">
				<span class="sub-pc-companies"><i class="fa fa-building-o m-r-xs"></i><?php echo $t('sub_plan_companies', array('{N}' => $companies)); ?></span>
				<a class="btn btn-white btn-sm" href="<?php echo $edit; ?>"><i class="fa fa-pencil m-r-xs"></i><?php __('sub_plan_btn_edit'); ?></a>
			</footer>
		</article>
		<?php } ?>

		<a class="sub-pc sub-pc-add" href="<?php echo $self; ?>pjActionPlan">
			<span class="sub-empty-icon"><i class="fa fa-plus"></i></span>
			<strong><?php __('sub_btn_add_plan'); ?></strong>
		</a>
	</div>

	<?php if (count($tpl['plans']) > 1) { ?>
	<details class="ibox sub-compare" id="subCompare">
		<summary class="ibox-title"><h5><i class="fa fa-columns m-r-xs"></i><?php __('sub_plans_compare'); ?></h5><i class="fa fa-chevron-down sub-compare-caret"></i></summary>
		<div class="ibox-content">
			<div class="table-responsive">
			<table class="table sub-compare-table">
				<thead><tr>
					<th></th>
					<?php foreach ($tpl['plans'] as $p) { ?><th class="text-center"><?php echo pjSanitize::html($p['name']); ?></th><?php } ?>
				</tr></thead>
				<tbody>
					<tr class="sub-compare-group"><td colspan="<?php echo count($tpl['plans']) + 1; ?>"><?php __('sub_col_price'); ?></td></tr>
					<tr><td><?php __('sub_col_price'); ?></td><?php foreach ($tpl['plans'] as $p) { ?><td class="text-center"><?php echo $p['price'] === null ? '&mdash;' : ((float) $p['price'] <= 0 ? $t('sub_plan_free') : $subMoney($p['price'], $p['currency']) . ' <small class="text-muted">/ ' . $subInterval($p) . '</small>'); ?></td><?php } ?></tr>
					<tr><td><?php __('sub_trial_days'); ?></td><?php foreach ($tpl['plans'] as $p) { ?><td class="text-center"><?php echo (int) $p['trial_days'] > 0 ? (int) $p['trial_days'] : '&mdash;'; ?></td><?php } ?></tr>
					<tr class="sub-compare-group"><td colspan="<?php echo count($tpl['plans']) + 1; ?>"><?php __('sub_limits'); ?></td></tr>
					<?php foreach ($limits as $k => $icon) { ?>
					<tr><td><?php __('sub_' . $k); ?></td><?php foreach ($tpl['plans'] as $p) { ?><td class="text-center"><?php echo $subLimit($p[$k]); ?></td><?php } ?></tr>
					<?php } ?>
					<tr class="sub-compare-group"><td colspan="<?php echo count($tpl['plans']) + 1; ?>"><?php __('sub_features'); ?></td></tr>
					<?php foreach ($all_features as $f => $lbl) { ?>
					<tr><td><?php __($lbl); ?></td><?php foreach ($tpl['plans'] as $p) { ?><td class="text-center"><?php echo in_array($f, $p['feature_list'], true) ? '<i class="fa fa-check sub-yes"></i>' : '<i class="fa fa-minus sub-no"></i>'; ?></td><?php } ?></tr>
					<?php } ?>
				</tbody>
			</table>
			</div>
		</div>
	</details>
	<?php } ?>
	<?php } ?>
</div>
