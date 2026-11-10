/**
 * Product compare — client-side list (localStorage `compare_products`, max 4) shared by
 * the card/product buttons, the floating tray and the compare page.
 * Data for the page comes from GET {rest}compare?ids=… (inc/product/compare-function.php).
 */
import Alpine from 'alpinejs';

const KEY = 'compare_products';
const MAX = 4;
const cfg = () => window.glCfg || {};
const restUrl = (path) => (cfg().rest || '/wp-json/gilllda/v1/') + path;

export const fa = (n) => String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
export const money = (n) => Math.round(Number(n) || 0).toLocaleString('en-US');
export const esc = (s) =>
	String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

function read() {
	let list = [];
	try {
		list = JSON.parse(localStorage.getItem(KEY) || '[]');
	} catch (e) {
		list = [];
	}
	if (!Array.isArray(list)) return [];
	const seen = new Set();
	const out = [];
	for (const raw of list) {
		const id = parseInt(raw && typeof raw === 'object' ? raw.id : raw, 10);
		if (!id || id < 0 || seen.has(id)) continue;
		seen.add(id);
		out.push({ id, name: String(raw?.name || '').slice(0, 120), img: String(raw?.img || '') });
		if (out.length >= MAX) break;
	}
	return out;
}

function write(items) {
	try {
		localStorage.setItem(KEY, JSON.stringify(items));
	} catch (e) {
		/* private mode */
	}
}

export function toast(message, type = 'success') {
	let region = document.getElementById('gl-toasts');
	if (!region) {
		region = document.createElement('div');
		region.id = 'gl-toasts';
		region.className =
			'pointer-events-none fixed left-3 right-3 top-3 z-[120] flex flex-col items-center gap-2 lg:right-auto lg:bottom-6 lg:left-6 lg:top-auto lg:w-96';
		region.setAttribute('aria-live', 'polite');
		document.body.appendChild(region);
	}
	const el = document.createElement('div');
	el.className = `gl-toast gl-toast--${type}`;
	el.setAttribute('role', type === 'error' ? 'alert' : 'status');
	el.textContent = message;
	region.appendChild(el);
	setTimeout(() => {
		el.classList.add('gl-toast--out');
		setTimeout(() => el.remove(), 300);
	}, type === 'error' ? 3600 : 2200);
}

/** Header badge (header-content.php keeps its own x-data { compareCount }). */
function syncBadges(n) {
	document.querySelectorAll('a[aria-label="go to compare page"]').forEach((el) => {
		try {
			const data = Alpine.$data(el);
			if (data && 'compareCount' in data) data.compareCount = n;
		} catch (e) {
			/* not initialised */
		}
	});
}

let hydrating = null;

document.addEventListener('alpine:init', () => {
	Alpine.store('compare', {
		items: read(),
		max: MAX,
		has(id) {
			return this.items.some((i) => i.id === id);
		},
		commit() {
			write(this.items);
			syncBadges(this.items.length);
			window.dispatchEvent(new CustomEvent('gl-compare-change', { detail: { ids: this.items.map((i) => i.id) } }));
		},
		toggle(p) {
			if (this.has(p.id)) {
				this.remove(p.id);
				toast('از لیست مقایسه حذف شد', 'success');
				return false;
			}
			if (this.items.length >= MAX) {
				toast(`حداکثر ${fa(MAX)} محصول را می‌توانید مقایسه کنید؛ یکی را حذف کنید.`, 'error');
				return false;
			}
			this.items = [...this.items, { id: p.id, name: p.name || '', img: p.img || '' }];
			this.commit();
			toast(
				this.items.length >= 2
					? `به مقایسه اضافه شد (${fa(this.items.length)} محصول)`
					: 'به مقایسه اضافه شد؛ یک محصول دیگر هم انتخاب کنید',
				'success'
			);
			return true;
		},
		remove(id) {
			this.items = this.items.filter((i) => i.id !== id);
			this.commit();
		},
		clear() {
			this.items = [];
			this.commit();
		},
		update(list) {
			// Fill names/images from the API; drop ids that no longer exist.
			const byReq = new Map(list.map((p) => [p.req ?? p.id, p]));
			const next = this.items
				.filter((i) => byReq.has(i.id))
				.map((i) => ({ id: i.id, name: byReq.get(i.id).name, img: byReq.get(i.id).thumb || byReq.get(i.id).img }));
			if (JSON.stringify(next) !== JSON.stringify(this.items)) {
				this.items = next;
				this.commit();
			}
		},
		sync() {
			this.items = read();
			syncBadges(this.items.length);
		},
	});

	Alpine.data('glCompareBtn', (p) => ({
		p,
		get on() {
			return Alpine.store('compare').has(p.id);
		},
		toggle() {
			Alpine.store('compare').toggle(p);
		},
	}));

	Alpine.data('glCompareTray', () => ({
		fa,
		url: cfg().compareUrl || '/compare/',
		get show() {
			const n = Alpine.store('compare').items.length;
			// body x-data owns these flags; hide the tray while overlays are open.
			return n >= 2 && !this.searchOpen && !this.cart && !this.menuOpen && !this.categoryOpen;
		},
		init() {
			this.$watch('show', (v) => v && this.hydrate());
			if (this.show) this.hydrate();
		},
		hydrate() {
			const store = Alpine.store('compare');
			if (hydrating || !store.items.some((i) => !i.img)) return;
			hydrating = fetch(restUrl('compare?ids=' + store.items.map((i) => i.id).join(',')))
				.then((r) => (r.ok ? r.json() : null))
				.then((d) => d && store.update(d.products || []))
				.catch(() => {})
				.finally(() => (hydrating = null));
		},
	}));

	Alpine.data('glComparePage', comparePage);
});

