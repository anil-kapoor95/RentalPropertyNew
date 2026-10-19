<?php
/** Shared helpers for the subscription views. */
$subStatus = function ($status) {
	if ($status === null || $status === '')
	{
		return '<span class="sub-badge sub-none">' . __('sub_status_none', true) . '</span>';
	}
	return '<span class="sub-badge sub-' . pjSanitize::html($status) . '">' . __('sub_status_' . $status, true) . '</span>';
};
$subMoney = function ($amount, $currency) {
	if ($amount === null || $amount === '')
	{
		return '<span class="text-muted">' . __('sub_price_not_set', true) . '</span>';
	}
	return pjSanitize::html(number_format((float) $amount, 2) . ' ' . $currency);
};
$subDate = function ($dt) use ($tpl) {
	if (empty($dt)) { return '&mdash;'; }
	return pjSanitize::html(date($tpl['option_arr']['o_date_format'], strtotime($dt)));
};
$subLimit = function ($v) {
	return $v === null ? __('sub_unlimited', true) : (int) $v;
};
$subInterval = function ($plan) {
	$n = (int) $plan['interval_count'];
	$u = $plan['billing_interval'] === 'year' ? __('sub_year', true) : __('sub_month', true);
	return $n > 1 ? $n . ' &times; ' . $u : $u;
};
$subSource = function ($state) {
	if ($state['source'] === 'unassigned')
	{
		return '<span class="sub-badge sub-none">' . __($state['entitled'] ? 'sub_unassigned_full' : 'sub_unassigned_restricted', true) . '</span>';
	}
	return '';
};
$subCsrf = '<input type="hidden" name="csrf_token" value="' . pjAppController::getCsrfToken() . '">';
?>
