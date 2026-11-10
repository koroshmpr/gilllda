/**
 * App-like navigation feedback + reveal on scroll.
 * - progress bar the moment an internal link is followed (no artificial delay)
 * - [data-reveal] elements fade/slide in once visible
 */
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------- navigation progress bar ---------- */
const bar = document.createElement('div');
bar.id = 'gl-navbar';
bar.setAttribute('aria-hidden', 'true');
document.body.appendChild(bar);

const isPlainNav = (e, a) => {
	if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return false;
	if (a.target && a.target !== '_self') return false;
	if (a.hasAttribute('download') || a.getAttribute('role') === 'button') return false;
	const href = a.getAttribute('href') || '';
	if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;
	if (a.origin !== location.origin) return false;
	if (a.pathname === location.pathname && a.search === location.search) return false; // same page / hash
	if (/[?&](add-to-cart|remove_item|wc-ajax)=/.test(a.search) || /\/wp-admin|\/wp-login/.test(a.pathname)) return false;
	return true;
};

document.addEventListener('click', (e) => {
	const a = e.target.closest('a[href]');
	if (isPlainNav(e, a)) {
		requestAnimationFrame(() => bar.classList.add('is-on'));
	}
});
document.addEventListener('submit', (e) => {
	if (!e.defaultPrevented && e.target.method !== 'dialog') bar.classList.add('is-on');
});
window.addEventListener('pageshow', () => bar.classList.remove('is-on'));

/* ---------- reveal on scroll ---------- */
const initReveal = () => {
	const els = document.querySelectorAll('[data-reveal]:not(.is-in)');
	if (reduce || !('IntersectionObserver' in window)) {
		els.forEach((el) => el.classList.add('is-in'));
		return;
	}
	const io = new IntersectionObserver(
		(entries) => {
			entries.forEach((en) => {
				if (en.isIntersecting) {
					en.target.classList.add('is-in');
					io.unobserve(en.target);
				}
			});
		},
		{ rootMargin: '0px 0px -6% 0px', threshold: 0.06 }
	);
	els.forEach((el) => io.observe(el));
};
initReveal();
window.glReveal = initReveal; // re-run after AJAX inserts content
