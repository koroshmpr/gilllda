<?php
/**
 * Product Loop End
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/loop-end.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
</ul>
<?php
$current_page = (get_query_var('paged')) ? get_query_var('paged') : 1;
$term_id = get_queried_object_id();
$posts_per_page = get_option('posts_per_page') ?? 9;
if (is_shop()) :
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $posts_per_page,
        'orderby' => 'Date', // Random order
        'post_status' => 'publish', // Only published products
        'paged' => $current_page, // For pagination

    );
    $random_query = new WP_Query($args);
    $total_products = $random_query->found_posts; // Total products
    $start_index = ($current_page - 1) * $posts_per_page + 1;
    $end_index = min($current_page * $posts_per_page, $total_products);
// Display pagination
    $pagination_links = paginate_links(array(
        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged', 1)),
        'total' => $random_query->max_num_pages,
        'type' => 'array',
        'prev_text' => '‹',
        'next_text' => '›',
    ));
    if ($pagination_links) : ?>
        <nav class="flex max-lg:flex-col gap-y-3 lg:justify-between items-center mt-3 border p-3 rounded-md border-gray-200">
            <ul aria-label="pagination" class="flex list-none gap-2 justify-center p-0 items-center mt-0 mb-0">
                <?php
                foreach ($pagination_links as $link) :
                    echo '<li class="page-item mt-0 mb-0 px-0">' . $link . '</li>';
                endforeach;
                ?>
            </ul>
            <div class="flex justify-center">
                نمایش محصولات
                <b class="px-1"><?php echo esc_html($start_index); ?></b>
                تا
                <b class="px-1"><?php echo esc_html($end_index); ?></b>
                از
                <b class="px-1"><?php echo esc_html($total_products); ?></b>
                نتیجه
            </div>
        </nav>
    <?php endif;
endif;
?>
</div>
<?php
if (is_product_category()):
    $term = get_queried_object();

    $acf_term_id = $term->taxonomy . '_' . $term->term_id;

    $content = get_field('content', $acf_term_id);
    if ($content) :
        $args = array(
            'id'    => $acf_term_id,
            'class' => 'lg:col-span-3 xl:col-span-4 my-3'
        );
        get_template_part('template-parts/shop/shop-content', null, $args);
    endif;
endif;?>
