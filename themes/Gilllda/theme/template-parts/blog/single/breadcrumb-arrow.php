<?php
if (is_front_page()) return;

// 1. Initialize variables for dynamic JSON-LD Schema (Kept for SEO purposes)
$schema_items = [];
$position = 1;
global $wp;
$current_url = home_url(add_query_arg(array(), $wp->request));

// Helper function to easily build the schema array
$add_schema_item = function ($name, $url) use (&$schema_items, &$position) {
    $schema_items[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => wp_strip_all_tags($name),
        'item' => esc_url($url)
    ];
};

// Variables to hold the visual button data
$btn_text = '';
$btn_link = '';

if (is_single()) {
    $post_type = get_post_type();
    $post_type_name = ($post_type === 'post') ? 'مقالات' : get_post_type_object($post_type)->labels->singular_name;
    $post_type_link = ($post_type === 'post') ? get_permalink(get_option('page_for_posts')) : get_post_type_archive_link($post_type);

    // Add Post Type to Schema
    $add_schema_item($post_type_name, $post_type_link ? $post_type_link : $current_url);

    $categories = get_the_category();
    if (!empty($categories)) {
        $category = $categories[0];
        // Add Category to Schema
        $add_schema_item($category->name, get_category_link($category->term_id));

        // Set visual button to show the Category
        $btn_text = $category->name;
        $btn_link = get_category_link($category->term_id);
    } else {
        // Fallback: If no category, show the post type name
        $btn_text = $post_type_name;
        $btn_link = $post_type_link;
    }

    // Add Final Post to Schema
    $add_schema_item(get_the_title(), get_permalink());

} elseif (is_category() || is_archive() || is_search()) {
    // If they are already on a category/archive, maybe show a "Back to Home" or "Blog" button
    $btn_text = 'صفحه اصلی';
    $btn_link = home_url();
    // (Schema is simplified here for brevity, adjust if you need full archive schemas)
}
?>

<?php
// 2. Output the Visual Button (Matching your image)
if (!empty($btn_text) && !empty($btn_link)) :
    ?>
    <nav class="<?= esc_attr($args['class'] ?? ''); ?> mb-4 flex items-center" aria-label="breadcrumb">
        <a href="<?= esc_url($btn_link); ?>"
           class="inline-flex items-center gap-x-2 px-4 py-1.5 bg-[#f8f9fa] border border-gray-200 rounded-2xl text-gray-700 hover:bg-gray-100 transition-colors text-sm font-medium">
            <?php get_template_part('template-parts/svg/chevron-right', null, ['size' => 20,]); ?>
            <span class="pt-[2px]"><?= esc_html($btn_text); ?></span>
        </a>
    </nav>
<?php endif; ?>

<?php
// 3. Output the Generated JSON-LD Schema (Invisible, but great for Google)
if (!empty($schema_items)) :
    $schema_data = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $schema_items
    ];
    ?>
    <script type="application/ld+json">
    <?= wp_json_encode($schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>

    </script>
<?php endif; ?>