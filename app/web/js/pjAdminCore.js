/* CSRF: attach token to all same-origin admin AJAX + form submits. */
function pjCsrfToken(){var m=document.querySelector('meta[name="csrf-token"]');return m?m.getAttribute("content"):"";}
 
(function(){
	if (typeof jQuery === 'undefined') { return; }
	jQuery.ajaxPrefilter(function (options) {
		if (options.crossDomain) { return; }
		var t = pjCsrfToken(); if (!t) { return; }
		options.headers = options.headers || {};
		options.headers['X-CSRF-Token'] = t;
	});
 
	function pjAttachCsrf(form) {
		var m = (form.getAttribute('method') || 'get').toLowerCase();
		if (m !== 'post') { return; }
		var t = pjCsrfToken();
 
		if (t && !form.querySelector('input[name="csrf_token"]')) {
			var i = document.createElement('input');
			i.type = 'hidden'; i.name = 'csrf_token'; i.value = t;
			form.appendChild(i);
		}
	}
 
	jQuery(document).on('submit', 'form', function () { pjAttachCsrf(this); });
 
	/* jQuery Validate (and other code) call the native HTMLFormElement.submit(),
	   which does NOT fire the delegated 'submit' handler above. Wrap it so the
	   CSRF token is still injected on programmatic submits. */
 
	if (window.HTMLFormElement && HTMLFormElement.prototype && !HTMLFormElement.prototype.__pjCsrfWrapped) {
		var pjNativeSubmit = HTMLFormElement.prototype.submit;
 
		HTMLFormElement.prototype.submit = function () {
			try { pjAttachCsrf(this); } catch (e) {}
			return pjNativeSubmit.apply(this, arguments);
		};
 
		HTMLFormElement.prototype.__pjCsrfWrapped = true;
	}
})();

var abApp = abApp || {};
var jQuery = jQuery || $.noConflict();
(function ($, undefined) {
	"use strict";
	$(function () {
		$(".pj-table tbody").on("mouseenter", "tr", function () {
			$(this).addClass("pj-table-row-hover");
		}).on("mouseleave", "tr", function () {
			$(this).removeClass("pj-table-row-hover");
		})
		;
		$(".pj-button").hover(
			function () {
				$(this).addClass("pj-button-hover");
			}, 
			function () {
				$(this).removeClass("pj-button-hover");
			}
		);
		$(".pj-checkbox").hover(
			function () {
				$(this).addClass("pj-checkbox-hover");
			}, 
			function () {
				$(this).removeClass("pj-checkbox-hover");
			}
		);
		$("#content").on("click", ".notice-close", function (e) {
			if (e && e.preventDefault) {
				e.preventDefault();
			}
			$(this).closest(".notice-box").fadeOut();
			return false;
		});
		
		$("#leftmenu").on("change", ".setCalendarId", function () {
			var cid = $("option:selected", this).val();
			if (cid !== "" && cid.length > 0) {
				var $this = $(this);
				window.location.href = ["index.php?controller=pjAdmin&action=pjActionRedirect&nextController=", $this.data("controller"), "&nextAction=", $this.data("action"), "&calendar_id=", cid, "&nextParams=", encodeURIComponent("id=" + cid)].join("");
			}
		});
		
		$("html").on("click", ".pjTsCheckAssignCalendar", function (e) {
			if (e && e.preventDefault) {
				e.preventDefault();
			}
			var href = $(this).attr('href');
			$.get("index.php?controller=pjAdminCalendars&action=pjActionCheckAssignCalendar").done(function (data) {
				if(data.status == 'OK')
				{
					window.location.href = href;	
				}else{
					swal({
		    			title: "",
						text: data.text,
						type: "warning",
						confirmButtonColor: "#DD6B55",
						confirmButtonText: data.btnOK,
						closeOnConfirm: false,
						showLoaderOnConfirm: false
					}, function () {
						swal.close();
					});
				}
			});
		});
	});
})(jQuery);

