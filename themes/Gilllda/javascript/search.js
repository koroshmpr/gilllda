/**
 * Site search — header modal (instant results) + search page helpers.
 * Endpoint: GET {rest}search?q=… (theme/inc/ajax-search.php).
 */
import Alpine from 'alpinejs';
import { fa, money, esc } from './compare.js';

const RECENT_KEY = 'gl_recent_searches';
const cfg = () => window.glCfg || {};

/* ----------------------------------------------------- normalisation */

const CHAR_MAP = {
	'ي': 'ی', 'ى': 'ی', 'ئ': 'ی', 'ك': 'ک', 'ة': 'ه', 'ۀ': 'ه',
	'أ': 'ا', 'إ': 'ا', 'آ': 'ا', 'ٱ': 'ا', 'ؤ': 'و',
	'‌': '', '‍': '', 'ـ': '',
};
'۰۱۲۳۴۵۶۷۸۹'.split('').forEach((d, i) => (CHAR_MAP[d] = String(i)));
'٠١٢٣٤٥٦٧٨٩'.split('').forEach((d, i) => (CHAR_MAP[d] = String(i)));

export function norm(text) {
	return String(text || '')
		.replace(/[ً-ٰٟ]/g, '')
		.split('')
		.map((c) => (c in CHAR_MAP ? CHAR_MAP[c] : c))
		.join('')
		.toLowerCase()
		.replace(/[^\p{L}\p{N}]+/gu, ' ')
		.trim();
}

// Variants of a normalised char that may appear in the original text.
const VARIANTS = { 'ی': 'یيىئ', 'ک': 'کك', 'ا': 'اآأإٱ', 'ه': 'هةۀ', 'و': 'وؤ' };
'0123456789'.split('').forEach((d) => (VARIANTS[d] = d + '۰۱۲۳۴۵۶۷۸۹'[d] + '٠١٢٣٤٥٦٧٨٩'[d]));
const reEsc = (s) => s.replace(/[.*+?^${}()|[\]\\-]/g, '\\$&');
const GAP = '[\\u200c\\u200d\\u0640\\u064B-\\u065F\\u0670]*';

export function matcher(q) {
	const tokens = [...new Set(norm(q).split(' ').filter(Boolean))].sort((a, b) => b.length - a.length);
	if (!tokens.length) return null;
	const parts = tokens.map((t) => [...t].map((c) => (VARIANTS[c] ? `[${VARIANTS[c]}]` : reEsc(c))).join(GAP));
	return new RegExp(`(${parts.join('|')})`, 'giu');
}

/** HTML-escaped text with <mark> around matches. */
export function highlight(text, re) {
	text = String(text || '');
	if (!re) return esc(text);
	let out = '';
	let last = 0;
	text.replace(re, (m, _g, idx) => {
		out += esc(text.slice(last, idx)) + '<mark class="rounded bg-secondary/10 px-0.5 text-secondary">' + esc(m) + '</mark>';
		last = idx + m.length;
		return m;
	});
	return out + esc(text.slice(last));
}

/* ----------------------------------------------------- recent searches */

export function getRecent() {
	try {
		const list = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
		if (Array.isArray(list)) return list.filter((s) => typeof s === 'string' && s.trim()).slice(0, 8);
	} catch (e) {
		/* ignore */
	}
	return [];
}

export function saveRecent(q) {
	q = String(q || '').trim().slice(0, 64);
	if (q.length < 2) return getRecent();
	const key = norm(q);
	const list = [q, ...getRecent().filter((s) => norm(s) !== key)].slice(0, 8);
	try {
		localStorage.setItem(RECENT_KEY, JSON.stringify(list));
	} catch (e) {
		/* ignore */
	}
	return list;
}

function setRecent(list) {
	try {
		localStorage.setItem(RECENT_KEY, JSON.stringify(list));
	} catch (e) {
		/* ignore */
	}
	return list;
}

