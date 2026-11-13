/*
 * Super Admin dashboard (pjAdmin/pjActionPlatform.php): date-range form, subscription overview donut and the
 * subscription trends line chart (7D / 30D / 90D). Data comes from the page (window.pjPlatformDash), no requests.
 */
(function () {
	'use strict';
	var NS = 'http://www.w3.org/2000/svg';
	function el(name, attrs) {
		var n = document.createElementNS(NS, name), k;
		for (k in attrs) { if (attrs.hasOwnProperty(k)) { n.setAttribute(k, attrs[k]); } }
		return n;
	}
	function ready(fn) {
		if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
	}
	/* date range: show the custom dates only for "custom", submit on change of a preset */
	function initRange() {
		var form = document.getElementById('pdRange');
		if (!form) { return; }
		var sel = form.querySelector('select[name="range"]');
		var sync = function () { form.classList.toggle('is-custom', sel.value === 'custom'); };
		sel.addEventListener('change', function () {
			sync();
			if (sel.value !== 'custom') { form.submit(); }
		});
		form.addEventListener('submit', function (e) {
			if (sel.value !== 'custom') { return; }
			var f = form.querySelector('input[name="from"]'), t = form.querySelector('input[name="to"]');
			if (f.value && t.value && f.value > t.value) {
				e.preventDefault();
				t.setCustomValidity(form.getAttribute('data-msg-order') || 'Check the dates.');
				t.reportValidity();
				t.setCustomValidity('');
			}
		});
		sync();
	}

	/* donut: one arc per status (the colors and counts are in the legend items) */
	function initDonut() {
		var box = document.getElementById('pdDonut');
		if (!box) { return; }
		var items = box.parentNode.querySelectorAll('.pd-legend li[data-value]');
		var total = 0, i;
		for (i = 0; i < items.length; i++) { total += parseInt(items[i].getAttribute('data-value'), 10) || 0; }
		var r = 70, c = 2 * Math.PI * r, svg = el('svg', { viewBox: '0 0 180 180', role: 'img', 'aria-label': box.getAttribute('data-label') || '' });
		svg.appendChild(el('circle', { cx: 90, cy: 90, r: r, fill: 'none', stroke: '#f1f3f6', 'stroke-width': 22 }));
		var offset = 0, gap = total > 0 ? 2 : 0;
		for (i = 0; i < items.length && total > 0; i++) {
			var v = parseInt(items[i].getAttribute('data-value'), 10) || 0;
			if (v <= 0) { continue; }
			var len = c * v / total;
			var arc = el('circle', { cx: 90, cy: 90, r: r, fill: 'none', stroke: items[i].getAttribute('data-color'), 'stroke-width': 22,
				'stroke-dasharray': Math.max(0.1, len - (v === total ? 0 : gap)) + ' ' + c, 'stroke-dashoffset': -offset });
			var t = el('title', {});
			t.textContent = items[i].querySelector('.pd-legend-name').textContent + ': ' + v;
			arc.appendChild(t);
			svg.appendChild(arc);
			offset += len;
		}
		box.insertBefore(svg, box.firstChild);
	}

	/* trends line chart */
	function initTrends(data) {
		var box = document.getElementById('pdTrends');
		if (!box || !data || !data.labels || !data.labels.length) { return; }
		var tip = box.querySelector('.pd-chart-tip');
		var series = data.series;

		// the whole selected period (one point per day, week or month, prepared by the server)
		function draw() {
			var n = data.labels.length;
			var rows = series.map(function (s) { return { s: s, v: data[s.key] }; });
			var max = 0;
			rows.forEach(function (r) { r.v.forEach(function (x) { if (x > max) { max = x; } }); });
			var step = max <= 4 ? 1 : Math.ceil(max / 4);
			var top = Math.max(4, step * 4);
			var W = Math.max(280, box.clientWidth), H = box.clientHeight || 240, L = 34, R = 10, T = 10, B = 26;
			var w = W - L - R, h = H - T - B;
			var x = function (i) { return L + (n === 1 ? w / 2 : w * i / (n - 1)); };
			var y = function (v) { return T + h - h * v / top; };

			var old = box.querySelector('svg');
			if (old) { box.removeChild(old); }
			var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, preserveAspectRatio: 'none', role: 'img', 'aria-label': box.getAttribute('data-label') || '' });
			var g, k;
			for (k = 0; k <= 4; k++) {
				var val = step * k, yy = y(val);
				svg.appendChild(el('line', { x1: L, x2: W - R, y1: yy, y2: yy, 'class': 'pd-gridline' }));
				var lab = el('text', { x: L - 8, y: yy + 4, 'text-anchor': 'end', 'class': 'pd-axis' });
				lab.textContent = val;
				svg.appendChild(lab);
			}
			var raw = Math.ceil(n / Math.max(2, Math.floor(w / 70))), ticks = raw;
			[1, 2, 3, 4, 5, 7, 10, 14, 15, 21, 30, 60, 90].some(function (c) { if (c >= raw) { ticks = c; return true; } return false; });
			for (k = 0; k < n; k += ticks) {
				var tx = el('text', { x: x(k), y: H - 6, 'text-anchor': k === 0 ? 'start' : (k + ticks >= n && n > 1 ? 'end' : 'middle'), 'class': 'pd-axis' });
				tx.textContent = data.labels[k];
				svg.appendChild(tx);
			}
			// soft area under the main series (as on the reference design)
			var defs = el('defs', {});
			svg.appendChild(defs);
			rows.forEach(function (r, ri) {
				if (!r.s.fill) { return; }
				var gid = 'pdFill' + ri;
				var grad = el('linearGradient', { id: gid, x1: 0, y1: 0, x2: 0, y2: 1 });
				grad.appendChild(el('stop', { offset: '0%', 'stop-color': r.s.color, 'stop-opacity': 0.22 }));
				grad.appendChild(el('stop', { offset: '100%', 'stop-color': r.s.color, 'stop-opacity': 0.02 }));
				defs.appendChild(grad);
				var area = r.v.map(function (v, i) { return (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(v).toFixed(1); }).join(' ')
					+ ' L' + x(n - 1).toFixed(1) + ' ' + y(0).toFixed(1) + ' L' + x(0).toFixed(1) + ' ' + y(0).toFixed(1) + ' Z';
				svg.appendChild(el('path', { d: area, fill: 'url(#' + gid + ')', stroke: 'none' }));
			});
			rows.forEach(function (r) {
				var d = r.v.map(function (v, i) { return (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(v).toFixed(1); }).join(' ');
				svg.appendChild(el('path', { d: d, fill: 'none', stroke: r.s.color, 'stroke-width': 2.2, 'stroke-linejoin': 'round', 'stroke-linecap': 'round' }));
				if (n <= 31) {
					r.v.forEach(function (v, i) {
						if (v > 0) { svg.appendChild(el('circle', { cx: x(i), cy: y(v), r: 3.5, fill: r.s.color, stroke: '#fff', 'stroke-width': 1.5 })); }
					});
				}
			});
			var guide = el('line', { y1: T, y2: T + h, stroke: '#d1d5db', 'stroke-dasharray': '3 3', visibility: 'hidden' });
			svg.appendChild(guide);
			var hit = el('rect', { x: L, y: T, width: w, height: h, fill: 'transparent' });
			svg.appendChild(hit);
			box.insertBefore(svg, box.firstChild);

			var move = function (ev) {
				var rect = svg.getBoundingClientRect();
				var px = (ev.touches ? ev.touches[0].clientX : ev.clientX) - rect.left;
				var vx = px * W / rect.width;
				var i = n === 1 ? 0 : Math.round((vx - L) / w * (n - 1));
				i = Math.max(0, Math.min(n - 1, i));
				guide.setAttribute('x1', x(i)); guide.setAttribute('x2', x(i)); guide.setAttribute('visibility', 'visible');
				tip.textContent = '';
				var head = document.createElement('strong');
				head.textContent = data.tips[i];
				tip.appendChild(head);
				rows.forEach(function (r) {
					tip.appendChild(document.createElement('br'));
					var dot = document.createElement('span');
					dot.style.color = r.s.color;
					dot.textContent = '\u25CF ';
					tip.appendChild(dot);
					tip.appendChild(document.createTextNode(r.s.label + ': ' + r.v[i]));
				});
				tip.style.display = 'block';
				var left = x(i) * rect.width / W + 12;
				if (left + tip.offsetWidth > rect.width) { left = x(i) * rect.width / W - tip.offsetWidth - 12; }
				tip.style.left = Math.max(0, left) + 'px';
				tip.style.top = '8px';
			};
			var leave = function () { guide.setAttribute('visibility', 'hidden'); tip.style.display = 'none'; };
			hit.addEventListener('mousemove', move);
			hit.addEventListener('touchstart', move, { passive: true });
			hit.addEventListener('touchmove', move, { passive: true });
			hit.addEventListener('mouseleave', leave);

			// totals of the shown period
			rows.forEach(function (r) {
				var out = document.querySelector('[data-pd-total="' + r.s.key + '"]');
				if (out) { out.textContent = r.v.reduce(function (a, b) { return a + b; }, 0); }
			});
		}

		var timer;
		window.addEventListener('resize', function () { clearTimeout(timer); timer = setTimeout(draw, 150); });
		draw();
	}

	ready(function () {
		initRange();
		initDonut();
		initTrends(window.pjPlatformDash);
	});
})();
