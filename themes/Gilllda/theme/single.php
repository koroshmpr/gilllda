<?php
/**
 * Single post — «مجله گیلدا».
 * Editorial hero · 30-second summary · series timeline · live TOC · shop-the-look products
 * inside the article · key takeaways · FAQ · next part · rating/share · author · related.
 *
 * @package bluebox
 */

get_header();

while (have_posts()) :
    the_post();
    $post_id   = get_the_ID();
    $content   = apply_filters('the_content', get_the_content());
    $headings  = function_exists('get_headings') ? get_headings($content, [2, 3]) : [];
    $minutes   = gl_reading_minutes($post_id);
    $views     = (int) get_post_meta($post_id, 'post_views', true);
    $tldr      = trim((string) get_post_meta($post_id, 'gl_tldr', true));
    $takeaways = gl_meta_lines($post_id, 'gl_takeaways');
    $series    = gl_series_info($post_id);
    $products  = function_exists('get_field') ? (array) get_field('related_product') : [];
    $products  = array_values(array_filter(array_map(function ($p) {
        $prod = function_exists('wc_get_product') ? wc_get_product(is_object($p) ? $p->ID : $p) : null;
        return $prod && $prod->is_visible() ? $prod : null;
    }, $products)));
    $cats      = get_the_category();
    $cat       = $cats[0] ?? null;
    $published = (int) get_the_time('U');
    $modified  = (int) get_the_modified_time('U');
    $blog_url  = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
    $author_id = (int) get_the_author_meta('ID');

    // Shop-the-look card goes right before the 3rd <h2> (or after the content if shorter).
    $shop_html = '';
    if ($products) {
        ob_start(); ?>
        <aside class="not-prose my-10 rounded-3xl border border-secondary/15 bg-gradient-to-br from-secondary/[0.06] via-white to-primary/[0.04] p-5 lg:p-6" aria-label="محصولات این مقاله" data-reveal>
            <p class="mb-4 flex items-center gap-2 text-sm font-bold text-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 7h12l-1 13H7z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
                ست کن با این مقاله
            </p>
            <div class="grid gap-3 <?= count($products) > 1 ? 'sm:grid-cols-2' : ''; ?>">
                <?php foreach (array_slice($products, 0, 2) as $prod) :
                    $link = function_exists('gl_lp_enabled') && gl_lp_enabled($prod->get_id()) ? gl_lp_url($prod->get_id(), ['utm_source' => 'blog', 'utm_medium' => 'article']) : $prod->get_permalink(); ?>
                    <a href="<?= esc_url($link); ?>" class="group flex items-center gap-4 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-lg">
                        <span class="block size-20 shrink-0 overflow-hidden rounded-xl bg-gray-50">
                            <?= $prod->get_image('thumbnail', ['class' => 'size-full object-cover transition duration-500 group-hover:scale-110', 'loading' => 'lazy', 'alt' => esc_attr($prod->get_name())]); ?>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-bold text-gray-900"><?= esc_html($prod->get_name()); ?></span>
                            <span class="mt-1 block text-sm text-gray-500 [&_del]:text-xs [&_del]:opacity-60 [&_ins]:font-bold [&_ins]:text-gray-900 [&_ins]:no-underline"><?= wp_kses_post($prod->get_price_html()); ?></span>
                        </span>
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-secondary text-white transition group-hover:scale-110" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 12H5m6-6-6 6 6 6"/></svg>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>
        <?php
        $shop_html = ob_get_clean();
        $pos = 0;
        for ($i = 0; $i < 3; $i++) {
            $pos = stripos($content, '<h2', $pos + ($i ? 1 : 0));
            if ($pos === false) {
                break;
            }
        }
        if ($pos !== false && $pos > 0) {
            $content   = substr($content, 0, $pos) . $shop_html . substr($content, $pos);
            $shop_html = '';
        }
    }
    ?>

