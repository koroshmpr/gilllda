<?php
/**
 * Product compare.
 *
 * The list lives in the browser (localStorage `compare_products` = [{id,name,img}], max 4),
 * so cached pages stay identical for every visitor. The compare page and the tray read
 * product data from one public, read-only endpoint:
 *
 *   GET /wp-json/gilllda/v1/compare?ids=12,34   → { products: [...], missing: [ids] }
 *
 * Per-product payloads are cached in transients and flushed on product updates.
 */

if (!defined('ABSPATH')) {
    exit;
}

const GL_COMPARE_MAX = 4;

/** Parse an id list ("1,2,3" or array) → max 4 unique positive ints. */
function gl_compare_parse_ids($raw) {
    if (is_array($raw)) {
        $raw = implode(',', array_map('strval', array_slice($raw, 0, 20)));
    }
    $raw = substr((string) $raw, 0, 200);
    $ids = array_filter(array_map('absint', preg_split('/[^0-9]+/', $raw)));
    return array_slice(array_values(array_unique($ids)), 0, GL_COMPARE_MAX);
}

function gl_compare_unit_label($unit) {
    $map = ['cm' => 'سانتی‌متر', 'mm' => 'میلی‌متر', 'm' => 'متر', 'in' => 'اینچ', 'g' => 'گرم', 'kg' => 'کیلوگرم', 'lbs' => 'پوند', 'oz' => 'اونس'];
    return $map[$unit] ?? $unit;
}

/** Compare payload of one product (array) or null when it can't be shown. */
function gl_compare_product_data($id) {
    $key = 'gl_cmp_' . $id;
    $cached = get_transient($key);
    if (is_array($cached)) {
        return $cached ?: null;
    }

    $product = function_exists('wc_get_product') ? wc_get_product($id) : null;
    if ($product && $product->is_type('variation')) {
        $product = wc_get_product($product->get_parent_id());
    }
    if (!$product || 'publish' !== $product->get_status() || 'hidden' === $product->get_catalog_visibility()) {
        set_transient($key, [], HOUR_IN_SECONDS);
        return null;
    }
    $pid = $product->get_id();

    if ($product->is_type('variable')) {
        $price     = (float) $product->get_variation_price('min', true);
        $price_max = (float) $product->get_variation_price('max', true);
        $regular   = (float) $product->get_variation_regular_price('min', true);
    } else {
        $price     = (float) wc_get_price_to_display($product);
        $price_max = $price;
        $regular   = (float) wc_get_price_to_display($product, ['price' => $product->get_regular_price()]);
    }

    // Colours (+ per-colour stock for variable products).
    $color_stock = [];
    if (function_exists('gl_card_variations')) {
        foreach (gl_card_variations($product) as $var) {
            $slug = $var['attributes']['attribute_pa_color'] ?? '';
            if ($slug && $var['in_stock']) {
                $color_stock[$slug] = true;
            }
        }
    }
    $colors = [];
    $terms = wc_get_product_terms($pid, 'pa_color', ['fields' => 'all']);
    if (!is_wp_error($terms)) {
        foreach ($terms as $t) {
            $colors[] = [
                'name' => $t->name,
                'hex'  => function_exists('gl_search_term_hex') ? gl_search_term_hex($t) : '',
                'ok'   => !$product->is_type('variable') || !empty($color_stock[$t->slug]),
            ];
        }
    }

    // Dimensions / weight with Persian units.
    $dims = array_filter(array_map('strval', $product->get_dimensions(false)), 'strlen');
    $dims_txt = $dims ? implode(' × ', array_map('wc_format_localized_decimal', $dims)) . ' ' . gl_compare_unit_label(get_option('woocommerce_dimension_unit')) : '';
    $weight = (string) $product->get_weight();
    $weight_txt = '' !== $weight ? wc_format_localized_decimal($weight) . ' ' . gl_compare_unit_label(get_option('woocommerce_weight_unit')) : '';

    // Other visible, non-colour attributes (e.g. material).
    $attrs = [];
    foreach ($product->get_attributes() as $attr) {
        if (!$attr->get_visible() || 'pa_color' === $attr->get_name() || 'pa_size' === $attr->get_name()) {
            continue;
        }
        if ($attr->is_taxonomy()) {
            $vals = wc_get_product_terms($pid, $attr->get_name(), ['fields' => 'names']);
            $vals = is_wp_error($vals) ? [] : $vals;
        } else {
            $vals = $attr->get_options();
        }
        if ($vals) {
            $attrs[wc_attribute_label($attr->get_name(), $product)] = implode('، ', array_map('wp_strip_all_tags', $vals));
        }
    }

    $cats = wp_get_post_terms($pid, 'product_cat', ['fields' => 'names']);
    $short = $product->get_short_description() ?: get_post_field('post_excerpt', $pid);
    $image_id = $product->get_image_id();
    $stock = $product->get_stock_status();

    $data = [
        'id'        => $pid,
        'name'      => $product->get_name(),
        'url'       => $product->get_permalink(),
        'img'       => $image_id ? (string) wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : (string) wc_placeholder_img_src('woocommerce_thumbnail'),
        'thumb'     => $image_id ? (string) wp_get_attachment_image_url($image_id, 'thumbnail') : '',
        'price'     => $price,
        'price_max' => $price_max > $price ? $price_max : 0,
        'regular'   => $regular > $price ? $regular : 0,
        'colors'    => $colors,
        'dims'      => $dims_txt,
        'weight'    => $weight_txt,
        'cats'      => is_wp_error($cats) ? '' : implode('، ', $cats),
        'stock'     => $stock,
        'rating'    => round((float) $product->get_average_rating(), 1),
        'reviews'   => (int) $product->get_review_count(),
        'short'     => wp_trim_words(wp_strip_all_tags(strip_shortcodes((string) $short)), 30, '…'),
        'attrs'     => $attrs,
    ];
    set_transient($key, $data, 6 * HOUR_IN_SECONDS);
    return $data;
}

