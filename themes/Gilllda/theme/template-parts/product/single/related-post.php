<?php
/**
 * Product page: magazine articles that recommend this product (ACF related_product on posts).
 */
$id = get_the_ID();
$related_post = new WP_Query([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 3,
    'no_found_rows'  => true,
    'meta_query'     => [[
        'key'     => 'related_product',
        'value'   => '"' . $id . '"', // ACF stores the relationship as a serialized array of strings
        'compare' => 'LIKE',
    ]],
]);
if ($related_post->have_posts()) : ?>
    <section class="container my-10 px-4 lg:px-0" aria-labelledby="prod-mag-title">
        <p class="mb-2 flex items-center gap-2 text-sm font-bold text-secondary"><span class="h-0.5 w-6 bg-current"></span>مجله گیلدا</p>
        <h2 id="prod-mag-title" class="mb-8 text-2xl font-black text-gray-900">درباره این کیف بیشتر بخوانید</h2>
        <div class="grid gap-x-6 gap-y-10 md:grid-cols-2 lg:grid-cols-3">
            <?php while ($related_post->have_posts()) : $related_post->the_post();
                get_template_part('template-parts/blog/mag-card', null, ['d' => $related_post->current_post]);
            endwhile;
            wp_reset_postdata(); ?>
        </div>
    </section>
<?php endif;
