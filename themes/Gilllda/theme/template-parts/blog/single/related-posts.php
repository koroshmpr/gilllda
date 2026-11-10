<?php
/**
 * Related articles: same category first, then latest.
 */
$current_post_id = get_the_ID();
$related = new WP_Query([
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 3,
    'post__not_in'        => [$current_post_id],
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
    'category__in'        => wp_get_post_categories($current_post_id),
]);
if (!$related->have_posts()) {
    $related = new WP_Query(['post_type' => 'post', 'posts_per_page' => 3, 'post__not_in' => [$current_post_id], 'ignore_sticky_posts' => true, 'no_found_rows' => true]);
}
if ($related->have_posts()) : ?>
    <section class="container mb-12 border-t border-gray-100 px-4 pt-12 lg:px-0" aria-labelledby="related-title">
        <p class="mb-2 flex items-center gap-2 text-sm font-bold text-secondary"><span class="h-0.5 w-6 bg-current"></span>بیشتر بخوانید</p>
        <h2 id="related-title" class="mb-8 text-2xl font-black text-gray-900 lg:text-3xl">مقالات مرتبط</h2>
        <div class="grid gap-x-6 gap-y-10 md:grid-cols-2 lg:grid-cols-3">
            <?php while ($related->have_posts()) : $related->the_post();
                get_template_part('template-parts/blog/mag-card', null, ['d' => $related->current_post]);
            endwhile;
            wp_reset_postdata(); ?>
        </div>
    </section>
<?php endif;
