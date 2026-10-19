(function () {
	"use strict";

	function base() {
		return window.location.pathname.replace(/[^\/]*$/, '') + 'index.php';
	}

	function post(action, onOk, onErr) {
		var xhr = new XMLHttpRequest();
		xhr.open('POST', base() + '?controller=pjAdminSetup&action=' + action, true);
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		xhr.setRequestHeader('X-CSRF-Token', typeof pjCsrfToken === 'function' ? pjCsrfToken() : '');
		xhr.onreadystatechange = function () {
			if (xhr.readyState !== 4) { return; }
			var res = null;
			try { res = JSON.parse(xhr.responseText); } catch (e) { res = null; }
			if (res && res.status === 'OK') { onOk(res); }
			else { onErr(res && res.text ? res.text : 'Request failed.'); }
		};
		xhr.send('');
	}

	function status(el, text, cls) {
		if (!el) { return; }
		el.style.display = 'block';
		el.className = 'pj-setup-status ' + (cls || '');
		el.textContent = text;
	}

	function disableAll(disabled) {
		['pjLoadDemo', 'pjSkipDemo', 'pjRemoveDemo'].forEach(function (id) {
			var b = document.getElementById(id);
			if (b) { b.disabled = disabled; }
		});
	}

	function dashboard() {
		return base() + '?controller=pjAdmin&action=pjActionIndex';
	}

	document.addEventListener('DOMContentLoaded', function () {
		var statusEl = document.getElementById('pjSetupStatus');

		var load = document.getElementById('pjLoadDemo');
		if (load) {
			load.addEventListener('click', function () {
				disableAll(true);
				status(statusEl, load.getAttribute('data-busy') || 'Importing demo properties…', 'busy');
				post('pjActionLoadDemo', function () {
					status(statusEl, load.getAttribute('data-ok') || 'Demo properties imported. Redirecting…', 'ok');
					setTimeout(function () { window.location.href = dashboard(); }, 900);
				}, function (msg) {
					disableAll(false);
					status(statusEl, msg, 'err');
				});
			});
		}

		var skip = document.getElementById('pjSkipDemo');
		if (skip) {
			skip.addEventListener('click', function () {
				disableAll(true);
				post('pjActionSkipDemo', function () {
					window.location.href = dashboard();
				}, function (msg) {
					disableAll(false);
					status(statusEl, msg, 'err');
				});
			});
		}

		var remove = document.getElementById('pjRemoveDemo');
		if (remove) {
			remove.addEventListener('click', function () {
				pjPopup.confirm(remove.getAttribute('data-confirm') || 'Remove all demo properties? This cannot be undone.', function () {
				disableAll(true);
				status(statusEl, remove.getAttribute('data-busy') || 'Removing demo properties…', 'busy');
				post('pjActionRemoveDemo', function () {
					status(statusEl, remove.getAttribute('data-ok') || 'Demo properties removed. Redirecting…', 'ok');
					setTimeout(function () { window.location.reload(); }, 800);
				}, function (msg) {
					disableAll(false);
					status(statusEl, msg, 'err');
				});
				});
			});
		}
	});
})();
