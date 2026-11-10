/**
 * Gilllda app-like shopping flow.
 *
 * - AJAX add to cart for every <form data-gl-cart-form> (single product buy
 *   form, mobile sheet, product cards) -> ?wc-ajax=gl_add_to_cart.
 * - Fly-to-cart animation, header badge bounce, drawer opens with the new item
 *   highlighted.
 * - Cart drawer: qty stepper, remove + undo, focus trap, Esc, swipe down.
 * - Header count / drawer kept fresh on full-page-cached pages (cart-hash
 *   cookie vs. rendered hash, sessionStorage cache, ?wc-ajax=gl_cart_refresh).
 * - Cart page qty stepper (auto update) and checkout inline validation.
 *
 * Server side: theme/inc/product/ajax-cart.php + cart-fragments.php
 */
import Alpine from 'alpinejs';

const STORE_KEY = 'gl_cart_v1';

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
const cfg = () => window.glCart || {};
const jq = () => window.jQuery || null;
const endpoint = (action) =>
	(cfg().ajax || '/?wc-ajax=%%endpoint%%').replace('%%endpoint%%', action);
const reducedMotion = () =>
	!!(
		window.matchMedia &&
		window.matchMedia('(prefers-reduced-motion: reduce)').matches
	);
const cssEscape = (v) =>
	window.CSS && CSS.escape
		? CSS.escape(v)
		: String(v).replace(/["\\]/g, '\\$&');
const toLatinDigits = (v) =>
	String(v ?? '')
		.replace(/[۰-۹]/g, (d) => String(d.charCodeAt(0) - 1776))
		.replace(/[٠-٩]/g, (d) => String(d.charCodeAt(0) - 1632));
const formatNumber = (n) => Math.round(Number(n) || 0).toLocaleString('en-US');

function getCookie(name) {
	const m = document.cookie.match(
		new RegExp(
			'(?:^|; )' +
				name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') +
				'=([^;]*)'
		)
	);
	return m ? decodeURIComponent(m[1]) : '';
}

function triggerJq(event, args) {
	const $j = jq();
	if (!$j) return;
	try {
		$j(document.body).trigger(event, args || []);
	} catch {
		/* third-party handler errors must not break the flow */
	}
}

/* ------------------------------------------------------------------ */
/* Network                                                             */
/* ------------------------------------------------------------------ */

class GlCartError extends Error {
	constructor(kind) {
		super(kind);
		this.kind = kind; // 'network' | 'bad_response'
	}
}

async function post(action, data = {}) {
	const body = new URLSearchParams();
	Object.entries(data).forEach(([k, v]) => {
		if (v !== undefined && v !== null) body.append(k, String(v));
	});

	let res;
	try {
		res = await fetch(endpoint(action), {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'X-GL-Cart': '1',
				Accept: 'application/json',
				'Content-Type':
					'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body,
		});
	} catch {
		throw new GlCartError('network');
	}

	let json = null;
	try {
		json = await res.json();
	} catch {
		json = null;
	}
	if (!json || typeof json !== 'object' || !('success' in json)) {
		throw new GlCartError('bad_response');
	}
	json.data = json.data || {};
	return json;
}

/* ------------------------------------------------------------------ */
/* UI helpers                                                          */
/* ------------------------------------------------------------------ */

function bodyState() {
	try {
		return window.Alpine && window.Alpine.$data
			? window.Alpine.$data(document.body)
			: null;
	} catch {
		return null;
	}
}

function openDrawer() {
	const s = bodyState();
	if (s) s.cart = true;
}

function closeDrawer() {
	const s = bodyState();
	if (s) s.cart = false;
}

function announce(message) {
	const live = $('#gl-cart-live');
	if (!live || !message) return;
	live.textContent = '';
	window.setTimeout(() => {
		live.textContent = message;
	}, 50);
}

function toast(message, type = 'error') {
	const region = $('#gl-toasts');
	if (!region || !message) return;
	const el = document.createElement('div');
	el.className = `gl-toast gl-toast--${type}`;
	el.setAttribute('role', type === 'error' ? 'alert' : 'status');
	el.textContent = message;
	region.appendChild(el);
	window.setTimeout(
		() => {
			el.classList.add('gl-toast--out');
			window.setTimeout(() => el.remove(), 300);
		},
		type === 'error' ? 4200 : 2600
	);
}

function setButtonState(btn, state) {
	if (!btn) return;
	window.clearTimeout(btn._glTimer);
	btn.dataset.state = state;
	btn.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false');
	btn.disabled = state === 'loading';
	if (state === 'success' || state === 'error') {
		btn._glTimer = window.setTimeout(
			() => setButtonState(btn, 'idle'),
			state === 'success' ? 1700 : 650
		);
	}
}

function bounceBadge() {
	$$('.header-cart-count').forEach((el) => {
		el.classList.remove('gl-bounce');
		void el.offsetWidth; // restart animation
		el.classList.add('gl-bounce');
	});
}

function updateCountChip(count) {
	const chip = $('[data-gl-count-chip]');
	if (!chip) return;
	chip.hidden = !count;
	chip.textContent = `${count} کالا`;
}

function highlightItem(key) {
	if (!key) return;
	const item = $(`[data-gl-item="${cssEscape(key)}"]`);
	if (!item) return;
	item.classList.add('gl-highlight');
	window.setTimeout(() => {
		try {
			item.scrollIntoView({
				block: 'nearest',
				behavior: reducedMotion() ? 'auto' : 'smooth',
			});
		} catch {
			/* noop */
		}
	}, 320);
	window.setTimeout(() => item.classList.remove('gl-highlight'), 2600);
}

/* ------------------------------------------------------------------ */
/* Fragments                                                           */
/* ------------------------------------------------------------------ */

function cacheFragments(data) {
	try {
		window.sessionStorage.setItem(
			STORE_KEY,
			JSON.stringify({
				hash: data.cart_hash || '',
				count: data.count || 0,
				fragments: data.fragments || {},
			})
		);
	} catch {
		/* private mode / quota */
	}
}

function readCache() {
	try {
		return JSON.parse(window.sessionStorage.getItem(STORE_KEY) || 'null');
	} catch {
		return null;
	}
}

function applyPayload(data, opts = {}) {
	if (!data) return;
	const fragments = data.fragments || {};

	// Remember focus inside the drawer body so it survives the HTML swap.
	const active = document.activeElement;
	const oldBody = $('#gl-cart-body');
	let focusHint = null;
	if (active && oldBody && oldBody.contains(active)) {
		const item = active.closest('[data-gl-item]');
		let sel = null;
		if (active.hasAttribute('data-gl-qty'))
			sel = `[data-gl-qty="${active.getAttribute('data-gl-qty')}"]`;
		else if (active.hasAttribute('data-gl-remove'))
			sel = '[data-gl-remove]';
		focusHint = { key: item ? item.dataset.glItem : null, sel };
	}

	Object.keys(fragments).forEach((selector) => {
		let nodes = [];
		try {
			nodes = $$(selector);
		} catch {
			return;
		}
		nodes.forEach((node) => {
			const tpl = document.createElement('template');
			tpl.innerHTML = String(fragments[selector]).trim();
			node.replaceWith(tpl.content);
		});
	});

	if (typeof data.count === 'number') updateCountChip(data.count);
	if (opts.bounce) bounceBadge();
	if (data.fragments) cacheFragments(data);

	if (focusHint) {
		let target = null;
		if (focusHint.key && focusHint.sel) {
			target = $(
				`[data-gl-item="${cssEscape(focusHint.key)}"] ${focusHint.sel}`
			);
		}
		if (!target || target.disabled)
			target = $('.gl-cart-panel [data-gl-close]');
		if (target) target.focus({ preventScroll: true });
	}

	if (opts.highlight) highlightItem(opts.highlight);
	document.dispatchEvent(
		new CustomEvent('gl:cart-updated', { detail: data })
	);
}

let refreshTimer = null;
let refreshWaiters = [];
/** Debounced; every caller's promise resolves with the same fresh payload (or null). */
function refresh() {
	window.clearTimeout(refreshTimer);
	return new Promise((resolve) => {
		refreshWaiters.push(resolve);
		refreshTimer = window.setTimeout(() => {
			const waiters = refreshWaiters;
			refreshWaiters = [];
			post('gl_cart_refresh')
				.then((r) => {
					applyPayload(r.data);
					waiters.forEach((fn) => fn(r.data));
				})
				.catch(() => waiters.forEach((fn) => fn(null)));
		}, 120);
	});
}

/**
 * Cached pages may carry another visitor's (or an old) cart. Compare the
 * hash the page was rendered with to the cart-hash cookie and fix it.
 */
function syncFromCookie() {
	const body = $('#gl-cart-body');
	if (!body) return;
	const cookieHash = getCookie('woocommerce_cart_hash');
	const renderedHash = body.dataset.hash || '';
	if (cookieHash === renderedHash) return;

	const cached = readCache();
	if (cached && (cached.hash || '') === cookieHash && cached.fragments) {
		applyPayload(cached);
		return;
	}
	refresh();
}

/** Keep WooCommerce's own cart / checkout pages in step with drawer changes. */
let skipWcSync = 0;
function syncWooPages() {
	const b = document.body.classList;
	if (b.contains('woocommerce-cart') && $('.woocommerce-cart-form')) {
		skipWcSync = 2;
		triggerJq('wc_update_cart');
	} else if (b.contains('woocommerce-checkout') && $('form.checkout')) {
		triggerJq('update_checkout');
	}
}

/* ------------------------------------------------------------------ */
/* Add to cart                                                         */
/* ------------------------------------------------------------------ */

function parseJSONAttr(value, fallback) {
	try {
		return value ? JSON.parse(value) : fallback;
	} catch {
		return fallback;
	}
}

function collectForm(form) {
	const fd = new FormData(form);
	const productId = form.dataset.product_id || fd.get('add-to-cart');
	const qty = Math.max(
		1,
		parseInt(toLatinDigits(fd.get('quantity') || '1'), 10) || 1
	);
	const payload = { product_id: productId, quantity: qty };

	const attrs = {};
	for (const [k, v] of fd.entries()) {
		if (k.indexOf('attribute_') === 0 && v) attrs[k] = v;
	}

	if (form.dataset.variable === '1') {
		const labels = parseJSONAttr(form.dataset.glAttrs, {});
		const missing = Object.keys(labels).filter((k) => !attrs[k]);
		const card = form.closest('[data-gl-card]');
		const variationId = parseInt(fd.get('variation_id') || '0', 10) || 0;

		if (missing.length && !variationId) {
			return {
				error: `لطفاً ${missing.map((k) => labels[k]).join(' و ')} را انتخاب کنید.`,
				code: 'choose_option',
			};
		}
		// Cards know the exact variation; the single-product form lets the
		// server resolve it from the chosen attributes.
		if (card && variationId) payload.variation_id = variationId;
		Object.assign(payload, attrs);
	}

	return { payload, attrs };
}

function showFormError(form, message) {
	const el = form.querySelector('[data-gl-form-error]');
	if (!el) return;
	el.textContent = message;
	el.hidden = false;
}

function hideFormError(form) {
	const el = form.querySelector('[data-gl-form-error]');
	if (el) el.hidden = true;
}

function flagOptions(form) {
	const card = form.closest('[data-gl-card]');
	const target = card
		? $('[data-gl-swatches]', card)
		: $('[data-gl-attr]', form);
	if (!target) return;
	target.classList.remove('gl-shake');
	void target.offsetWidth;
	target.classList.add('gl-shake');
	window.setTimeout(() => target.classList.remove('gl-shake'), 700);
	if (!card) {
		const first = $('input[type="radio"]:not(:disabled)', target);
		if (first) first.focus({ preventScroll: false });
	}
}

function variationImage(form, attrs) {
	const card = form.closest('[data-gl-card]');
	if (card) {
		const img = $('[data-gl-fly-img]', card);
		if (img && img.currentSrc) return img.currentSrc;
		if (img && img.src) return img.src;
	}
	const variations =
		form._glVariations ||
		(form._glVariations = parseJSONAttr(
			form.dataset.product_variations,
			[]
		));
	if (Array.isArray(variations) && attrs && Object.keys(attrs).length) {
		const match = findVariation(variations, attrs);
		if (match && match.image)
			return (
				match.image.thumb_src || match.image.src || form.dataset.glImage
			);
	}
	return form.dataset.glImage || '';
}

function inViewport(el) {
	if (!el) return false;
	const r = el.getBoundingClientRect();
	return (
		r.width > 0 &&
		r.height > 0 &&
		r.bottom > 0 &&
		r.top < window.innerHeight
	);
}

function flySource(form, btn) {
	const card = form.closest('[data-gl-card]');
	if (card) {
		const img = $('[data-gl-fly-img]', card);
		if (inViewport(img)) return img;
	}
	if (!form.closest('#openForm')) {
		const hero = $('main img[fetchpriority="high"]');
		if (inViewport(hero)) return hero;
	}
	return btn && inViewport(btn) ? btn : null;
}

function cartTarget() {
	const candidates = $$(
		'#header button[aria-label="open cart modal"], .header-cart-count'
	);
	return candidates.find((el) => inViewport(el)) || null;
}

function fly(fromEl, src) {
	const target = cartTarget();
	if (
		reducedMotion() ||
		!fromEl ||
		!src ||
		!target ||
		typeof document.body.animate !== 'function'
	) {
		return Promise.resolve();
	}
	const a = fromEl.getBoundingClientRect();
	const b = target.getBoundingClientRect();
	const size = Math.max(48, Math.min(140, a.width, a.height || a.width));
	const startX = a.left + a.width / 2 - size / 2;
	const startY = a.top + a.height / 2 - size / 2;
	const dx = b.left + b.width / 2 - (startX + size / 2);
	const dy = b.top + b.height / 2 - (startY + size / 2);

	const img = document.createElement('img');
	img.src = src;
	img.alt = '';
	img.setAttribute('aria-hidden', 'true');
	img.className = 'gl-fly';
	Object.assign(img.style, {
		left: `${startX}px`,
		top: `${startY}px`,
		width: `${size}px`,
		height: `${size}px`,
	});
	document.body.appendChild(img);

	const lift = Math.min(160, Math.abs(dy) * 0.35 + 60);
	const anim = img.animate(
		[
			{ transform: 'translate(0, 0) scale(1)', opacity: 1 },
			{
				transform: `translate(${dx * 0.45}px, ${dy * 0.45 - lift}px) scale(0.7)`,
				opacity: 1,
				offset: 0.5,
			},
			{
				transform: `translate(${dx}px, ${dy}px) scale(0.12)`,
				opacity: 0.4,
			},
		],
		{
			duration: 720,
			easing: 'cubic-bezier(0.45, 0, 0.25, 1)',
			fill: 'forwards',
		}
	);
	return new Promise((resolve) => {
		const done = () => {
			img.remove();
			resolve();
		};
		anim.onfinish = done;
		anim.oncancel = done;
		window.setTimeout(done, 1200); // safety net
	});
}

async function addToCart(form, btn) {
	if (form.dataset.glBusy === '1') return;

	const collected = collectForm(form);
	hideFormError(form);

	if (collected.error) {
		const card = form.closest('[data-gl-card]');
		// A card with no selectable colours: let the product page handle it.
		if (card && !$('[data-gl-swatches] button', card)) {
			window.location.href = form.action;
			return;
		}
		showFormError(form, collected.error);
		flagOptions(form);
		toast(collected.error, 'error');
		setButtonState(btn, 'error');
		return;
	}

	form.dataset.glBusy = '1';
	setButtonState(btn, 'loading');
	const countBefore =
		parseInt(($('#gl-cart-body') || {}).dataset?.count || '0', 10) || 0;

	try {
		const r = await post('gl_add_to_cart', collected.payload);
		const data = r.data;

		if (!r.success) {
			const message = data.message || 'افزودن به سبد خرید انجام نشد.';
			setButtonState(btn, 'error');
			showFormError(form, message);
			if (data.code === 'choose_option') flagOptions(form);
			toast(message, 'error');
			if (data.fragments) applyPayload(data);
			return;
		}

		await onAdded(form, btn, data, collected.attrs);
	} catch (err) {
		if (!(err instanceof GlCartError)) {
			// Unexpected client-side error after a successful request: resync.
			setButtonState(btn, 'idle');
			refresh();
			if (window.console) window.console.error(err);
		} else if (err.kind === 'bad_response') {
			// The server may or may not have added the item (e.g. a PHP notice
			// broke the JSON). Check before falling back to a normal POST.
			const fresh = await refresh();
			if (fresh && fresh.count > countBefore) {
				await onAdded(form, btn, fresh, collected.attrs);
			} else {
				setButtonState(btn, 'idle');
				form.dataset.glNative = '1';
				HTMLFormElement.prototype.submit.call(form);
			}
		} else {
			setButtonState(btn, 'error');
			toast(
				'ارتباط با سرور برقرار نشد؛ اتصال اینترنت را بررسی و دوباره تلاش کنید.',
				'error'
			);
		}
	} finally {
		delete form.dataset.glBusy;
	}
}

async function onAdded(form, btn, data, attrs) {
	setButtonState(btn, 'success');

	const state = bodyState();
	const inSheet = !!form.closest('#openForm');
	const source = flySource(form, btn);
	const image = variationImage(form, attrs);
	if (state && inSheet) state.openForm = false;

	triggerJq('added_to_cart', [{}, data.cart_hash || '', null]);
	if (document.body.classList.contains('woocommerce-checkout'))
		triggerJq('update_checkout');

	await fly(source, image);
	applyPayload(data, { highlight: data.key, bounce: true });
	announce(data.message || 'به سبد خرید اضافه شد.');
	openDrawer();
}

/* ------------------------------------------------------------------ */
/* Drawer: quantity / remove / undo                                    */
/* ------------------------------------------------------------------ */

let mutationSeq = 0;
const qtyTimers = new Map();
let undoState = { key: null, timer: null };

async function mutate(action, payload, opts = {}) {
	const seq = ++mutationSeq;
	try {
		const r = await post(action, payload);
		if (seq !== mutationSeq && r.success) return r; // a newer change will repaint
		if (!r.success && r.data.message) toast(r.data.message, 'error');
		applyPayload(r.data, opts);
		syncWooPages();
		return r;
	} catch {
		toast('ارتباط با سرور برقرار نشد؛ دوباره تلاش کنید.', 'error');
		refresh();
		return null;
	}
}

function paintQty(item, qty) {
	item.dataset.qty = String(qty);
	const out = $('[data-gl-qty-value]', item);
	if (out) out.textContent = String(qty);
	const max = parseInt(item.dataset.max || '0', 10);
	const plus = $('[data-gl-qty="1"]', item);
	if (plus) plus.disabled = max > 0 && qty >= max;
	const minus = $('[data-gl-qty="-1"]', item);
	if (minus)
		minus.setAttribute(
			'aria-label',
			qty <= 1 ? 'حذف از سبد' : 'کاهش تعداد'
		);
}

function stepItem(btn) {
	const item = btn.closest('[data-gl-item]');
	if (!item || item.dataset.busy === '1') return;
	const key = item.dataset.glItem;
	const delta = parseInt(btn.dataset.glQty, 10) || 0;
	const qty = parseInt(item.dataset.qty || '1', 10) || 1;
	const max = parseInt(item.dataset.max || '0', 10);
	const next = qty + delta;

	if (next < 1) {
		removeItem(item);
		return;
	}
	if (max > 0 && next > max) {
		toast(`از این محصول حداکثر ${max} عدد قابل خرید است.`, 'error');
		return;
	}
	paintQty(item, next);

	window.clearTimeout(qtyTimers.get(key));
	qtyTimers.set(
		key,
		window.setTimeout(() => {
			qtyTimers.delete(key);
			const live = $(`[data-gl-item="${cssEscape(key)}"]`);
			if (live) live.dataset.busy = '1';
			mutate('gl_cart_set_qty', { key, quantity: next });
		}, 380)
	);
}

function removeItem(item) {
	if (!item || item.dataset.busy === '1') return;
	const key = item.dataset.glItem;
	const name = item.dataset.name || '';
	window.clearTimeout(qtyTimers.get(key));
	qtyTimers.delete(key);
	item.dataset.busy = '1';
	item.classList.add('gl-removing');

	mutate('gl_cart_remove', { key }).then((r) => {
		if (r && r.success) {
			showUndo(key, r.data.name || name);
			announce(r.data.message || 'کالا از سبد حذف شد.');
		} else if (item.isConnected) {
			item.classList.remove('gl-removing');
			delete item.dataset.busy;
		}
	});
}

function showUndo(key, name) {
	const bar = $('[data-gl-undo-bar]');
	if (!bar) return;
	window.clearTimeout(undoState.timer);
	undoState.key = key;
	const text = $('[data-gl-undo-text]', bar);
	if (text) text.textContent = name ? `«${name}» حذف شد` : 'کالا حذف شد';
	bar.hidden = false;
	undoState.timer = window.setTimeout(hideUndo, 7000);
}

function hideUndo() {
	const bar = $('[data-gl-undo-bar]');
	if (bar) bar.hidden = true;
	window.clearTimeout(undoState.timer);
	undoState = { key: null, timer: null };
}

function undoRemove() {
	const key = undoState.key;
	if (!key) return;
	hideUndo();
	mutate('gl_cart_undo', { key }, { highlight: key, bounce: true }).then(
		(r) => {
			if (r && r.success)
				announce(r.data.message || 'کالا به سبد بازگشت.');
			const close = $('.gl-cart-panel [data-gl-close]');
			if (close && !$('.gl-cart-panel').contains(document.activeElement))
				close.focus({ preventScroll: true });
		}
	);
}

/* ------------------------------------------------------------------ */
/* Single product: qty buttons, colour sync, live price               */
/* ------------------------------------------------------------------ */

function stepNumberInput(btn) {
	const group = btn.closest('[role="group"]') || btn.parentElement;
	const input = group && $('input[type="number"]', group);
	if (!input) return;
	const min = parseInt(input.min || '1', 10);
	const max = parseInt(input.max || '0', 10);
	const current = parseInt(toLatinDigits(input.value), 10) || min;
	let next = current + (parseInt(btn.dataset.glStep, 10) || 0);
	if (next < min) next = min;
	if (max > 0 && next > max) {
		next = max;
		toast(`از این محصول حداکثر ${max} عدد قابل خرید است.`, 'error');
	}
	input.value = String(next);
	input.dispatchEvent(new Event('change', { bubbles: true }));
}

function findVariation(variations, attrs) {
	return variations.find((v) => {
		const va = v.attributes || {};
		return Object.keys(va).every((k) => va[k] === '' || va[k] === attrs[k]);
	});
}

function chosenAttrs(form) {
	const attrs = {};
	$$('input.variation-input:checked', form).forEach((r) => {
		attrs[r.name] = r.value;
	});
	return attrs;
}

function updateLivePrice(form) {
	const productId = form.dataset.product_id;
	const variations =
		form._glVariations ||
		(form._glVariations = parseJSONAttr(
			form.dataset.product_variations,
			[]
		));
	if (!productId || !Array.isArray(variations) || !variations.length) return;
	const match = findVariation(variations, chosenAttrs(form));
	if (!match || typeof match.display_price === 'undefined') return;

	const now = Number(match.display_price) || 0;
	const was = Number(match.display_regular_price) || 0;
	const onSale = was > now;
	const pct = onSale && was > 0 ? Math.round(((was - now) / was) * 100) : 0;

	$$(`[data-gl-price][data-product-id="${cssEscape(productId)}"]`).forEach(
		(box) => {
			const nowEl = $('[data-gl-price-now]', box);
			const wasEl = $('[data-gl-price-was]', box);
			const offEl = $('[data-gl-price-off]', box);
			const pctEl = $('[data-gl-price-pct]', box);
			if (nowEl) nowEl.textContent = formatNumber(now);
			if (wasEl) {
				wasEl.textContent = formatNumber(was);
				wasEl.hidden = !onSale;
			}
			if (offEl) offEl.hidden = !(onSale && pct > 0);
			if (pctEl) pctEl.textContent = String(pct);
		}
	);
}

let syncingRadios = false;
function onVariationChange(radio) {
	const form = radio.closest('form[data-gl-cart-form]');
	if (!form) return;
	hideFormError(form);

	const fieldset = radio.closest('[data-gl-attr]');
	const chosen = fieldset && $('[data-gl-attr-chosen]', fieldset);
	if (chosen) chosen.textContent = radio.dataset.label || '';

	updateLivePrice(form);

	// Mirror the choice into the other copy of the form (sidebar <-> sheet).
	if (syncingRadios) return;
	syncingRadios = true;
	try {
		$$(
			`form[data-gl-cart-form][data-product_id="${cssEscape(form.dataset.product_id)}"]`
		).forEach((other) => {
			if (other === form) return;
			const twin = $$(
				`input.variation-input[name="${cssEscape(radio.name)}"]`,
				other
			).find((r) => r.value === radio.value);
			if (twin && !twin.checked) {
				twin.checked = true;
				twin.dispatchEvent(new Event('change', { bubbles: true }));
			}
		});
	} finally {
		syncingRadios = false;
	}
}

/* ------------------------------------------------------------------ */
/* Cart page (WooCommerce cart.js does the AJAX update)                */
/* ------------------------------------------------------------------ */

let cartPageTimer = null;
function cartPageStep(btn) {
	const wrap = btn.closest('[data-gl-cart-qty]');
	const input = wrap && $('input.qty', wrap);
	if (!input) return;
	const min = parseInt(input.min || '0', 10);
	const max = parseInt(input.max || '0', 10);
	const current = parseInt(toLatinDigits(input.value), 10) || 0;
	let next = current + (parseInt(btn.dataset.glCartStep, 10) || 0);
	if (next < Math.max(min, 0)) next = Math.max(min, 0);
	if (max > 0 && next > max) {
		toast(`از این محصول حداکثر ${max} عدد قابل خرید است.`, 'error');
		next = max;
	}
	if (next === current) return;
	input.value = String(next);
	input.dispatchEvent(new Event('change', { bubbles: true }));

	scheduleCartUpdate(input);
}

function scheduleCartUpdate(input) {
	window.clearTimeout(cartPageTimer);
	cartPageTimer = window.setTimeout(() => {
		const form = input.closest('form.woocommerce-cart-form');
		const update = form && $('[name="update_cart"]', form);
		if (update) {
			update.disabled = false;
			update.click();
		}
	}, 650);
}

/* ------------------------------------------------------------------ */
/* Checkout inline validation messages                                 */
/* ------------------------------------------------------------------ */

function fieldLabel(row) {
	const label = $('label', row);
	if (!label) return 'این فیلد';
	const clone = label.cloneNode(true);
	$$('.required, .optional, abbr, .screen-reader-text', clone).forEach((n) =>
		n.remove()
	);
	return (
		clone.textContent.replace(/\s+/g, ' ').replace(/[*:]/g, '').trim() ||
		'این فیلد'
	);
}

function messageFor(row) {
	const field = $('input, select, textarea', row);
	if (row.classList.contains('woocommerce-invalid-email'))
		return 'ایمیل واردشده معتبر نیست (مثال: name@example.com).';
	if (row.classList.contains('woocommerce-invalid-phone'))
		return 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.';
	if (field && field.type === 'checkbox')
		return 'برای ثبت سفارش، پذیرفتن قوانین و مقررات الزامی است.';
	if (field && field.tagName === 'SELECT')
		return `لطفاً ${fieldLabel(row)} را انتخاب کنید.`;
	return `لطفاً ${fieldLabel(row)} را وارد کنید.`;
}

function syncInlineError(row) {
	const invalid = row.classList.contains('woocommerce-invalid');
	let mine = $(':scope > .gl-inline-error', row);
	const wcMessage = $('.checkout-inline-error-message', row);
	if (!invalid || wcMessage) {
		if (mine) mine.remove();
		return;
	}
	const text = messageFor(row);
	if (!mine) {
		const field = $('input, select, textarea', row);
		mine = document.createElement('p');
		mine.className = 'gl-inline-error';
		mine.id = `${(field && field.id) || 'field'}-gl-error`;
		row.appendChild(mine);
		if (field && !field.getAttribute('aria-describedby'))
			field.setAttribute('aria-describedby', mine.id);
	}
	if (mine.textContent !== text) mine.textContent = text;
}

function initCheckoutValidation() {
	const form = $('form.checkout');
	if (!form || typeof MutationObserver === 'undefined') return;

	new MutationObserver((mutations) => {
		mutations.forEach((m) => {
			const row = m.target;
			if (row.classList && row.classList.contains('form-row'))
				syncInlineError(row);
		});
	}).observe(form, {
		subtree: true,
		attributes: true,
		attributeFilter: ['class'],
	});

	// Iranian mobile format (server re-validates in inc/account-rules.php).
	form.addEventListener('focusout', (e) => {
		const input = e.target;
		if (!input || input.id !== 'billing_phone') return;
		const value = toLatinDigits(input.value).replace(/[\s\-()]/g, '');
		if (!value) return;
		if (!/^(?:\+98|0098|98|0)?9\d{9}$/.test(value)) {
			window.setTimeout(() => {
				const row = input.closest('.form-row');
				if (!row) return;
				row.classList.remove('woocommerce-validated');
				row.classList.add(
					'woocommerce-invalid',
					'woocommerce-invalid-phone'
				);
				input.setAttribute('aria-invalid', 'true');
			}, 0);
		}
	});
}

/* ------------------------------------------------------------------ */
/* Drawer Alpine component (focus trap, swipe to close)                */
/* ------------------------------------------------------------------ */

const FOCUSABLE =
	'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

document.addEventListener('alpine:init', () => {
	const A = window.Alpine || Alpine;
	A.data('glCartDrawer', () => ({
		lastFocus: null,

		init() {
			this.$watch('cart', (open) =>
				open ? this.onOpen() : this.onClose()
			);
			// x-ref children are registered after init(): wait a tick.
			this.$nextTick(() => this.initDrag());
		},

		onOpen() {
			this.lastFocus = document.activeElement;
			syncFromCookie();
			window.setTimeout(() => {
				const target = this.$refs.close || this.$refs.panel;
				if (target) target.focus({ preventScroll: true });
			}, 80);
		},

		onClose() {
			hideUndo();
			const el = this.lastFocus;
			this.lastFocus = null;
			if (el && typeof el.focus === 'function' && document.contains(el)) {
				window.setTimeout(() => el.focus({ preventScroll: true }), 220);
			}
		},

		trap(e) {
			const panel =
				this.$refs.panel || this.$el.querySelector('.gl-cart-panel');
			const items = $$(FOCUSABLE, panel).filter(
				(el) => el.offsetParent !== null && !el.closest('[hidden]')
			);
			if (!items.length) return;
			const first = items[0];
			const last = items[items.length - 1];
			if (!panel.contains(document.activeElement)) {
				e.preventDefault();
				first.focus();
			} else if (e.shiftKey && document.activeElement === first) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && document.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		},

		initDrag() {
			const panel =
				this.$refs.panel || this.$el.querySelector('.gl-cart-panel');
			const handle = panel && $('[data-gl-drag]', panel);
			if (!handle) return;
			let startY = null;
			let dy = 0;
			handle.addEventListener('pointerdown', (e) => {
				if (window.innerWidth >= 1024) return;
				startY = e.clientY;
				dy = 0;
				try {
					handle.setPointerCapture(e.pointerId);
				} catch {
					/* noop */
				}
				panel.style.transition = 'none';
			});
			handle.addEventListener('pointermove', (e) => {
				if (startY === null) return;
				dy = Math.max(0, e.clientY - startY);
				panel.style.transform = `translateY(${dy}px)`;
			});
			const end = () => {
				if (startY === null) return;
				startY = null;
				panel.style.transition = '';
				panel.style.transform = '';
				if (dy > 90) this.cart = false;
			};
			handle.addEventListener('pointerup', end);
			handle.addEventListener('pointercancel', end);
		},
	}));
});

/* ------------------------------------------------------------------ */
/* Global listeners                                                    */
/* ------------------------------------------------------------------ */

document.documentElement.classList.add('gl-js');

// Capture phase: we decide before anything else submits the form.
document.addEventListener(
	'submit',
	(e) => {
		const form = e.target;
		if (
			!(form instanceof HTMLFormElement) ||
			!form.hasAttribute('data-gl-cart-form')
		)
			return;
		if (form.dataset.glNative === '1') return;
		if (!window.fetch || !window.URLSearchParams) return; // very old browser: native POST
		e.preventDefault();
		const btn =
			e.submitter ||
			(form.id &&
				$(`button[type="submit"][form="${cssEscape(form.id)}"]`)) ||
			$('button[type="submit"]', form);
		addToCart(form, btn);
	},
	true
);

document.addEventListener('click', (e) => {
	const t = e.target;
	if (!(t instanceof Element)) return;

	const close = t.closest('[data-gl-close]');
	if (close) {
		e.preventDefault();
		closeDrawer();
		return;
	}
	const qtyBtn = t.closest('[data-gl-qty]');
	if (qtyBtn) {
		e.preventDefault();
		stepItem(qtyBtn);
		return;
	}
	const remove = t.closest('[data-gl-remove]');
	if (remove) {
		e.preventDefault();
		removeItem(remove.closest('[data-gl-item]'));
		return;
	}
	if (t.closest('[data-gl-undo]')) {
		e.preventDefault();
		undoRemove();
		return;
	}
	const step = t.closest('[data-gl-step]');
	if (step) {
		e.preventDefault();
		stepNumberInput(step);
		return;
	}
	const cartStep = t.closest('[data-gl-cart-step]');
	if (cartStep) {
		e.preventDefault();
		cartPageStep(cartStep);
	}
});

document.addEventListener('change', (e) => {
	const t = e.target;
	// Typed quantity on the cart page: the "update cart" button is hidden, so auto-update.
	if (
		e.isTrusted &&
		t instanceof HTMLInputElement &&
		t.classList.contains('qty') &&
		t.closest('form.woocommerce-cart-form')
	) {
		scheduleCartUpdate(t);
		return;
	}
	if (
		t instanceof HTMLInputElement &&
		t.classList.contains('variation-input') &&
		t.closest('form[data-gl-cart-form]')
	) {
		onVariationChange(t);
	}
});

function boot() {
	syncFromCookie();
	initCheckoutValidation();

	// Cart / checkout pages changed by WooCommerce itself -> refresh drawer.
	const $j = jq();
	if ($j) {
		$j(document.body).on(
			'updated_wc_div updated_cart_totals removed_from_cart wc_cart_emptied applied_coupon removed_coupon',
			() => {
				if (skipWcSync > 0) {
					skipWcSync -= 1;
					return;
				}
				refresh();
			}
		);
	}
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', boot);
} else {
	boot();
}

// Back/forward cache restores a stale page: resync.
window.addEventListener('pageshow', (e) => {
	if (e.persisted) syncFromCookie();
});
