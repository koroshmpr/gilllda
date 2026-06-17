<?php
/**
 * Product Loop Start
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/loop-start.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div :class="scrollingDown ? 'scale-90 translate-y-1' : ''" class="lg:hidden fixed transition-all duration-500 bottom-17 divide-x divide-gray-300 border border-gray-300 inset-x-5 rounded-2xl z-40 overflow-hidden flex items-center rtl"
     dir="rtl">
    <?php
    $btnCLass = 'flex-1 flex items-center justify-center gap-2 py-2.5 bg-white/90 backdrop-blur-sm text-sm font-bold active:scale-95 transition-transform'
    ?>
    <button @click="$dispatch('open-filter')" type="button"
            class="<?= $btnCLass; ?>">
        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
        </svg>
        فیلترها
    </button>
    <button @click="$dispatch('open-sort')" type="button"
            class="<?= $btnCLass; ?>">
        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
        </svg>
        مرتب‌سازی
    </button>
</div>

<div x-data="{ sortOpen: false }" @open-sort.window="sortOpen = true"
     class="grid w-full lg:gap-4 mb-4 lg:grid-cols-3 xl:grid-cols-4 rtl" dir="rtl">

    <?php get_template_part('template-parts/shop/filter'); ?>

    <div class="lg:col-span-2 xl:col-span-3">

        <?php
        // --- 1. RUN THE QUERY FIRST SO WE KNOW THE RESULT COUNT ---

        $term_id = get_queried_object_id();
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $posts_per_page = get_option('posts_per_page') ?? 9;

        if (is_shop()) :
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => $posts_per_page,
                'post_status' => 'publish',
                'paged' => $paged,
            );
        else :
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => $posts_per_page, // FIXED: Changed -1 to $posts_per_page to fix category pagination
                'post_status' => 'publish',
                'paged' => $paged,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $term_id,
                    ),
                ),
            );
        endif;

        // --- INJECT URL FILTERS ---
        $meta_query = $args['meta_query'] ?? array();
        $tax_query = $args['tax_query'] ?? array();

        if (!empty($_GET['product_cat'])) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => explode(',', sanitize_text_field($_GET['product_cat'])),
                'operator' => 'IN',
            );
        }

        foreach ($_GET as $key => $value) {
            if (strpos($key, 'pa_') === 0 && !empty($value)) {
                $tax_query[] = array(
                    'taxonomy' => sanitize_key($key),
                    'field'    => 'slug',
                    'terms'    => explode(',', sanitize_text_field($value)),
                    'operator' => 'IN',
                );
            }
        }

        if (!empty($_GET['max_price'])) {
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => sanitize_text_field($_GET['max_price']),
                'compare' => '<=',
                'type'    => 'NUMERIC',
            );
        }

        if (!empty($_GET['min_weight'])) {
            $meta_query[] = array(
                'key'     => '_weight',
                'value'   => sanitize_text_field($_GET['min_weight']),
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );
        }

        if (isset($_GET['in_stock']) && $_GET['in_stock'] === 'true') {
            $meta_query[] = array(
                'key'     => '_stock_status',
                'value'   => 'instock',
                'compare' => '='
            );
        }

        // --- CORRECTED ON SALE FILTER ---
        if (isset($_GET['on_sale']) && $_GET['on_sale'] === 'true') {
            $on_sale_ids = wc_get_product_ids_on_sale();
            $on_sale_ids = empty($on_sale_ids) ? array(0) : $on_sale_ids;

            // Safely merge with any existing post__in arguments
            if (isset($args['post__in']) && !empty($args['post__in'])) {
                $args['post__in'] = array_intersect($args['post__in'], $on_sale_ids);
            } else {
                $args['post__in'] = $on_sale_ids;
            }
        }

        if (!empty($meta_query)) {
            if (count($meta_query) > 1) $meta_query['relation'] = 'AND';
            $args['meta_query'] = $meta_query;
        }

        if (!empty($tax_query)) {
            if (count($tax_query) > 1) $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }

        $random_query = new WP_Query($args);
        $total_products = $random_query->found_posts;
        $start_index = ($paged - 1) * $posts_per_page + 1;
        $end_index = min($paged * $posts_per_page, $total_products);
        ?>

        <nav class="flex items-center justify-between bg-white rounded-lg p-4 lg:p-2 border border-gray-200 mb-3 gap-3">
            <div class="flex items-center gap-4">
                <?php get_template_part('template-parts/global/grid-button'); ?>
                <div class="text-xs font-bold text-gray-400 text-nowrap custom-result-count [&>p]:!mb-0">
                    <?php
                    // --- 2. RENDER THE CUSTOM RESULT COUNT ---
                    if ($total_products == 0) {
                        echo 'هیچ نتیجه‌ای یافت نشد';
                    } elseif ($total_products == 1) {
                        echo 'نمایش ۱ نتیجه';
                    } elseif ($total_products <= $posts_per_page) {
                        echo 'نمایش  ' . $total_products . ' نتیجه';
                    } else {
                        echo 'نمایش ' . $start_index . ' تا ' . $end_index . ' از ' . $total_products . ' نتیجه';
                    }
                    ?>
                </div>
            </div>

            <div class="max-lg:hidden flex items-center gap-2 custom-ordering">
                <span class="text-xs font-bold text-gray-400">مرتب‌سازی:</span>
                <div class="[&_select]:bg-transparent [&_select]:text-xs [&_select]:w-fit [&_select]:font-black [&_select]:border-none [&>form]:!mb-0 [&_select]:focus:ring-0 [&_select]:cursor-pointer [&>form]:flex [&>form]:items-center [&>form]:border [&>form]:border-black/5 [&>form]:rounded-md [&>form]:p-2">
                    <?php woocommerce_catalog_ordering(); ?>
                </div>
            </div>
        </nav>

        <div class="grid gap-3 transition-all duration-500 p-0 !my-0 list-none grid-cols-1"
            :class="gridView === 'large' ? 'md:grid-cols-2 xl:grid-cols-3' : ' lg:grid-cols-3 xl:grid-cols-4'">

            <template x-teleport="body">
                <div x-show="sortOpen" class="fixed inset-0 z-[50] lg:hidden rtl" dir="rtl">
                    <div x-show="sortOpen" x-transition:opacity @click="sortOpen = false"
                         class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                    <div x-show="sortOpen" x-transition:enter="transition ease-out duration-300 transform"
                         x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                         class="absolute bottom-0 inset-x-0 bg-white rounded-t-lg p-4 shadow-2xl">
                        <div class="flex justify-between items-center mb-3 pb-3 border-b border-gray-200">
                            <h3 class="text-center font-bold text-lg">ترتیب نمایش</h3>
                            <button @click="sortOpen = false" aria-label="close menu" class=" text-black bg-gray-100 border border-gray-200 p-2 rounded-sm">
                                <?php
                                $args = array(
                                    'size' => '15',
                                    'class' => '',
                                );
                                get_template_part('template-parts/svg/close', null, $args);
                                ?>
                            </button>
                        </div>
                        <div class="w-full py-3 flex flex-col items-center [&_select]:bg-transparent [&_select]:text-xs [&_select]:font-black [&_select]:border-none [&>form]:!mb-0 [&_select]:focus:ring-0 [&_select]:cursor-pointer [&>form]:flex [&>form]:items-center [&>form]:border [&>form]:border-black/10 [&>form]:rounded-md [&>form]:p-2">
                            <?php woocommerce_catalog_ordering(); ?>
                        </div>
                    </div>
                </div>
            </template>

            <?php
            // --- 3. LOOP THROUGH THE ALREADY RUN QUERY ---
            if ($random_query->have_posts()) :
                while ($random_query->have_posts()) {
                    $random_query->the_post();
                    get_template_part('template-parts/product/card/product-card',null , ['isArchive' => true]);
                }
                wp_reset_postdata(); // Safely resets the loop
            else : ?>
                <h2 class="text-2xl opacity-25 text-center w-full py-4 col-span-full">
                    هیچ محصولی یافت نشد
                </h2>
            <?php endif; ?>