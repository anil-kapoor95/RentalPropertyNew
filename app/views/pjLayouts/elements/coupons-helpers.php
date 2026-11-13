<?php
/**
 * Helpers of the company Coupons & Discounts views (lists and forms).
 * Same markup as the Super Admin's Coupons & Discounts (sub-* classes of app/web/css/subscriptions.css).
 */
$cdDate = function ($dt) use ($tpl) {
	if (empty($dt)) { return '&mdash;'; }
	return pjSanitize::html(date($tpl['option_arr']['o_date_format'], strtotime($dt)));
};
$cdValue = function ($row) use ($tpl) {
	return $row['type'] === 'percent'
		? pjSanitize::html(rtrim(rtrim((string) $row['discount'], '0'), '.')) . '%'
		: pjSanitize::html(pjCurrency::formatPrice((float) $row['discount']));
};
$cdStatus = function ($row) {
	$st = pjAppController::cdStatus($row['date_from'], $row['date_to']);
	$cls = array('active' => 'sub-active', 'scheduled' => 'sub-trial', 'expired' => 'sub-expired');
	return '<span class="sub-badge ' . $cls[$st] . '">' . __('cd_status_' . $st, true) . '</span>';
};
$cdPropertyTags = function ($ids) use ($tpl) {
	$out = '';
	foreach ((array) $ids as $cid)
	{
		if (isset($tpl['calendar_names'][$cid]))
		{
			$out .= '<span class="sub-tag">' . pjSanitize::html((string) $tpl['calendar_names'][$cid]) . '</span>';
		}
	}
	return $out !== '' ? $out : '<span class="text-muted">&mdash;</span>';
};
$cdConditions = __('apply_conditions', true);
$cdApplyOn = __('apply_on', true);
$cdOptions = __('discount_options', true);
$cdTypes = __('voucher_types', true);
$cdCurrency = pjCurrency::getCurrencySign($tpl['option_arr']['o_currency'], false);
$cdCsrf = '<input type="hidden" name="csrf_token" value="' . pjAppController::getCsrfToken() . '">';
