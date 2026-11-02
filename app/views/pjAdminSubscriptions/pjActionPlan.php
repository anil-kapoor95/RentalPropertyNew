<?php
include dirname(__FILE__) . '/elements/common.php';
$heading_title = $tpl['plan'] ? 'sub_plan_edit' : 'sub_plan_add';
$heading_desc = 'sub_plan_desc';
include dirname(__FILE__) . '/elements/heading.php';
$p = $tpl['plan'] ? $tpl['plan'] : array('id' => 0, 'code' => '', 'name' => '', 'description' => '', 'price' => null, 'currency' => '', 'billing_interval' => 'month',
	'interval_count' => 1, 'trial_days' => 0, 'max_properties' => null, 'max_users' => null, 'max_payment_gateways' => null, 'ai_monthly_messages' => null,
	'feature_list' => pjEntitlement::$baseFeatures, 'is_public' => 1, 'show_branding' => 1, 'status' => 'T', 'sort' => 0);
$v = function ($k) use ($p) { return $p[$k] === null ? '' : pjSanitize::html((string) $p[$k]); };
$cur = $tpl['plan'] && $tpl['plan']['currency'] ? $tpl['plan']['currency'] : strtoupper((string) pjEntitlement::setting('currency'));
$is_default = $tpl['plan'] && (int) $tpl['plan']['id'] === (int) $tpl['default_plan_id'];
$limits = array(
	'max_properties'       => 'fa-home',
	'max_users'            => 'fa-users',
	'max_payment_gateways' => 'fa-credit-card',
	'ai_monthly_messages'  => 'fa-comments-o',
);
$base = pjEntitlement::$baseFeatures;
$self = $_SERVER['PHP_SELF'] . '?controller=pjAdminSubscriptions&amp;action=';
/** On / off row of the Visibility section: switch, title and what it does. */
$toggle = function ($name, $value, $on, $title, $hint) {
	return '<label class="sub-opt"><span class="sub-switch"><input type="checkbox" name="' . $name . '" value="' . $value . '"' . ($on ? ' checked' : '') . '><i></i></span>'
		. '<span><strong>' . __($title, true) . '</strong><small>' . __($hint, true) . '</small></span></label>';
};
?>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
	<form method="post" action="<?php echo $self; ?>pjActionPlan" id="subPlanForm" class="sub-plan-form"
		data-free="<?php __('sub_plan_free', false, true); ?>" data-not-set="<?php __('sub_price_not_set', false, true); ?>"
		data-unlimited="<?php __('sub_unlimited', false, true); ?>" data-month="<?php __('sub_month', false, true); ?>" data-year="<?php __('sub_year', false, true); ?>"
		data-trial="<?php __('sub_plan_trial', false, true); ?>" data-count="<?php __('sub_features_count', false, true); ?>">
		<?php echo $subCsrf; ?><input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>">

		<div class="sub-plan-layout">
			<div class="sub-plan-main">

				<!-- 1. Plan details -->
				<section class="ibox sub-sec">
					<div class="ibox-content">
						<h4 class="sub-sec-title"><span>1</span><?php __('sub_plan_sec_details'); ?></h4>
						<div class="row">
							<div class="col-sm-8 form-group">
								<label for="subName"><?php __('sub_plan_name'); ?> *</label>
								<input class="form-control required" id="subName" name="name" maxlength="100" required value="<?php echo $v('name'); ?>">
							</div>
							<div class="col-sm-4 form-group">
								<label for="subCode"><?php __('sub_plan_code'); ?> *</label>
								<input class="form-control required" id="subCode" name="code" maxlength="32" required pattern="[a-z0-9_\-]{2,32}" value="<?php echo $v('code'); ?>" aria-describedby="subCodeHint">
								<small class="help-block" id="subCodeHint"><?php __('sub_plan_code_hint'); ?></small>
							</div>
						</div>
						<div class="form-group m-b-none">
							<label for="subDesc"><?php __('sub_description'); ?></label>
							<textarea class="form-control" id="subDesc" name="description" rows="2"><?php echo $v('description'); ?></textarea>
						</div>
					</div>
				</section>

				<!-- 2. Pricing -->
				<section class="ibox sub-sec">
					<div class="ibox-content">
						<h4 class="sub-sec-title"><span>2</span><?php __('sub_plan_sec_pricing'); ?></h4>
						<div class="row">
							<div class="col-sm-4 col-xs-8 form-group">
								<label for="subPrice"><?php __('sub_col_price'); ?></label>
								<input class="form-control" id="subPrice" name="price" type="number" min="0" step="0.01" value="<?php echo $v('price'); ?>" placeholder="<?php __('sub_price_not_set', false, true); ?>">
							</div>
							<div class="col-sm-2 col-xs-4 form-group">
								<label for="subCurrency"><?php __('sub_currency'); ?></label>
								<select class="form-control" id="subCurrency" name="currency">
									<?php foreach ($tpl['currencies'] as $code) { ?><option value="<?php echo pjSanitize::html($code); ?>"<?php echo $code === $cur ? ' selected' : ''; ?>><?php echo pjSanitize::html($code); ?></option><?php } ?>
								</select>
							</div>
							<div class="col-sm-4 col-xs-8 form-group">
								<label for="subEvery"><?php __('sub_billing_interval'); ?></label>
								<div class="sub-every">
									<input class="form-control" id="subEvery" name="interval_count" type="number" min="1" max="12" value="<?php echo (int) $p['interval_count']; ?>">
									<select class="form-control" name="billing_interval" aria-label="<?php __('sub_billing_interval', false, true); ?>">
										<option value="month"<?php echo $p['billing_interval'] === 'month' ? ' selected' : ''; ?>><?php __('sub_month'); ?></option>
										<option value="year"<?php echo $p['billing_interval'] === 'year' ? ' selected' : ''; ?>><?php __('sub_year'); ?></option>
									</select>
								</div>
							</div>
							<div class="col-sm-2 col-xs-4 form-group">
								<label for="subTrial"><?php __('sub_trial_days'); ?></label>
								<input class="form-control" id="subTrial" name="trial_days" type="number" min="0" max="365" value="<?php echo (int) $p['trial_days']; ?>">
							</div>
						</div>
						<p class="sub-sec-hint"><i class="fa fa-info-circle"></i><?php __('sub_price_hint'); ?></p>
					</div>
				</section>

				<!-- 3. Limits -->
				<section class="ibox sub-sec">
					<div class="ibox-content">
						<h4 class="sub-sec-title"><span>3</span><?php __('sub_limits'); ?></h4>
						<div class="sub-limit-grid">
							<?php foreach ($limits as $k => $icon) { ?>
							<label class="sub-limit">
								<span class="sub-limit-label"><i class="fa <?php echo $icon; ?>"></i><?php __('sub_' . $k); ?></span>
								<input class="form-control" name="<?php echo $k; ?>" type="number" min="0" value="<?php echo $v($k); ?>" placeholder="<?php __('sub_unlimited', false, true); ?>" data-limit="<?php echo $k; ?>">
							</label>
							<?php } ?>
						</div>
						<p class="sub-sec-hint"><i class="fa fa-info-circle"></i><?php __('sub_limits_hint'); ?></p>
					</div>
				</section>

				<!-- 4. Features -->
				<section class="ibox sub-sec">
					<div class="ibox-content">
						<div class="sub-sec-row">
							<h4 class="sub-sec-title"><span>4</span><?php __('sub_features'); ?></h4>
							<div class="sub-sec-actions">
								<button type="button" class="btn btn-white btn-xs" data-sub-features="all"><?php __('sub_select_all'); ?></button>
								<button type="button" class="btn btn-white btn-xs" data-sub-features="base"><?php __('sub_select_base'); ?></button>
							</div>
						</div>
						<div class="sub-feature-chips">
							<?php foreach (pjEntitlement::$features as $f => $lbl) { ?>
							<label class="sub-chip<?php echo in_array($f, $base, true) ? ' sub-chip-base' : ''; ?>">
								<input type="checkbox" name="features[]" value="<?php echo $f; ?>"<?php echo in_array($f, $p['feature_list'], true) ? ' checked' : ''; ?> data-label="<?php __($lbl, false, true); ?>"> <?php __($lbl); ?>
							</label>
							<?php } ?>
						</div>
						<p class="sub-sec-hint"><i class="fa fa-info-circle"></i><?php __('sub_features_hint'); ?></p>
					</div>
				</section>

				<!-- 5. Visibility & access -->
				<section class="ibox sub-sec">
					<div class="ibox-content">
						<h4 class="sub-sec-title"><span>5</span><?php __('sub_plan_sec_visibility'); ?></h4>
						<div class="sub-opts">
							<?php
							echo $toggle('status', 'T', $p['status'] === 'T', 'sub_active_plan', 'sub_active_plan_hint');
							echo $toggle('is_public', '1', (int) $p['is_public'] === 1, 'sub_public', 'sub_public_hint');
							echo $toggle('is_default', '1', $is_default, 'sub_default_plan', 'sub_default_plan_hint');
							echo $toggle('show_branding', '1', !isset($p['show_branding']) || (int) $p['show_branding'] === 1, 'sub_show_branding', 'sub_show_branding_hint');
							?>
						</div>
						<div class="form-group m-t-md m-b-none sub-order">
							<label for="subSort"><?php __('sub_sort'); ?></label>
							<input class="form-control" id="subSort" name="sort" type="number" min="0" value="<?php echo (int) $p['sort']; ?>">
							<small class="help-block"><?php __('sub_sort_hint'); ?></small>
						</div>
					</div>
				</section>
			</div>

			<!-- Live summary + actions -->
			<aside class="sub-plan-side">
				<div class="sub-preview">
					<p class="sub-pc-label"><?php __('sub_plan_preview'); ?></p>
					<h3 class="sub-pc-name" data-pv="name"><?php echo $v('name') !== '' ? $v('name') : '&mdash;'; ?></h3>
					<div class="sub-pc-pricing" data-pv="price"></div>
					<p class="sub-pc-trial" data-pv="trial" hidden></p>
					<ul class="sub-pc-limits">
						<?php foreach ($limits as $k => $icon) { ?>
						<li><span><i class="fa <?php echo $icon; ?>"></i><?php __('sub_' . $k); ?></span><strong data-pv-limit="<?php echo $k; ?>"></strong></li>
						<?php } ?>
					</ul>
					<p class="sub-pc-label m-t-md" data-pv="count"></p>
					<div class="sub-pv-badges">
						<span class="sub-badge sub-active" data-pv-flag="status"><?php __('sub_active_plan'); ?></span>
						<span class="sub-badge sub-none" data-pv-flag="!status"><?php __('sub_inactive'); ?></span>
						<span class="sub-badge sub-trial" data-pv-flag="is_default"><i class="fa fa-star m-r-xs"></i><?php __('sub_default_plan_badge'); ?></span>
						<span class="sub-badge sub-none" data-pv-flag="!is_public"><i class="fa fa-eye-slash m-r-xs"></i><?php __('sub_plan_private'); ?></span>
					</div>
				</div>
				<div class="sub-plan-actions">
					<button class="btn btn-primary btn-lg btn-block" type="submit"><i class="fa fa-check m-r-xs"></i><?php __('sub_btn_save'); ?></button>
					<a class="btn btn-white btn-block" href="<?php echo $self; ?>pjActionPlans"><?php __('sub_btn_cancel'); ?></a>
				</div>
			</aside>
		</div>
		<!-- small screens: Save / Cancel stay at the bottom of the screen -->
		<div class="sub-plan-actions-bar">
			<a class="btn btn-white" href="<?php echo $self; ?>pjActionPlans"><?php __('sub_btn_cancel'); ?></a>
			<button class="btn btn-primary" type="submit"><?php __('sub_btn_save'); ?></button>
		</div>
	</form>

	<?php if ($tpl['plan']) { ?>
	<form method="post" action="<?php echo $self; ?>pjActionPlan" class="sub-danger" data-sub-confirm="<?php __('sub_confirm', false, true); ?>">
		<?php echo $subCsrf; ?><input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>"><input type="hidden" name="do" value="delete">
		<div>
			<strong><?php __('sub_btn_delete_plan'); ?></strong>
			<small><?php __('sub_delete_plan_hint'); ?></small>
		</div>
		<button class="btn btn-white btn-sm text-danger" type="submit"><i class="fa fa-trash m-r-xs"></i><?php __('sub_btn_delete_plan'); ?></button>
	</form>
	<?php } ?>
</div>
