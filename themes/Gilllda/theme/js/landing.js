/* Gilllda product landing — vanilla, no dependencies (loaded only on /l/{slug}/). */
(function () {
	'use strict';

	var dataEl = document.getElementById('gl-lp-data');
	if (!dataEl) return;
	var P = JSON.parse(dataEl.textContent || '{}');
	var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var $ = function (s, c) { return (c || document).querySelector(s); };
	var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
	var fa = function (n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
	var money = function (n) { return fa(Math.round(n).toLocaleString('en-US')) + ' <small>تومان</small>'; };

	/* ---------- tracking ---------- */
	function track(type) {
		if (!P.track) return;
		var key = 'gl_lp_' + P.id + '_' + type;
		try { if (sessionStorage.getItem(key)) return; sessionStorage.setItem(key, '1'); } catch (e) {}
		var body = JSON.stringify({ id: P.id, type: type, source: P.source });
		if (navigator.sendBeacon) {
			navigator.sendBeacon(P.rest, new Blob([body], { type: 'application/json' }));
		} else {
			fetch(P.rest, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, keepalive: true });
		}
	}
	function ga(event, extra) {
		if (typeof window.gtag !== 'function') return;
		window.gtag('event', event, Object.assign({
			currency: 'IRR',
			value: (P.price || 0) * 10,
			items: [{ item_id: P.sku || String(P.id), item_name: P.name, price: (P.price || 0) * 10 }]
		}, extra || {}));
	}
	track('view');
	ga('view_item', { landing: 1 });

	/* ---------- header: solid on scroll, hide on scroll down, progress ---------- */
	var top = $('[data-top]'), bar = $('[data-bar]'), prog = $('[data-progress]'), buy = $('#lp-buy');
	var last = 0, ticking = false;
	function onScroll() {
		var y = window.scrollY, h = document.documentElement.scrollHeight - innerHeight;
		top.classList.toggle('is-solid', y > 30);
		top.classList.toggle('is-hidden', y > 400 && y > last);
		if (prog) prog.style.transform = 'scaleX(' + (h > 0 ? y / h : 0) + ')';
		if (bar && buy) {
			var r = buy.getBoundingClientRect();
			var inBuy = r.top < innerHeight * 0.8 && r.bottom > innerHeight * 0.2;
			var on = y > innerHeight * 0.7 && !inBuy;
			bar.classList.toggle('is-on', on);
			bar.setAttribute('aria-hidden', on ? 'false' : 'true');
			var link = bar.querySelector('a'); if (link) link.tabIndex = on ? 0 : -1;
		}
		last = y; ticking = false;
	}
	addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
	onScroll();

	/* ---------- reveal on scroll ---------- */
	if ('IntersectionObserver' in window && !reduce) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
		$$('[data-reveal]').forEach(function (el) { io.observe(el); });
	} else {
		$$('[data-reveal]').forEach(function (el) { el.classList.add('is-in'); });
	}

	/* ---------- hero: float after intro + pointer tilt ---------- */
	var media = $('[data-tilt]');
	if (media) {
		setTimeout(function () { media.classList.add('is-ready'); }, 1600);
		if (!reduce && matchMedia('(hover: hover) and (min-width: 1024px)').matches) {
			media.addEventListener('pointermove', function (e) {
				var r = media.getBoundingClientRect();
				var x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5;
				media.style.transform = 'perspective(1200px) rotateY(' + (x * 12) + 'deg) rotateX(' + (-y * 12) + 'deg)';
			});
			media.addEventListener('pointerleave', function () { media.style.transform = ''; });
		}
	}

	/* ---------- magnetic buttons ---------- */
	if (!reduce && matchMedia('(hover: hover)').matches) {
		$$('[data-magnetic]').forEach(function (b) {
			b.addEventListener('pointermove', function (e) {
				var r = b.getBoundingClientRect();
				b.style.transform = 'translate(' + ((e.clientX - r.left - r.width / 2) * 0.18) + 'px,' + ((e.clientY - r.top - r.height / 2) * 0.3) + 'px)';
			});
			b.addEventListener('pointerleave', function () { b.style.transform = ''; });
		});
	}

	/* ---------- CTA clicks ---------- */
	$$('[data-cta]').forEach(function (a) {
		a.addEventListener('click', function () { track('cta'); ga('select_content', { content_type: 'landing_cta', content_id: a.dataset.cta }); });
	});

	/* ---------- countdown ---------- */
	var cd = $('[data-countdown]');
	if (cd && P.deadline) {
		var parts = { d: $('[data-cd="d"]', cd), h: $('[data-cd="h"]', cd), m: $('[data-cd="m"]', cd), s: $('[data-cd="s"]', cd) };
		var pad = function (n) { return fa(String(n).padStart(2, '0')); };
		var tick = function () {
			var t = Math.max(0, P.deadline - Date.now()) / 1000;
			if (t <= 0) { cd.hidden = true; return; }
			parts.d.textContent = fa(Math.floor(t / 86400));
			parts.h.textContent = pad(Math.floor(t % 86400 / 3600));
			parts.m.textContent = pad(Math.floor(t % 3600 / 60));
			parts.s.textContent = pad(Math.floor(t % 60));
		};
		tick(); setInterval(tick, 1000);
	}

	/* ---------- gallery strip ---------- */
	var strip = $('[data-strip]');
	if (strip) {
		var step = function (dir) { strip.scrollBy({ left: dir * strip.clientWidth * 0.6, behavior: reduce ? 'auto' : 'smooth' }); };
		var next = $('[data-strip-next]'), prev = $('[data-strip-prev]');
		next && next.addEventListener('click', function () { step(-1); }); // RTL: next goes left
		prev && prev.addEventListener('click', function () { step(1); });
		var vid = $('video[data-autoplay]', strip);
		if (vid && 'IntersectionObserver' in window) {
			new IntersectionObserver(function (es) { es.forEach(function (e) { e.isIntersecting ? vid.play().catch(function () {}) : vid.pause(); }); }, { threshold: 0.4 }).observe(vid);
		}
	}

	/* ---------- zoom dialog ---------- */
	var dlg = $('[data-zoom-dialog]'), dimg = $('[data-zoom-img]');
	if (dlg && dlg.showModal) {
		$$('[data-zoom]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var im = P.images[+btn.dataset.zoom]; if (!im) return;
				dimg.src = im.src; if (im.srcset) dimg.srcset = im.srcset; dimg.sizes = '90vw';
				dlg.showModal();
			});
		});
		$('[data-zoom-close]').addEventListener('click', function () { dlg.close(); });
		dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
	}

	/* ---------- read more ---------- */
	$$('[data-more]').forEach(function (box) {
		var b = $('[data-more-btn]', box);
		var body = $('.lp-desc__body', box);
		if (body && body.scrollHeight <= body.clientHeight + 20) { box.classList.add('is-open'); return; }
		b && b.addEventListener('click', function () { box.classList.add('is-open'); });
	});

	/* ---------- configurator ---------- */
	var form = $('[data-buy-form]');
	if (!form) return;
	var varInput = $('[data-variation]', form), stockEl = $('[data-stock]', form), buyBtn = $('[data-buy]', form);
	var qty = $('input[name="quantity"]', form);
	var heroImg = $('[data-hero]'), buyImg = $('[data-buy-img]');
	var imgById = {}; (P.images || []).forEach(function (i) { imgById[i.id] = i; });

	function chosen() {
		var o = {};
		$$('input[type="radio"]:checked', form).forEach(function (r) { o[r.name] = r.value; });
		return o;
	}
	function matches(v, sel) {
		return Object.keys(v.attrs).every(function (k) { return !v.attrs[k] || !sel[k] || v.attrs[k] === sel[k]; });
	}
	function swap(img, im) {
		if (!img || !im || img.dataset.cur == im.id) return;
		img.dataset.cur = im.id;
		img.classList.add('is-swapping');
		var pre = new Image();
		pre.onload = function () {
			img.src = im.src; if (im.srcset) img.srcset = im.srcset;
			requestAnimationFrame(function () { img.classList.remove('is-swapping'); });
		};
		pre.src = im.src;
	}
	function setPrice(v) {
		$$('[data-price]').forEach(function (el) { el.innerHTML = money(v ? v.price : P.price); });
		var reg = $('[data-regular]', form);
		if (reg) {
			var r = v ? v.regular : P.regular, p = v ? v.price : P.price;
			reg.hidden = !(r > p); reg.textContent = fa(Math.round(r).toLocaleString('en-US'));
		}
	}
	function update() {
		if (P.type !== 'variable') return;
		var sel = chosen();
		var names = Object.keys(sel);
		// mark unavailable options
		$$('input[type="radio"]', form).forEach(function (r) {
			var test = Object.assign({}, sel); test[r.name] = r.value;
			var ok = P.variations.some(function (v) { return v.stock && matches(v, test); });
			r.closest('.lp-pill').classList.toggle('is-out', !ok);
		});
		$$('[data-picked]').forEach(function (b) {
			var r = $('input[name="' + b.dataset.picked + '"]:checked', form);
			b.textContent = r ? r.dataset.label : 'انتخاب کنید';
		});
		var total = $$('.lp-opt', form).length;
		var cand = P.variations.filter(function (v) { return matches(v, sel); });
		var withImg = cand.filter(function (v) { return v.image; })[0];
		if (withImg && imgById[withImg.image]) { swap(heroImg, imgById[withImg.image]); swap(buyImg, imgById[withImg.image]); }
		if (names.length < total) { varInput.value = ''; setPrice(cand.length === 1 ? cand[0] : null); stockEl.textContent = ''; return; }
		var v = cand[0];
		if (!v) { varInput.value = ''; stockEl.textContent = 'این ترکیب موجود نیست؛ گزینه‌ی دیگری را انتخاب کنید.'; buyBtn.disabled = true; return; }
		varInput.value = v.id;
		setPrice(v);
		buyBtn.disabled = !v.stock;
		stockEl.textContent = v.stock ? '' : 'این رنگ فعلاً ناموجود است.';
		if (v.max && v.max > 0) qty.max = Math.min(10, v.max);
	}
	form.addEventListener('change', update);

	// hero swatches pre-select the matching option and scroll to the form
	$$('[data-pick]').forEach(function (sw) {
		sw.addEventListener('click', function () {
			var r = $('input[name="' + sw.dataset.pick + '"][value="' + CSS.escape(sw.dataset.value) + '"]', form);
			if (r) { r.checked = true; update(); }
			$$('[data-pick]').forEach(function (s) { s.classList.toggle('is-active', s === sw); });
		});
	});

	$$('[data-qty]', form).forEach(function (b) {
		b.addEventListener('click', function () {
			var n = Math.max(1, Math.min(+qty.max || 10, (+qty.value || 1) + +b.dataset.qty));
			qty.value = n;
		});
	});

	// auto-select when an option has only one value
	$$('.lp-opt', form).forEach(function (fs) {
		var radios = $$('input[type="radio"]', fs);
		if (radios.length === 1) radios[0].checked = true;
	});
	update();

	// back from checkout (bfcache): re-enable the button
	addEventListener('pageshow', function (e) {
		if (!e.persisted) return;
		var label = $('[data-buy-label]', form); if (label) label.textContent = label.dataset.text || label.textContent;
		update();
		if (P.type !== 'variable') buyBtn.disabled = false;
	});
	var lbl = $('[data-buy-label]', form); if (lbl) lbl.dataset.text = lbl.textContent;

	form.addEventListener('submit', function (e) {
		if (P.type === 'variable' && !varInput.value) {
			e.preventDefault();
			stockEl.textContent = 'لطفاً ' + ($$('[data-picked]').filter(function (b) { return b.textContent === 'انتخاب کنید'; }).map(function (b) { return b.closest('legend').firstChild.textContent.replace(':', '').trim(); }).join(' و ') || 'گزینه‌ها') + ' را انتخاب کنید.';
			form.classList.remove('is-shake'); void form.offsetWidth; form.classList.add('is-shake');
			return;
		}
		track('cta');
		ga('add_to_cart');
		ga('begin_checkout');
		var label = $('[data-buy-label]', form); if (label) label.textContent = 'در حال انتقال به پرداخت…';
		buyBtn.disabled = true;
	});
})();
