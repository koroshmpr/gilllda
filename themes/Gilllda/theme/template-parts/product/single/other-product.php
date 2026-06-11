<?php
// 1. Get the global WooCommerce product object securely
global $product;

// Fallback just in case the global product isn't loaded yet by your theme
if ( ! is_a( $product, 'WC_Product' ) ) {
    $product = wc_get_product( get_the_ID() );
}

// Proceed only if we successfully have a product
if ( $product ) :
    $current_post_id = $product->get_id();

    // 2. Use WooCommerce's native method to get an array of category IDs
    $product_cat_ids = $product->get_category_ids();

    $args_other = [
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => 5,
        'post__not_in'        => [ $current_post_id ], // ❌ strictly exclude current product
        'ignore_sticky_posts' => true,
        'orderby'             => 'rand',
    ];

    // 3. Exclude the categories
    if ( ! empty( $product_cat_ids ) ) {
        $args_other['tax_query'] = [
            [
                'taxonomy'         => 'product_cat',
                'field'            => 'term_id',
                'terms'            => $product_cat_ids,
                'operator'         => 'NOT IN', // ❌ exclude products sharing these categories
                'include_children' => true,     // Optional but safe: excludes child categories too
            ],
        ];
    }

    $other_products = new WP_Query($args_other);

    if ($other_products->have_posts()) :
        ?>
        <section class="container max-lg:px-3 my-8 lg:mt-12">
            <div class="border-b border-black/10 flex mb-6">
                <p class="pb-3 border-b-2 border-primary text-center text-black text-2xl fw-bold">
                    محصولات دیگر
                </p>
            </div>
            <div class="list-none flex max-w-full flex-nowrap overflow-x-scroll md:grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-2 lg:gap-4">
                <?php
                while ($other_products->have_posts()) :
                    $other_products->the_post();
                    $template_args = array(
                        'class' => 'max-lg:min-w-[250px] block'
                    );
                    get_template_part('template-parts/product/card/product-card', null, $template_args);
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        </section>
    <?php
    endif;
endif;
?>