// One-time migration of the old `recent_searches` cookie (it also broke page caching).
(function migrateCookie() {
	const m = document.cookie.match(/(?:^|; )recent_searches=([^;]*)/);
	if (!m) return;
	try {
		const old = JSON.parse(decodeURIComponent(m[1]).replace(/\\"/g, '"'));
		if (Array.isArray(old)) old.reverse().forEach((s) => saveRecent(String(s)));
	} catch (e) {
		/* ignore */
	}
	document.cookie = 'recent_searches=; path=/; max-age=0';
})();

export const searchUrl = (q) => (cfg().home || '/') + '?s=' + encodeURIComponent(String(q).trim());

/* ----------------------------------------------------- Alpine */

document.addEventListener('alpine:init', () => {
	Alpine.data('glSearch', () => {
		const cache = new Map();
		let timer = 0;
		let ctrl = null;
		return {
			q: '',
			res: null,
			loading: false,
			failed: false,
			active: -1,
			recent: getRecent(),
			re: null,
			fa,
			money,
			init() {
				this.$watch('searchOpen', (open) => {
					if (open) {
						this.recent = getRecent();
						this.$nextTick(() => {
							this.$refs.input?.focus();
							this.$refs.input?.select();
						});
					} else {
						this.abort();
						this.active = -1;
					}
				});
			},
			close() {
				this.searchOpen = false;
			},
			abort() {
				clearTimeout(timer);
				if (ctrl) ctrl.abort();
				ctrl = null;
			},
			get term() {
				return this.q.trim();
			},
			get ready() {
				return this.term.length >= 2;
			},
			get empty() {
				const r = this.res;
				return !!r && !r.products.length && !r.cats.length && !r.posts.length;
			},
			/** Flat list for keyboard navigation (results + "view all"). */
			get items() {
				const r = this.res;
				if (!r || !this.ready) return [];
				return [
					...r.products.map((x) => x.url),
					...r.cats.map((x) => x.url),
					...r.posts.map((x) => x.url),
					searchUrl(this.term),
				];
			},
			idx(group, i) {
				const r = this.res;
				if (!r) return -1;
				if (group === 'cats') return r.products.length + i;
				if (group === 'posts') return r.products.length + r.cats.length + i;
				if (group === 'all') return r.products.length + r.cats.length + r.posts.length;
				return i;
			},
			hl(text) {
				return highlight(text, this.re);
			},
			onInput() {
				this.abort();
				this.active = -1;
				this.failed = false;
				const q = this.term;
				if (q.length < 2) {
					this.loading = false;
					this.res = null;
					return;
				}
				const key = norm(q);
				if (cache.has(key)) {
					this.show(q, cache.get(key));
					return;
				}
				this.loading = true;
				timer = setTimeout(() => this.fetch(q, key), 250);
			},
			fetch(q, key) {
				ctrl = new AbortController();
				const url = (cfg().rest || '/wp-json/gilllda/v1/') + 'search?q=' + encodeURIComponent(q);
				fetch(url, { signal: ctrl.signal, headers: { Accept: 'application/json' } })
					.then((r) => {
						if (!r.ok) throw new Error(r.status);
						return r.json();
					})
					.then((data) => {
						data = { products: data.products || [], cats: data.cats || [], posts: data.posts || [] };
						if (cache.size > 40) cache.delete(cache.keys().next().value);
						cache.set(key, data);
						if (q === this.term) this.show(q, data);
					})
					.catch((e) => {
						if (e.name === 'AbortError') return;
						if (q === this.term) {
							this.loading = false;
							this.failed = true;
						}
					});
			},
			show(q, data) {
				this.re = matcher(q);
				this.res = data;
				this.loading = false;
			},
			move(step) {
				const n = this.items.length;
				if (!n) return;
				this.active = (this.active + step + n) % n;
				this.$nextTick(() => document.getElementById('gls-opt-' + this.active)?.scrollIntoView({ block: 'nearest' }));
			},
			enter(e) {
				if (this.active >= 0 && this.items[this.active]) {
					e.preventDefault();
					this.go(this.items[this.active]);
				}
				// otherwise the form submits natively → search page
			},
			submit(e) {
				if (this.term.length < 2) {
					e.preventDefault();
					this.$refs.input?.focus();
					return;
				}
				this.recent = saveRecent(this.term);
			},
			go(url) {
				if (this.ready) saveRecent(this.term);
				window.location.href = url;
			},
			pick(text) {
				this.q = text;
				this.onInput();
				this.$refs.input?.focus();
			},
			removeRecent(s) {
				this.recent = setRecent(this.recent.filter((x) => x !== s));
			},
			clearRecent() {
				this.recent = setRecent([]);
			},
			clear() {
				this.q = '';
				this.onInput();
				this.$refs.input?.focus();
			},
			allUrl() {
				return searchUrl(this.term);
			},
		};
	});

	/** search.php: tabs + recent chips. */
	Alpine.data('glSearchPage', (q = '', tab = 'all') => ({
		tab,
		recent: [],
		init() {
			this.recent = (q ? saveRecent(q) : getRecent()).filter((s) => norm(s) !== norm(q)).slice(0, 6);
		},
		url: searchUrl,
		removeRecent(s) {
			this.recent = this.recent.filter((x) => x !== s);
			setRecent(getRecent().filter((x) => x !== s));
		},
	}));
});
