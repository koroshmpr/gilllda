<?php
/**
 * 404 — not found. Illustration is an external SVG (was 127 KB inline on every 404 hit).
 *
 * @package bluebox
 */

get_header();
$shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$blog = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
$cats = taxonomy_exists('product_cat') ? get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0]) : [];
?>
    <section class="container px-4 pb-16 pt-10 lg:px-0 lg:pt-16">
        <div class="mx-auto flex max-w-2xl flex-col items-center text-center">
            <img src="<?= esc_url(get_template_directory_uri() . '/img/404.svg'); ?>" width="600" height="400" alt="" class="h-auto w-full max-w-md mag-in" decoding="async">
            <h1 class="mt-4 text-3xl font-black text-gray-900 lg:text-4xl mag-in" style="--d:1">صفحه‌ای که دنبالش بودید پیدا نشد</h1>
            <p class="mt-3 leading-8 text-gray-600 mag-in" style="--d:2">ممکن است آدرس تغییر کرده باشد. از جستجو یا لینک‌های زیر کمک بگیرید.</p>
            <form role="search" method="get" action="<?= esc_url(home_url('/')); ?>" class="mt-6 flex w-full gap-2 mag-in" style="--d:3">
                <label for="nf-s" class="sr-only">جستجو</label>
                <input id="nf-s" type="search" name="s" placeholder="جستجوی کیف، رنگ یا مقاله…" class="min-h-12 flex-1 rounded-2xl border border-gray-200 px-4 focus:border-primary focus:outline-none">
                <input type="hidden" name="post_type" value="product">
                <button class="min-h-12 rounded-2xl bg-primary px-6 font-bold text-white">جستجو</button>
            </form>
            <nav class="mt-8 flex flex-wrap justify-center gap-2 mag-in" style="--d:4" aria-label="پیشنهادها">
                <a class="inline-flex min-h-10 items-center rounded-full bg-secondary px-5 text-sm font-bold text-white" href="<?= esc_url($shop); ?>">فروشگاه</a>
                <?php if (!is_wp_error($cats)) : foreach ($cats as $c) : ?>
                    <a class="inline-flex min-h-10 items-center rounded-full border border-gray-200 px-5 text-sm font-bold text-gray-700 hover:border-primary hover:text-primary" href="<?= esc_url(get_term_link($c)); ?>"><?= esc_html($c->name); ?></a>
                <?php endforeach; endif; ?>
                <a class="inline-flex min-h-10 items-center rounded-full border border-gray-200 px-5 text-sm font-bold text-gray-700 hover:border-primary hover:text-primary" href="<?= esc_url($blog); ?>">مجله گیلدا</a>
            </nav>
        </div>
    </section>
<?php
get_footer();
