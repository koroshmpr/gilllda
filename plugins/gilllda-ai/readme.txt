=== Gilllda AI Visibility ===
Contributors: gilllda
Tags: llms.txt, ai, markdown, robots.txt, rank math, woocommerce
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

دیده‌شدن فروشگاه گیلدا در ChatGPT، Claude، Perplexity، Gemini و سایر دستیارهای هوش مصنوعی.

== Description ==

این افزونه همه چیزهایی را که مدل‌های زبانی برای شناخت فروشگاه لازم دارند، خودکار و هماهنگ با Rank Math و ووکامرس می‌سازد:

* ‎/llms.txt‎ — معرفی فروشگاه، قوانین ارسال و مرجوعی (از مناطق ارسال ووکامرس و برگه شرایط بازگشت)، دسته‌ها، همه محصولات با قیمت و رنگ، مقاله‌های مجله به تفکیک پرونده و دسته، صفحه‌های کلیدی.
* ‎/llms-full.txt‎ — متن کامل محصولات (تنوع‌ها، قیمت، موجودی)، مقاله‌ها (خلاصه، نکات کلیدی، سؤالات متداول)، سؤالات متداول سایت و قوانین؛ با سقف حجم قابل تنظیم.
* نسخه Markdown هر صفحه: انتهای آدرس ‎.md‎ بگذارید (یا ‎?format=md‎).
* سیاست ربات‌های هوش مصنوعی در robots.txt (سازگار با ویرایشگر robots رنک‌مث، بدون تکرار).
* اسکیمای تکمیلی فقط در جاهایی که Rank Math و قالب ندارند (FAQPage، OnlineStore/ContactPoint/sameAs، speakable).
* گزارش: بازدید ربات‌های AI، پربازدیدترین آدرس‌ها، دریافت llms.txt، ورودی بازدیدکننده از ChatGPT/Perplexity/Gemini/Copilot/Claude، و ممیزی آمادگی محتوا با امتیاز و لینک ویرایش.

همه کارهای سنگین در پس‌زمینه (WP-Cron) و با تأخیر یک‌دقیقه‌ای بعد از آخرین تغییر انجام می‌شود؛ بازدید عادی صفحه‌ها هزینه‌ای ندارد.

== Installation ==

1. پوشه `gilllda-ai` را در `wp-content/plugins/` قرار دهید و افزونه را فعال کنید.
2. منوی «هوش مصنوعی گیلدا» › تنظیمات: متن معرفی، تلفن/ایمیل و سیاست ربات‌ها را بررسی کنید.
3. اگر ماژول «LLMS Txt» در Rank Math روشن است، خاموشش کنید (افزونه زودتر پاسخ می‌دهد، ولی دو منبع باعث سردرگمی می‌شود).

== Technical notes (English) ==

* Endpoints: `/llms.txt`, `/llms-full.txt` (rewrite rules + early `parse_request` handling, so they win over Rank Math's llms.txt module), `{permalink}.md`, `/index.md`, `?format=md`, and `Accept: text/markdown` content negotiation (sends `Vary: Accept`).
* Responses: `Content-Type: text/plain|text/markdown; charset=utf-8`, `Cache-Control: public, max-age=3600`, strong `ETag` + `Last-Modified`, 304 on `If-None-Match`/`If-Modified-Since`, `X-Robots-Tag: noindex`; `.md` responses carry `Link: <html-url>; rel="canonical"`.
* HTML pages get `<link rel="alternate" type="text/markdown">` and a `Link: <…md>; rel="alternate"; type="text/markdown"` header. Landing pages `/l/{slug}/` are not listed; their Markdown is the product's.
* Storage: `wp-content/uploads/gilllda-ai/` (denied by .htaccess, always served through PHP). Per-URL Markdown is cached on disk until the next content change.
* Regeneration: one debounced single cron event (`gai_rebuild`, 60 s) fired by post status changes, watched meta (Rank Math, gl_* magazine fields, prices, stock), WooCommerce stock/sale/shipping-zone hooks, term edits, ACF options saves. If WP-Cron is dead, the first llms request 10 min after the due time rebuilds inline. Locking prevents concurrent builds.
* Visibility: published, not password-protected, not Rank Math noindex (post meta, then post-type default), not hidden from catalog, not cart/checkout/my-account/login/compare or manual exclusions.
* robots.txt: appended on `robots_txt` (priority 100, after Rank Math's editor at 10) and `rank_math/frontend/robots_txt`, guarded by `# BEGIN/END Gilllda AI Visibility`. Allowed bots get the `*` group's rules mirrored plus private store paths. Adds `Sitemap:` only if missing.
* Schema: `rank_math/json_ld` at priority 120 (after theme `inc/seo.php` at 99). FAQPage is skipped when Rank Math already has one or (auto mode) the theme prints its own.
* Logging: table `{prefix}gai_hits` (day, kind bot|ref|file, bot, path, hits) with `INSERT … ON DUPLICATE KEY UPDATE`; only requests from AI user agents / AI referrers write. No IPs or user agents stored. Pruned daily (default 90 days). 4xx/5xx paths are collapsed into one bucket.
* Filters: `gai_is_public` (bool, WP_Post).
* Uninstall removes options, transients, cron events, the table and the uploads folder.
