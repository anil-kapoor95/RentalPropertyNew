<script>
(function () {
	var orderId = <?php echo pjAppController::jsonEncode((string) @$tpl['order_id']); ?>,
		options = {
			key: <?php echo pjAppController::jsonEncode((string) @$tpl['arr']['public_key']); ?>,
			amount: <?php echo (int) @$tpl['arr']['amount']; ?>,
			currency: <?php echo pjAppController::jsonEncode(strtoupper((string) @$tpl['arr']['currency_code'])); ?>,
			name: <?php echo pjAppController::jsonEncode((string) @$tpl['arr']['item_name']); ?>,
			description: <?php echo pjAppController::jsonEncode((string) @$tpl['arr']['description']); ?>,
			order_id: orderId,
			callback_url: <?php echo pjAppController::jsonEncode(@$tpl['arr']['notify_url'] . '&id=' . rawurlencode((string) @$tpl['arr']['custom'])); ?>,
			cancel_url: <?php echo pjAppController::jsonEncode(@$tpl['arr']['cancel_url'] . '&id=' . rawurlencode((string) @$tpl['arr']['custom'])); ?>,
			redirect: true,
			prefill: {
				name: <?php echo pjAppController::jsonEncode(trim(@$tpl['arr']['first_name'] . ' ' . @$tpl['arr']['last_name'])); ?>,
				email: <?php echo pjAppController::jsonEncode((string) @$tpl['arr']['email']); ?>,
				contact: <?php echo pjAppController::jsonEncode((string) @$tpl['arr']['phone']); ?>
			},
			notes: {
				uuid: <?php echo pjAppController::jsonEncode((string) @$tpl['arr']['custom']); ?>
			}
		};

	function loadScript(url, callback) {
		var script = document.createElement("script");
		script.type = "text/javascript";
		script.async = true;
		if (script.readyState) {
			script.onreadystatechange = function () {
				if (script.readyState === "loaded" || script.readyState === "complete") {
					script.onreadystatechange = null;
					if (callback && typeof callback === "function") {
						callback();
					}
				}
			};
		} else {
			script.onload = function () {
				if (callback && typeof callback === "function") {
					callback();
				}
			};
		}
		script.src = url;
		(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(script);
	}

	function razorpayCallback() {
		if (!orderId || !window.Razorpay) {
			return;
		}

		var rzp = new Razorpay({
			key: options.key,
			amount: options.amount,
			currency: options.currency,
			name: options.name,
			description: options.description,
			order_id: options.order_id,
			callback_url: options.callback_url,
			redirect: options.redirect,
			prefill: options.prefill,
			notes: options.notes,
			modal: {
				escape: false,
				ondismiss: function () {
					// Cancel the order if the checkout is closed by the customer.
					window.location.href = options.cancel_url;
				}
			}
		});

		rzp.on('payment.failed', function () {
			window.location.href = options.cancel_url;
		});

		rzp.open();
	}

	if (!window.Razorpay) {
		loadScript('https://checkout.razorpay.com/v1/checkout.js', razorpayCallback);
	} else {
		razorpayCallback();
	}
})();
</script>
