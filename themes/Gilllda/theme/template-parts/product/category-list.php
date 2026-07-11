<?php
// Ensure arguments are extracted correctly
$post_type = $args['post_type'] ?? '';
$taxonomy = $args['taxonomy'] ?? '';

// Only render if both post type and taxonomy arguments are valid and provided
if (empty($post_type) || empty($taxonomy) || !post_type_exists($post_type) || !taxonomy_exists($taxonomy)) {
    return;
}

$terms = $args['productCategoryList'] ?? $args['terms'] ?? '';

if (empty($terms)) {
    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'orderby'    => 'name',
        'order'      => 'ASC',
        'hide_empty' => true,
        'parent'     => 0, // Get top-level terms
    ]);
}

// Remove the "uncategorized" term if it exists
if (!empty($terms) && !is_wp_error($terms)) {
    $terms = array_filter($terms, function ($term) {
        return $term->slug !== 'uncategorized';  // exclude the default category slug
    });
}

if (!empty($terms) && !is_wp_error($terms)):
    ?>
    <section class="py-5 mt-4 bg-white">
        <div class="container mx-auto px-4">
            <div class="flex items-center overflow-x-auto gap-6 lg:grid lg:grid-cols-7 xl:grid-cols-8 lg:gap-y-8 lg:gap-x-6 justify-items-center lg:overflow-x-visible [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
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
                    <a href="<?= esc_url(get_term_link($term, $taxonomy)); ?>" class="group flex flex-col items-center select-none shrink-0 text-center w-24 md:w-32 transition-all duration-300">
                        <!-- Bubble and Overlapping Image Container -->
                        <div class="relative w-24 h-24 md:w-32 md:h-32 flex items-center justify-center mb-3">
                            <!-- Bubble Background -->
                            <div class="absolute w-20 h-20 md:w-28 md:h-28 rounded-full bg-[#f0f0f2] transition-all duration-300 ease-out group-hover:scale-105 group-hover:bg-[#e2e2e5]"></div>
                            <!-- Overlapping Image -->
                            <?php if ($thumbnail_url): ?>
                                <img class="relative z-10 w-24 h-24 md:w-32 md:h-32 object-contain transform -translate-y-2 transition-all duration-300 ease-out group-hover:-translate-y-4 group-hover:scale-110"
                                     src="<?= esc_url($thumbnail_url); ?>"
                                     alt="<?= esc_attr($term->name); ?>">
                            <?php else: ?>
                                <!-- Placeholder space if image is not present -->
                                <div class="relative z-10 w-20 h-20 md:w-28 md:h-28 rounded-full bg-gray-200 flex items-center justify-center transform -translate-y-2">
                                    <span class="text-xs text-gray-400 font-light"><?= esc_html($term->name); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- Label -->
                        <span class="text-xs md:text-sm font-semibold text-gray-800 line-clamp-2 group-hover:text-primary transition-colors duration-300 leading-relaxed px-1">
                            <?= esc_html($term->name); ?>
                        </span>
                    </a>
                <?php
                endforeach;
                wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
<?php endif; ?>