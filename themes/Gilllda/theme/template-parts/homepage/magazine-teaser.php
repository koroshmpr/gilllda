<?php
/**
 * Homepage: latest from «مجله گیلدا».
 */
$q = new WP_Query(['post_type' => 'post', 'posts_per_page' => 3, 'ignore_sticky_posts' => true, 'no_found_rows' => true]);
if (!$q->have_posts()) {
    return;
}
$blog = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
?>
<section class="container px-4 py-14 lg:px-0 lg:py-20" aria-labelledby="home-mag-title">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div data-reveal>
            <p class="mb-2 flex items-center gap-2 text-sm font-bold text-secondary"><span class="h-0.5 w-6 bg-current"></span>مجله گیلدا</p>
            <h2 id="home-mag-title" class="text-2xl font-black text-gray-900 lg:text-4xl">راهنمای انتخاب، نگهداری و استایل کیف</h2>
        </div>
        <a href="<?= esc_url($blog); ?>" class="group inline-flex min-h-11 items-center gap-2 rounded-full border border-gray-200 px-5 text-sm font-bold text-gray-700 transition hover:border-primary hover:bg-primary hover:text-white" data-reveal>
            همه مقالات
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="transition group-hover:-translate-x-1" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6"/></svg>
        </a>
    </div>
    <div class="grid gap-x-6 gap-y-10 md:grid-cols-2 lg:grid-cols-3">
        <?php while ($q->have_posts()) : $q->the_post();
            get_template_part('template-parts/blog/mag-card', null, ['d' => $q->current_post]);
        endwhile;
        wp_reset_postdata(); ?>
    </div>
</section>
