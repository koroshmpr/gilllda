<?php
$current_post_id = get_the_ID();
$categories = wp_get_post_categories($current_post_id);

$args = [
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'post__not_in' => [$current_post_id], // ❌ exclude current post
    'ignore_sticky_posts' => true,
    'category__in' => $categories, // ✅ sibling posts
];
$related = new WP_Query($args);
if ($related->have_posts()) :
    ?>
    <section class="container max-lg:px-3 my-8 lg:mt-12">
        <div class="border-b border-black/10 flex mb-6">
            <p class="pb-3 border-b-2 border-primary text-center text-black text-2xl fw-bold">
                محصولات مرتبط
            </p>
        </div>
        <ul class="list-none flex max-w-full flex-nowrap overflow-x-scroll md:grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-2 lg:gap-4">
            <?php

            while ($related->have_posts()) :
                $related->the_post();
                $args = array(
                    'class' => 'max-lg:min-w-[250px] block'
                );
                get_template_part('template-parts/product/card/product-card', null, $args);
            endwhile;
            wp_reset_postdata();
            ?>
        </ul>
    </section>
<?php endif; ?>