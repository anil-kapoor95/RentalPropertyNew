<?php
/**
 * Dashboard of the Super Admin (pjAdmin::pjActionIndex, role 1): platform-level information only.
 * No bookings, guests, properties or other operational data of a company, and no company switcher.
 * Data (pjAdmin::platformDashboard, from pjPlatformDashboard): range {from, to, key, days}, admin_name, stats, overview,
 * trends {bucket, keys, companies, purchases, trials}, billing, activity[], transactions[], has_transactions,
 * health{gateway, email, tasks, jobs}, overall. Every figure follows the selected period, except Platform Health (now)
 * and the renewals / trial endings look-ahead (next 30 days from today).
 */
$h = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$self = $_SERVER['PHP_SELF'];
$range = $tpl['range'];
$stats = $tpl['stats'];
$bill = $tpl['billing'];
$df = !empty($tpl['option_arr']['o_date_format']) ? $tpl['option_arr']['o_date_format'] : 'd.m.Y';
$fmt = function ($ymd) use ($df) { return date($df, strtotime($ymd)); };
$overall = $tpl['overall'];
$status_label = array('ok' => 'dash_status_ok', 'warning' => 'dash_status_warning', 'error' => 'dash_status_error');

// change badge against the previous period of the same length (real data; nothing to compare = no badge)
$badge = function ($change) use ($h) {
	if ($change === null) { return ''; }
	list($dir, $pct) = $change;
	$title = $h(__('dash_vs_previous', true));
	if ($dir === 'new') { return '<span class="pd-badge is-up" title="' . $title . '"><i class="fa fa-arrow-up"></i> ' . $h(__('dash_badge_new', true)) . '</span>'; }
	$icon = $dir === 'up' ? 'fa-arrow-up' : ($dir === 'down' ? 'fa-arrow-down' : 'fa-minus');
	return '<span class="pd-badge is-' . $dir . '" title="' . $title . '"><i class="fa ' . $icon . '"></i> ' . ($pct > 0 ? '+' : '') . (int) $pct . '%</span>';
};
// money: one formatter for the whole page (currency symbols, decimals and separators from the saved settings)
$money = function ($by_currency) {
	return pjMoney::formatMany($by_currency);
};
// relative time of an activity item
$ago = function ($datetime) use ($df) {
	$sec = max(0, time() - strtotime($datetime));
	if ($sec < 60) { return __('dash_ago_now', true); }
	if ($sec < 3600) { return sprintf(__('dash_ago_minutes', true), floor($sec / 60)); }
	if ($sec < 7200) { return __('dash_ago_hour', true); }
	if ($sec < 86400) { return sprintf(__('dash_ago_hours', true), floor($sec / 3600)); }
	if ($sec < 2 * 86400) { return __('dash_ago_day', true); }
	if ($sec < 7 * 86400) { return sprintf(__('dash_ago_days', true), floor($sec / 86400)); }
	return date($df, strtotime($datetime));
};

// subscription overview: status => [label, color]; zero rows are left out of the legend
$ov_def = array(
	'active' => array('sub_status_active', '#4caf6e'),
	'trial' => array('sub_status_trial', '#F2994A'),
	'past_due' => array('sub_status_past_due', '#e5b931'),
	'cancelled' => array('sub_status_cancelled', '#9aa3ae'),
	'expired' => array('sub_status_expired', '#d4d8de'),
	'no_plan' => array('dash_status_no_plan', '#c7d2fe'),
);
$ov_total = array_sum(array_map('intval', $tpl['overview']));

