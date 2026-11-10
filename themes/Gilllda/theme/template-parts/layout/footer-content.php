<?php
/**
 * Site footer: community CTA · brand · shop · magazine · customer care · contact · bottom bar.
 * Columns collapse into accordions on mobile.
 *
 * @package bluebox
 */

global $woo_active;
$opt = function ($key) {
    return function_exists('get_field') ? get_field($key, 'option') : null;
};

$slogan   = $opt('footer_content');
$address  = $opt('address');
$email    = $opt('email');
$phone    = $opt('phone');
$socials  = (array) $opt('social');
$channels = (array) $opt('channels');
$shop_url = $woo_active ? wc_get_page_permalink('shop') : home_url('/');
$blog_url = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
$page_url = function ($slug) {
    $p = get_page_by_path($slug);
    return $p ? get_permalink($p) : '';
};
$fa_year = function_exists('shamsi_date') ? shamsi_date('Y', time()) : wp_date('Y');

// Shop column: top-level product categories
$cats = $woo_active ? get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0]) : [];
$cats = is_wp_error($cats) ? [] : array_filter($cats, function ($t) {
    return $t->slug !== 'uncategorized';
});

// Magazine column: most viewed posts (cached 6 h)
$popular = get_transient('gl_footer_popular');
if (!is_array($popular)) {
    $popular = get_posts(['post_type' => 'post', 'posts_per_page' => 4, 'fields' => 'ids', 'meta_key' => 'post_views', 'orderby' => 'meta_value_num', 'order' => 'DESC', 'no_found_rows' => true]);
    if (count($popular) < 4) {
        $popular = array_values(array_unique(array_merge($popular, get_posts(['post_type' => 'post', 'posts_per_page' => 4, 'fields' => 'ids', 'no_found_rows' => true]))));
    }
    $popular = array_slice($popular, 0, 4);
    set_transient('gl_footer_popular', $popular, 6 * HOUR_IN_SECONDS);
}

// Customer care links (only pages that exist)
$care = array_filter([
    'سوالات متداول'       => $page_url('faq'),
    'قوانین و شرایط بازگشت' => $page_url('refund_returns'),
    'حساب کاربری و سفارش‌ها' => $woo_active ? wc_get_page_permalink('myaccount') : '',
    'مقایسه محصولات'      => $page_url('compare'),
    'درباره گیلدا'         => $page_url('about-us'),
    'ارتباط با ما'          => $page_url('contact-us'),
]);

$col_title = 'flex w-full items-center justify-between gap-3 py-4 text-base font-black text-white lg:pointer-events-none lg:py-0 lg:pb-5';
$link      = 'group inline-flex items-center gap-2 py-1.5 text-sm text-white/65 transition hover:text-white';
$chev      = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="shrink-0 opacity-0 transition group-hover:-translate-x-0.5 group-hover:opacity-100" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>';
$toggle    = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="shrink-0 transition lg:hidden" :class="open ? \'rotate-180\' : \'\'" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
?>

