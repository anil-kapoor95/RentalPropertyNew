<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = 'sub_menu_currency';
$heading_desc = 'sub_currency_page_desc';
include dirname(__FILE__) . '/elements/heading.php';
$self = $_SERVER['PHP_SELF'] . '?controller=pjAdminSubscriptions&amp;action=';
?>
<div class="wrapper wrapper-content animated fadeInRight sub-fx-page">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>

	<!-- Currency & exchange rate: 1 USD = x INR, and which billing countries pay in INR -->
	<?php
	$rate = (float) $tpl['inr_rate'];
	$rate_val = rtrim(rtrim(number_format($rate, 4, '.', ''), '0'), '.');
	$inr_set = array_flip($tpl['inr_countries']);
	$follow = 0;
	foreach ($tpl['plans'] as $p) { if ((int) $p['inr_auto'] === 1 && $p['price'] !== null && (float) $p['price'] > 0) { $follow++; } }
	?>
	<form method="post" action="<?php echo $self; ?>pjActionCurrency" class="ibox sub-fx" id="subRate" data-sub-confirm="<?php __('sub_rate_confirm', false, true); ?>"
		data-inr-sign="<?php echo pjSanitize::html(pjMoney::symbol('INR')); ?>" data-usd-sign="<?php echo pjSanitize::html(pjMoney::symbol('USD')); ?>">
		<?php echo $subCsrf; ?>
		<header class="sub-fx-head">
			<span class="sub-fx-icon"><i class="fa fa-exchange"></i></span>
			<div class="sub-fx-title">
				<h3><?php __('sub_rate_title'); ?></h3>
				<p><?php __('sub_fx_intro'); ?></p>
			</div>
			<?php if (!empty($tpl['rate_snapshot'])) { ?>
			<span class="sub-fx-saved"><i class="fa fa-history"></i> <?php echo str_replace('{DATE}', pjSanitize::html(date($tpl['option_arr']['o_date_format'] . ' H:i', strtotime($tpl['rate_snapshot']['captured_at']))), __('sub_fx_saved', true)); ?></span>
			<?php } ?>
		</header>

		<div class="sub-fx-body">
			<!-- 1. Exchange rate -->
			<section class="sub-fx-panel">
				<h4><span class="sub-step-no">1</span><?php __('sub_fx_rate_title'); ?></h4>
				<div class="sub-fx-rate">
					<label for="subRateInput" class="sub-fx-rate-label"><span class="sub-cur-chip">USD</span> 1 =</label>
					<div class="sub-money-input sub-fx-rate-input">
						<span class="sub-money-sign"><?php echo pjSanitize::html(pjMoney::symbol('INR')); ?></span>
						<input class="form-control" id="subRateInput" name="inr_rate" type="number" min="1" max="100000" step="0.0001" required value="<?php echo pjSanitize::html($rate_val); ?>" aria-describedby="subRateHelp">
					</div>
					<span class="sub-cur-chip sub-cur-inr">INR</span>
				</div>
				<p class="sub-fx-help" id="subRateHelp"><?php __('sub_fx_rate_help'); ?></p>
				<div class="sub-fx-examples" aria-live="polite">
					<span class="sub-fx-examples-label"><?php __('sub_fx_examples'); ?></span>
					<?php foreach (array(10, 19, 49) as $usd) { ?>
					<span class="sub-fx-example" data-usd="<?php echo $usd; ?>"><b><?php echo pjSanitize::html(pjMoney::format($usd, 'USD')); ?></b> <i class="fa fa-long-arrow-right"></i> <span><?php echo pjSanitize::html(pjMoney::format(round($usd * $rate), 'INR')); ?></span></span>
					<?php } ?>
				</div>
				<p class="sub-fx-note"><i class="fa fa-info-circle"></i> <?php echo str_replace('{N}', '<strong>' . (int) $follow . '</strong>', __('sub_fx_rate_note', true)); ?></p>

				<?php
				// Automatic daily update: switch, last result, "Update now" (separate form below), recent rates
				$auto = $tpl['rate_auto'];
				$res = $auto['result'];
				$fmt_dt = function ($v) use ($tpl) { return $v ? date($tpl['option_arr']['o_date_format'] . ' H:i', strtotime($v)) : ''; };
				?>
				<div class="sub-fx-auto<?php echo $auto['auto'] ? ' is-on' : ''; ?>">
					<label class="sub-fx-auto-switch">
						<input type="checkbox" name="rate_auto" value="1"<?php echo $auto['auto'] ? ' checked' : ''; ?> aria-describedby="subRateAutoHelp">
						<span><?php __('sub_fx_auto_label'); ?></span>
					</label>
					<p class="sub-fx-help" id="subRateAutoHelp"><?php __('sub_fx_auto_help'); ?></p>
					<div class="sub-fx-auto-status" role="status">
						<?php if (!$auto['auto']) { ?>
							<span class="sub-fx-auto-off"><i class="fa fa-pause-circle"></i> <?php __('sub_fx_auto_off'); ?></span>
						<?php } elseif ($res && !empty($res['ok'])) { ?>
							<span class="sub-fx-auto-ok"><i class="fa fa-check-circle"></i> <?php echo str_replace(
								array('{DATE}', '{SOURCE}', '{RATEDATE}'),
								array(pjSanitize::html($fmt_dt($auto['at'])), pjSanitize::html($res['source']), pjSanitize::html(!empty($res['rate_date']) ? date($tpl['option_arr']['o_date_format'], strtotime($res['rate_date'])) : '-')),
								__('sub_fx_auto_ok', true)); ?></span>
						<?php } elseif ($res) { ?>
							<span class="sub-fx-auto-err"><i class="fa fa-exclamation-triangle"></i> <?php echo str_replace(
								array('{DATE}', '{ERROR}'), array(pjSanitize::html($fmt_dt($auto['at'])), pjSanitize::html(isset($res['error']) ? $res['error'] : '')),
								__('sub_fx_auto_failed', true)); ?></span>
						<?php } else { ?>
							<span class="sub-fx-auto-off"><i class="fa fa-clock-o"></i> <?php __('sub_fx_auto_never'); ?></span>
						<?php } ?>
						<button type="submit" form="subRateRefresh" class="btn btn-white btn-sm"><i class="fa fa-refresh m-r-xs"></i><?php __('sub_fx_update_now'); ?></button>
					</div>
					<?php if (!empty($auto['history'])) { ?>
					<details class="sub-fx-history">
						<summary><?php __('sub_fx_history'); ?></summary>
						<table class="table table-condensed">
							<tbody>
							<?php foreach ($auto['history'] as $h) { ?>
								<tr>
									<td><?php echo pjSanitize::html($fmt_dt($h['captured_at'])); ?></td>
									<td><strong>1 USD = <?php echo pjSanitize::html(pjMoney::format((float) $h['rate'], 'INR')); ?></strong></td>
									<td class="text-muted"><?php echo strpos((string) $h['source'], 'auto') === 0 ? pjSanitize::html(trim(substr($h['source'], 5))) : __('sub_fx_history_manual', true); ?></td>
								</tr>
							<?php } ?>
							</tbody>
						</table>
					</details>
					<?php } ?>
				</div>
			</section>

			<!-- 2. Countries that pay in INR -->
			<section class="sub-fx-panel">
				<h4><span class="sub-step-no">2</span><?php __('sub_fx_countries_title'); ?></h4>
				<p class="sub-fx-help"><?php __('sub_fx_countries_help'); ?></p>
				<div class="sub-fx-chips" id="subInrChips">
					<?php foreach ($tpl['country_list'] as $c) { if (!isset($inr_set[$c['code']])) { continue; } ?>
					<span class="sub-fx-chip" data-code="<?php echo pjSanitize::html($c['code']); ?>">
						<input type="hidden" name="inr_countries[]" value="<?php echo pjSanitize::html($c['code']); ?>">
						<?php echo pjSanitize::html($c['name']); ?> <small><?php echo pjSanitize::html($c['code']); ?></small>
						<button type="button" class="sub-fx-chip-x" aria-label="<?php echo pjSanitize::html(str_replace('{NAME}', $c['name'], __('sub_fx_remove', true))); ?>">&times;</button>
					</span>
					<?php } ?>
					<span class="sub-fx-empty"<?php echo empty($tpl['inr_countries']) ? '' : ' hidden'; ?>><?php __('sub_fx_none'); ?></span>
				</div>
				<label class="sr-only" for="subInrAdd"><?php __('sub_fx_add'); ?></label>
				<select class="form-control sub-fx-add" id="subInrAdd">
					<option value=""><?php __('sub_fx_add'); ?></option>
					<?php foreach ($tpl['country_list'] as $c) { ?>
					<option value="<?php echo pjSanitize::html($c['code']); ?>"<?php echo isset($inr_set[$c['code']]) ? ' disabled' : ''; ?>><?php echo pjSanitize::html($c['name'] . ' (' . $c['code'] . ')'); ?></option>
					<?php } ?>
				</select>
				<p class="sub-fx-note"><i class="fa fa-lightbulb-o"></i> <?php __('sub_fx_countries_tip'); ?></p>
			</section>
		</div>

		<footer class="sub-fx-foot">
			<span><?php __('sub_fx_foot'); ?></span>
			<button class="btn btn-primary" type="submit"><i class="fa fa-check m-r-xs"></i><?php __('sub_fx_save'); ?></button>
		</footer>
	</form>
	<!-- "Update now" (button inside the form above, form="subRateRefresh"): fetches today's rate -->
	<form method="post" action="<?php echo $self; ?>pjActionCurrency" id="subRateRefresh" hidden>
		<?php echo $subCsrf; ?>
		<input type="hidden" name="rate_refresh" value="1">
	</form>

</div>
