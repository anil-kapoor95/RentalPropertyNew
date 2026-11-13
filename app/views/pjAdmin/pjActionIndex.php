<?php
/**
 * Dashboard of the companies (pjAdmin::pjActionIndex for every role except the Super Admin):
 * the active company's bookings of today and this month, its properties and users, today's arrivals / departures,
 * the latest bookings and (company login, Companies Admin) the company's billing overview.
 */
$h = function ($s) { return pjSanitize::html((string) $s); };
$self = $_SERVER['PHP_SELF'];
$df = !empty($tpl['option_arr']['o_date_format']) ? $tpl['option_arr']['o_date_format'] : 'd.m.Y';
$tf = !empty($tpl['option_arr']['o_time_format']) ? $tpl['option_arr']['o_time_format'] : 'H:i';
$fmt = function ($ymd) use ($df) { return $ymd ? date($df, strtotime($ymd)) : '—'; };
$reservation_statuses = __('reservation_statuses', true);
$has_reservations = pjAuth::factory('pjAdminReservations', 'pjActionIndex')->hasAccess();
$has_update_booking = pjAuth::factory('pjAdminReservations', 'pjActionUpdate')->hasAccess();
$bookingUrl = function ($id) use ($self, $has_update_booking) {
	return $has_update_booking ? $self . '?controller=pjAdminReservations&amp;action=pjActionUpdate&amp;id=' . (int) $id : '';
};
$badge = function ($change) use ($h) {
	if ($change === null) { return ''; }
	list($dir, $pct) = $change;
	$title = $h(__('dash_vs_last_month', true));
	if ($dir === 'new') { return '<span class="pd-badge is-up" title="' . $title . '"><i class="fa fa-arrow-up"></i> ' . $h(__('dash_badge_new', true)) . '</span>'; }
	$icon = $dir === 'up' ? 'fa-arrow-up' : ($dir === 'down' ? 'fa-arrow-down' : 'fa-minus');
	return '<span class="pd-badge is-' . $dir . '" title="' . $title . '"><i class="fa ' . $icon . '"></i> ' . ($pct > 0 ? '+' : '') . (int) $pct . '%</span>';
};
$bill = isset($tpl['billing']) ? $tpl['billing'] : null;
$limit = function ($used, $max) {
	return $max === null ? (int) $used . ' / ' . __('sub_unlimited', true) : (int) $used . ' / ' . (int) $max;
};
$cards = array(
	array('dash_new_reservations', (int) $tpl['cnt_bookings_today'], 'fa-calendar-plus-o', __('dash_today', true) . ' · ' . pjMoney::format($tpl['total_amount_today']), null),
	array('dash_total_reservations', (int) $tpl['cnt_bookings_this_month'], 'fa-calendar-check-o', __('dash_this_month', true) . ' · ' . pjMoney::format($tpl['total_amount_this_month']),
		pjPlatformDashboard::change($tpl['cnt_bookings_this_month'], $tpl['cnt_bookings_prev_month'])),
	array('lblDashProperties', (int) $tpl['cnt_calendars'], 'fa-home', '', null),
	array('lblDashUsers', (int) $tpl['cnt_users'], 'fa-users', '', null),
);
$latest = array_slice($tpl['latest_reservation_arr'], 0, 5);
$sub_status_labels = array('trial' => 'sub_status_trial', 'active' => 'sub_status_active', 'past_due' => 'sub_status_past_due', 'cancelled' => 'sub_status_cancelled', 'expired' => 'sub_status_expired');
$pay_def = array(
	'paid' => array('dash_pay_paid', 's-good'), 'free' => array('dash_pay_free', 's-good'), 'trial' => array('dash_pay_trial', 's-info'),
	'pending' => array('dash_pay_pending', 's-warn'), 'failed' => array('dash_pay_failed', 's-bad'), 'overdue' => array('dash_pay_overdue', 's-bad'),
	'ended' => array('dash_pay_ended', 's-bad'), 'none' => array('dash_pay_none', ''),
);
$status_class = array('active' => 's-good', 'trial' => 's-info', 'past_due' => 's-warn', 'cancelled' => 's-bad', 'expired' => 's-bad');
// quick actions: only what the user may do (permissions) and the plan allows (properties)
$cid = (int) $controller->getCompanyId();
$quick = array();
if (pjAuth::factory('pjAdminReservations', 'pjActionCreate')->hasAccess())
{
	$quick[] = array('dash_qa_reservation', $self . '?controller=pjAdminReservations&amp;action=pjActionCreate', 'fa-plus', true);
}
if (pjAuth::factory('pjAdminReservations', 'pjActionSchedule')->hasAccess())
{
	$quick[] = array('dash_qa_schedule', $self . '?controller=pjAdminReservations&amp;action=pjActionSchedule', 'fa-calendar', false);
}
if (pjAuth::factory('pjAdminCalendars', 'pjActionCreate')->hasAccess() && (!class_exists('pjEntitlement') || pjEntitlement::canAddProperty($cid)))
{
	$quick[] = array('dash_qa_property', $self . '?controller=pjAdminCalendars&amp;action=pjActionCreate', 'fa-home', false);
}
// plan limits reached (company login / Companies Admin see the billing card)
$limits_reached = array();
if ($bill !== null && $bill['source'] === 'plan' && $bill['entitled'])
{
	foreach (array('properties' => 'max_properties', 'users' => 'max_users') as $u => $l)
	{
		if ($bill['limits'][$l] !== null && (int) $bill['usage'][$u] >= (int) $bill['limits'][$l])
		{
			$limits_reached[] = __('sub_col_' . $u, true);
		}
	}
}
$today_lists = array(
	array('dash_reservations_arriving_today', $tpl['arriving_today_arr'], 'fa-sign-in'),
	array('dash_reservations_leaving_today', $tpl['leaving_today_arr'], 'fa-sign-out'),
);
?>
<div class="pd">
	<div class="pd-head">
		<div class="pd-hello">
			<span class="pd-role"><i class="fa fa-building-o"></i> <?php echo $h($tpl['dash_company_name'] !== '' ? $tpl['dash_company_name'] : __('dash_company', true)); ?></span>
			<h1><?php echo $h(sprintf(__('dash_welcome', true), $tpl['dash_user_name'])); ?></h1>
			<p><?php echo $h(sprintf(__('dash_company_subtitle', true), date($df))); ?></p>
		</div>
		<?php if (!empty($quick)) { ?>
		<div class="pd-quick">
			<?php foreach ($quick as $q) { ?>
				<a class="btn <?php echo $q[3] ? 'btn-primary' : 'btn-white'; ?>" href="<?php echo $q[1]; ?>"><i class="fa <?php echo $q[2]; ?> m-r-xs"></i><?php __($q[0]); ?></a>
			<?php } ?>
		</div>
		<?php } ?>
	</div>

	<div class="pd-stats is-4">
		<?php foreach ($cards as $i => $c) { ?>
		<div class="pd-stat<?php echo $i === 0 ? ' is-accent' : ''; ?>">
			<span class="pd-stat-icon"><i class="fa <?php echo $c[2]; ?>"></i></span>
			<span class="pd-stat-main">
				<span class="pd-stat-label"><?php __($c[0]); ?></span>
				<span class="pd-stat-value"><?php echo number_format($c[1]); ?></span>
				<?php if ($c[3] !== '') { ?><span class="pd-stat-sub"><?php echo $h($c[3]); ?></span><?php } ?>
			</span>
			<?php echo $badge($c[4]); ?>
			<svg class="pd-stat-wave" viewBox="0 0 200 40" preserveAspectRatio="none" aria-hidden="true"><path d="M0 30 C40 10 70 38 110 22 S170 8 200 18 L200 40 L0 40 Z"/></svg>
		</div>
		<?php } ?>
	</div>

	<div class="<?php echo $bill !== null ? 'pd-grid-2 is-even' : ''; ?>">
		<?php if ($bill !== null) { ?>
		<div class="pd-card">
			<div class="pd-card-head">
				<h3><i class="fa fa-file-text-o"></i><?php __('dash_billing_title'); ?></h3>
				<?php if (!empty($tpl['dash_can_manage_plan'])) { ?>
					<a class="pd-head-link" href="<?php echo $self; ?>?controller=pjAdminSubscriptions&amp;action=pjActionMyPlan"><?php __('dash_manage_plan'); ?> <i class="fa fa-arrow-right"></i></a>
				<?php } ?>
			</div>
			<div class="pd-card-body">
				<?php if ($bill['source'] === 'admin') { ?>
					<p class="pd-muted m-b-none"><?php __('dash_bill_admin_workspace'); ?></p>
				<?php } elseif ($bill['source'] === 'unassigned') { ?>
					<div class="pd-plan"><span class="pd-plan-name"><?php __('sub_no_plan'); ?></span></div>
					<p class="pd-muted m-b-none"><?php __($bill['entitled'] ? 'sub_unassigned_full_msg' : 'sub_unassigned_restricted_msg'); ?></p>
				<?php } else {
					$pd = $pay_def[$bill['payment']];
					?>
					<div class="pd-plan">
						<span class="pd-plan-name"><?php echo $bill['plan'] ? $h($bill['plan']['name']) : $h(__('sub_no_plan', true)); ?></span>
						<?php if ($bill['status']) { ?><span class="pd-pill <?php echo $status_class[$bill['status']]; ?>"><?php __($sub_status_labels[$bill['status']]); ?></span><?php } ?>
					</div>
					<dl class="pd-facts">
						<div><dt><?php __('dash_pay_status'); ?></dt><dd><span class="pd-pill <?php echo $pd[1]; ?>"><?php __($pd[0]); ?></span></dd></div>
						<div><dt><?php __($bill['status'] === 'trial' ? 'sub_trial_ends' : ($bill['cancel_at_end'] || !in_array($bill['status'], array('active', 'past_due'), true) ? 'dash_bill_ends' : 'dash_bill_next')); ?></dt>
							<dd><?php echo $bill['next_billing'] || $bill['ends_at'] ? $h($fmt($bill['next_billing'] ? $bill['next_billing'] : $bill['ends_at'])) : $h(__('dash_bill_not_needed', true)); ?></dd></div>
						<div><dt><?php __('dash_bill_price'); ?></dt>
							<dd><?php if ($bill['price'] !== '') { echo $h($bill['price']); ?> <small class="pd-muted"><?php echo $bill['interval'][0] === 1 ? $h(__($bill['interval'][1] === 'year' ? 'dash_bill_per_year' : 'dash_bill_per_month', true)) : $h(sprintf(__($bill['interval'][1] === 'year' ? 'dash_bill_every_year' : 'dash_bill_every_month', true), $bill['interval'][0])); ?></small><?php } else { __('dash_pay_free'); } ?></dd></div>
						<div><dt><?php __('dash_bill_last_payment'); ?></dt>
							<dd><?php echo $bill['last_payment'] ? $h($fmt($bill['last_payment']['date']) . ' · ' . $bill['last_payment']['amount']) : '—'; ?></dd></div>
						<?php foreach (array('properties' => 'max_properties', 'users' => 'max_users') as $u => $l) {
							$max = $bill['limits'][$l];
							$used = (int) $bill['usage'][$u];
							?>
						<div><dt><?php __('sub_col_' . $u); ?></dt><dd><?php echo $h($limit($used, $max)); ?>
							<?php if ($max !== null) { ?><div class="pd-meter"><i style="width:<?php echo $max > 0 ? min(100, round($used * 100 / $max)) : 100; ?>%"></i></div><?php } ?></dd></div>
						<?php } ?>
					</dl>
					<?php if (!empty($limits_reached)) { ?>
						<p class="pd-note"><i class="fa fa-info-circle m-r-xs"></i><?php echo $h(sprintf(__('dash_bill_limit_reached', true), implode(', ', $limits_reached))); ?>
						<?php if (!empty($tpl['dash_can_manage_plan'])) { ?> <a href="<?php echo $self; ?>?controller=pjAdminSubscriptions&amp;action=pjActionMyPlan"><?php __('dash_bill_see_plans'); ?></a><?php } ?></p>
					<?php } ?>
					<?php if ($bill['pending_plan'] !== '') { ?>
						<p class="pd-note"><?php echo $h(sprintf(__('dash_bill_scheduled', true), $bill['pending_plan'], $fmt($bill['ends_at']))); ?></p>
					<?php } elseif ($bill['cancel_at_end']) { ?>
						<p class="pd-note"><?php echo $h(sprintf(__('dash_bill_cancels', true), $fmt($bill['ends_at']))); ?></p>
					<?php } elseif ($bill['payment'] === 'overdue') { ?>
						<p class="pd-note is-bad"><?php echo $h(sprintf(__('dash_bill_overdue', true), $fmt($bill['grace_ends_at']))); ?></p>
					<?php } elseif ($bill['payment'] === 'ended') { ?>
						<p class="pd-note is-bad"><?php __('sub_expired_msg'); ?></p>
					<?php } elseif ($bill['payment'] === 'failed') { ?>
						<p class="pd-note is-bad"><?php __('dash_bill_failed_msg'); ?></p>
					<?php } ?>
				<?php } ?>
			</div>
		</div>
		<?php } ?>

		<div class="pd-card">
			<div class="pd-card-head">
				<h3><i class="fa fa-sun-o"></i><?php __('dash_today_title'); ?></h3>
				<?php if ($has_reservations) { ?><a class="pd-head-link" href="<?php echo $self; ?>?controller=pjAdminReservations&amp;action=pjActionIndex"><?php __('dash_view_all'); ?> <i class="fa fa-arrow-right"></i></a><?php } ?>
			</div>
			<div class="pd-card-body">
				<div class="pd-today">
					<?php foreach ($today_lists as $t) { ?>
					<div class="pd-kpi"><span class="pd-kpi-label"><i class="fa <?php echo $t[2]; ?> m-r-xs"></i><?php __($t[0]); ?></span><span class="pd-kpi-value"><?php echo count($t[1]); ?></span></div>
					<?php } ?>
				</div>
				<?php
				$rows = array();
				foreach ($today_lists as $ti => $t) { foreach ($t[1] as $r) { $rows[] = array($ti, $r); } }
				if (empty($rows)) { ?>
					<p class="pd-muted m-t-sm m-b-none"><?php __('dash_today_none'); ?></p>
				<?php } else { ?>
				<ul class="pd-rows">
					<?php foreach (array_slice($rows, 0, 4) as $row) {
						list($ti, $r) = $row;
						$url = $bookingUrl($r['id']);
						?>
					<li>
						<span class="pd-row-main"><strong><?php if ($url) { ?><a href="<?php echo $url; ?>"><?php } echo $h($r['c_name']); if ($url) { ?></a><?php } ?></strong><small><?php echo $h($r['calendar_name']); ?></small></span>
						<span class="pd-row-side"><strong><?php __($ti === 0 ? 'dash_arrives' : 'dash_leaves'); ?></strong><small><?php echo (int) $r['nights']; ?> <?php __('lblDashNights'); ?></small></span>
					</li>
					<?php } ?>
				</ul>
				<?php if (count($rows) > 4) { ?><p class="pd-muted m-b-none m-t-xs"><?php echo $h(sprintf(__('dash_and_more', true), count($rows) - 4)); ?></p><?php } ?>
				<?php } ?>
			</div>
		</div>
	</div>

	<div class="pd-card">
		<div class="pd-card-head">
			<h3><i class="fa fa-clock-o"></i><?php __('lblDashLatestReservations'); ?></h3>
			<?php if ($has_reservations) { ?><a class="pd-head-link" href="<?php echo $self; ?>?controller=pjAdminReservations&amp;action=pjActionIndex"><?php __('dash_view_all'); ?> <i class="fa fa-arrow-right"></i></a><?php } ?>
		</div>
		<div class="pd-card-body pd-tight">
			<?php if (empty($latest)) { ?>
				<div class="pd-empty"><i class="fa fa-calendar-o"></i><?php __('dashboard_reservations_empty'); ?></div>
			<?php } else { ?>
			<ul class="pd-rows">
				<?php foreach ($latest as $r) {
					$url = $bookingUrl($r['id']);
					?>
				<li>
					<span class="pd-row-main">
						<strong><?php if ($url) { ?><a href="<?php echo $url; ?>"><?php } echo $h($r['c_name']); if ($url) { ?></a><?php } ?></strong>
						<small><?php echo $h($r['calendar_name']); ?> &middot; <?php echo $h($fmt($r['date_from']) . ' – ' . $fmt($r['date_to'])); ?> &middot; <?php echo (int) $r['nights']; ?> <?php __('lblDashNights'); ?></small>
					</span>
					<span class="pd-row-side">
						<span class="pd-status-badge s-<?php echo $h($r['status']); ?>"><?php echo $h(isset($reservation_statuses[$r['status']]) ? $reservation_statuses[$r['status']] : $r['status']); ?></span>
						<small><?php echo $h(date($df . ', ' . $tf, strtotime($r['created']))); ?></small>
					</span>
				</li>
				<?php } ?>
			</ul>
			<?php } ?>
		</div>
	</div>
	<?php
	// the company's latest billing transactions (only when there are any); full history on My plan (company login)
	$bh_rows = isset($tpl['transactions']) ? $tpl['transactions'] : array();
	$bh_show_company = false;
	$bh_view_all = !empty($tpl['dash_can_manage_plan']) ? $self . '?controller=pjAdminSubscriptions&amp;action=pjActionMyPlan' : '';
	include PJ_VIEWS_PATH . 'pjAdmin/elements/billing-history.php';
	?>
</div>
