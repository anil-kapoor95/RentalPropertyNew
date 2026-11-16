<?php
/**
 * Super Admin dashboard (pjAdmin::pjActionIndex, role 1): platform-level information only — revenue, companies,
 * subscriptions, plans, payments, renewals, health and billing. No bookings, guests, properties or other operational
 * data of a company, and no company switcher.
 *
 * The page is drawn by platform-dashboard.js from $tpl['payload'] (pjAdmin::platformPayload, real data). Changing the
 * period (7D / 30D / 90D / 12M / custom) loads the same payload from pjAdmin::pjActionPlatformData without a reload;
 * the URL keeps the period so the page can be bookmarked and reloaded.
 */
$h = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$self = $_SERVER['PHP_SELF'];
$payload = $tpl['payload'];
$range = $payload['range'];
$today = date('Y-m-d');
$is_custom = $range['key'] === 'custom' || !empty($range['error']);

// texts for the script (labels of the back office, so they can be translated)
$t_keys = array(
	'kpi_revenue' => 'dash_k_revenue', 'kpi_companies' => 'dash_k_companies', 'kpi_subs' => 'dash_k_subs', 'kpi_mrr' => 'dash_k_mrr', 'kpi_conv' => 'dash_k_conv',
	'vs_prev' => 'dash_k_vs_prev', 'new_n' => 'dash_k_new_n', 'left_n' => 'dash_k_left_n', 'trial_n' => 'dash_k_trial_n', 'paying_n' => 'dash_k_paying_n',
	'conv_sub' => 'dash_k_conv_sub', 'no_trials' => 'dash_k_no_trials', 'badge_new' => 'dash_badge_new',
	'by_plan' => 'dash_rev_by_plan', 'by_kind' => 'dash_rev_by_kind', 'average_per' => 'dash_rev_average', 'total' => 'dash_total', 'other_currency' => 'dash_rev_other_currency',
	'no_revenue' => 'dash_rev_none', 'new_companies' => 'dash_series_companies', 'left' => 'dash_g_left', 'net' => 'dash_g_net',
	'companies' => 'dash_overview_total', 'mrr_short' => 'dash_mix_mrr', 'mrr_total' => 'dash_mix_mrr_total', 'mrr_total_sub' => 'dash_mix_mrr_sub', 'free' => 'dash_pay_free',
	'on_trial' => 'dash_mix_on_trial', 'ended' => 'dash_mix_ended', 'ended_sub' => 'dash_mix_ended_sub', 'no_plan' => 'dash_status_no_plan', 'no_plan_sub' => 'dash_mix_no_plan_sub',
	'no_companies' => 'dash_overview_empty',
	'f_signups' => 'dash_f_signups', 'f_trials' => 'dash_f_trials', 'f_paying' => 'dash_f_paying', 'f_renewed' => 'dash_f_renewed', 'f_of_prev' => 'dash_f_of_prev',
	'f_note' => 'dash_f_note', 'f_none' => 'dash_f_none',
	'paid' => 'dash_bill_paid_short', 'failed' => 'dash_bill_failed_short', 'open' => 'dash_bill_open_short', 'successful' => 'dash_pay_successful', 'no_payments' => 'dash_pay_none_period',
	'past_due' => 'dash_bill_past_due', 'renewals' => 'dash_bill_tag_renewal', 'trials_ending' => 'dash_bill_tag_trial', 'if_they_buy' => 'dash_bill_may_convert',
	'if_bought' => 'dash_bill_if_bought', 'renews' => 'dash_bill_renews_on', 'trial_ends' => 'dash_bill_trial_ends_on', 'no_renewals' => 'dash_bill_no_renewals',
	'no_billing' => 'dash_bh_empty', 'no_activity' => 'dash_activity_empty', 'loading' => 'dash_loading', 'load_error' => 'dash_load_error',
	'err_both' => 'dash_range_err_both', 'err_order' => 'dash_range_order', 'err_future' => 'dash_range_err_future',
	'csv_period' => 'dash_csv_period', 'csv_metric' => 'dash_csv_metric', 'csv_value' => 'dash_csv_value',
);
$t = array();
foreach ($t_keys as $k => $key) { $t[$k] = __($key, true); }
?>
<div class="sa" id="saDash" data-endpoint="<?php echo $h($self . '?controller=pjAdmin&action=pjActionPlatformData'); ?>" data-page="<?php echo $h($self . '?controller=pjAdmin&action=pjActionIndex'); ?>">
	<div class="sa-top">
		<div class="sa-hello">
			<span class="sa-role"><i class="fa fa-shield"></i> <?php __('dash_super_admin'); ?></span>
			<h1><?php echo $h(sprintf(__('dash_welcome', true), $tpl['admin_name'])); ?></h1>
			<p><?php __('dash_glance'); ?> · <span data-sa="rangeLabel"><?php echo $h($range['label']); ?></span> <span class="sa-muted" data-sa="daysLabel">(<?php echo $h($range['days_label']); ?>)</span></p>
		</div>
		<div class="sa-tools">
			<a class="sa-status is-<?php echo $h($payload['overall']); ?>" href="#saHealth" data-sa="status"><i></i><span><?php echo $h($payload['overall_label']); ?></span></a>
			<div class="sa-seg" role="group" aria-label="<?php __('dash_range', false, true); ?>" id="saPeriod">
				<?php foreach (array('7' => 'dash_7d', '30' => 'dash_30d', '90' => 'dash_90d', '12m' => 'dash_12m') as $k => $lbl) { ?>
					<button type="button" data-p="<?php echo $k; ?>" aria-pressed="<?php echo !$is_custom && (string) $range['key'] === (string) $k ? 'true' : 'false'; ?>"><?php __($lbl); ?></button>
				<?php } ?>
				<button type="button" data-p="custom" id="saCustomBtn" aria-haspopup="dialog" aria-expanded="false" aria-pressed="<?php echo $is_custom || $range['key'] === 'month' ? 'true' : 'false'; ?>"><i class="fa fa-calendar"></i> <?php __('dash_range_custom'); ?></button>
			</div>
			<div class="sa-pop" id="saPop" role="dialog" aria-label="<?php __('dash_range_custom', false, true); ?>" hidden>
				<div class="sa-pop-quick">
					<button type="button" data-q="month"><?php __('dash_q_this_month'); ?></button>
					<button type="button" data-q="lastmonth"><?php __('dash_q_last_month'); ?></button>
					<button type="button" data-q="quarter"><?php __('dash_q_this_quarter'); ?></button>
					<button type="button" data-q="year"><?php __('dash_q_this_year'); ?></button>
				</div>
				<div class="sa-pop-fields">
					<label><?php __('dash_range_from'); ?><input type="date" id="saFrom" max="<?php echo $today; ?>" value="<?php echo $h($range['from']); ?>"></label>
					<label><?php __('dash_range_to'); ?><input type="date" id="saTo" max="<?php echo $today; ?>" value="<?php echo $h($range['to']); ?>"></label>
				</div>
				<p class="sa-pop-err" id="saPopErr" role="alert" hidden></p>
				<div class="sa-pop-actions">
					<button type="button" class="sa-btn" id="saPopCancel"><?php __('sub_btn_cancel'); ?></button>
					<button type="button" class="sa-btn is-primary" id="saPopApply"><?php __('dash_apply'); ?></button>
				</div>
			</div>
			<button type="button" class="sa-btn sa-hide-sm" id="saExport" title="<?php __('dash_export_hint', false, true); ?>"><i class="fa fa-download"></i> <?php __('dash_export'); ?></button>
		</div>
	</div>

	<div class="alert alert-warning sa-alert" data-sa="rangeError"<?php echo empty($range['error']) ? ' hidden' : ''; ?>><i class="fa fa-exclamation-triangle m-r-xs"></i><?php __('dash_range_invalid'); ?></div>
	<?php if (empty($payload['data']['ready'])) { ?>
		<div class="alert alert-warning sa-alert"><i class="fa fa-info-circle m-r-xs"></i><?php __('dash_not_ready'); ?></div>
	<?php } ?>

	<div class="sa-body" data-sa="body" aria-live="polite">
		<section class="sa-grid sa-kpis" data-sa="kpis" aria-label="<?php __('dash_key_figures', false, true); ?>"></section>

		<section class="sa-grid sa-row-a">
			<article class="sa-card">
				<div class="sa-card-h">
					<h3><i class="fa fa-line-chart"></i><?php __('dash_rev_title'); ?></h3>
					<div class="sa-mini-seg" role="group" aria-label="<?php __('dash_rev_title', false, true); ?>" id="saRevView">
						<button type="button" data-v="plan" aria-pressed="true"><?php __('dash_rev_by_plan'); ?></button>
						<button type="button" data-v="kind" aria-pressed="false"><?php __('dash_rev_by_kind'); ?></button>
					</div>
					<p><?php __('dash_rev_desc'); ?> <span data-sa="bucketLabel"><?php echo $h($range['bucket_label']); ?></span></p>
				</div>
				<div class="sa-card-b">
					<div class="sa-totals" data-sa="revTotals"></div>
					<div class="sa-chart" data-sa="revChart" style="height:250px"></div>
					<div class="sa-legend" data-sa="revLegend"></div>
					<p class="sa-note" data-sa="revNote" hidden></p>
				</div>
			</article>
			<article class="sa-card">
				<div class="sa-card-h">
					<h3><i class="fa fa-pie-chart"></i><?php __('dash_mix_title'); ?></h3>
					<a class="sa-link" href="<?php echo $self; ?>?controller=pjAdminSubscriptions&amp;action=pjActionPlans"><?php __('dash_mgmt_plans'); ?> <i class="fa fa-arrow-right"></i></a>
					<p><?php __('dash_mix_desc'); ?> <span data-sa="asOf"></span></p>
				</div>
				<div class="sa-card-b" data-sa="mix"></div>
			</article>
		</section>

		<section class="sa-grid sa-row-b">
			<article class="sa-card">
				<div class="sa-card-h">
					<h3><i class="fa fa-bar-chart"></i><?php __('dash_g_title'); ?></h3>
					<p><?php __('dash_g_desc'); ?></p>
				</div>
				<div class="sa-card-b">
					<div class="sa-totals" data-sa="growTotals"></div>
					<div class="sa-chart" data-sa="growChart" style="height:220px"></div>
					<div class="sa-legend" data-sa="growLegend"></div>
				</div>
			</article>
			<article class="sa-card">
				<div class="sa-card-h">
					<h3><i class="fa fa-filter"></i><?php __('dash_f_title'); ?></h3>
					<p><?php __('dash_f_desc'); ?></p>
				</div>
				<div class="sa-card-b" data-sa="funnel"></div>
			</article>
		</section>

		<section class="sa-grid sa-row-c">
			<article class="sa-card">
				<div class="sa-card-h">
					<h3><i class="fa fa-credit-card"></i><?php __('dash_pay_title'); ?></h3>
					<p><?php __('dash_pay_desc'); ?></p>
				</div>
				<div class="sa-card-b" data-sa="payments"></div>
			</article>
			<article class="sa-card">
				<div class="sa-card-h">
					<h3><i class="fa fa-clock-o"></i><?php __('dash_up_title'); ?></h3>
					<a class="sa-link" href="<?php echo $self; ?>?controller=pjAdminSubscriptions&amp;action=pjActionIndex"><?php __('dash_view_all'); ?> <i class="fa fa-arrow-right"></i></a>
					<p><?php __('dash_from_today'); ?></p>
				</div>
				<div class="sa-card-b" data-sa="upcoming"></div>
			</article>
			<article class="sa-card" id="saHealth">
				<div class="sa-card-h"><h3><i class="fa fa-heartbeat"></i><?php __('dash_health_title'); ?></h3><p><?php __('dash_health_now'); ?></p></div>
				<div class="sa-card-b" data-sa="health"></div>
			</article>
		</section>

		<section class="sa-grid sa-row-d" data-sa="billingRow">
			<article class="sa-card" data-sa="billingCard">
				<div class="sa-card-h">
					<h3><i class="fa fa-history"></i><?php __('dash_bh_title'); ?></h3>
					<a class="sa-link" href="<?php echo $self; ?>?controller=pjAdminSubscriptions&amp;action=pjActionBilling"><?php __('dash_view_all'); ?> <i class="fa fa-arrow-right"></i></a>
				</div>
				<div class="sa-card-b" data-sa="billing"></div>
			</article>
			<article class="sa-card">
				<div class="sa-card-h"><h3><i class="fa fa-bolt"></i><?php __('dash_activity_title'); ?></h3></div>
				<div class="sa-card-b" data-sa="activity"></div>
			</article>
		</section>
	</div>
</div>
<div class="sa-tip" id="saTip" role="tooltip"></div>
<script>
window.pjSaDashboard = <?php echo json_encode(array('t' => $t, 'payload' => $payload, 'today' => $today), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
