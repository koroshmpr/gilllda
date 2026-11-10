# Gilllda (gilllda.ir) — project guide

Persian (RTL, fa-IR) WooCommerce store selling **women's bags** (not shoes — old `pa_size` attribute is a leftover).
Repo root = `wp-content/`; versioned/deployed: the theme and `plugins/gilllda-ai/` (AI visibility plugin: llms.txt, AI-bot robots policy, markdown views, AI traffic reports — deployed to `…/plugins/gilllda-ai/`, path derived from `FTP_SERVER_DIR`). All other plugins are ignored. Live server path of the active theme: `wp-content/themes/Gilllda/theme/` (theme slug `Gilllda/theme`).

## Deploy (GitHub Actions → FTPS)
- Push to `main` touching `themes/Gilllda/**` → `.github/workflows/deploy.yml`: `npm ci && npm run production` → `php -l` all PHP → upload changed files of `themes/Gilllda/theme/` → smoke test `/`, `/shop/`, `/login/`.
- Secrets: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_SERVER_DIR` (must end `/themes/Gilllda/theme/`), optional `FTP_PROTOCOL`, `FTP_PORT`.
- **Owner preference: batch changes and deploy once** (Actions minutes are limited). Don't push after every small fix.
- No local WordPress is normally running (Local app is off) → verify with `php -l`, a scratch esbuild bundle, then check live after the batched deploy.

## Theme layout (`themes/Gilllda/`)
- `tailwind.css`, `tailwind/**` → `theme/style.css` (Tailwind v4). Custom CSS: `tailwind/custom/**` (motion/magazine styles in `components/motion.css`).
- `javascript/script.js` (+ `motion.js`, `cart.js`) → `theme/js/script.min.js` (esbuild). Alpine.js 3 global; body `x-data` in `theme/header.php` holds UI state (`cart`, `menuOpen`, `searchOpen`, …).
- `theme/functions.php` requires everything in `theme/inc/`. Asset versions = file mtime (`bluebox_asset_ver()`).
- Key modules:
  - `inc/landing/` — per-product landing pages `/l/{slug}/` (`landing-product.php`, standalone `css/landing.css` + `js/landing.js`). Toggle meta `gl_lp_on`=`yes`, settings meta `gl_lp_settings`, stats `_gl_lp_stats`. Admin: product box «لندینگ محصول», Products → «لندینگ‌ها».
  - `inc/blog/magazine.php` — «مجله گیلدا»: post meta `gl_tldr`, `gl_takeaways` (lines), `gl_series` + `gl_series_part` (series = posts sharing `gl_series`). ACF on posts: `faq_list` (question/answer), `related_product`. Templates: `single.php`, `index.php` (blog home, main query 9/page excluding 4 featured), `category.php`, `template-parts/blog/mag-*.php`.
  - `inc/otp-login-form.php` — login/register AJAX. OTP only registers when a Kavenegar key+template are configured (`gl_otp_enabled()`); default flow is email+password. `inc/account-rules.php` — guest checkout off, account required, mobile required.
  - `inc/app-shell.php` — `.js` class, speculation-rules prefetch (moderate), meta. `template-parts/global/preloader.php` — first-visit splash (ACF option `preloader`).
  - `inc/seo.php` — Rank Math JSON-LD cleanup, sitemap exclusions, default product/category titles.
  - `inc/telegram.php` — auto-post on first publish: posts → blog channel, products → shop channel (WP-Cron, once per item, meta `gl_tg_sent`). Settings → «تلگرام گیلدا» (bot token, channels default from ACF option `channels`, bulk send). Header «join channel» button: `gl_header_channel()`.
- WooCommerce overrides in `theme/woocommerce/**` — keep `@version` in sync with `plugins/woocommerce/templates/`.
- Use `is_admin_bar_showing()` (not `current_user_can('administrator')`) for admin-bar offsets.

## Live data / tools (`themes/Gilllda/tools/`, not deployed)
- `python3 -I tools/mcp.py <tool> '<json>'` — WebSensePro MCP (posts, pages, Woo, ACF, Rank Math, media, menus, redirects). Key in `~/.gilllda-mcp-key`. `list` / `schema <tool>` to discover. Underscore meta keys are refused.
- `node tools/shot.mjs <url> <prefix> mobile|desktop` — real-viewport screenshots (needs `npm i --no-save puppeteer-core@23.11.1` in `tools/`).
- Public Store API for product data: `https://gilllda.ir/wp-json/wc/store/v1/products`.
- Audit history & open items: `docs/STATUS.md`.

## Conventions
- Persian UI copy; Persian digits for display (`gl_fa_num()`); prices in Toman.
- Escape output (`esc_html/esc_attr/esc_url/wp_kses_post`), nonce + capability checks on every AJAX/REST write.
- Brand: primary `#102542`, secondary `#9d0617`, font Peyda.
