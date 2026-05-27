<?php
// Ensure arguments are extracted correctly
$terms = $args['productCategoryList'] ?? '';
$taxonomy = $args['taxonomy'] ?? 'product_cat';
//if (empty($categories)) :
//
//// Fetch terms dynamically based on taxonomy
//    $terms = get_terms([
//        'taxonomy' => $taxonomy,
//        'orderby' => 'name',
//        'hide_empty' => true,
//        'posts_per_page' => 7, // fix typo 'post_per_page' to 'posts_per_page'
//        'parent' => 0, // For hierarchical taxonomies, this gets top-level terms
//    ]);
//
//// Remove the "uncategorized" term if it exists
//    if (!empty($terms) && !is_wp_error($terms)) {
//        $terms = array_filter($terms, function ($term) {
//            return $term->slug !== 'uncategorized';  // exclude the default category slug
//        });
//    }
//
//    $current_term_id = 0;
//    $i = 0;
//
//// Detect current term in archive pages
//    if (is_tax($taxonomy)) {
//        $term = get_queried_object();
//        if ($term) {
//            $current_term_id = $term->term_id;
//        }
//    }
//endif;

if (!empty($terms)):
    ?>
    <section class="bg-gray-100">
        <div class="container flex max-lg:px-5 lg:grid grid-cols-7 group/category lg:justify-center overflow-x-scroll flex-nowrap gap-x-3 lg:gap-x-2 max-lg:mt-0 my-8">
            <?php
            foreach ($terms as $term):
                // Get ACF field for term thumbnail
                $thumbnail = get_field('image_cover', "{$taxonomy}_{$term->term_id}");
                $thumbnail_url = is_array($thumbnail) ? $thumbnail['url'] : (is_numeric($thumbnail) ? wp_get_attachment_url($thumbnail) : $thumbnail);

                // Fallback: get term meta thumbnail
                if (!$thumbnail_url) {
                    $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                    $thumbnail_url = wp_get_attachment_url($thumbnail_id);
                }
                ?>
                <a class="transition-all shrink-0 group flex flex-col relative gap-2 pt-4 pb-8 hover:pt-0 hover:pb-0 hover:bg-white items-center duration-500 overflow-hidden"
                   href="<?= esc_url(get_term_link($term, $taxonomy)); ?>">
                    <?php if ($thumbnail_url): ?>
                        <img :class="scrolled ? '' : 'lg:scale-75'"
                             class="w-[150px] lg:w-full group-hover:scale-150 object-cover duration-500 group-hover:!grayscale-0 group-hover/category:grayscale-25 group-hover/category:blur-[1px] group-hover:!blur-none bg-white transition-all aspect-square"
                             src="<?= esc_url($thumbnail_url); ?>"
                             alt="<?= esc_attr($term->name); ?>">
                    <?php endif; ?>
                    <p class="text-center absolute bottom-0 inset-x-0 py-1 px-2 max-lg:pt-2 text-nowrap line-clamp-1 group-hover:bg-white/95 backdrop-blur-sm duration-500 transition-all text-sm lg:text-base opacity-75 ">
                        <?= esc_html($term->name); ?>
                    </p>
                </a>
            <?php
            endforeach;
            wp_reset_postdata(); ?>
        </div>
    </section>
<?php endif; ?>