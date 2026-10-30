/* Options > AI settings: show the chosen service, list / load its models, custom model, test service + key + model. */
(function ($) {
	"use strict";
	if (typeof $ === 'undefined') { return; }
	$(function () {
		var $form = $('#frmAiSettings');
		if (!$form.length) { return; }
		var url = $form.data('url');

		function panel(el) { return $(el).closest('.ai-service-panel'); }
		function show($p, ok, text) {
			$p.find('.ai-result').removeClass('text-success text-danger text-muted')
				.addClass(ok === null ? 'text-muted' : (ok ? 'text-success' : 'text-danger')).text(text);
		}
		/* model to use: the list entry, or the id typed after "Custom model..." */
		function currentModel($p) {
			var v = $p.find('.ai-model-select').val();
			return v === '__custom__' ? $.trim($p.find('.ai-custom-model').val()) : v;
		}
		function toggleCustom($p) {
			var custom = $p.find('.ai-model-select').val() === '__custom__';
			$p.find('.ai-custom-model').toggleClass('hidden', !custom);
			if (custom) { $p.find('.ai-custom-model').trigger('focus'); }
		}

		$('#ai_provider').on('change', function () {
			var s = $(this).val();
			$form.find('.ai-service-panel').addClass('hidden').filter('[data-service="' + s + '"]').removeClass('hidden');
		});

		$form.on('change', '.ai-model-select', function () { toggleCustom(panel(this)); });

		/* API key field: hidden as dots, the eye shows / hides it */
		$form.on('click', '.ai-key-toggle', function () {
			var $p = panel(this), $key = $p.find('.ai-key'), shown = $key.hasClass('ai-key-masked');
			$key.toggleClass('ai-key-masked', !shown);
			$(this).attr({ 'aria-pressed': shown ? 'true' : 'false', 'aria-label': shown ? 'Hide the key' : 'Show the key' })
				.find('i').toggleClass('fa-eye', !shown).toggleClass('fa-eye-slash', shown);
		});

		$form.on('click', '.ai-load-models', function () {
			var $p = panel(this), $btn = $(this), $sel = $p.find('.ai-model-select');
			show($p, null, $form.data('txt-loading'));
			$btn.prop('disabled', true);
			$.post(url, { 'do': 'models', service: $p.data('service'), key: $p.find('.ai-key').val() }, null, 'json')
				.done(function (r) {
					if (r && r.status === 'OK') {
						var keep = $sel.val(), customs = [];
						/* keep custom entries (marked) that are not in the loaded list */
						$sel.find('option').each(function () {
							var v = $(this).val();
							if (v && v !== '__custom__' && $.inArray(v, r.models || []) === -1 && $(this).text().indexOf($form.data('txt-custom-tag')) !== -1) { customs.push(v); }
						});
						$sel.empty();
						$.each(r.models || [], function (i, id) { $('<option>').val(id).text(id).appendTo($sel); });
						$.each(customs, function (i, id) { $('<option>').val(id).text(id + ' ' + $form.data('txt-custom-tag')).appendTo($sel); });
						if (keep && keep !== '__custom__' && !$sel.find('option').filter(function () { return $(this).val() === keep; }).length) {
							$('<option>').val(keep).text(keep).prependTo($sel);
						}
						$('<option>').val('__custom__').text($form.data('txt-custom')).appendTo($sel);
						$sel.val(keep && keep !== '__custom__' ? keep : ($sel.find('option').first().val()));
						toggleCustom($p);
						show($p, true, String($form.data('txt-models-found')).replace('%u', (r.models || []).length));
					} else {
						show($p, false, (r && r.text) || $form.data('txt-error'));
					}
				})
				.fail(function () { show($p, false, $form.data('txt-error')); })
				.always(function () { $btn.prop('disabled', false); });
		});

		$form.on('click', '.ai-test', function () {
			var $p = panel(this), $btn = $(this);
			show($p, null, $form.data('txt-testing'));
			$btn.prop('disabled', true);
			$.ajax({ url: url, type: 'POST', dataType: 'json', timeout: 180000,
				data: { 'do': 'test', service: $p.data('service'), key: $p.find('.ai-key').val(), model: currentModel($p) } })
				.done(function (r) { show($p, r && r.status === 'OK', (r && r.text) || $form.data('txt-error')); })
				.fail(function () { show($p, false, $form.data('txt-error')); })
				.always(function () { $btn.prop('disabled', false); });
		});
	});
})(window.jQuery);
