/*
 * Super Admin dashboard (pjAdmin/pjActionPlatform.php). Draws every card and chart from the payload of
 * pjAdmin::platformPayload (real data) and reloads it from pjAdmin::pjActionPlatformData when the period changes.
 * Amounts use the currency style the server sends (pjMoney: symbol, decimals, thousands separator, position);
 * ready-made texts are used where several currencies are summed.
 */
(function () {
	'use strict';
	var boot = window.pjSaDashboard;
	var root = document.getElementById('saDash');
	if (!boot || !root) { return; }
	var T = boot.t, P = boot.payload, TODAY = boot.today;
	var NS = 'http://www.w3.org/2000/svg';
	var PLAN_COLORS = ['#F97618', '#2a78d6', '#1baf7a', '#eda100'], OTHER = '#9aa3ae';
	var KIND_COLORS = ['#F97618', '#2a78d6', '#1baf7a'];
	var state = { revView: 'plan', revHidden: {}, growHidden: {} };
	/* reporting currency of the figures (USD default / INR): sent with every reload of the period */
	var params = {};
	(function () {
		var q = new URLSearchParams(window.location.search);
		['range', 'from', 'to'].forEach(function (k) { if (q.get(k)) { params[k] = q.get(k); } });
		params.cur = P.data.report && P.data.report.code === 'INR' ? 'INR' : 'USD';
	})();

	/* ---------- helpers ---------- */
	function $(name) { return root.querySelector('[data-sa="' + name + '"]'); }
	function esc(s) { return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	function svgEl(n, a) { var e = document.createElementNS(NS, n); for (var k in a) { if (a[k] !== null && a[k] !== undefined) { e.setAttribute(k, a[k]); } } return e; }
	function sum(a) { return a.reduce(function (x, y) { return x + y; }, 0); }
	function fmt(s, a) { var i = 0; return String(s).replace(/%[su]/g, function () { return a[i++]; }); }
	function num(v) { return Math.round(v).toLocaleString('en-US').replace(/,/g, P.data.money.thousands || ''); }
	/* the same rules as pjMoney::format */
	function money(v, decimals) {
		var m = P.data.money, d = decimals === undefined ? m.decimals : decimals;
		var neg = v < 0 && Math.abs(v) >= Math.pow(10, -d) / 2;
		var parts = Math.abs(v).toFixed(d).split('.');
		var whole = m.thousands ? parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, m.thousands) : parts[0];
		var n = whole + (parts[1] ? '.' + parts[1] : '');
		var out = !m.symbol ? n : (m.place === 'front' ? m.symbol + ' ' + n : n + ' ' + m.symbol);
		return (neg ? '-' : '') + out;
	}
	/* short axis labels: 12.5k / 1.2M with the symbol */
	function shortMoney(v) {
		var m = P.data.money, s = v >= 1e6 ? (v / 1e6).toFixed(v % 1e6 ? 1 : 0) + 'M' : v >= 1e3 ? (v / 1e3).toFixed(v % 1e3 ? 1 : 0) + 'k' : String(Math.round(v));
		return m.place === 'front' ? m.symbol + s : s + ' ' + m.symbol;
	}
	function niceStep(v) { if (v <= 0) { return 1; } var p = Math.pow(10, Math.floor(Math.log10(v))), x = v / p; return (x <= 1 ? 1 : x <= 2 ? 2 : x <= 2.5 ? 2.5 : x <= 5 ? 5 : 10) * p; }
	function barPath(x, y, w, h, r) { if (h <= 0) { return ''; } r = Math.min(r, h, w / 2); return 'M' + x + ' ' + (y + h) + 'V' + (y + r) + 'Q' + x + ' ' + y + ' ' + (x + r) + ' ' + y + 'H' + (x + w - r) + 'Q' + (x + w) + ' ' + y + ' ' + (x + w) + ' ' + (y + r) + 'V' + (y + h) + 'Z'; }
	function delta(cur, prev, isPoints) {
		if (prev === null || prev === undefined || cur === null) { return ''; }
		if (!prev) { return cur > 0 ? '<span class="sa-delta up">▲ ' + esc(T.badge_new) + '</span>' : ''; }
		var d = isPoints ? Math.round(cur - prev) : Math.round((cur - prev) * 100 / prev);
		var cls = d > 0 ? 'up' : d < 0 ? 'down' : 'flat';
		return '<span class="sa-delta ' + cls + '" title="' + esc(P.range.compare) + '">' + (d > 0 ? '▲ ' : d < 0 ? '▼ ' : '') + Math.abs(d) + (isPoints ? ' pt' : '%') + '</span>';
	}

	/* ---------- tooltip ---------- */
	var tip = document.getElementById('saTip');
	function showTip(ev, title, rows) {
		var html = '<b>' + esc(title) + '</b>';
		rows.forEach(function (r) { html += '<div class="sa-tip-row"><span>' + (r.c ? '<i style="background:' + r.c + '"></i>' : '') + esc(r.k) + '</span><span>' + esc(r.v) + '</span></div>'; });
		tip.innerHTML = html; tip.style.opacity = 1;
		var x = ev.clientX + 14, y = ev.clientY + 14, w = tip.offsetWidth, h = tip.offsetHeight;
		tip.style.left = (x + w > window.innerWidth - 8 ? ev.clientX - w - 14 : x) + 'px';
		tip.style.top = (y + h > window.innerHeight - 8 ? ev.clientY - h - 14 : y) + 'px';
	}
	function hideTip() { tip.style.opacity = 0; }

	/* ---------- KPI cards ---------- */
	function spark(values, color) {
		if (!values || values.length < 2) { return '<div class="sa-spark is-empty"></div>'; }
		var w = 200, h = 34, max = Math.max.apply(null, values), min = Math.min.apply(null, values), span = (max - min) || 1;
		var d = values.map(function (v, i) { return (i ? 'L' : 'M') + (i * w / (values.length - 1)).toFixed(1) + ' ' + (h - 3 - (h - 6) * (v - min) / span).toFixed(1); }).join(' ');
		return '<svg class="sa-spark" viewBox="0 0 ' + w + ' ' + h + '" preserveAspectRatio="none" aria-hidden="true"><path d="' + d + ' L' + w + ' ' + h + ' L0 ' + h + ' Z" fill="' + color + '" opacity=".10"/><path d="' + d + '" fill="none" stroke="' + color + '" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round"/></svg>';
	}
	function renderKpis() {
		var k = P.data.kpi, s = P.data.series;
		var conv = k.conversion;
		var cards = [
			{ label: T.kpi_revenue, value: k.revenue.multi ? k.revenue.text : money(k.revenue.value), icon: 'fa-money', accent: true, spark: spark(s.revenue, '#F97618'),
				// sub line: what was collected per currency (INR / USD); the big number is the consolidated USD at stored rates
				// the amounts actually charged, in their own currencies (not converted)
				foot: delta(k.revenue.value, k.revenue.prev), sub: k.revenue.collected_text ? fmt(T.charged, [k.revenue.collected_text]) : T.vs_prev,
				title: k.revenue.text + (k.revenue.collected_text ? ' (' + fmt(T.charged, [k.revenue.collected_text]) + ')' : '') + ' · ' + P.range.compare + ': ' + k.revenue.prev_text },
			{ label: T.kpi_companies, value: num(k.companies.value), icon: 'fa-building', spark: spark(s.companies, '#2a78d6'),
				foot: '<span class="sa-delta up">▲ ' + esc(fmt(T.new_n, [k.companies['new']])) + '</span>', sub: fmt(T.left_n, [k.companies.left]) },
			{ label: T.kpi_subs, value: num(k.subs.value), icon: 'fa-credit-card', spark: spark(s.active, '#1baf7a'),
				foot: delta(k.subs.value, k.subs.prev), sub: fmt(T.trial_n, [k.subs.trial]) },
			{ label: T.kpi_mrr, value: k.mrr.text, icon: 'fa-refresh', spark: spark(s.mrr, '#2a78d6'), foot: delta(k.mrr.value, k.mrr.prev), sub: fmt(T.paying_n, [k.mrr.paying]) },
			{ label: T.kpi_conv, value: conv.rate === null ? '—' : conv.rate + '%', icon: 'fa-random', spark: spark(s.trials_started, '#6b7280'),
				foot: conv.rate === null ? '<span class="sa-muted">' + esc(T.no_trials) + '</span>' : delta(conv.rate, conv.prev_rate, true), sub: conv.rate === null ? '' : fmt(T.conv_sub, [conv.converted, conv.trials]) }
		];
		$('kpis').innerHTML = cards.map(function (c) {
			var long = String(c.value).length > 12 ? ' is-long' : '';
			return '<article class="sa-card sa-kpi' + (c.accent ? ' is-accent' : '') + '"' + (c.title ? ' title="' + esc(c.title) + '"' : '') + '>'
				+ '<div class="sa-kpi-top"><span class="sa-kpi-label">' + esc(c.label) + '</span><span class="sa-kpi-ico"><i class="fa ' + c.icon + '"></i></span></div>'
				+ '<div class="sa-kpi-val' + long + '">' + esc(c.value) + '</div>'
				+ '<div class="sa-kpi-foot">' + c.foot + '<span>' + esc(c.sub) + '</span></div>' + c.spark + '</article>';
		}).join('');
	}

	/* ---------- revenue: stacked bars ---------- */
	function revSeries() {
		var src = state.revView === 'plan' ? P.data.series.by_plan : P.data.series.by_kind;
		return src.map(function (s, i) {
			var color = state.revView === 'plan' ? (s.other ? OTHER : PLAN_COLORS[i] || OTHER) : KIND_COLORS[i];
			return { id: state.revView + i, name: s.name, values: s.values, color: color };
		});
	}
	function renderRevenue() {
		var box = $('revChart'), series = revSeries(), labels = P.labels, n = labels.length;
		var shown = series.filter(function (s) { return !state.revHidden[s.id]; });
		var total = sum(P.data.series.revenue);
		$('revTotals').innerHTML = series.filter(function (s) { return sum(s.values) > 0; }).map(function (s) { return '<div><strong>' + esc(money(sum(s.values))) + '</strong>' + esc(s.name) + '</div>'; }).join('')
			+ '<div><strong>' + esc(money(n ? total / n : 0)) + '</strong>' + esc(fmt(T.average_per, [P.range.bucket_label])) + '</div>';
		var note = $('revNote');
		note.hidden = !P.data.other_currency; note.textContent = P.data.other_currency ? T.other_currency : '';
		if (!total) { box.innerHTML = '<div class="sa-empty"><i class="fa fa-line-chart"></i>' + esc(T.no_revenue) + '</div>'; $('revLegend').innerHTML = ''; return; }
		var W = Math.max(280, box.clientWidth), H = box.clientHeight || 250, L = 62, R = 6, Tp = 8, B = 26, w = W - L - R, h = H - Tp - B;
		var totals = labels.map(function (_, i) { return sum(shown.map(function (s) { return s.values[i]; })); });
		var step = niceStep(Math.max(1, Math.max.apply(null, totals)) / 4), max = step * 4;
		var svg = svgEl('svg', { viewBox: '0 0 ' + W + ' ' + H, height: H, role: 'img', 'aria-label': T.kpi_revenue });
		for (var k = 0; k <= 4; k++) {
			var y = Tp + h - h * k / 4;
			svg.appendChild(svgEl('line', { x1: L, x2: W - R, y1: y, y2: y, 'class': 'sa-gridline' }));
			var tx = svgEl('text', { x: L - 8, y: y + 4, 'text-anchor': 'end', 'class': 'sa-axis' }); tx.textContent = shortMoney(step * k); svg.appendChild(tx);
		}
		var slot = w / n, bw = Math.max(3, Math.min(34, slot * 0.62)), every = Math.ceil(n / Math.max(2, Math.floor(w / 70)));
		labels.forEach(function (lab, i) {
			var x = L + slot * i + (slot - bw) / 2, yy = Tp + h, last = -1;
			shown.forEach(function (s, si) { if (s.values[i] > 0) { last = si; } });
			shown.forEach(function (s, si) {
				var bh = h * s.values[i] / max; if (bh <= 0) { return; }
				var gap = si > 0 && yy < Tp + h ? 2 : 0;
				svg.appendChild(svgEl('path', { d: barPath(x, yy - bh, bw, Math.max(0.5, bh - gap), si === last ? 4 : 0), fill: s.color }));
				yy -= bh;
			});
			if (i % every === 0) { var t = svgEl('text', { x: x + bw / 2, y: H - 6, 'text-anchor': 'middle', 'class': 'sa-axis' }); t.textContent = lab; svg.appendChild(t); }
			var hit = svgEl('rect', { x: L + slot * i, y: Tp, width: slot, height: h, fill: 'transparent' });
			hit.addEventListener('mousemove', function (ev) {
				showTip(ev, P.tips[i], series.map(function (s) { return { k: s.name, v: money(s.values[i]), c: s.color }; }).concat([{ k: T.total, v: money(P.data.series.revenue[i]) }]));
			});
			hit.addEventListener('mouseleave', hideTip);
			svg.appendChild(hit);
		});
		box.replaceChildren(svg);
		var lg = $('revLegend');
		lg.innerHTML = series.map(function (s) { return '<button type="button" data-id="' + s.id + '" aria-pressed="' + (!state.revHidden[s.id]) + '"><i style="background:' + s.color + '"></i>' + esc(s.name) + '</button>'; }).join('');
		Array.prototype.forEach.call(lg.querySelectorAll('button'), function (b) {
			b.addEventListener('click', function () {
				var id = b.getAttribute('data-id');
				state.revHidden[id] = !state.revHidden[id];
				if (series.every(function (s) { return state.revHidden[s.id]; })) { state.revHidden[id] = false; }
				renderRevenue();
			});
		});
	}

	/* ---------- plan distribution: donut ---------- */
	function renderMix() {
		var mix = P.data.plan_mix, box = $('mix');
		$('asOf').textContent = '(' + P.range.to_label + ')';
		var items = mix.plans.map(function (p, i) {
			return { name: p.name, n: p.count, color: p.free ? '#c4c9d2' : (PLAN_COLORS[i] || OTHER),
				sub: (p.free ? T.free : fmt(T.mrr_short, [p.mrr_text || ''])) + (p.trial ? ' · ' + fmt(T.on_trial, [p.trial]) : '') };
		});
		if (mix.ended) { items.push({ name: T.ended, n: mix.ended, color: '#e8a1a1', sub: T.ended_sub }); }
		if (mix.no_plan) { items.push({ name: T.no_plan, n: mix.no_plan, color: '#e5e7eb', sub: T.no_plan_sub }); }
		var total = mix.total;
		if (!total) { box.innerHTML = '<div class="sa-empty"><i class="fa fa-pie-chart"></i>' + esc(T.no_companies) + '</div>'; return; }
		box.innerHTML = '<div class="sa-donut-wrap"><div class="sa-donut"><div class="sa-donut-c"><strong>' + num(total) + '</strong><span>' + esc(T.companies) + '</span></div></div><ul class="sa-plan-list"></ul></div>';
		var svg = svgEl('svg', { viewBox: '0 0 160 160', role: 'img', 'aria-label': T.companies });
		var r = 62, c = 2 * Math.PI * r, off = 0;
		svg.appendChild(svgEl('circle', { cx: 80, cy: 80, r: r, fill: 'none', stroke: '#f1f3f6', 'stroke-width': 18 }));
		items.forEach(function (it) {
			if (!it.n) { return; }
			var len = c * it.n / total, gap = it.n === total ? 0 : 2;
			var arc = svgEl('circle', { cx: 80, cy: 80, r: r, fill: 'none', stroke: it.color, 'stroke-width': 18, 'stroke-dasharray': Math.max(0.5, len - gap) + ' ' + c, 'stroke-dashoffset': -off, transform: 'rotate(-90 80 80)' });
			arc.addEventListener('mousemove', function (ev) { arc.setAttribute('stroke-width', 22); showTip(ev, it.name, [{ k: T.companies, v: it.n + ' (' + Math.round(it.n * 100 / total) + '%)', c: it.color }, { k: '', v: it.sub }]); });
			arc.addEventListener('mouseleave', function () { arc.setAttribute('stroke-width', 18); hideTip(); });
			svg.appendChild(arc); off += len;
		});
		box.querySelector('.sa-donut').insertBefore(svg, box.querySelector('.sa-donut-c'));
		box.querySelector('.sa-plan-list').innerHTML = items.map(function (it) {
			return '<li><i style="background:' + it.color + '"></i><span>' + esc(it.name) + '<small>' + esc(it.sub) + '</small></span><b>' + num(it.n) + '<em>' + Math.round(it.n * 100 / total) + '%</em></b></li>';
		}).join('') + '<li class="is-total"><span>' + esc(T.mrr_total) + '<small>' + esc(T.mrr_total_sub) + '</small></span><b>' + esc(mix.mrr_text) + '</b></li>';
	}

	/* ---------- company growth: new above, left below, net line (one axis) ---------- */
	function renderGrowth() {
		var s = P.data.series, box = $('growChart'), labels = P.labels, n = labels.length;
		var net = s['new'].map(function (v, i) { return v + s.left[i]; });
		$('growTotals').innerHTML = '<div><strong>+' + sum(s['new']) + '</strong>' + esc(T.new_companies) + '</div><div><strong>' + sum(s.left) + '</strong>' + esc(T.left) + '</div><div><strong>' + (sum(net) > 0 ? '+' : '') + sum(net) + '</strong>' + esc(T.net) + '</div>';
		var W = Math.max(280, box.clientWidth), H = box.clientHeight || 220, L = 34, R = 6, Tp = 8, B = 26, w = W - L - R, h = H - Tp - B;
		var top = Math.max(1, Math.max.apply(null, s['new'].concat(net))), bot = Math.min(0, Math.min.apply(null, s.left.concat(net)));
		var step = niceStep(Math.max(1, (top - bot) / 4)); top = Math.ceil(top / step) * step; bot = Math.floor(bot / step) * step; if (bot === 0 && top === 0) { top = 1; }
		var y = function (v) { return Tp + h * (top - v) / ((top - bot) || 1); };
		var svg = svgEl('svg', { viewBox: '0 0 ' + W + ' ' + H, height: H, role: 'img', 'aria-label': T.new_companies });
		for (var v = bot; v <= top + 1e-9; v += step) {
			svg.appendChild(svgEl('line', { x1: L, x2: W - R, y1: y(v), y2: y(v), 'class': 'sa-gridline', stroke: v === 0 ? '#d1d5db' : null }));
			var tx = svgEl('text', { x: L - 8, y: y(v) + 4, 'text-anchor': 'end', 'class': 'sa-axis' }); tx.textContent = Math.round(v); svg.appendChild(tx);
		}
		var slot = w / n, bw = Math.max(3, Math.min(22, slot * 0.5)), every = Math.ceil(n / Math.max(2, Math.floor(w / 70)));
		labels.forEach(function (lab, i) {
			var x = L + slot * i + (slot - bw) / 2;
			if (!state.growHidden['new'] && s['new'][i] > 0) { svg.appendChild(svgEl('path', { d: barPath(x, y(s['new'][i]), bw, y(0) - y(s['new'][i]) - 1, 3), fill: '#2a78d6' })); }
			if (!state.growHidden.left && s.left[i] < 0) { svg.appendChild(svgEl('rect', { x: x, y: y(0) + 1, width: bw, height: Math.max(0.5, y(s.left[i]) - y(0) - 1), fill: '#e8a1a1', rx: 1 })); }
			if (i % every === 0) { var t = svgEl('text', { x: x + bw / 2, y: H - 6, 'text-anchor': 'middle', 'class': 'sa-axis' }); t.textContent = lab; svg.appendChild(t); }
		});
		if (!state.growHidden.net) {
			var pts = net.map(function (v, i) { return [L + slot * i + slot / 2, y(v)]; });
			svg.appendChild(svgEl('path', { d: pts.map(function (p, i) { return (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join(' '), fill: 'none', stroke: '#1f2430', 'stroke-width': 2, 'stroke-linejoin': 'round' }));
			if (n <= 31) { pts.forEach(function (p) { svg.appendChild(svgEl('circle', { cx: p[0], cy: p[1], r: 3, fill: '#1f2430', stroke: '#fff', 'stroke-width': 1.5 })); }); }
		}
		labels.forEach(function (lab, i) {
			var hit = svgEl('rect', { x: L + slot * i, y: Tp, width: slot, height: h, fill: 'transparent' });
			hit.addEventListener('mousemove', function (ev) { showTip(ev, P.tips[i], [{ k: T.new_companies, v: '+' + s['new'][i], c: '#2a78d6' }, { k: T.left, v: String(s.left[i]), c: '#e8a1a1' }, { k: T.net, v: (net[i] > 0 ? '+' : '') + net[i], c: '#1f2430' }]); });
			hit.addEventListener('mouseleave', hideTip); svg.appendChild(hit);
		});
		box.replaceChildren(svg);
		var items = [['new', T.new_companies, '#2a78d6'], ['left', T.left, '#e8a1a1'], ['net', T.net, '#1f2430']];
		var lg = $('growLegend');
		lg.innerHTML = items.map(function (it) { return '<button type="button" data-id="' + it[0] + '" aria-pressed="' + (!state.growHidden[it[0]]) + '"><i style="background:' + it[2] + '"></i>' + esc(it[1]) + '</button>'; }).join('');
		Array.prototype.forEach.call(lg.querySelectorAll('button'), function (b) {
			b.addEventListener('click', function () { var id = b.getAttribute('data-id'); state.growHidden[id] = !state.growHidden[id]; renderGrowth(); });
		});
	}

	/* ---------- funnel ---------- */
	function renderFunnel() {
		var f = P.data.funnel, box = $('funnel');
		var steps = [[T.f_signups, f.signups], [T.f_trials, f.trials], [T.f_paying, f.paying], [T.f_renewed, f.renewed]];
		var max = Math.max.apply(null, steps.map(function (s) { return s[1]; }));
		if (!max) { box.innerHTML = '<div class="sa-empty"><i class="fa fa-filter"></i>' + esc(T.f_none) + '</div>'; return; }
		box.innerHTML = '<div class="sa-funnel">' + steps.map(function (s, i) {
			var prev = i ? steps[i - 1][1] : 0;
			var pct = i && prev ? fmt(T.f_of_prev, [Math.round(s[1] * 100 / prev)]) : (i ? '—' : '');
			return '<div class="sa-f-row"><span>' + esc(s[0]) + '</span><div class="sa-f-bar"><span style="width:' + (s[1] * 100 / max) + '%"></span></div><span class="sa-f-val">' + num(s[1]) + '<small>' + esc(pct) + '</small></span></div>';
		}).join('') + '</div>'
			+ (f.signups ? '<p class="sa-f-note">' + esc(fmt(T.f_note, [Math.round(f.paying / f.signups * 100)])) + '</p>' : '');
	}

	/* ---------- payments: success gauge ---------- */
	function renderPayments() {
		var p = P.data.payments, box = $('payments');
		var tried = p.paid + p.failed;
		var stats = '<div class="sa-mini-stats"><div><b>' + num(p.paid) + '</b><small>' + esc(T.paid) + '</small></div><div><b>' + num(p.failed) + '</b><small>' + esc(T.failed) + '</small></div><div><b>' + num(p.pending) + '</b><small>' + esc(T.open) + '</small></div></div>'
			+ '<p class="sa-past-due' + (P.upcoming.past_due ? ' is-bad' : '') + '"><span>' + esc(T.past_due) + '</span><b>' + num(P.upcoming.past_due) + '</b></p>';
		if (!tried) { box.innerHTML = '<div class="sa-empty is-compact"><i class="fa fa-credit-card"></i>' + esc(T.no_payments) + '</div>' + stats; return; }
		var rate = p.rate, color = rate >= 90 ? '#22a35a' : rate >= 70 ? '#e8a33d' : '#dc2626';
		var arc = function (from, to, c) {
			var a0 = Math.PI * (1 - from), a1 = Math.PI * (1 - to), r = 90, cx = 110, cy = 112;
			if (to - from >= 0.9999) { a1 = Math.PI * 0.0001; }
			return '<path d="M' + (cx + r * Math.cos(a0)) + ' ' + (cy - r * Math.sin(a0)) + ' A' + r + ' ' + r + ' 0 0 1 ' + (cx + r * Math.cos(a1)) + ' ' + (cy - r * Math.sin(a1)) + '" fill="none" stroke="' + c + '" stroke-width="16" stroke-linecap="round"/>';
		};
		box.innerHTML = '<div class="sa-gauge"><svg viewBox="0 0 220 130" role="img" aria-label="' + esc(rate + '% ' + T.successful) + '">' + arc(0, 1, '#f1f3f6') + (rate > 0 ? arc(0, rate / 100, color) : '') + '</svg>'
			+ '<div class="sa-gauge-val"><strong>' + rate + '%</strong><span>' + esc(T.successful) + '</span></div></div>' + stats;
	}

	/* ---------- coming up ---------- */
	function renderUpcoming() {
		var u = P.upcoming, box = $('upcoming');
		var html = '<div class="sa-up-sums"><span class="sa-pill good">' + esc(T.renewals) + ' ' + u.renewals + ' · ' + esc(u.renewals_text) + '</span><span class="sa-pill info">' + esc(T.trials_ending) + ' ' + u.trials + ' · ' + esc(fmt(T.if_they_buy, [u.trials_text])) + '</span></div>';
		if (!u.list.length) { box.innerHTML = html + '<div class="sa-empty is-compact"><i class="fa fa-calendar-check-o"></i>' + esc(T.no_renewals) + '</div>'; return; }
		box.innerHTML = html + '<ul class="sa-up-list">' + u.list.map(function (r) {
			return '<li><span class="sa-chip" title="' + esc(r.date) + '"><b>' + r.day + '</b><small>' + esc(r.mon) + '</small></span><span class="sa-up-who"><strong>' + esc(r.company) + '</strong><small>' + esc(r.plan) + ' · ' + esc(r.trial ? T.trial_ends : T.renews) + '</small></span>'
				+ '<span class="sa-up-amt">' + esc(r.amount || '—') + (r.trial && r.amount ? '<small>' + esc(T.if_bought) + '</small>' : '') + '</span></li>';
		}).join('') + '</ul>';
	}

	/* ---------- health ---------- */
	function renderHealth() {
		var icon = { ok: '✓', warning: '!', error: '×', unknown: '?' };
		$('health').innerHTML = '<ul class="sa-health">' + P.health.map(function (hh) {
			return '<li><a href="' + esc(hh.url) + '" title="' + esc(hh.detail) + '"><span class="sa-h-ico s-' + hh.state + '">' + icon[hh.state] + '</span><span class="sa-h-main"><strong>' + esc(hh.title) + '</strong><small>' + esc(hh.detail) + '</small></span><span class="sa-h-state s-' + hh.state + '">' + esc(hh.state_label) + '</span></a></li>';
		}).join('') + '</ul>';
		var st = $('status');
		st.className = 'sa-status is-' + P.overall; st.querySelector('span').textContent = P.overall_label;
	}

	/* ---------- billing + activity ---------- */
	function renderBilling() {
		var row = $('billingRow'), card = $('billingCard');
		card.hidden = !P.has_billing;
		row.classList.toggle('is-single', !P.has_billing);
		if (!P.has_billing) { return; }
		var cls = { paid: 'good', pending: 'warn', failed: 'bad', cancelled: 'bad', refunded: 'gray' };
		$('billing').innerHTML = !P.billing.length ? '<div class="sa-empty is-compact"><i class="fa fa-file-text-o"></i>' + esc(T.no_billing) + '</div>'
			: '<div class="sa-table-wrap"><table class="sa-table"><tbody>' + P.billing.map(function (b) {
				return '<tr><td class="sa-nowrap">' + esc(b.date) + '</td><td><strong>' + esc(b.company) + '</strong><small>' + esc(b.plan) + ' · ' + esc(b.kind) + (b.coupon ? ' · ' + esc(b.coupon) : '') + '</small></td><td class="sa-num">' + esc(b.amount) + '</td><td class="sa-r"><span class="sa-pill ' + (cls[b.status] || 'gray') + '">' + esc(b.status_label) + '</span></td></tr>';
			}).join('') + '</tbody></table></div>';
	}
	function renderActivity() {
		var dot = { registration: '#F97618', purchase: '#1baf7a', upgrade: '#2a78d6', coupon: '#F97618', change: '#6b7280' };
		$('activity').innerHTML = !P.activity.length ? '<div class="sa-empty is-compact"><i class="fa fa-history"></i>' + esc(T.no_activity) + '</div>'
			: '<ul class="sa-feed">' + P.activity.map(function (a) { return '<li style="--dot:' + (dot[a.type] || '#6b7280') + '"><div><strong>' + esc(a.title) + '</strong><small>' + esc(a.sub) + '</small></div><time title="' + esc(a.date) + '">' + esc(a.ago) + '</time></li>'; }).join('') + '</ul>';
	}

	/* ---------- the whole page ---------- */
	function renderCurrency() {
		var r = P.data.report || { code: 'USD', converted: false, rate_text: '' };
		Array.prototype.forEach.call(document.querySelectorAll('#saCurrency button'), function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-cur') === r.code)); });
		var note = $('curNote');
		note.innerHTML = '<i class="fa fa-info-circle"></i> ' + esc(fmt(r.converted ? T.cur_note_inr : T.cur_note_usd, [r.rate_text]));
		note.classList.toggle('is-converted', !!r.converted);
	}
	function renderAll() {
		renderCurrency();
		$('rangeLabel').textContent = P.range.label;
		$('daysLabel').textContent = '(' + P.range.days_label + ')';
		$('bucketLabel').textContent = P.range.bucket_label;
		$('rangeError').hidden = !P.range.error;
		renderKpis(); renderRevenue(); renderMix(); renderGrowth(); renderFunnel(); renderPayments(); renderUpcoming(); renderHealth(); renderBilling(); renderActivity();
	}

	/* ---------- period: presets, custom range, loading ---------- */
	var seg = document.getElementById('saPeriod'), pop = document.getElementById('saPop'), cBtn = document.getElementById('saCustomBtn');
	var fFrom = document.getElementById('saFrom'), fTo = document.getElementById('saTo'), fErr = document.getElementById('saPopErr');
	function press(key) {
		Array.prototype.forEach.call(seg.querySelectorAll('button'), function (b) {
			var p = b.getAttribute('data-p');
			b.setAttribute('aria-pressed', String(p === key || (p === 'custom' && (key === 'custom' || key === 'month'))));
		});
	}
	var req = 0;
	function load(next) {
		// keeps the reporting currency with a new period, and the period with a new currency
		params = next.range ? { range: next.range, cur: params.cur } : params;
		if (next.from) { params.from = next.from; params.to = next.to; }
		if (next.cur) { params.cur = next.cur; }
		var qs = Object.keys(params).map(function (k) { return k + '=' + encodeURIComponent(params[k]); }).join('&');
		var my = ++req;
		root.classList.add('is-loading');
		fetch(root.getAttribute('data-endpoint') + '&' + qs, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })
			.then(function (j) {
				if (my !== req) { return; }
				if (!j || j.status !== 'OK') { throw new Error('ERR'); }
				P = j.payload; state.revHidden = {};
				press(P.range.error ? 'custom' : P.range.key);
				fFrom.value = P.range.from; fTo.value = P.range.to;
				renderAll();
				try { history.replaceState(null, '', root.getAttribute('data-page') + '&' + qs); } catch (e) {}
			})
			.catch(function () { if (my === req) { window.alert(T.load_error); } })
			.then(function () { if (my === req) { root.classList.remove('is-loading'); } });
	}
	function openPop() { fErr.hidden = true; fFrom.value = P.range.from; fTo.value = P.range.to; pop.hidden = false; cBtn.setAttribute('aria-expanded', 'true'); fFrom.focus(); }
	function closePop() { pop.hidden = true; cBtn.setAttribute('aria-expanded', 'false'); }
	Array.prototype.forEach.call(seg.querySelectorAll('button'), function (b) {
		b.addEventListener('click', function () {
			var p = b.getAttribute('data-p');
			if (p === 'custom') { if (pop.hidden) { openPop(); } else { closePop(); } return; }
			closePop(); press(p); load({ range: p });
		});
	});
	function ymd(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
	Array.prototype.forEach.call(pop.querySelectorAll('[data-q]'), function (b) {
		b.addEventListener('click', function () {
			var t = TODAY.split('-'), y = +t[0], m = +t[1] - 1, today = new Date(y, m, +t[2]);
			var r = { month: [new Date(y, m, 1), today], lastmonth: [new Date(y, m - 1, 1), new Date(y, m, 0)], quarter: [new Date(y, m - m % 3, 1), today], year: [new Date(y, 0, 1), today] }[b.getAttribute('data-q')];
			fFrom.value = ymd(r[0]); fTo.value = ymd(r[1]); fErr.hidden = true;
		});
	});
	document.getElementById('saPopCancel').addEventListener('click', closePop);
	document.getElementById('saPopApply').addEventListener('click', function () {
		var err = function (m) { fErr.textContent = m; fErr.hidden = false; };
		if (!fFrom.value || !fTo.value) { return err(T.err_both); }
		if (fFrom.value > fTo.value) { return err(T.err_order); }
		if (fFrom.value > TODAY) { return err(T.err_future); }
		closePop(); press('custom');
		load({ range: 'custom', from: fFrom.value, to: fTo.value > TODAY ? TODAY : fTo.value });
	});
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !pop.hidden) { closePop(); cBtn.focus(); } });
	document.addEventListener('click', function (e) { if (!pop.hidden && !pop.contains(e.target) && !cBtn.contains(e.target)) { closePop(); } });

	/* ---------- reporting currency switch ---------- */
	Array.prototype.forEach.call(document.querySelectorAll('#saCurrency button'), function (b) {
		b.addEventListener('click', function () {
			var c = b.getAttribute('data-cur');
			if (c === params.cur) { return; }
			Array.prototype.forEach.call(document.querySelectorAll('#saCurrency button'), function (x) { x.setAttribute('aria-pressed', String(x === b)); });
			load({ cur: c });
		});
	});

	/* ---------- revenue view toggle ---------- */
	Array.prototype.forEach.call(document.querySelectorAll('#saRevView button'), function (b) {
		b.addEventListener('click', function () {
			Array.prototype.forEach.call(document.querySelectorAll('#saRevView button'), function (x) { x.setAttribute('aria-pressed', String(x === b)); });
			state.revView = b.getAttribute('data-v'); state.revHidden = {}; renderRevenue();
		});
	});

	/* ---------- export: the figures of the period as CSV ---------- */
	document.getElementById('saExport').addEventListener('click', function () {
		var k = P.data.kpi, s = P.data.series, rows = [[T.csv_period, P.range.label], [], [T.csv_metric, T.csv_value]];
		rows.push([T.kpi_revenue, k.revenue.text + (k.revenue.collected_text ? ' (' + fmt(T.charged, [k.revenue.collected_text]) + ')' : '')], [T.kpi_companies, k.companies.value], [T.kpi_subs, k.subs.value], [T.kpi_mrr, k.mrr.text],
			[T.kpi_conv, k.conversion.rate === null ? '' : k.conversion.rate + '%'], []);
		var head = [T.csv_period, T.kpi_revenue + ' (' + P.data.money.code + ')'].concat(s.by_plan.map(function (x) { return x.name; }), [T.new_companies, T.left, T.kpi_subs, T.kpi_mrr + ' (' + P.data.money.code + ')']);
		rows.push(head);
		P.tips.forEach(function (tp, i) {
			rows.push([tp, s.revenue[i]].concat(s.by_plan.map(function (x) { return x.values[i]; }), [s['new'][i], s.left[i], s.active[i], s.mrr[i]]));
		});
		if (P.data.report) { rows.unshift([fmt(P.data.report.converted ? T.cur_note_inr : T.cur_note_usd, [P.data.report.rate_text])], []); }
		var csv = rows.map(function (r) { return r.map(function (c) { c = String(c === undefined ? '' : c); return /[",;\n]/.test(c) ? '"' + c.replace(/"/g, '""') + '"' : c; }).join(','); }).join('\r\n');
		var a = document.createElement('a');
		a.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' }));
		a.download = 'platform-dashboard-' + P.range.from + '-' + P.range.to + '.csv';
		document.body.appendChild(a); a.click(); setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 0);
	});

	var rt;
	window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(function () { renderRevenue(); renderGrowth(); }, 150); });
	renderAll();
})();