window.addEventListener('storage', (e) => {
	if (e.key === KEY && Alpine.store('compare')) Alpine.store('compare').sync();
});

/* ---------------------------------------------------------------- page */

const STOCK = {
	instock: ['موجود', 'text-emerald-700 bg-emerald-50'],
	onbackorder: ['پیش‌سفارش', 'text-amber-700 bg-amber-50'],
	outofstock: ['ناموجود', 'text-secondary bg-secondary/5'],
};

function priceHtml(p) {
	if (p.stock === 'outofstock' && !p.price) return '<span class="text-gray-400">—</span>';
	let html = `<span class="font-bold text-gray-900">${p.price_max ? 'از ' : ''}${money(p.price)}</span> <span class="text-[11px] text-gray-500">تومان</span>`;
	if (p.regular) {
		const off = Math.round(((p.regular - p.price) / p.regular) * 100);
		html += `<span class="mt-1 flex items-center gap-1.5"><del class="text-xs text-gray-400">${money(p.regular)}</del>${off > 0 ? `<span class="rounded-full bg-secondary px-1.5 text-[11px] font-bold text-white">${fa(off)}٪</span>` : ''}</span>`;
	}
	return `<span class="flex flex-col items-start">${html}</span>`;
}

function colorsHtml(p) {
	if (!p.colors?.length) return '<span class="text-gray-400">—</span>';
	return `<span class="flex flex-wrap gap-x-3 gap-y-2">${p.colors
		.map(
			(c) =>
				`<span class="inline-flex items-center gap-1.5 ${c.ok ? '' : 'opacity-50'}" title="${esc(c.name)}${c.ok ? '' : ' (ناموجود)'}"><span class="size-4 shrink-0 rounded-full border border-gray-300 shadow-sm" style="background:${esc(c.hex || '#e5e7eb')}"></span><span class="text-xs">${esc(c.name)}</span></span>`
		)
		.join('')}</span>`;
}

function ratingHtml(p) {
	if (!p.rating) return '<span class="text-gray-400">بدون امتیاز</span>';
	return `<span class="inline-flex items-center gap-1"><svg class="size-4 text-amber-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.8l-5.2 2.8 1-5.8L1.5 7.7l5.9-.9z"/></svg><b>${fa(p.rating)}</b><span class="text-xs text-gray-400">(${fa(p.reviews)} نظر)</span></span>`;
}

const textCell = (v) => (v ? esc(v) : '<span class="text-gray-400">—</span>');