function gl_compare_flush($id) {
    $id = is_object($id) && method_exists($id, 'get_id') ? $id->get_id() : absint($id);
    if (!$id) {
        return;
    }
    delete_transient('gl_cmp_' . $id);
    $parent = wp_get_post_parent_id($id);
    if ($parent) {
        delete_transient('gl_cmp_' . $parent);
    }
}
foreach (['woocommerce_update_product', 'woocommerce_update_product_variation', 'woocommerce_delete_product_transients', 'woocommerce_product_set_stock_status', 'woocommerce_variation_set_stock_status', 'woocommerce_product_set_stock', 'woocommerce_variation_set_stock', 'save_post_product'] as $gl_hook) {
    add_action($gl_hook, 'gl_compare_flush');
}
unset($gl_hook);

add_action('rest_api_init', function () {
    register_rest_route('gilllda/v1', '/compare', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'args'                => ['ids' => ['type' => 'string', 'required' => true]],
        'callback'            => function (WP_REST_Request $request) {
            $products = [];
            $missing  = [];
            foreach (gl_compare_parse_ids($request->get_param('ids')) as $id) {
                $data = gl_compare_product_data($id);
                if ($data) {
                    $data['req'] = $id; // id the client stored (may be a variation id)
                    $products[] = $data;
                } else {
                    $missing[] = $id;
                }
            }
            $res = new WP_REST_Response(['products' => $products, 'missing' => $missing], 200);
            $res->header('Cache-Control', 'public, max-age=300');
            return $res;
        },
    ]);
});

/** Legacy admin-ajax action (old cached pages): same data, bounded. */
function get_compare_products() {
    $out = [];
    foreach (gl_compare_parse_ids($_POST['ids'] ?? '') as $id) { // phpcs:ignore WordPress.Security.NonceVerification
        $d = gl_compare_product_data($id);
        if ($d) {
            $out[] = ['id' => $id, 'name' => $d['name'], 'image' => $d['img'], 'price' => esc_html(number_format($d['price'])) . ' تومان', 'url' => $d['url'], 'weight' => $d['weight'], 'dims' => esc_html($d['dims'])];
        }
    }
    wp_send_json($out);
}
add_action('wp_ajax_get_compare_products', 'get_compare_products');
add_action('wp_ajax_nopriv_get_compare_products', 'get_compare_products');

/** Compare page: keep it noindex and free of JSON-LD. */
function gl_is_compare_page() {
    return is_page_template('page-compare.php') || is_page('compare');
}
add_filter('rank_math/json_ld', function ($data) {
    return gl_is_compare_page() ? [] : $data;
}, 999);
add_filter('rank_math/frontend/robots', function ($robots) {
    if (gl_is_compare_page()) {
        $robots['index'] = 'noindex';
    }
    return $robots;
}, 999);

/**
 * Floating compare tray (same markup for everyone; Alpine shows it from localStorage).
 * Sits above the mobile bottom nav (and above the sticky buy bar on product pages).
 */
add_action('wp_footer', function () {
    if (is_admin() || gl_is_compare_page() || (function_exists('gl_is_landing') && gl_is_landing())
        || (function_exists('is_cart') && (is_cart() || is_checkout()))) {
        return;
    }
    $bottom = function_exists('is_product') && is_product()
        ? 'bottom-[calc(9rem+env(safe-area-inset-bottom))] xl:bottom-6'
        : 'bottom-[calc(5rem+env(safe-area-inset-bottom))] lg:bottom-6';
    ?>
    <div x-data="glCompareTray" x-show="show" style="display:none"
         x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed inset-x-3 z-40 mx-auto max-w-md <?php echo esc_attr($bottom); ?>" dir="rtl" role="region" aria-label="لیست مقایسه">
        <div class="flex items-center gap-2 rounded-full border border-gray-200 bg-white/95 p-1.5 ps-2 shadow-[0_10px_40px_-10px_rgba(16,37,66,0.35)] backdrop-blur">
            <button type="button" @click="$store.compare.clear()" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-secondary" aria-label="پاک کردن لیست مقایسه" title="پاک کردن لیست">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
            <ul class="flex min-w-0 items-center -space-x-2">
                <template x-for="item in $store.compare.items" :key="item.id">
                    <li class="relative size-10 shrink-0 overflow-hidden rounded-full border-2 border-white bg-gray-100 shadow-sm">
                        <img x-show="item.img" :src="item.img" :alt="item.name" width="40" height="40" loading="lazy" class="size-full object-cover">
                    </li>
                </template>
            </ul>
            <span class="min-w-0 flex-1 truncate ps-1 text-xs text-gray-500"><span x-text="fa($store.compare.items.length)"></span> از <span x-text="fa($store.compare.max)"></span> محصول</span>
            <a :href="url" class="flex min-h-11 shrink-0 items-center gap-1.5 rounded-full bg-primary px-5 text-sm font-bold text-white transition hover:brightness-125 active:scale-[0.98]">
                مقایسه
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
            </a>
        </div>
    </div>
    <?php
}, 5);
