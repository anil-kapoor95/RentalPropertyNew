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

		/* ---- My plan: choose the payment method in a popup ---- */
		var $pay = $('#subPayModal');
		if ($pay.length) {
			$doc.on('click', '.subPayBtn', function (e) {
				e.preventDefault();
				$('#frmSubPay').find('input[name="plan_id"]').val($(this).attr('data-plan'));
				$pay.modal('show');
			});
			$doc.on('submit', '#frmSubPay', function () {
				$(this).find('button[type="submit"]').prop('disabled', true);
			});
		}
	});
})(window.jQuery);
