/*
 * Company Coupons & Discounts (pjAdminVouchers / pjAdminDiscounts page forms): the same behaviour as the
 * Super Admin's Coupons & Discounts. Confirm before delete: data-sub-confirm (pjAdminCore.js).
 */
(function ($) {
	if (typeof $ === 'undefined') { return; }
	$(function () {
		// discount rule: show the fields of the chosen rule only (others are cleared on the server)
		var $rule = $('.cdRule');
		if ($rule.length) {
			$rule.on('change', function () {
				var val = this.value;
				$('.cdRuleBox').each(function () {
					var on = $(this).attr('data-rule') === val;
					$(this).toggle(on);
					// the first field of the shown rule is required (early days, minimum guests / nights)
					$(this).find('input').prop('required', false).first().prop('required', on);
				});
			}).trigger('change');
		}
		// a percent discount is at most 100
		$('.cdType').on('change', function () {
			var $val = $(this).closest('form').find('input[name="discount"]');
			if (this.value === 'percent') { $val.attr('max', '100'); } else { $val.removeAttr('max'); }
		}).trigger('change');
		// at least one property
		$('#frmCdCoupon, #frmCdDiscount').on('submit', function (e) {
			var $boxes = $(this).find('input[name="calendar_id[]"]');
			if ($boxes.length && !$boxes.filter(':checked').length) {
				e.preventDefault();
				var $first = $boxes.first();
				$first[0].setCustomValidity && $first[0].setCustomValidity($(this).attr('data-msg-property') || 'Choose at least one property.');
				$first[0].reportValidity && $first[0].reportValidity();
				$first[0].setCustomValidity && $first[0].setCustomValidity('');
			}
		});
	});
})(window.jQuery);