/* A required field filled with spaces only counts as empty (all admin forms). */
(function ($) {
	if (typeof $ === 'undefined') { return; }
	function pjPatchRequired() {
		if (!$.validator || $.validator.pjTrimRequired) { return; }
		var orig = $.validator.methods.required;
		$.validator.methods.required = function (value, element, param) {
			if (element && /^(input|textarea)$/i.test(element.nodeName) && !this.checkable(element) && element.type !== 'file' && typeof value === 'string') {
				value = $.trim(value);
			}
			return orig.call(this, value, element, param);
		};
		$.validator.pjTrimRequired = true;
	}
	pjPatchRequired();
	$(pjPatchRequired);
})(window.jQuery);

/*
 * In-page popups for the whole back-office (never the browser's alert / confirm boxes).
 *   pjPopup.confirm(text, onYes)        - Yes / Cancel popup
 *   pjPopup.message(text, type)         - message popup ("error", "warning", "success", "info")
 *   <form data-sub-confirm="text">       - asks in a popup before the form is sent
 */
(function (window, document) {
	function meta(name, def) {
		var m = document.querySelector('meta[name="' + name + '"]');
		return m && m.getAttribute('content') ? m.getAttribute('content') : def;
	}
	function fallback(text, buttons, done) {
		// sweetalert missing: a small built-in popup
		var wrap = document.createElement('div');
		wrap.className = 'pj-popup-wrap';
		wrap.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:100000;display:flex;align-items:center;justify-content:center;padding:16px';
		wrap.innerHTML = '<div class="pj-popup" role="dialog" aria-modal="true" style="background:#fff;border-radius:10px;padding:24px;max-width:420px;width:100%;box-shadow:0 10px 30px rgba(0,0,0,.2)"><p style="margin:0 0 18px;font-size:15px"></p><div class="pj-popup-btns" style="display:flex;gap:8px;justify-content:flex-end"></div></div>';
		wrap.querySelector('p').textContent = text;
		var box = wrap.querySelector('.pj-popup-btns');
		buttons.forEach(function (b) {
			var el = document.createElement('button');
			el.type = 'button';
			el.className = 'btn ' + (b.primary ? 'btn-primary' : 'btn-white');
			el.textContent = b.label;
			el.addEventListener('click', function () { document.body.removeChild(wrap); if (b.primary && done) { done(); } });
			box.appendChild(el);
		});
		document.body.appendChild(wrap);
		box.lastChild.focus();
	}
	var pjPopup = {
		confirm: function (text, onYes) {
			var yes = meta('pj-label-yes', 'Yes'), no = meta('pj-label-cancel', 'Cancel');
			if (typeof window.swal === 'function') {
				window.swal({title: '', text: text, type: 'warning', showCancelButton: true, confirmButtonColor: '#DD6B55',
					confirmButtonText: yes, cancelButtonText: no, closeOnConfirm: true}, function (ok) { if (ok && onYes) { onYes(); } });
			} else {
				fallback(text, [{label: no}, {label: yes, primary: true}], onYes);
			}
		},
		message: function (text, type) {
			var okLbl = meta('pj-label-close', 'OK');
			if (typeof window.swal === 'function') {
				window.swal({title: '', text: text, type: type || 'error', confirmButtonColor: '#DD6B55', confirmButtonText: okLbl});
			} else {
				fallback(text, [{label: okLbl, primary: true}]);
			}
		}
	};
	window.pjPopup = pjPopup;
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || !form.getAttribute || !form.hasAttribute('data-sub-confirm') || form.getAttribute('data-sub-confirmed') === '1') { return; }
		e.preventDefault();
		pjPopup.confirm(form.getAttribute('data-sub-confirm'), function () {
			form.setAttribute('data-sub-confirmed', '1');
			if (typeof form.requestSubmit === 'function') { form.requestSubmit(); } else { form.submit(); }
		});
	}, true);
})(window, document);
