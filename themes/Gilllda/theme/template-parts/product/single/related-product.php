<?php
$current_post_id = get_the_ID();

// 1. Fetch WooCommerce product category IDs instead of standard WP categories
$product_cat_ids = wp_get_post_terms($current_post_id, 'product_cat', array('fields' => 'ids'));

$args = [
    'post_type'           => 'product',
    'post_status'         => 'publish',
    'posts_per_page'      => 5,
    'post__not_in'        => [$current_post_id], // ❌ exclude current post
    'ignore_sticky_posts' => true,
];

// 2. Safely add a tax_query only if the current product actually belongs to categories
if (!empty($product_cat_ids) && !is_wp_error($product_cat_ids)) {
    $args['tax_query'] = [
        [
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $product_cat_ids,
        ],
    ];
}

$related = new WP_Query($args);

if ($related->have_posts()) :
    ?>
    <section class="container max-lg:px-3 my-8 lg:mt-12">
        <div class="border-b border-black/10 flex mb-6">
            <p class="pb-3 border-b-2 border-primary text-center text-black text-2xl fw-bold">
                محصولات مرتبط
            </p>
        </div>
        <div class="list-none flex max-w-full flex-nowrap overflow-x-scroll md:grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-2 lg:gap-4">
            <?php
            while ($related->have_posts()) :
                $related->the_post();

                $template_args = array(
                    'class' => 'max-lg:min-w-[250px] block',
                    'eager' => $related->current_post < 4 // Eager load the first 4 items
                );

                // I renamed $args to $template_args here so it doesn't overwrite your WP_Query $args above, just in case!
                get_template_part('template-parts/product/card/product-card', null, $template_args);
            endwhile;
            wp_reset_postdata();
            ?>
        </div>
    </section>
<?php endif; ?>