<div x-data="{ active: null, sheet: null, toc: false, pct: 0 }"
     x-init="const b = $refs.body; const f = () => { const r = b.getBoundingClientRect(); pct = Math.min(100, Math.max(0, Math.round((innerHeight - r.top) / (r.height + innerHeight * .2) * 100))); }; addEventListener('scroll', () => requestAnimationFrame(f), { passive: true }); f();"
     @keydown.escape.window="sheet = null"
     class="relative pb-24 lg:pb-0">

    <!-- reading progress -->
    <div class="pointer-events-none fixed inset-x-0 top-0 z-[60] h-1 <?= is_admin_bar_showing() ? 'lg:top-8' : ''; ?>" aria-hidden="true">
        <div class="h-full origin-right bg-gradient-to-l from-secondary to-primary transition-transform duration-150" :style="'transform:scaleX(' + pct / 100 + ')'" style="transform:scaleX(0)"></div>
    </div>

    <!-- ============ HERO ============ -->
    <header class="relative overflow-hidden">
        <div class="pointer-events-none absolute -top-40 start-1/3 size-[32rem] rounded-full bg-secondary/10 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-40 -start-20 size-[26rem] rounded-full bg-primary/10 blur-3xl" aria-hidden="true"></div>

        <div class="container relative px-4 pt-6 lg:px-0 lg:pt-10">
            <nav aria-label="مسیر صفحه" class="mb-6">
                <ol class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                    <li><a class="hover:text-secondary" href="<?= esc_url(home_url('/')); ?>">خانه</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a class="hover:text-secondary" href="<?= esc_url($blog_url); ?>">مجله گیلدا</a></li>
                    <?php if ($cat) : ?>
                        <li aria-hidden="true">/</li>
                        <li><a class="hover:text-secondary" href="<?= esc_url(get_category_link($cat)); ?>"><?= esc_html($cat->name); ?></a></li>
                    <?php endif; ?>
                </ol>
            </nav>

            <div class="grid items-end gap-8 lg:grid-cols-12">
                <div class="lg:col-span-7">
                    <div class="mb-5 flex flex-wrap items-center gap-2 mag-in" style="--d:0">
                        <?php if ($cat) : ?>
                            <a href="<?= esc_url(get_category_link($cat)); ?>" class="inline-flex items-center gap-2 rounded-full bg-secondary/10 px-3.5 py-1.5 text-xs font-bold text-secondary transition hover:bg-secondary hover:text-white">
                                <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span><?= esc_html($cat->name); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($series && $series['total'] > 1) : ?>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary px-3.5 py-1.5 text-xs font-bold text-white">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                                پرونده «<?= esc_html($series['name']); ?>» · قسمت <?= esc_html(gl_fa_num($series['number'])); ?> از <?= esc_html(gl_fa_num($series['total'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h1 class="text-[1.75rem] font-black leading-[1.45] text-gray-900 sm:text-4xl lg:text-[2.75rem] mag-in" style="--d:1"><?php the_title(); ?></h1>

                    <?php if (has_excerpt()) : ?>
                        <p class="mt-5 max-w-2xl text-base leading-8 text-gray-600 lg:text-lg mag-in" style="--d:2"><?= esc_html(get_the_excerpt()); ?></p>
                    <?php endif; ?>

                    <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-gray-500 mag-in" style="--d:3">
                        <a href="<?= esc_url(get_author_posts_url($author_id)); ?>" class="inline-flex items-center gap-3">
                            <?= get_avatar($author_id, 40, '', esc_attr(get_the_author()), ['class' => 'size-10 rounded-full object-cover ring-2 ring-white shadow']); ?>
                            <span class="flex flex-col leading-tight">
                                <span class="font-bold text-gray-900"><?php the_author(); ?></span>
                                <time class="text-xs" datetime="<?= esc_attr(get_the_date('c')); ?>"><?= esc_html(function_exists('shamsi_date') ? shamsi_date('j F Y', $published) : get_the_date()); ?></time>
                            </span>
                        </a>
                        <span class="inline-flex items-center gap-1.5">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-secondary" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                            <?= esc_html(gl_fa_num($minutes)); ?> دقیقه مطالعه
                        </span>
                        <?php if ($views > 10) : ?>
                            <span class="inline-flex items-center gap-1.5">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-secondary" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <?= esc_html(gl_fa_num(number_format($views))); ?> بازدید
                            </span>
                        <?php endif; ?>
                        <?php if ($modified - $published > DAY_IN_SECONDS && function_exists('shamsi_date')) : ?>
                            <span class="inline-flex items-center gap-1.5 text-emerald-700">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                                به‌روزرسانی: <time datetime="<?= esc_attr(get_the_modified_date('c')); ?>"><?= esc_html(shamsi_date('j F Y', $modified)); ?></time>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (has_post_thumbnail()) : ?>
                    <figure class="mag-cover lg:col-span-5">
                        <?php the_post_thumbnail('large', ['class' => 'aspect-[4/3] w-full rounded-[2rem] object-cover shadow-2xl shadow-primary/10', 'fetchpriority' => 'high', 'loading' => 'eager', 'decoding' => 'async', 'sizes' => '(min-width: 1024px) 40vw, 100vw', 'alt' => esc_attr(get_the_title())]); ?>
                    </figure>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- ============ BODY ============ -->
    <div class="container px-4 py-10 lg:px-0 lg:py-14">
        <div class="grid items-start gap-10 lg:grid-cols-12 xl:gap-14">

            <article id="post-<?php the_ID(); ?>" <?php post_class('min-w-0 lg:col-span-8'); ?>>

                <?php if ($tldr) : ?>
                    <aside class="relative mb-10 overflow-hidden rounded-3xl bg-primary p-6 text-white sm:p-7" aria-label="خلاصه سریع" data-reveal>
                        <div class="pointer-events-none absolute -end-10 -top-10 size-40 rounded-full bg-secondary/40 blur-2xl" aria-hidden="true"></div>
                        <p class="relative mb-2 inline-flex items-center gap-2 text-xs font-bold text-white/70">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8Z"/></svg>
                            خلاصه در ۳۰ ثانیه
                        </p>
                        <p class="relative leading-8 text-white/95"><?= esc_html($tldr); ?></p>
                    </aside>
                <?php endif; ?>

                <?php if ($headings) : ?>
                    <button type="button" @click="sheet = 'toc'" class="mb-8 flex min-h-12 w-full items-center justify-between rounded-2xl border border-gray-200 bg-gray-50 px-5 py-3 text-sm font-bold text-gray-800 lg:hidden">
                        <span class="inline-flex items-center gap-2"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-secondary" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>فهرست مطالب</span>
                        <span class="text-xs font-normal text-gray-500"><?= esc_html(gl_fa_num(count($headings))); ?> بخش</span>
                    </button>
                <?php endif; ?>

                <div x-ref="body" class="mag-prose prose prose-neutral max-w-none prose-lg prose-headings:font-black prose-headings:text-gray-900 prose-h2:mt-12 prose-h2:scroll-mt-28 prose-h3:scroll-mt-28 prose-p:leading-9 prose-p:text-gray-700 prose-a:font-bold prose-a:text-secondary prose-a:no-underline hover:prose-a:underline prose-img:rounded-2xl prose-li:my-1 prose-table:text-sm">
                    <?= $content; // phpcs:ignore -- filtered post content ?>
                    <?= $shop_html; // phpcs:ignore -- built above ?>
                </div>

                <?php wp_link_pages(['before' => '<nav class="mt-10 flex flex-wrap gap-2" aria-label="صفحات مقاله">', 'after' => '</nav>']); ?>

                <?php if ($takeaways) : ?>
                    <section class="mt-14 rounded-3xl border border-emerald-200 bg-emerald-50/60 p-6 sm:p-8" aria-labelledby="takeaways-title" data-reveal>
                        <h2 id="takeaways-title" class="mb-5 flex items-center gap-3 text-xl font-black text-gray-900">
                            <span class="inline-flex size-10 items-center justify-center rounded-2xl bg-emerald-500 text-white" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2Z"/></svg>
                            </span>
                            نکات کلیدی این مقاله
                        </h2>
                        <ul class="grid gap-3">
                            <?php foreach ($takeaways as $t) : ?>
                                <li class="flex items-start gap-3 leading-8 text-gray-700">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="mt-1.5 shrink-0 text-emerald-500" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.58 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/></svg>
                                    <span><?= esc_html($t); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <?php get_template_part('template-parts/global/faq-list', null, ['class' => 'mt-14', 'title' => 'پرسش‌های متداول']); ?>

                <?php if ($series && ($series['next'] || $series['prev'])) : ?>
                    <nav class="mt-14 grid gap-4" aria-label="قسمت‌های پرونده" data-reveal>
                        <?php if ($series['next']) : $n = $series['next']; ?>
                            <a href="<?= esc_url(get_permalink($n)); ?>" rel="next" class="group relative flex flex-col gap-5 overflow-hidden rounded-3xl bg-gradient-to-br from-primary to-[#1d3a63] p-6 text-white sm:flex-row sm:items-center sm:p-8">
                                <div class="pointer-events-none absolute -bottom-16 -start-10 size-56 rounded-full bg-secondary/40 blur-3xl transition-transform duration-700 group-hover:scale-125" aria-hidden="true"></div>
                                <?php if (has_post_thumbnail($n)) : ?>
                                    <span class="relative block aspect-video w-full shrink-0 overflow-hidden rounded-2xl sm:w-48"><?= get_the_post_thumbnail($n, 'medium_large', ['class' => 'size-full object-cover transition duration-700 group-hover:scale-110', 'loading' => 'lazy', 'alt' => '']); ?></span>
                                <?php endif; ?>
                                <span class="relative min-w-0 flex-1">
                                    <span class="mb-2 block text-xs font-bold text-white/60">قسمت بعدی · <?= esc_html(gl_fa_num($series['number'] + 1)); ?> از <?= esc_html(gl_fa_num($series['total'])); ?></span>
                                    <span class="block text-xl font-black leading-snug sm:text-2xl"><?= esc_html(get_the_title($n)); ?></span>
                                </span>
                                <span class="relative inline-flex min-h-11 items-center gap-2 self-start whitespace-nowrap rounded-full bg-white px-6 text-sm font-bold text-primary transition group-hover:gap-3 sm:self-center">
                                    ادامه پرونده
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6"/></svg>
                                </span>
                            </a>
                        <?php endif; ?>
                        <?php if ($series['prev']) : $p = $series['prev']; ?>
                            <a href="<?= esc_url(get_permalink($p)); ?>" rel="prev" class="group flex items-center gap-4 rounded-2xl border border-gray-200 p-4 transition hover:border-secondary/40 hover:bg-gray-50">
                                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-500 group-hover:text-secondary" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-xs text-gray-500">قسمت قبلی · <?= esc_html(gl_fa_num($series['number'] - 1)); ?></span>
                                    <span class="block truncate font-bold text-gray-800"><?= esc_html(get_the_title($p)); ?></span>
                                </span>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

                <div class="mt-14 grid gap-5 md:grid-cols-2">
                    <?php get_template_part('template-parts/blog/single/rating', null, ['class' => 'flex flex-col rounded-3xl border border-amber-200 bg-amber-50/50 p-5']); ?>
                    <div class="flex flex-col justify-center gap-3 rounded-3xl border border-gray-200 p-5">
                        <p class="font-bold text-gray-900">این مقاله را با دوستانت به اشتراک بگذار</p>
                        <?php get_template_part('template-parts/blog/single/share-button', null, ['linkClass' => 'border border-gray-200 text-gray-600 bg-white hover:bg-gray-50 p-2 rounded-xl transition-all']); ?>
                    </div>
                </div>

                <div class="mt-10"><?php get_template_part('template-parts/blog/single/author-box'); ?></div>

                <?php $tags = get_the_tags();
                if ($tags) : ?>
                    <ul class="mt-8 flex flex-wrap gap-2" aria-label="برچسب‌ها">
                        <?php foreach ($tags as $tag) : ?>
                            <li><a href="<?= esc_url(get_tag_link($tag)); ?>" class="inline-flex min-h-9 items-center rounded-full border border-gray-200 px-3.5 text-xs font-bold text-gray-600 transition hover:border-secondary/40 hover:text-secondary">#<?= esc_html($tag->name); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="mt-14"><?php if (comments_open() || get_comments_number()) {
                    comments_template();
                } ?></div>
            </article>

            <!-- ============ SIDEBAR ============ -->
            <aside class="hidden self-start lg:sticky lg:col-span-4 lg:block <?= is_admin_bar_showing() ? 'lg:top-28' : 'lg:top-20'; ?>" aria-label="فهرست و پرونده">
                <div class="flex max-h-[calc(100vh-7rem)] flex-col gap-5">
                    <div class="rounded-3xl border border-gray-200 bg-white px-5 py-4">
                        <div class="mb-2 flex items-center justify-between text-[13px]">
                            <span class="font-bold text-gray-600">پیشرفت مطالعه</span>
                            <span class="text-base font-black text-secondary" x-text="pct.toLocaleString('fa-IR') + '٪'">۰٪</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-gradient-to-l from-secondary to-primary" :style="'width:' + pct + '%'" style="width:0"></div></div>
                    </div>

                    <?php if ($series && $series['total'] > 1) : ?>
                        <?php get_template_part('template-parts/blog/single/series-nav', null, ['series' => $series, 'current' => $post_id]); ?>
                    <?php endif; ?>

                    <?php if ($headings) : ?>
                        <div class="flex min-h-0 shrink flex-col rounded-3xl border border-gray-200 bg-white p-5">
                            <p class="mb-3 flex items-center gap-2 text-sm font-black text-gray-900">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-secondary" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                                در این مقاله
                            </p>
                            <div class="min-h-0 overflow-y-auto overscroll-contain pe-1"><?= get_toc($content, [2]); // phpcs:ignore ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($products) : ?>
                        <div class="rounded-3xl border border-gray-200 bg-white p-4">
                            <p class="mb-3 text-sm font-black text-gray-900">محصولات این مقاله</p>
                            <div class="grid gap-2">
                                <?php foreach (array_slice($products, 0, 3) as $prod) : ?>
                                    <a href="<?= esc_url($prod->get_permalink()); ?>" class="group flex items-center gap-3 rounded-2xl p-2 transition hover:bg-gray-50">
                                        <span class="block size-14 shrink-0 overflow-hidden rounded-xl bg-gray-50"><?= $prod->get_image('thumbnail', ['class' => 'size-full object-cover', 'loading' => 'lazy', 'alt' => esc_attr($prod->get_name())]); ?></span>
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-bold text-gray-800 group-hover:text-secondary"><?= esc_html($prod->get_name()); ?></span>
                                            <span class="block text-xs text-gray-500 [&_del]:hidden [&_ins]:no-underline"><?= wp_kses_post($prod->get_price_html()); ?></span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>

    <?php get_template_part('template-parts/blog/single/related-posts'); ?>

    <!-- ============ MOBILE: bottom bar + sheet ============ -->
    <div class="fixed inset-x-0 bottom-20 z-40 px-3 lg:hidden">
        <div class="mx-auto flex max-w-md items-center gap-2 rounded-2xl border border-gray-200 bg-white/95 p-2 shadow-xl backdrop-blur">
            <span class="relative size-10 shrink-0">
                <svg width="40" height="40" viewBox="0 0 36 36" class="-rotate-90" aria-hidden="true">
                    <circle cx="18" cy="18" r="15" fill="none" stroke="currentColor" stroke-width="3" class="text-gray-100"/>
                    <circle cx="18" cy="18" r="15" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="text-secondary" stroke-dasharray="94.2" :stroke-dashoffset="94.2 - 94.2 * pct / 100" stroke-dashoffset="94.2"/>
                </svg>
                <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-gray-700" x-text="pct.toLocaleString('fa-IR')">۰</span>
            </span>
            <?php if ($series && $series['total'] > 1) : ?>
                <button type="button" @click="sheet = 'series'" class="min-h-11 min-w-0 flex-1 truncate rounded-xl px-2 text-start text-xs font-bold text-gray-700">
                    قسمت <?= esc_html(gl_fa_num($series['number'])); ?> از <?= esc_html(gl_fa_num($series['total'])); ?> · <?= esc_html($series['name']); ?>
                </button>
            <?php else : ?>
                <span class="min-w-0 flex-1 truncate text-xs font-bold text-gray-700"><?= esc_html(gl_fa_num($minutes)); ?> دقیقه مطالعه</span>
            <?php endif; ?>
            <?php if ($headings) : ?>
                <button type="button" @click="sheet = 'toc'" class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-700" aria-label="فهرست مطالب">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                </button>
            <?php endif; ?>
            <?php if ($series && $series['next']) : ?>
                <a href="<?= esc_url(get_permalink($series['next'])); ?>" class="inline-flex min-h-11 shrink-0 items-center rounded-xl bg-secondary px-4 text-xs font-bold text-white">بعدی</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="lg:hidden" x-show="sheet" x-cloak>
        <div class="fixed inset-0 z-[70] bg-black/40 backdrop-blur-sm" @click="sheet = null" x-show="sheet" x-transition.opacity></div>
        <div class="fixed inset-x-0 bottom-0 z-[71] flex max-h-[80vh] flex-col rounded-t-3xl bg-white shadow-2xl"
             role="dialog" aria-modal="true" :aria-label="sheet === 'toc' ? 'فهرست مطالب' : 'قسمت‌های پرونده'"
             x-show="sheet" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
            <div class="flex items-center justify-between border-b border-gray-100 p-4">
                <span class="mx-auto h-1.5 w-12 rounded-full bg-gray-200" aria-hidden="true"></span>
                <button type="button" @click="sheet = null" class="absolute end-3 top-2 inline-flex size-11 items-center justify-center rounded-full text-gray-500" aria-label="بستن">✕</button>
            </div>
            <div x-show="sheet === 'toc'" class="min-h-0 overflow-y-auto overscroll-contain p-5" @click="if ($event.target.closest('button')) sheet = null">
                <?= $headings ? get_toc($content, [2]) : ''; // phpcs:ignore ?>
            </div>
            <?php if ($series && $series['total'] > 1) : ?>
                <div x-show="sheet === 'series'" class="min-h-0 overflow-y-auto overscroll-contain p-4">
                    <?php get_template_part('template-parts/blog/single/series-nav', null, ['series' => $series, 'current' => $post_id, 'flat' => true]); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endwhile;
get_footer();