<footer id="footer"
        x-intersect:enter="atBottom = true"
        x-intersect:leave="atBottom = false"
        class="<?= $woo_active && (is_singular() || is_shop() || is_product_category()) ? 'max-lg:pb-36' : 'max-lg:pb-28'; ?> relative mt-10 overflow-hidden bg-primary text-white">

    <div class="pointer-events-none absolute -top-40 end-0 size-[30rem] rounded-full bg-secondary/25 blur-3xl" aria-hidden="true"></div>

    <!-- Community CTA -->
    <div class="container relative px-4 pt-12 lg:px-0 lg:pt-16">
        <div class="flex flex-col items-start justify-between gap-6 rounded-3xl bg-white/[0.06] p-6 ring-1 ring-white/10 backdrop-blur lg:flex-row lg:items-center lg:p-8" data-reveal>
            <div>
                <p class="text-lg font-black lg:text-2xl">اولین نفری باش که کالکشن‌های جدید را می‌بیند</p>
                <p class="mt-1 text-sm text-white/65">تخفیف‌های ویژه، معرفی مدل‌های تازه و راهنمای استایل را در کانال‌های گیلدا دنبال کن.</p>
            </div>
            <div class="flex w-full flex-wrap gap-2 lg:w-auto">
                <?php foreach (['shop' => 'کانال فروشگاه', 'blog' => 'کانال مجله'] as $key => $label) :
                    $url = $channels[$key]['url'] ?? '';
                    if (!$url) {
                        continue;
                    } ?>
                    <a href="<?= esc_url($url); ?>" target="_blank" rel="noopener" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-full bg-white px-5 text-sm font-bold text-primary transition hover:-translate-y-0.5 hover:shadow-lg lg:flex-none">
                        <?php get_template_part('template-parts/svg/socials/telegram', null, ['size' => 18]); ?>
                        <?= esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
                <?php foreach ($socials as $s) :
                    if (($s['name'] ?? '') !== 'instagram' || empty($s['link']['url'])) {
                        continue;
                    } ?>
                    <a href="<?= esc_url($s['link']['url']); ?>" target="_blank" rel="noopener" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-full border border-white/25 px-5 text-sm font-bold text-white transition hover:bg-white/10 lg:flex-none">
                        <?php get_template_part('template-parts/svg/socials/instagram', null, ['size' => 18]); ?>
                        اینستاگرام
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Columns -->
    <div class="container relative grid gap-x-10 px-4 pt-10 lg:grid-cols-12 lg:px-0 lg:pt-14">
        <div class="flex flex-col items-center gap-4 pb-6 text-center lg:col-span-4 lg:items-start lg:pb-0 lg:text-start">
            <?php get_template_part('template-parts/global/logo', null, ['logoSize' => 'max-h-14 w-auto', 'logoLink' => 'footer_logo', 'width' => '98', 'height' => '59']); ?>
            <?php if ($slogan) : ?>
                <p class="text-xl font-black leading-9"><?= esc_html(wp_strip_all_tags($slogan)); ?></p>
            <?php endif; ?>
            <p class="max-w-sm text-sm leading-7 text-white/60">فروشگاه آنلاین کیف زنانه؛ کیف دستی، دوشی و کوله با ارسال به سراسر ایران و ۷ روز ضمانت تعویض.</p>
            <div class="mt-2 flex items-center gap-3 rounded-2xl bg-white p-2 [&_img]:h-20 [&_img]:w-auto"><?php get_template_part('template-parts/shop/enamad-list'); ?></div>
        </div>

        <?php if ($cats) : ?>
            <nav x-data="{ open: false }" class="border-t border-white/10 lg:col-span-2 lg:border-0" aria-label="فروشگاه">
                <button type="button" @click="open = !open" class="<?= esc_attr($col_title); ?>" :aria-expanded="open.toString()">فروشگاه<?= $toggle; // phpcs:ignore ?></button>
                <ul class="pb-4 lg:block lg:pb-0" :class="open ? 'block' : 'hidden'">
                    <li><a class="<?= esc_attr($link); ?>" href="<?= esc_url($shop_url); ?>"><?= $chev; // phpcs:ignore ?>همه محصولات</a></li>
                    <?php foreach ($cats as $c) : ?>
                        <li><a class="<?= esc_attr($link); ?>" href="<?= esc_url(get_term_link($c)); ?>"><?= $chev; // phpcs:ignore ?><?= esc_html($c->name); ?></a></li>
                    <?php endforeach; ?>
                    <li><a class="<?= esc_attr($link); ?>" href="<?= esc_url(add_query_arg('on_sale', 'true', $shop_url)); ?>"><?= $chev; // phpcs:ignore ?>تخفیف‌دارها</a></li>
                </ul>
            </nav>
        <?php endif; ?>

        <?php if ($popular) : ?>
            <nav x-data="{ open: false }" class="border-t border-white/10 lg:col-span-3 lg:border-0" aria-label="مجله گیلدا">
                <button type="button" @click="open = !open" class="<?= esc_attr($col_title); ?>" :aria-expanded="open.toString()">مجله گیلدا<?= $toggle; // phpcs:ignore ?></button>
                <ul class="pb-4 lg:block lg:pb-0" :class="open ? 'block' : 'hidden'">
                    <?php foreach ($popular as $pid) : ?>
                        <li><a class="<?= esc_attr($link); ?> max-w-full" href="<?= esc_url(get_permalink($pid)); ?>"><?= $chev; // phpcs:ignore ?><span class="line-clamp-1"><?= esc_html(get_the_title($pid)); ?></span></a></li>
                    <?php endforeach; ?>
                    <li><a class="<?= esc_attr($link); ?> font-bold text-white" href="<?= esc_url($blog_url); ?>"><?= $chev; // phpcs:ignore ?>همه مقالات</a></li>
                </ul>
            </nav>
        <?php endif; ?>

        <nav x-data="{ open: false }" class="border-t border-white/10 lg:col-span-3 lg:border-0" aria-label="خدمات مشتریان">
            <button type="button" @click="open = !open" class="<?= esc_attr($col_title); ?>" :aria-expanded="open.toString()">خدمات مشتریان<?= $toggle; // phpcs:ignore ?></button>
            <div class="pb-4 lg:block lg:pb-0" :class="open ? 'block' : 'hidden'">
                <ul>
                    <?php foreach ($care as $label => $url) : ?>
                        <li><a class="<?= esc_attr($link); ?>" href="<?= esc_url($url); ?>"><?= $chev; // phpcs:ignore ?><?= esc_html($label); ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <ul class="mt-4 grid gap-2 text-sm text-white/75">
                    <?php if ($phone) : ?>
                        <li><a href="tel:<?= esc_attr(preg_replace('/[^\d+]/', '', (string) $phone)); ?>" class="inline-flex items-center gap-2 hover:text-white">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>
                            <span dir="ltr"><?= esc_html($phone); ?></span></a></li>
                    <?php endif; ?>
                    <?php if ($email) : ?>
                        <li><a href="mailto:<?= esc_attr(sanitize_email($email)); ?>" class="inline-flex items-center gap-2 hover:text-white">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                            <span dir="ltr"><?= esc_html($email); ?></span></a></li>
                    <?php endif; ?>
                    <?php if ($address) : ?>
                        <li class="flex items-start gap-2 leading-6">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-1 shrink-0" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                            <address class="not-italic"><?= esc_html(wp_strip_all_tags($address)); ?></address></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </div>

    <!-- Bottom bar -->
    <div class="container relative mt-10 flex flex-col-reverse items-center justify-between gap-4 border-t border-white/10 px-4 py-6 text-xs text-white/55 lg:flex-row lg:px-0">
        <p>© <?= esc_html($fa_year); ?> <?= esc_html(get_bloginfo('name')); ?> · تمامی حقوق محفوظ است · طراحی و توسعه <a class="text-white/80 underline-offset-4 hover:text-white hover:underline" target="_blank" rel="noopener" href="https://kmpr.ir/">وبکو</a></p>
        <?php if ($socials) : ?>
            <nav aria-label="شبکه‌های اجتماعی" class="flex items-center gap-2">
                <?php foreach ($socials as $s) :
                    if (empty($s['name']) || empty($s['link']['url'])) {
                        continue;
                    } ?>
                    <a href="<?= esc_url($s['link']['url']); ?>" target="_blank" rel="noopener" aria-label="<?= esc_attr($s['name']); ?>" title="<?= esc_attr($s['name']); ?>"
                       class="inline-flex size-11 items-center justify-center rounded-full border border-white/15 text-white transition hover:-translate-y-0.5 hover:bg-white/10">
                        <?php get_template_part('template-parts/svg/socials/' . sanitize_file_name($s['name']), null, ['size' => 18]); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
</footer>
