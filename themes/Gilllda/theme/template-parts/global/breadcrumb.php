<?php
if (is_front_page()) return;

$currentColor = 'text-gray-700';
$slashClass = 'text-gray-400 px-1';

// 1. Initialize variables for dynamic JSON-LD Schema
$schema_items = [];
$position = 1;
global $wp;
$current_url = home_url(add_query_arg(array(), $wp->request));

// Helper function to easily build the schema array
$add_schema_item = function($name, $url) use (&$schema_items, &$position) {
    $schema_items[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => wp_strip_all_tags($name),
        'item' => esc_url($url)
    ];
};
?>

    <nav class="<?= esc_attr($args['class'] ?? ''); ?> flex text-nowrap items-center text-gray-600 overflow-x-scroll text-sm mb-1" aria-label="breadcrumb">
        <ol class="flex items-center gap-x-1 m-0 p-0 list-none">

            <?php if (is_single()) : ?>
                <?php
                $post_type = get_post_type();
                $post_type_name = ($post_type === 'post') ? 'مقالات' : get_post_type_object($post_type)->labels->singular_name;
                $post_type_link = ($post_type === 'post') ? get_permalink(get_option('page_for_posts')) : get_post_type_archive_link($post_type);

                // Add First Item to Schema
                $add_schema_item($post_type_name, $post_type_link ? $post_type_link : $current_url);
                ?>

                <li class="flex items-center">
                    <?php if ($post_type_link) : ?>
                        <a href="<?= esc_url($post_type_link); ?>" class="text-gray-600 hover:text-gray-700">
                            <?= esc_html($post_type_name); ?>
                        </a>
                    <?php else : ?>
                        <span class="<?= $currentColor; ?>"><?= esc_html($post_type_name); ?></span>
                    <?php endif; ?>
                </li>

                <li class="<?= $slashClass; ?>" aria-hidden="true">/</li>

                <?php
                $categories = get_the_category();
                if (!empty($categories)) :
                    $category = $categories[0];

                    // Add Category to Schema
                    $add_schema_item($category->name, get_category_link($category->term_id));
                    ?>
                    <li class="flex items-center">
                        <a href="<?= esc_url(get_category_link($category->term_id)); ?>" class="text-gray-500 hover:text-gray-700">
                            <?= esc_html($category->name); ?>
                        </a>
                    </li>
                    <li class="<?= $slashClass; ?>" aria-hidden="true">/</li>
                <?php endif; ?>

                <?php
                // Add Final Post to Schema
                $add_schema_item(get_the_title(), get_permalink());
                ?>
                <li class="flex items-center">
                    <span class="<?= $currentColor; ?>" aria-current="page"><?php the_title(); ?></span>
                </li>

            <?php elseif (is_category()) : ?>
                <?php $add_schema_item(single_cat_title('', false), $current_url); ?>
                <li class="flex items-center">
                    <span class="<?= $currentColor; ?>" aria-current="page"><?= esc_html(single_cat_title('', false)); ?></span>
                </li>

            <?php elseif (is_archive()) : ?>
                <?php
                $archive_title = (get_post_type() === 'post') ? 'بلاگ' : post_type_archive_title('', false);
                $add_schema_item($archive_title, $current_url);
                ?>
                <li class="flex items-center">
                <span class="<?= $currentColor; ?>" aria-current="page">
                    <?= esc_html($archive_title); ?>
                </span>
                </li>

            <?php elseif (is_search()) : ?>
                <?php
                $search_title = 'نتایج جستجو برای "' . get_search_query() . '"';
                $add_schema_item($search_title, $current_url);
                ?>
                <li class="flex items-center">
                    <span class="<?= $currentColor; ?>" aria-current="page"><?= esc_html($search_title); ?></span>
                </li>

            <?php elseif (is_404()) : ?>
                <?php $add_schema_item('صفحه پیدا نشد', $current_url); ?>
                <li class="flex items-center">
                    <span class="<?= $currentColor; ?>" aria-current="page">صفحه پیدا نشد</span>
                </li>
            <?php endif; ?>
        </ol>
    </nav>

<?php
// 2. Output the Generated JSON-LD Schema
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