<?php

$current_post_id = get_the_ID();
$categories = wp_get_post_categories($current_post_id);

$args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 4,
    'post__not_in' => [$current_post_id], // ❌ exclude current post
    'ignore_sticky_posts' => true,
    'category__in' => $categories, // ✅ sibling posts
];

$related = new WP_Query($args);

if ($related->have_posts()) : ?>
    <section class="container max-lg:px-3 mb-8 lg:mt-12">
        <div class="border-b border-black/10 flex mb-6">
            <p class="pb-3 border-b-2 border-primary text-center text-black text-2xl fw-bold">
                مقالات مرتبط
            </p>
        </div>

        <div class="flex max-w-full flex-nowrap overflow-x-scroll md:grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2 lg:gap-4">
            <?php
            while ($related->have_posts()) :
                $related->the_post();
                $args = array(
                    'class' => 'max-lg:min-w-[250px] block'
                );
                get_template_part('template-parts/blog/archive-card', null, $args);
            endwhile;
            wp_reset_postdata();
            ?>
        </div>
    </section>
<?php endif; ?>