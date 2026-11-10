# Gilllda — status & open items

_Last updated: 2026-11-10 (v1.6.x / v1.7.0 batch)._ Keep this short; newest on top.

## Shipped 2026-11-11 (v1.8.0)
- My Account redesign + animated 5-step order tracker (`inc/account/`), Woo 11.2.2 template sync.
- Compare (REST `gilllda/v1/compare`, tray, new page) and instant search (REST `gilllda/v1/search`, Persian/Arabic normalisation, results page).
- Footer rebuilt, FAQ page + content rewritten (24 Q; free-shipping claim removed, coupon = 5%), header unified, design system in CLAUDE.md.
- Fixes: mobile overflow on series posts, drop cap removed, cart/card/checkout layout, mobile phone required at every layer.
- Verified live: 13 key pages 200, no PHP notices, 390px width on mobile, endpoints OK.

## Shipped in the 2026-11-10 batch
- Deploy pipeline (GitHub Actions → FTPS, PHP lint, smoke test); plugin `plugins/gilllda-ai/` deployed by the same workflow.
- Security: OTP login takeover fixed (strict compare, attempt limits, endpoints only when Kavenegar configured), password brute-force limit, rating spam & fake Product stars removed, comment-reply leak, search input bounds.
- Login = email + password; guest checkout locked off in code; mobile required at checkout.
- Product landings `/l/{slug}/` + stats per utm_source; Telegram auto-post (needs bot token).
- «مجله گیلدا»: new single/archive/category templates, series («پرونده»), shop-the-look, TL;DR, takeaways.
- App shell: View Transitions, speculation-rules prefetch, nav progress bar, first-visit splash.
- App-like cart: AJAX add-to-cart (`?wc-ajax=gl_add_to_cart`), drawer, cart/checkout redesign (`inc/product/ajax-cart.php`, `javascript/cart.js`, `css/shop-flow.css`).
- Speed: Swiper modules only, Woo CSS only on cart/checkout/account, 404 SVG externalised, responsive banners, cached "other products", lighter related posts.
- SEO: page titles/descriptions (10 pages via MCP), schema cleanup in code, default product/category titles in code, sitemap exclusions, About page rewritten, FAQ & rules corrected, `pa_size` deleted.
- Content: 14 new articles (IDs 17710–17730) in 5 series; old 11 posts got series meta + links.

## Owner actions (manual)
1. **LiteSpeed Cache**: install in WP (cPanel bulk tool errored). Settings: Cache Mobile ON, logged-in OFF, REST OFF, TTL 36000, exclude `/cart/ /checkout/ /my-account/ /login/ /l/`, no minify/combine/lazy. Don't cache `?wc-ajax=gl_*`.
2. **Telegram**: Settings → «تلگرام گیلدا» → bot token (bot admin in @gillldactionary and @gillldastore) → test → bulk-send old posts if wanted.
3. **Activate plugin «Gilllda AI Visibility»** after deploy.
4. **Rank Math** (API can't): product titles/descriptions (defaults now generated in code), category title template, 404 Monitor on, homepage schema type.
5. **Free shipping**: FAQ mentions free shipping over 3M Toman but no such shipping method exists → add method or change the FAQ.
6. «دنیز مینی» is described as a shoulder bag but only in «کیف دستی زنانه».
7. Regenerate the MCP API key (it was shared in chat) and update `~/.gilllda-mcp-key`.
8. Kavenegar: to enable SMS login later, define `KAVENEGAR_API_KEY` + `KAVENEGAR_TEMPLATE` in wp-config (OTP UI appears automatically).

## Verify after each deploy
- `/`, `/shop/`, a product, `/blog/`, a post, `/l/deniz-mini/`, `/about-us/`, `/404-test` → 200/404, no PHP notices.
- Add to cart on product page & card, drawer qty/remove/undo, guest checkout shows account fields.
- Live WooCommerce is **11.2.2** (local copy is 10.7): check WooCommerce → Status → template overrides.
- `.ftp-deploy-sync-state.json` under the theme returns 403.

## Known leftovers / ideas
- Deploy state file (`.htftp-deploy-state.json`, old `.ftp-deploy-sync-state.json`) is publicly readable: this host ignores the theme `.htaccess` FilesMatch and doesn't block `.ht*`. Only lists theme file names/hashes. Could delete the old one via FTP.
- Product image filenames are swapped (ونوس uses `parastoo-*`, پرستو uses `nikka-*`) — images are correct, only names; rename for image SEO if desired.
- `header-content.php` still uses `wp_is_mobile()` for the mobile header (page-cache needs "Cache Mobile" ON).
- Product reviews: 0 — ask buyers for reviews (enables rating rich results).
- Category pages: add hero banners/description styling; shop page has no intro copy.