function comparePage() {
	return {
		loading: true,
		failed: false,
		products: [],
		highlight: false,
		stuck: false,
		top: 0,
		fa,
		money,
		priceHtml,
		get n() {
			return this.products.length;
		},
		get rows() {
			const P = this.products;
			const rows = [
				{ key: 'price', label: 'قیمت', html: priceHtml, cmp: (p) => `${p.price}|${p.regular}|${p.price_max}` },
				{ key: 'colors', label: 'رنگ‌ها', html: colorsHtml, cmp: (p) => p.colors.map((c) => c.name).sort().join() },
				{
					key: 'stock',
					label: 'وضعیت موجودی',
					html: (p) => {
						const s = STOCK[p.stock] || STOCK.instock;
						return `<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold ${s[1]}">${s[0]}</span>`;
					},
					cmp: (p) => p.stock,
				},
				{ key: 'cats', label: 'دسته‌بندی', html: (p) => textCell(p.cats), cmp: (p) => p.cats },
				{ key: 'dims', label: 'ابعاد', html: (p) => textCell(p.dims), cmp: (p) => p.dims },
				{ key: 'weight', label: 'وزن', html: (p) => textCell(p.weight), cmp: (p) => p.weight },
			];
			const labels = [];
			P.forEach((p) => Object.keys(p.attrs || {}).forEach((l) => labels.includes(l) || labels.push(l)));
			labels.forEach((l, i) =>
				rows.push({ key: 'a' + i, label: l, html: (p) => textCell(p.attrs?.[l]), cmp: (p) => p.attrs?.[l] || '' })
			);
			rows.push(
				{ key: 'rating', label: 'امتیاز کاربران', html: ratingHtml, cmp: (p) => `${p.rating}` },
				{
					key: 'short',
					label: 'توضیحات',
					html: (p) => (p.short ? `<span class="text-xs leading-6 text-gray-600">${esc(p.short)}</span>` : textCell('')),
					cmp: (p) => p.short,
				}
			);
			return rows
				.filter((r) => P.some((p) => r.cmp(p))) // skip rows empty for everyone
				.map((r) => ({ ...r, diff: P.length > 1 && new Set(P.map((p) => String(r.cmp(p) ?? ''))).size > 1 }));
		},
		get diffCount() {
			return this.rows.filter((r) => r.diff).length;
		},
		init() {
			this.load();
			window.addEventListener('gl-compare-change', () => {
				const ids = Alpine.store('compare').items.map((i) => i.id);
				this.products = this.products.filter((p) => ids.includes(p.req ?? p.id));
				if (ids.some((id) => !this.products.find((p) => (p.req ?? p.id) === id))) this.load();
			});
			// Compact sticky product bar once the header row scrolls away.
			let ticking = false;
			const check = () => {
				ticking = false;
				const head = this.$refs.head;
				const table = this.$refs.table;
				if (!head || !table || !this.n) return (this.stuck = false);
				this.measureTop();
				const edge = this.top + 8;
				this.stuck = head.getBoundingClientRect().top < edge && table.getBoundingClientRect().bottom > edge + 140;
				if (this.stuck) this.$nextTick(() => this.syncScroll());
			};
			window.addEventListener(
				'scroll',
				() => {
					if (!ticking) {
						ticking = true;
						requestAnimationFrame(check);
					}
				},
				{ passive: true }
			);
		},
		measureTop() {
			let top = 0;
			const desktop = window.matchMedia('(min-width: 1024px)').matches;
			const el = desktop ? document.getElementById('header') : document.querySelector('.gl-mobile-top');
			if (el) {
				const r = el.getBoundingClientRect();
				if (r.height && r.top <= 1 && r.bottom > 0) top = Math.round(r.bottom);
			}
			this.top = top;
		},
		syncScroll() {
			if (this.$refs.mini && this.$refs.table) this.$refs.mini.scrollLeft = this.$refs.table.scrollLeft;
		},
		load() {
			const ids = Alpine.store('compare').items.map((i) => i.id);
			if (!ids.length) {
				this.products = [];
				this.loading = false;
				return;
			}
			this.loading = !this.products.length;
			this.failed = false;
			fetch(restUrl('compare?ids=' + ids.join(',')))
				.then((r) => {
					if (!r.ok) throw new Error(r.status);
					return r.json();
				})
				.then((d) => {
					this.products = d.products || [];
					Alpine.store('compare').update(this.products);
				})
				.catch(() => {
					this.failed = true;
				})
				.finally(() => {
					this.loading = false;
				});
		},
		remove(p) {
			Alpine.store('compare').remove(p.req ?? p.id);
			toast('از لیست مقایسه حذف شد', 'success');
		},
		clearAll() {
			Alpine.store('compare').clear();
		},
	};
}
