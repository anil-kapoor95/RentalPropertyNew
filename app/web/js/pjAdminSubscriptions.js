/* Subscriptions: Options > Payments (gateway settings popup) and My plan (choose how to pay). */
(function ($) {
	if (typeof $ === 'undefined') { return; }
	$(function () {
		var $doc = $(document);

		/* ---- Options > Payments ---- */
		var $gw = $('#subGateways');
		if ($gw.length) {
			var url = $gw.attr('data-url').replace(/&amp;/g, '&');
			$doc.on('click', '.subGatewayLink', function (e) {
				e.preventDefault();
				$.get(url, {payment_method: $(this).attr('data-method')}).done(function (html) {
					if (typeof html === 'object' && html && html.text) { pjPopup.message(html.text); return; }
					$('#subGatewayModalContent').html(html);
					var $m = $('#subGatewayModalContent');
					$('#subGatewayModal').modal('show');
					$m.find('#enableTestMode').trigger('change');
				});
			}).on('change', '#subGatewayModalContent #enablePayment', function () {
				var on = $(this).prop('checked');
				$('#subGatewayModalContent .hidden-area').toggle(on);
				$('#subGatewayModalContent #payment_is_active').val(on ? 1 : 0);
				if (on) { $('#subGatewayModalContent #enableTestMode').trigger('change'); }
			}).on('change', '#subGatewayModalContent #enableTestMode', function () {
				if (!$('#subGatewayModalContent #enablePayment').prop('checked')) { return; }
				var test = $(this).is(':checked');
				$('#subGatewayModalContent .test-area').toggle(test);
				$('#subGatewayModalContent .live-area').toggle(!test);
				$('#subGatewayModalContent #payment_is_test_mode').val(test ? 1 : 0);
			}).on('click', '#btnSaveSubGateway', function (e) {
				e.preventDefault();
				var $f = $('#frmSubGatewayOptions');
				// required fields of the visible (switched on) part
				var missing = false;
				if ($f.find('#payment_is_active').val() === '1') {
					$f.find('.required:visible').each(function () {
						if ($.trim($(this).val()) === '') { missing = true; $(this).closest('.form-group').addClass('has-error'); }
						else { $(this).closest('.form-group').removeClass('has-error'); }
					});
				}
				if (missing) { return false; }
				var $btn = $(this).prop('disabled', true);
				$.post(url, $f.serialize()).done(function (data) {
					if (data && data.status === 'OK') {
						$('#subGatewayModal').modal('hide');
						window.location.href = url.replace('pjActionGatewayOptions', 'pjActionGateways') + '&msg=subSaved';
					} else {
						pjPopup.message(data && data.text ? data.text : 'Error');
					}
				}).fail(function () {
					pjPopup.message('Error');
				}).always(function () {
					$btn.prop('disabled', false);
				});
				return false;
			});
		}

		/* ---- Subscriptions > Coupons & Discounts: the currency only matters for a fixed amount ---- */
		var $promoType = $('#subPromoType');
		if ($promoType.length) {
			$promoType.on('change', function () {
				$('.subPromoCurrency').toggle(this.value === 'amount');
			}).trigger('change');
		}

		/* ---- My plan: choose the payment method in a popup ---- */
		var $pay = $('#subPayModal');
		if ($pay.length) {
			var $frm = $('#frmSubPay'),
				checkUrl = ($frm.attr('data-check') || '').replace(/&amp;/g, '&');
			// price with the discount and the coupon (worked out on the server; the payment uses the same rules)
			var quote = function (code) {
				var $msg = $frm.find('.subCouponMsg').text('').removeClass('text-danger text-success');
				$.getJSON(checkUrl, {plan_id: $frm.find('input[name="plan_id"]').val(), code: code}).done(function (data) {
					if (!data) { return; }
					if (data.list) {
						$frm.find('.subQuoteList').text(data.list);
						$frm.find('.subQuoteDiscount').text(data.discount || '');
						$frm.find('.subQuoteDiscountRow').toggle(!!data.discount);
						$frm.find('.subQuoteAmount').text(data.amount);
					}
					if (data.text) {
						$msg.text(data.text).addClass(data.status === 'OK' ? 'text-success' : 'text-danger');
					}
				});
			};
			$doc.on('click', '.subPayBtn', function (e) {
				e.preventDefault();
				$frm.find('input[name="plan_id"]').val($(this).attr('data-plan'));
				$frm.find('input[name="coupon_code"]').val('');
				var name = $.trim($(this).closest('.sub-plan').find('h3').first().text());
				if (name) { $pay.find('.modal-title').text(name); }
				quote('');
				$pay.modal('show');
			}).on('click', '#frmSubPay .subCouponApply', function (e) {
				e.preventDefault();
				quote($.trim($frm.find('input[name="coupon_code"]').val()));
			}).on('keydown', '#frmSubPay input[name="coupon_code"]', function (e) {
				if (e.which === 13) {
					e.preventDefault();
					quote($.trim($(this).val()));
				}
			});
			$doc.on('submit', '#frmSubPay', function () {
				$(this).find('button[type="submit"]').prop('disabled', true);
			});
		}
	});
})(window.jQuery);
