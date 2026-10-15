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
