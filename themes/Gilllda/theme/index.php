<?php
/**
 * Blog home — «مجله گیلدا» (also the generic fallback template).
 *
 * @package bluebox
 */

$id           = is_home() ? (int) get_option('page_for_posts') : get_queried_object_id();
$current_page = max(1, (int) get_query_var('paged'));
$faq          = function_exists('get_field') ? get_field('faq_list', $id) : null;
$page_content = $id ? get_post_field('post_content', $id) : '';
$first        = $current_page === 1 && empty($_GET) && is_home();

get_header();

if (is_home()) {
    $title = single_post_title('', false) ?: 'مجله گیلدا';
} elseif (is_search()) {
    $title = 'نتایج جستجو برای: ' . get_search_query();
} else {
    $title = get_the_title($id);
}
get_template_part('template-parts/blog/mag-header', null, [
    'title' => $title,
    'desc'  => is_home() ? 'راهنمای خرید، نگهداری و استایل کیف زنانه؛ پرونده‌های چندقسمتی که قدم‌به‌قدم انتخاب بهتر را یادت می‌دهند.' : '',
]);

$exclude = [];

/* ---------- featured ---------- */
if ($first) :
    $featured = new WP_Query(['post_type' => 'post', 'post__in' => gl_featured_post_ids(), 'orderby' => 'post__in', 'posts_per_page' => 4, 'ignore_sticky_posts' => true, 'no_found_rows' => true]);
    if ($featured->have_posts()) : ?>
        <section class="container grid gap-8 px-4 py-6 lg:grid-cols-12 lg:px-0" aria-label="مقالات تازه">
            <?php while ($featured->have_posts()) : $featured->the_post();
                $exclude[] = get_the_ID();
                if ($featured->current_post === 0) : ?>
                    <div class="lg:col-span-7"><?php get_template_part('template-parts/blog/mag-card', null, ['size' => 'lg', 'eager' => true]); ?></div>
                    <div class="flex flex-col gap-5 lg:col-span-5">
                <?php else : ?>
                    <a href="<?php the_permalink(); ?>" class="group flex items-center gap-4" data-reveal style="--d:<?= (int) $featured->current_post; ?>">
                        <span class="block aspect-square w-24 shrink-0 overflow-hidden rounded-2xl bg-gray-100 sm:w-28">
                            <?php the_post_thumbnail('medium', ['class' => 'size-full object-cover transition duration-700 group-hover:scale-110', 'loading' => 'lazy', 'alt' => '']); ?>
                        </span>
                        <span class="min-w-0">
                            <?php $c = get_the_category()[0] ?? null;
                            if ($c) : ?><span class="text-xs font-bold text-secondary"><?= esc_html($c->name); ?></span><?php endif; ?>
                            <span class="mt-1 line-clamp-2 block font-black leading-7 text-gray-900 group-hover:text-secondary"><?php the_title(); ?></span>
                            <span class="mt-1 block text-xs text-gray-400"><?= esc_html(gl_fa_num(gl_reading_minutes())); ?> دقیقه مطالعه</span>
                        </span>
                    </a>
                <?php endif;
            endwhile;
            if ($featured->post_count > 0) {
                echo '</div>';
            }
            wp_reset_postdata(); ?>
        </section>
    <?php endif;

    /* ---------- series rail ---------- */
    $all_series = array_filter(gl_all_series(), function ($ids) {
        return count($ids) > 1;
    });
    if ($all_series) : ?>
        <section class="my-10 bg-primary py-14 text-white lg:py-20" aria-labelledby="series-title">
            <div class="container px-4 lg:px-0">
                <p class="mb-2 flex items-center gap-2 text-sm font-bold text-white/60"><span class="h-0.5 w-6 bg-current"></span>قدم‌به‌قدم یاد بگیر</p>
                <h2 id="series-title" class="mb-8 text-2xl font-black lg:text-4xl">پرونده‌های مجله</h2>
                <div class="-mx-4 flex snap-x snap-mandatory gap-5 overflow-x-auto px-4 pb-4 [scrollbar-width:none] lg:mx-0 lg:grid lg:grid-cols-3 lg:overflow-visible lg:px-0">
                    <?php $si = 0;
                    foreach ($all_series as $name => $ids) :
                        $first_id = $ids[0]; ?>
                        <a href="<?= esc_url(get_permalink($first_id)); ?>" class="group relative flex w-[82%] shrink-0 snap-start flex-col overflow-hidden rounded-3xl bg-white/[0.06] ring-1 ring-white/10 transition hover:-translate-y-1 hover:bg-white/10 sm:w-[46%] lg:w-auto" data-reveal style="--d:<?= $si++; ?>">
                            <span class="relative block aspect-[16/9] overflow-hidden">
                                <?= get_the_post_thumbnail($first_id, 'medium_large', ['class' => 'size-full object-cover opacity-80 transition duration-700 group-hover:scale-105 group-hover:opacity-100', 'loading' => 'lazy', 'alt' => '']); ?>
                                <span class="absolute inset-0 bg-gradient-to-t from-primary via-primary/20 to-transparent"></span>
                                <span class="absolute bottom-3 start-4 rounded-full bg-secondary px-3 py-1 text-xs font-bold"><?= esc_html(gl_fa_num(count($ids))); ?> قسمت</span>
                            </span>
                            <span class="flex flex-1 flex-col p-5">
                                <span class="text-xl font-black"><?= esc_html($name); ?></span>
                                <ol class="mt-3 grid gap-1.5 text-sm text-white/70">
                                    <?php foreach (array_slice($ids, 0, 3) as $n => $pid) : ?>
                                        <li class="flex gap-2"><span class="text-white/40"><?= esc_html(gl_fa_num($n + 1)); ?>.</span><span class="line-clamp-1"><?= esc_html(get_the_title($pid)); ?></span></li>
                                    <?php endforeach; ?>
                                    <?php if (count($ids) > 3) : ?><li class="text-white/40">و <?= esc_html(gl_fa_num(count($ids) - 3)); ?> قسمت دیگر…</li><?php endif; ?>
                                </ol>
                                <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-white transition-all group-hover:gap-3">شروع پرونده
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6"/></svg>
                                </span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif;
endif;

/* ---------- all posts (main query: 9/page, featured excluded — see inc/blog/magazine.php) ---------- */
global $wp_query;
?>
<section class="container px-4 pb-14 pt-6 lg:px-0" aria-labelledby="all-title">
    <?php if ($first && is_home()) : ?><h2 id="all-title" class="mb-8 text-2xl font-black text-gray-900 lg:text-3xl">همه مقالات</h2><?php endif; ?>
    <?php if (have_posts()) : ?>
        <div class="grid gap-x-6 gap-y-12 md:grid-cols-2 lg:grid-cols-3">
            <?php while (have_posts()) : the_post();
                get_template_part('template-parts/blog/mag-card', null, ['d' => $wp_query->current_post % 3, 'eager' => !$first && $wp_query->current_post < 3]);
            endwhile; ?>
        </div>
        <?php get_template_part('template-parts/global/pagination'); ?>
    <?php elseif (!$exclude) : ?>
        <p class="py-20 text-center text-2xl text-gray-400">مقاله‌ای یافت نشد!</p>
    <?php endif; ?>
</section>

<?php if ($first) :
    get_template_part('template-parts/global/content', null, ['content' => $page_content]);
    if ($faq) : ?>
        <section class="container px-4 lg:mb-4 lg:px-0">
            <?php get_template_part('template-parts/global/faq-list', null, ['items' => $faq]); ?>
        </section>
    <?php endif;
endif;

get_footer();
