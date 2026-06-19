<?php

// Get the current service ID
$id = get_the_ID();
// Query portfolios related to this service
$args = array(
    'post_type' => 'post',
    'post_per_page' => 6,
    'meta_query' => array(
        array(
            'key' => 'related_product', // ACF field name
            'value' => $id, // Need to search within serialized array
            'compare' => 'LIKE'
        )
    )
);

$related_post = new WP_Query($args);
if ($related_post->have_posts()) :?>
    <section class="container max-lg:px-3 my-4">
        <div class="border-b border-black/10 flex mb-6">
            <p class="pb-3 border-b-2 border-primary text-center text-black text-2xl fw-bold">
                مقالات مرتبط
            </p>
        </div>
        <div class="flex flex-nowrap overflow-x-scroll md:grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-2 lg:gap-4">
            <?php
            while ($related_post->have_posts()):
                $related_post->the_post();

                $args = array(
                    'class' => 'max-lg:min-w-[250px] block',
                    'eager' => $related_post->current_post < 4 // Eager load the first 4 items
                );

                get_template_part('template-parts/blog/archive-card', null, $args);
            endwhile;
            wp_reset_postdata(); // Added this to safely reset the custom query
            ?>
        </div>
    </section>
<?php endif; ?>