$series = array(
	array('key' => 'purchases', 'label' => __('dash_series_purchases', true), 'color' => '#F97618', 'fill' => true),
	array('key' => 'companies', 'label' => __('dash_series_companies', true), 'color' => '#2563eb', 'fill' => false),
	array('key' => 'trials', 'label' => __('dash_series_trials', true), 'color' => '#16a34a', 'fill' => false),
);
// chart steps: labels on the axis, longer text in the tooltip
$mon = array_map('trim', explode(',', (string) __('dash_months_short', true)));
if (count($mon) !== 12) { $mon = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'); }
$tr = $tpl['trends'];
$tr_labels = array();
$tr_tips = array();
foreach ($tr['keys'] as $i => $k)
{
	$t = strtotime($k);
	if ($tr['bucket'] === 'month')
	{
		$tr_labels[] = $mon[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
		$tr_tips[] = $tr_labels[$i];
	} else {
		$tr_labels[] = (int) date('j', $t) . ' ' . $mon[(int) date('n', $t) - 1];
		$tr_tips[] = $tr['bucket'] === 'week' ? sprintf(__('dash_week_of', true), $fmt($k)) : $fmt($k);
	}
}
$preset_url = function ($key) use ($self) { return $self . '?controller=pjAdmin&amp;action=pjActionIndex&amp;range=' . $key; };
$rev_one = count($stats['revenue']) <= 1 && count($stats['revenue_prev']) <= 1;
$rev_cur = $stats['revenue'] ? key($stats['revenue']) : ($stats['revenue_prev'] ? key($stats['revenue_prev']) : '');
$rev_change = $rev_one && $rev_cur !== '' ? pjPlatformDashboard::change(isset($stats['revenue'][$rev_cur]) ? $stats['revenue'][$rev_cur] : 0, isset($stats['revenue_prev'][$rev_cur]) ? $stats['revenue_prev'][$rev_cur] : 0) : null;

$health_def = array(
	'gateway' => array('dash_h_gateway', 'pjAdminSubscriptions&amp;action=pjActionGateways', 'dash_h_ok_gateway'),
	'email' => array('dash_h_email', 'pjBaseOptions&amp;action=pjActionEmailSettings', 'dash_h_ok_email'),
	'tasks' => array('dash_h_tasks', 'pjBaseCron&amp;action=pjActionIndex', 'dash_h_ok_tasks'),
	'jobs' => array('dash_h_jobs', 'pjBaseCron&amp;action=pjActionIndex', 'dash_h_ok_jobs'),
);
$state_label = array('warning' => 'dash_state_warning', 'error' => 'dash_state_error', 'unknown' => 'dash_state_unknown');
$state_icon = array('ok' => 'fa-check', 'warning' => 'fa-exclamation', 'error' => 'fa-times', 'unknown' => 'fa-question');

$act_title = array('registration' => 'dash_act_t_registration', 'purchase' => 'dash_act_t_purchase', 'upgrade' => 'dash_act_t_upgrade',
	'coupon' => 'dash_act_t_coupon', 'change' => 'dash_act_t_change');
$activity = array_slice($tpl['activity'], 0, 4);

// cards: label, value (text), icon, link, tooltip (what is counted), change badge
$cards = array(
	array('dash_stat_companies', number_format((int) $stats['companies_total']), 'fa-building', 'pjAdminSubscriptions&amp;action=pjActionIndex',
		sprintf(__('dash_stat_companies_sub', true), $stats['companies_active'], $stats['companies_new']), pjPlatformDashboard::change($stats['companies_new'], $stats['companies_new_prev'])),
	array('dash_stat_subs', number_format((int) $stats['subs_active']), 'fa-credit-card', 'pjAdminSubscriptions&amp;action=pjActionIndex',
		sprintf(__('dash_stat_subs_sub', true), $stats['subs_trial'], $stats['purchases']), pjPlatformDashboard::change($stats['purchases'], $stats['purchases_prev'])),
	array('dash_stat_revenue', '', 'fa-line-chart', 'pjAdminSubscriptions&amp;action=pjActionBilling',
		$money($stats['revenue']) . ' · ' . sprintf(__('dash_stat_revenue_sub', true), $stats['purchases'], $money($stats['revenue_prev'])), $rev_change),
	array('dash_stat_plans', number_format((int) $stats['plans_available']), 'fa-clone', 'pjAdminSubscriptions&amp;action=pjActionPlans',
		sprintf(__('dash_stat_plans_sub', true), $stats['plans_total']), null),
	array('dash_stat_coupons', number_format((int) $stats['coupons_active']), 'fa-ticket', 'pjAdminSubscriptions&amp;action=pjActionPromos',
		sprintf(__('dash_stat_coupons_sub', true), $stats['coupons_total'], $stats['coupon_uses']), pjPlatformDashboard::change($stats['coupon_uses'], $stats['coupon_uses_prev'])),
);
$period_note = sprintf(__('dash_range_showing', true), $fmt($range['from']), $fmt($range['to']));
$compare_note = sprintf(__('dash_compared_with', true), $fmt($range['prev_from']), $fmt($range['prev_to']));
// custom dates shown in the form: what was asked for when it was not valid
$form_from = $range['error'] && $range['requested_from'] !== '' ? $range['requested_from'] : $range['from'];
$form_to = $range['error'] && $range['requested_to'] !== '' ? $range['requested_to'] : $range['to'];
?>
<div class="pd">
	<div class="pd-head">
		<div class="pd-hello">
			<span class="pd-role"><i class="fa fa-shield"></i> <?php __('dash_super_admin'); ?></span>
			<h1><?php echo $h(sprintf(__('dash_welcome', true), $tpl['admin_name'])); ?></h1>
			<p><?php __('dash_subtitle'); ?></p>
		</div>
		<div class="pd-tools">
			<div class="pd-tools-row">
				<a class="pd-status is-<?php echo $h($overall); ?>" href="#pdHealth"><span class="pd-dot"></span><?php __($status_label[$overall]); ?></a>
				<form class="pd-range<?php echo $range['key'] === 'custom' || $range['error'] ? ' is-custom' : ''; ?>" id="pdRange" method="get" action="<?php echo $self; ?>" data-msg-order="<?php __('dash_range_order', false, true); ?>">
					<input type="hidden" name="controller" value="pjAdmin">
					<input type="hidden" name="action" value="pjActionIndex">
					<label class="sr-only" for="pdRangeSel"><?php __('dash_range'); ?></label>
					<select class="form-control" name="range" id="pdRangeSel">
						<?php foreach (array('7' => 'dash_range_7', '30' => 'dash_range_30', '90' => 'dash_range_90', 'month' => 'dash_range_month', 'custom' => 'dash_range_custom') as $k => $lbl) { ?>
							<option value="<?php echo $k; ?>"<?php echo ($range['error'] ? 'custom' : (string) $range['key']) === (string) $k ? ' selected' : ''; ?>><?php __($lbl); ?></option>
						<?php } ?>
					</select>
					<span class="pd-range-custom">
						<input class="form-control" type="date" name="from" aria-label="<?php __('dash_range_from', false, true); ?>" value="<?php echo $h($form_from); ?>" max="<?php echo date('Y-m-d'); ?>" required>
						<input class="form-control" type="date" name="to" aria-label="<?php __('dash_range_to', false, true); ?>" value="<?php echo $h($form_to); ?>" max="<?php echo date('Y-m-d'); ?>" required>
						<button class="btn btn-primary" type="submit"><?php __('dash_apply'); ?></button>
					</span>
				</form>
			</div>
			<span class="pd-range-note" title="<?php echo $h($compare_note); ?>"><?php echo $h($period_note); ?> <span class="pd-muted">&middot; <?php echo $h($range['days'] === 1 ? __('dash_day_count', true) : sprintf(__('dash_days_count', true), $range['days'])); ?></span></span>
		</div>
	</div>

	<?php if ($range['error']) { ?>
		<div class="alert alert-warning"><i class="fa fa-exclamation-triangle m-r-xs"></i><?php __('dash_range_invalid'); ?></div>
	<?php } ?>
	<?php if (empty($stats['ready'])) { ?>
		<div class="alert alert-warning"><i class="fa fa-info-circle m-r-xs"></i><?php __('dash_not_ready'); ?></div>
	<?php } ?>

	<div class="pd-stats">
		<?php foreach ($cards as $i => $c) { ?>
		<a class="pd-stat<?php echo $i === 2 ? ' is-accent' : ''; ?>" href="<?php echo $self; ?>?controller=<?php echo $c[3]; ?>" title="<?php echo $h($c[4]); ?>">
			<span class="pd-stat-icon"><i class="fa <?php echo $c[2]; ?>"></i></span>
			<span class="pd-stat-main">
				<span class="pd-stat-label"><?php __($c[0]); ?></span>
				<?php if ($c[0] === 'dash_stat_revenue') {
					// compact: whole amount of the main currency, the exact figures are in the tooltip
					$rev_amount = $rev_cur !== '' && isset($stats['revenue'][$rev_cur]) ? (float) $stats['revenue'][$rev_cur] : 0;
					?>
				<?php
				// the amount exactly as the price format writes it (symbol, separators, decimals, position)
				list($rev_sign, $rev_number, $rev_place) = pjMoney::parts($rev_amount, $rev_cur !== '' ? $rev_cur : null);
				$rev_more = count($stats['revenue']) > 1 ? ' +' . (count($stats['revenue']) - 1) : '';
				?>
				<span class="pd-stat-value pd-stat-money<?php echo mb_strlen($rev_number) > 10 ? ' is-xlong' : (mb_strlen($rev_number) > 6 ? ' is-long' : ''); ?>"><?php if ($rev_place === 'front') { ?><small class="pd-cur is-front"><?php echo $h($rev_sign); ?></small><?php } ?><?php echo $h($rev_number); ?><?php if ($rev_place !== 'front') { ?><small class="pd-cur"><?php echo $h($rev_sign); ?></small><?php } ?><?php if ($rev_more !== '') { ?><small class="pd-cur"><?php echo $h($rev_more); ?></small><?php } ?></span>
				<?php } else { ?>
				<span class="pd-stat-value"><?php echo htmlspecialchars((string) $c[1], ENT_QUOTES, 'UTF-8'); ?></span>
				<?php } ?>
			</span>
			<?php echo $badge($c[5]); ?>
			<svg class="pd-stat-wave" viewBox="0 0 200 40" preserveAspectRatio="none" aria-hidden="true"><path d="M0 30 C40 10 70 38 110 22 S170 8 200 18 L200 40 L0 40 Z"/></svg>
		</a>
		<?php } ?>
	</div>

	<div class="pd-grid-2">
		<div class="pd-card">
			<div class="pd-card-head"><h3><i class="fa fa-bar-chart"></i><?php __('dash_overview_title'); ?></h3><span class="pd-head-note"><?php echo $h(sprintf(__('dash_as_of', true), $fmt($range['to']))); ?></span></div>
			<div class="pd-card-body">
				<?php if ($ov_total === 0) { ?>
					<div class="pd-empty"><i class="fa fa-pie-chart"></i><?php __('dash_overview_empty'); ?></div>
				<?php } else { ?>
				<div class="pd-overview">
					<div class="pd-donut" id="pdDonut" data-label="<?php __('dash_overview_title', false, true); ?>">
						<div class="pd-donut-center"><strong><?php echo $ov_total; ?></strong><span><?php __('dash_overview_total'); ?></span></div>
					</div>
					<ul class="pd-legend">
						<?php foreach ($ov_def as $k => $d) {
							$val = (int) $tpl['overview'][$k];
							if ($val === 0) { continue; }
							?>
							<li data-value="<?php echo $val; ?>" data-color="<?php echo $d[1]; ?>">
								<span class="pd-swatch" style="background:<?php echo $d[1]; ?>"></span>
								<span class="pd-legend-name"><?php __($d[0]); ?></span>
								<span class="pd-legend-val"><?php echo $val; ?> <small>(<?php echo round(100 * $val / $ov_total, 1); ?>%)</small></span>
							</li>
						<?php } ?>
					</ul>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="pd-card">
			<div class="pd-card-head">
				<h3><i class="fa fa-line-chart"></i><?php __('dash_trends_title'); ?></h3>
				<div class="pd-seg" id="pdTrendSeg" role="group" aria-label="<?php __('dash_trends_title', false, true); ?>">
					<?php // presets of the page filter: the whole page follows them, not only the chart
					foreach (array('7' => 'dash_7d', '30' => 'dash_30d', '90' => 'dash_90d') as $d => $lbl) {
						$on = !$range['error'] && (string) $range['key'] === (string) $d; ?>
						<a href="<?php echo $preset_url($d); ?>" class="<?php echo $on ? 'active' : ''; ?>"<?php echo $on ? ' aria-current="true"' : ''; ?>><?php __($lbl); ?></a>
					<?php } ?>
				</div>
			</div>
			<div class="pd-card-body">
				<div class="pd-chart" id="pdTrends" data-label="<?php __('dash_trends_title', false, true); ?>"><div class="pd-chart-tip"></div></div>
				<div class="pd-series">
					<?php foreach ($series as $s) { ?><span><i style="background:<?php echo $s['color']; ?>"></i><?php echo $h($s['label']); ?> <strong data-pd-total="<?php echo $s['key']; ?>">0</strong></span><?php } ?>
					<span class="pd-series-period"><?php echo $h($period_note); ?><?php echo $tr['bucket'] !== 'day' ? ' · ' . $h(__($tr['bucket'] === 'week' ? 'dash_per_week' : 'dash_per_month', true)) : ''; ?></span>
				</div>
			</div>
		</div>
	</div>

	<?php if (!empty($bill['ready'])) {
		$rev_change = null;
		if (count($bill['revenue']) <= 1 && count($bill['revenue_prev']) <= 1)
		{
			$cur = key($bill['revenue']) ?: key($bill['revenue_prev']);
			$rev_change = pjPlatformDashboard::change(isset($bill['revenue'][$cur]) ? $bill['revenue'][$cur] : 0, isset($bill['revenue_prev'][$cur]) ? $bill['revenue_prev'][$cur] : 0);
		}
		?>
	<div class="pd-card pd-bill">
		<div class="pd-card-head">
			<h3><i class="fa fa-file-text-o"></i><?php __('dash_billing_title'); ?> <small class="pd-head-note"><?php echo $h($period_note); ?></small></h3>
			<a class="pd-head-link" href="<?php echo $self; ?>?controller=pjAdminSubscriptions&amp;action=pjActionBilling"><?php __('dash_view_all'); ?> <i class="fa fa-arrow-right"></i></a>
		</div>
		<div class="pd-card-body">
			<?php
			// four figures of the period: icon, label, value, tone, link
			$bill_tiles = array(
				array('fa-money', 'dash_bill_revenue', $money($bill['revenue']), 'is-accent', '', $badge($rev_change)),
				array('fa-check-circle', 'dash_bill_paid', (string) (int) $bill['paid'], 'is-good', '', ''),
				array('fa-clock-o', 'dash_bill_open_failed', (int) $bill['pending'] . ' / ' . (int) $bill['failed'], $bill['pending'] + $bill['failed'] > 0 ? 'is-warn' : '', 'pjAdminSubscriptions&amp;action=pjActionBilling', ''),
				array('fa-exclamation-triangle', 'dash_bill_past_due', (string) (int) $bill['past_due'], $bill['past_due'] > 0 ? 'is-bad' : '', 'pjAdminSubscriptions&amp;action=pjActionIndex', ''),
			);
			?>
			<div class="pd-bill-tiles">
				<?php foreach ($bill_tiles as $bt) {
					$tag = $bt[4] !== '' ? 'a' : 'div';
					?>
				<<?php echo $tag; ?> class="pd-bill-tile <?php echo $bt[3]; ?>"<?php echo $bt[4] !== '' ? ' href="' . $self . '?controller=' . $bt[4] . '"' : ''; ?>>
					<span class="pd-bill-icon"><i class="fa <?php echo $bt[0]; ?>"></i></span>
					<span class="pd-bill-main">
						<span class="pd-bill-label"><?php __($bt[1]); ?></span>
						<span class="pd-bill-value"><?php echo htmlspecialchars((string) $bt[2], ENT_QUOTES, 'UTF-8'); ?></span>
					</span>
					<?php echo $bt[5]; ?>
				</<?php echo $tag; ?>>
				<?php } ?>
			</div>

			<div class="pd-bill-upcoming">
				<div class="pd-bill-uphead">
					<p class="pd-sub-title"><?php __('dash_bill_upcoming'); ?> <small class="pd-muted"><?php __('dash_from_today'); ?></small></p>
					<div class="pd-bill-sums">
						<span class="pd-bill-sum is-good" title="<?php __('dash_bill_renewals_tip', false, true); ?>">
							<span class="pd-bill-sum-label"><?php __('dash_bill_tag_renewal'); ?> <b><?php echo (int) $bill['renewals']; ?></b></span>
							<span class="pd-bill-sum-value"><?php echo $h($money($bill['renewals_amount'])); ?></span>
						</span>
						<span class="pd-bill-sum is-info" title="<?php __('dash_bill_trials_tip', false, true); ?>">
							<span class="pd-bill-sum-label"><?php __('dash_bill_tag_trial'); ?> <b><?php echo (int) $bill['trials_ending']; ?></b></span>
							<span class="pd-bill-sum-value"><?php echo $h(sprintf(__('dash_bill_may_convert', true), $money($bill['trials_amount']))); ?></span>
						</span>
					</div>
				</div>
				<?php if (empty($bill['renewals_list'])) { ?>
					<div class="pd-empty"><i class="fa fa-calendar-check-o"></i><?php __('dash_bill_no_renewals'); ?></div>
				<?php } else { ?>
				<ul class="pd-bill-list">
					<?php foreach ($bill['renewals_list'] as $r) {
						$rt = strtotime($r['date']);
						$is_trial = $r['status'] === 'trial';
						?>
					<li>
						<span class="pd-date-chip" title="<?php echo $h($fmt($r['date'])); ?>"><b><?php echo (int) date('j', $rt); ?></b><small><?php echo $h($mon[(int) date('n', $rt) - 1]); ?></small></span>
						<span class="pd-bill-who"><strong><?php echo $h($r['company']); ?></strong><small><?php echo $h($r['plan']); ?></small></span>
						<span class="pd-pill <?php echo $is_trial ? 's-info' : 's-good'; ?>"><?php __($is_trial ? 'dash_bill_trial_ends_on' : 'dash_bill_renews_on'); ?></span>
						<span class="pd-bill-amount"><?php echo $h($r['amount'] !== '' ? $r['amount'] : '—'); ?><?php if ($is_trial && $r['amount'] !== '') { ?><small><?php __('dash_bill_if_bought'); ?></small><?php } ?></span>
					</li>
					<?php } ?>
				</ul>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php } ?>

	<div class="pd-grid-3">
		<div class="pd-card">
			<div class="pd-card-head"><h3><i class="fa fa-cog"></i><?php __('dash_mgmt_title'); ?></h3></div>
			<div class="pd-card-body pd-tight">
				<a class="pd-link" href="<?php echo $self; ?>?controller=pjAdminSubscriptions&amp;action=pjActionPlans">
					<span class="pd-link-icon"><i class="fa fa-clone"></i></span>
					<span><strong><?php __('dash_mgmt_plans'); ?></strong><small><?php __('dash_mgmt_plans_desc'); ?></small></span>
					<i class="fa fa-angle-right"></i>
				</a>
				<a class="pd-link" href="<?php echo $self; ?>?controller=pjAdminSubscriptions&amp;action=pjActionPromos">
					<span class="pd-link-icon is-accent"><i class="fa fa-ticket"></i></span>
					<span><strong><?php __('dash_mgmt_promos'); ?></strong><small><?php __('dash_mgmt_promos_desc'); ?></small></span>
					<i class="fa fa-angle-right"></i>
				</a>
				<a class="pd-link" href="<?php echo $self; ?>?controller=pjBaseOptions&amp;action=pjActionPlatformGeneral">
					<span class="pd-link-icon"><i class="fa fa-cogs"></i></span>
					<span><strong><?php __('dash_mgmt_settings'); ?></strong><small><?php __('dash_mgmt_settings_desc'); ?></small></span>
					<i class="fa fa-angle-right"></i>
				</a>
			</div>
		</div>

		<div class="pd-card">
			<div class="pd-card-head">
				<h3><i class="fa fa-clock-o"></i><?php __('dash_activity_title'); ?></h3>
				<a class="pd-head-link" href="<?php echo $self; ?>?controller=pjAdminSubscriptions&amp;action=pjActionBilling"><?php __('dash_view_all'); ?> <i class="fa fa-arrow-right"></i></a>
			</div>
			<div class="pd-card-body pd-tight">
				<?php if (empty($activity)) { ?>
					<div class="pd-empty"><i class="fa fa-history"></i><?php __('dash_activity_empty'); ?></div>
				<?php } else { ?>
				<ul class="pd-timeline">
					<?php foreach ($activity as $a) {
						if ($a['type'] === 'coupon') {
							$sub = sprintf(__('dash_act_s_coupon', true), $a['code'], $a['company']);
						} elseif ($a['type'] === 'registration') {
							$sub = $a['company'];
						} else {
							$sub = $a['company'] . ($a['plan'] !== '' ? ' · ' . $a['plan'] : '');
						}
						?>
						<li class="t-<?php echo $h($a['type']); ?>">
							<span class="pd-tl-main"><strong><?php __($act_title[$a['type']]); ?></strong><small><?php echo $h($sub); ?></small></span>
							<span class="pd-tl-time" title="<?php echo $h(date($df . ', H:i', strtotime($a['date']))); ?>"><?php echo $h($ago($a['date'])); ?></span>
						</li>
					<?php } ?>
				</ul>
				<?php } ?>
			</div>
		</div>

		<div class="pd-card" id="pdHealth">
			<div class="pd-card-head"><h3><i class="fa fa-heartbeat"></i><?php __('dash_health_title'); ?></h3></div>
			<div class="pd-card-body pd-tight">
				<ul class="pd-health">
					<?php foreach ($health_def as $k => $d) {
						$c = isset($tpl['health'][$k]) ? $tpl['health'][$k] : array('state' => 'unknown', 'detail' => '');
						$tip = $c['detail'] . (!empty($c['items']) ? ' (' . implode(', ', $c['items']) . ')' : '');
						?>
						<li>
							<a href="<?php echo $self; ?>?controller=<?php echo $d[1]; ?>" title="<?php echo $h($tip); ?>">
								<span class="pd-health-icon s-<?php echo $h($c['state']); ?>"><i class="fa <?php echo $state_icon[$c['state']]; ?>"></i></span>
								<span class="pd-health-main"><strong><?php __($d[0]); ?></strong><small><?php echo $h($c['detail']); ?></small></span>
								<span class="pd-health-state s-<?php echo $h($c['state']); ?>"><?php __($c['state'] === 'ok' ? $d[2] : $state_label[$c['state']]); ?></span>
							</a>
						</li>
					<?php } ?>
				</ul>
			</div>
		</div>
	</div>
	<?php
	// latest billing transactions of the platform: only when there are any
	$bh_rows = isset($tpl['transactions']) ? $tpl['transactions'] : array();
	$bh_show_empty = !empty($tpl['has_transactions']);
	$bh_note = $period_note;
	$bh_show_company = true;
	$bh_view_all = $self . '?controller=pjAdminSubscriptions&amp;action=pjActionBilling';
	include PJ_VIEWS_PATH . 'pjAdmin/elements/billing-history.php';
	?>
</div>
<script>
window.pjPlatformDash = <?php echo json_encode(array(
	'labels' => $tr_labels,
	'tips' => $tr_tips,
	'companies' => $tr['companies'],
	'trials' => $tr['trials'],
	'purchases' => $tr['purchases'],
	'series' => $series,
), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
