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

		/* ---- My plan: currency switch above the plans (only changes what is shown) ---- */
		$doc.on('click', '#subCurSwitch .sub-seg-btn', function () {
			var cur = $(this).attr('data-cur');
			$('#subCurSwitch .sub-seg-btn').each(function () {
				var on = $(this).attr('data-cur') === cur;
				$(this).toggleClass('active', on).attr('aria-checked', on ? 'true' : 'false');
			});
			$('.sub-plans .sub-price[data-cur]').each(function () { this.hidden = $(this).attr('data-cur') !== cur; });
		});

		/* ---- My plan: popup to pay a plan - currency, payment method (gateways of that currency), coupon, summary ---- */
		var $pay = $('#subPayModal');
		if ($pay.length) {
			var $frm = $('#frmSubPay'),
				checkUrl = ($frm.attr('data-check') || '').replace(/&amp;/g, '&'),
				seq = 0;
			var currentCur = function () { return $frm.find('input[name="currency"]:checked').val() || ''; };
			// only the gateways that take the chosen currency; the first one is picked
			var showMethods = function (allowed) {
				var $m = $frm.find('.sub-method'), shown = 0;
				$m.each(function () {
					var ok = allowed.indexOf($(this).find('input').val()) !== -1;
					$(this).prop('hidden', !ok);
					if (!ok) { $(this).find('input').prop('checked', false); } else { shown++; }
				});
				if (!$m.filter(':not([hidden])').find('input:checked').length) {
					$m.filter(':not([hidden])').first().find('input').prop('checked', true);
				}
				$frm.find('.sub-pay-nomethod').prop('hidden', shown > 0);
				$frm.find('.sub-pay-submit span').text(shown > 0 ? $frm.attr('data-btn-pay') : $frm.attr('data-btn-request'));
				return shown;
			};
			// price with the discount and the coupon, in the chosen currency (worked out on the server; the payment uses the same rules)
			var quote = function (code) {
				var my = ++seq;
				var $msg = $frm.find('.subCouponMsg').text('').removeClass('text-danger text-success');
				var $btn = $frm.find('.sub-pay-submit').prop('disabled', true);
				$.getJSON(checkUrl, {plan_id: $frm.find('input[name="plan_id"]').val(), code: code, currency: currentCur()}).done(function (data) {
					if (!data || my !== seq) { return; }
					if (data.currency) {
						$frm.find('input[name="currency"][value="' + data.currency + '"]').prop('checked', true);
						$frm.find('.sub-cur-option').each(function () {
							var c = $(this).attr('data-cur');
							$(this).toggleClass('active', c === data.currency).toggleClass('locked', !!data.locked && c !== data.currency);
							$(this).find('input').prop('disabled', $(this).hasClass('unavailable') || (!!data.locked && c !== data.currency));
						});
						$frm.find('.sub-pay-locked').prop('hidden', !data.locked).find('span').text(String($frm.attr('data-locked')).replace('{CUR}', data.currency));
					}
					if (data.list) {
						$frm.find('.subQuoteList').text(data.list);
						$frm.find('.subQuoteDiscount').text(data.discount || '');
						$frm.find('.subQuoteDiscountRow').toggle(!!data.discount);
						$frm.find('.subQuoteAmount').text(data.amount);
						$frm.find('.sub-summary-usd').prop('hidden', !data.usd).text(data.usd ? '≈ ' + data.usd + ' · ' + data.rate : '');
						$frm.find('.sub-summary-lock').prop('hidden', !data.price_locked).find('span').text($frm.attr('data-price-locked') || '');
					}
					showMethods($.isArray(data.methods) ? data.methods : []);
					if (data.text) {
						$msg.text(data.text).addClass(data.status === 'OK' ? 'text-success' : 'text-danger');
					}
				}).always(function () {
					if (my === seq) { $btn.prop('disabled', false); }
				});
			};
			$doc.on('click', '.subPayBtn', function (e) {
				e.preventDefault();
				var $card = $(this).closest('.sub-plan'),
					avail = String($card.attr('data-currencies') || '').split(',').filter(Boolean),
					prices = {};
				try { prices = JSON.parse($card.attr('data-prices') || '{}'); } catch (err) { prices = {}; }
				$frm.find('input[name="plan_id"]').val($(this).attr('data-plan'));
				$frm.find('input[name="coupon_code"]').val('');
				$frm.find('#subCouponBox').prop('hidden', true);
				$frm.find('.sub-coupon-toggle').attr('aria-expanded', 'false');
				$pay.find('.sub-pay-title').text($.trim($card.find('h3').first().text()));
				$pay.find('[data-pv="kind"]').text($(this).attr('data-kind-label') || '');
				$pay.find('[data-pv="interval"]').text($card.attr('data-interval') || '');
				// currencies of the plan (the ones without a price can't be picked)
				$frm.find('.sub-cur-option').each(function () {
					var c = $(this).attr('data-cur'), ok = avail.indexOf(c) !== -1;
					$(this).toggleClass('unavailable', !ok).removeClass('locked').find('input').prop('disabled', !ok).prop('checked', false);
					$(this).find('[data-pv="price"]').text(ok && prices[c] ? prices[c] : '—');
				});
				// default: the currency shown above the plans, else the billing currency, else the first one
				var shown = $('#subCurSwitch .sub-seg-btn.active').attr('data-cur'),
					pick = avail.indexOf(shown) !== -1 ? shown : (avail.indexOf($frm.attr('data-billing')) !== -1 ? $frm.attr('data-billing') : avail[0]);
				$frm.find('input[name="currency"][value="' + pick + '"]').prop('checked', true);
				quote('');
				$pay.modal('show');
			}).on('change', '#frmSubPay input[name="currency"]', function () {
				quote($.trim($frm.find('input[name="coupon_code"]').val()));
			}).on('click', '#frmSubPay .sub-coupon-toggle', function () {
				var $box = $frm.find('#subCouponBox'), open = $box.prop('hidden');
				$box.prop('hidden', !open);
				$(this).attr('aria-expanded', open ? 'true' : 'false');
				if (open) { $box.find('input').trigger('focus'); }
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
				// disabled radios are not sent: the (locked) currency is always sent
				var cur = currentCur();
				$(this).find('input[name="currency"]').prop('disabled', false).filter('[value="' + cur + '"]').prop('checked', true);
				$(this).find('button[type="submit"]').prop('disabled', true);
			});
		}
		/* ---- Options > Payments: gateways for each currency (switch state, count, warning when none is on) ---- */
		var gwcRefresh = function ($cur) {
			var $boxes = $cur.find('.sub-gwc-item input[type="checkbox"]');
			if (!$boxes.length) { return; }
			var on = $boxes.filter(':checked').length;
			$boxes.each(function () { $(this).closest('.sub-gwc-item').toggleClass('on', this.checked); });
			$cur.find('.sub-gwc-count b').text(on);
			$cur.find('.sub-gwc-warn').prop('hidden', on > 0);
		};
		$('.sub-gwc-cur').each(function () { gwcRefresh($(this)); });
		$doc.on('change', '.sub-gwc-item input[type="checkbox"]', function () { gwcRefresh($(this).closest('.sub-gwc-cur')); });

		/* ---- Subscriptions > Plans: exchange rate (live examples) and the countries that pay in INR ---- */
		var $fx = $('#subRate');
		if ($fx.length) {
			var inrSign = $fx.attr('data-inr-sign') || '₹';
			var fxRefresh = function () {
				var rate = parseFloat($('#subRateInput').val());
				$fx.find('.sub-fx-example').each(function () {
					var usd = parseFloat($(this).attr('data-usd'));
					$(this).find('span').text(rate > 0
						? inrSign + ' ' + Math.round(usd * rate).toLocaleString('en-IN')
						: '—');
				});
			};
			var syncChips = function () {
				var $chips = $('#subInrChips .sub-fx-chip');
				$('#subInrChips .sub-fx-empty').prop('hidden', $chips.length > 0);
				var used = $chips.map(function () { return $(this).attr('data-code'); }).get();
				$('#subInrAdd option').each(function () {
					if (this.value) { this.disabled = used.indexOf(this.value) !== -1; }
				});
			};
			$doc.on('input change', '#subRateInput', fxRefresh)
				.on('change', '#subInrAdd', function () {
					var code = this.value;
					if (!code) { return; }
					var label = $(this).find('option:selected').text().replace(/\s*\([A-Z]{2}\)$/, '');
					var $chip = $('<span class="sub-fx-chip">').attr('data-code', code)
						.append($('<input type="hidden" name="inr_countries[]">').val(code))
						.append(document.createTextNode(label + ' '))
						.append($('<small>').text(code))
						.append($('<button type="button" class="sub-fx-chip-x">&times;</button>').attr('aria-label', 'Remove ' + label));
					$chip.insertBefore('#subInrChips .sub-fx-empty');
					this.value = '';
					syncChips();
				}).on('click', '#subInrChips .sub-fx-chip-x', function () {
					$(this).closest('.sub-fx-chip').remove();
					syncChips();
					$('#subInrAdd').trigger('focus');
				});
			fxRefresh();
		}

		/* ---- Subscriptions > Plans list: "+N more" opens the comparison ---- */
		$doc.on('click', '.sub-pc-more', function () {
			var c = document.getElementById('subCompare');
			if (c) { c.open = true; }
		});

		/* ---- Subscriptions > Plans: add / edit form (live summary, feature shortcuts) ---- */
		var $plan = $('#subPlanForm');
		if ($plan.length) {
			var d = $plan.data();
			var base = $plan.find('.sub-chip-base input').map(function () { return this.value; }).get();
			var esc = function (s) { return $('<div>').text(s).html(); };
			// Dual pricing: INR follows USD x rate (whole rupees) while "Follow the exchange rate" is on
			var rate = parseFloat(d.rate) || 0,
				$usd = $plan.find('[name="price_usd"]'),
				$inr = $plan.find('[name="price_inr"]'),
				$auto = $plan.find('[name="inr_auto"]');
			var fmt = function (v, cur) {
				return Number(v).toLocaleString(cur === 'INR' ? 'en-IN' : 'en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
			};
			var suggest = function () {
				var u = parseFloat($usd.val());
				if (isNaN(u)) { return null; }
				return u <= 0 ? 0 : Math.round(u * rate);
			};
			$plan.on('click', '#subUseSuggested', function () {
				var sg = suggest();
				if (sg !== null) { $inr.val(sg.toFixed(2)); refresh(); }
			});
			var refresh = function () {
				var name = $.trim($plan.find('[name="name"]').val());
				$plan.find('[data-pv="name"]').text(name || '—');
				var sg = suggest(), auto = $auto.is(':checked');
				if (auto) { $inr.val(sg === null ? '' : sg.toFixed(2)); }
				$inr.prop('readonly', auto);
				$plan.find('[data-pv="suggest"]').text(sg === null ? '—' : d.inrSign + ' ' + fmt(sg, 'INR'));
				$('#subInrSuggest').toggle(!auto && sg !== null && sg > 0 && parseFloat($inr.val()) !== sg);
				var price = $.trim($usd.val()), inr = $.trim($inr.val());
				var n = parseInt($plan.find('[name="interval_count"]').val(), 10) || 1;
				var unit = $plan.find('[name="billing_interval"]').val() === 'year' ? d.year : d.month;
				var html;
				if (price === '') {
					html = '<span class="sub-pc-price sub-pc-price-none">' + esc(d.notSet) + '</span>';
				} else if (parseFloat(price) <= 0) {
					html = '<span class="sub-pc-price">' + esc(d.free) + '</span>';
				} else {
					html = '<span class="sub-pc-price"><small>' + esc(d.usdSign) + '</small> ' + esc(fmt(price, 'USD')) + '</span>'
						+ '<span class="sub-pc-per">/ ' + (n > 1 ? n + ' × ' : '') + esc(unit) + '</span>'
						+ '<span class="sub-pc-alt">' + (inr !== '' && parseFloat(inr) > 0 ? esc(d.inrSign + ' ' + fmt(inr, 'INR')) + ' <small>INR</small>' : '<small>INR —</small>') + '</span>';
				}
				$plan.find('[data-pv="price"]').html(html);
				var trial = parseInt($plan.find('[name="trial_days"]').val(), 10) || 0;
				$plan.find('[data-pv="trial"]').prop('hidden', trial <= 0).html('<i class="fa fa-gift m-r-xs"></i>' + esc(String(d.trial).replace('{N}', trial)));
				$plan.find('[data-limit]').each(function () {
					var v = $.trim(this.value);
					$plan.find('[data-pv-limit="' + $(this).attr('data-limit') + '"]').text(v === '' ? d.unlimited : v);
				});
				var $f = $plan.find('input[name="features[]"]');
				$plan.find('[data-pv="count"]').text(String(d.count).replace('{N}', $f.filter(':checked').length).replace('{T}', $f.length));
				var on = function (name) { return $plan.find('input[name="' + name + '"]').is(':checked'); };
				$plan.find('[data-pv-flag]').each(function () {
					var flag = $(this).attr('data-pv-flag'), not = flag.charAt(0) === '!';
					$(this).toggle(not ? !on(flag.slice(1)) : on(flag));
				});
			};
			$plan.on('input change', 'input, select, textarea', refresh);
			$plan.on('click', '[data-sub-features]', function () {
				var all = $(this).attr('data-sub-features') === 'all';
				$plan.find('input[name="features[]"]').each(function () {
					this.checked = all || base.indexOf(this.value) !== -1;
				});
				refresh();
			});
			// "Plan for new companies" needs an active plan
			$plan.on('change', 'input[name="is_default"]', function () {
				if (this.checked) { $plan.find('input[name="status"]').prop('checked', true); refresh(); }
			}).on('change', 'input[name="status"]', function () {
				if (!this.checked) { $plan.find('input[name="is_default"]').prop('checked', false); refresh(); }
			});
			refresh();
		}
	});
})(window.jQuery);
