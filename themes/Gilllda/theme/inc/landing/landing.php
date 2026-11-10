<?php
/**
 * Product landing pages — core.
 *
 * Every product can get a standalone, animated marketing landing at
 *   https://gilllda.ir/l/{product-slug}/
 * fed entirely by the product's own data (images, variations, price, reviews…)
 * with optional per-product overrides from the «لندینگ محصول» box in the editor.
 *
 * - Disabled landing → 302 to the normal product page (marketing links never die).
 * - Main query stays the product, so Rank Math prints the product canonical/schema.
 * - Views / CTA clicks / orders / revenue are counted per traffic source (utm_source).
 *
 * @package bluebox
 */

if (!defined('ABSPATH')) {
    exit;
}

// Settings/toggle keys are deliberately not "_"-prefixed so they can also be managed
// over the REST/MCP API (e.g. turning a landing on for a campaign); they are hidden
// from the classic "Custom Fields" box below.
const GL_LP_META   = 'gl_lp_settings'; // array of settings
const GL_LP_STATS  = '_gl_lp_stats';   // array of counters
const GL_LP_ON     = 'gl_lp_on';       // 'yes' when enabled (separate key so it is queryable)
const GL_LP_REWRITE_VERSION = '1';

add_filter('is_protected_meta', function ($protected, $key) {
    if (in_array($key, [GL_LP_META, GL_LP_ON], true) && is_admin() && !wp_doing_ajax()) {
        return true;
    }
    return $protected;
}, 10, 2);

/* -------------------------------------------------------------------------
 * Settings
 * ---------------------------------------------------------------------- */

function gl_lp_defaults() {
    return [
        'headline'    => '',
        'tagline'     => '',
        'eyebrow'     => '',
        'accent'      => '',
        'mode'        => 'light',   // light | dark
        'benefits'    => '',        // one per line, "عنوان | توضیح"
        'faq'         => '',        // one per line, "سوال | جواب"
        'video'       => '',
        'offer'       => '',        // badge text
        'countdown'   => '',        // Y-m-d H:i (Tehran)
        'coupon'      => '',
        'cta'         => '',
        'final_title' => '',
    ];
}

function gl_lp_settings($product_id) {
    $saved = get_post_meta($product_id, GL_LP_META, true);
    return wp_parse_args(is_array($saved) ? $saved : [], gl_lp_defaults());
}

function gl_lp_enabled($product_id) {
    return get_post_meta($product_id, GL_LP_ON, true) === 'yes';
}

function gl_lp_url($product_id, $args = []) {
    $slug = get_post_field('post_name', $product_id);
    $url  = home_url('/l/' . rawurlencode(urldecode($slug)) . '/');
    return $args ? add_query_arg($args, $url) : $url;
}

/* -------------------------------------------------------------------------
 * Routing
 * ---------------------------------------------------------------------- */

add_action('init', function () {
    add_rewrite_rule('^l/([^/]+)/?$', 'index.php?post_type=product&product=$matches[1]&name=$matches[1]&gl_landing=1', 'top');
    if (get_option('gl_lp_rewrite') !== GL_LP_REWRITE_VERSION) {
        flush_rewrite_rules(false);
        update_option('gl_lp_rewrite', GL_LP_REWRITE_VERSION, true);
    }
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'gl_landing';
    return $vars;
});

function gl_is_landing() {
    return (bool) get_query_var('gl_landing') && is_singular('product');
}

add_action('template_redirect', function () {
    if (!get_query_var('gl_landing')) {
        return;
    }
    if (!is_singular('product')) {
        return; // core shows 404
    }
    $id = get_queried_object_id();
    if (!gl_lp_enabled($id) && !current_user_can('edit_post', $id)) {
        wp_safe_redirect(get_permalink($id), 302);
        exit;
    }
    // Never let a cache serve a stale cart/nonce on landings.
    nocache_headers();
}, 1);

add_filter('template_include', function ($template) {
    if (gl_is_landing()) {
        $landing = get_template_directory() . '/landing-product.php';
        if (file_exists($landing)) {
            return $landing;
        }
    }
    return $template;
}, 99);

/** Landing is its own light page: drop the theme bundle and WooCommerce CSS. */
add_action('wp_enqueue_scripts', function () {
    if (!gl_is_landing()) {
        return;
    }
    foreach (['bluebox-style', 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen', 'woocommerce-inline', 'wc-blocks-style', 'photoswipe', 'photoswipe-default-skin'] as $h) {
        wp_dequeue_style($h);
    }
    foreach (['bluebox-script', 'single-product', 'wc-single-product', 'zoom', 'flexslider', 'photoswipe', 'photoswipe-ui-default', 'wc-add-to-cart-variation', 'comment-reply'] as $h) {
        wp_dequeue_script($h);
    }
    $dir = get_template_directory_uri();
    wp_enqueue_style('gl-landing', $dir . '/css/landing.css', [], bluebox_asset_ver('css/landing.css'));
    wp_enqueue_script('gl-landing', $dir . '/js/landing.js', [], bluebox_asset_ver('js/landing.js'), ['in_footer' => true, 'strategy' => 'defer']);
}, 200);

/* -------------------------------------------------------------------------
 * Data for the template
 * ---------------------------------------------------------------------- */

/** Toman price formatted with Persian digits. */
function gl_lp_money($amount) {
    return gl_fa_num(number_format((float) $amount)) . ' <small>تومان</small>';
}

if (!function_exists('gl_fa_num')) {
    function gl_fa_num($value) {
        return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }
}

/** "a | b" lines → [[a, b], …] */
function gl_lp_pairs($text) {
    $rows = [];
    foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts  = array_map('trim', explode('|', $line, 2));
        $rows[] = [$parts[0], $parts[1] ?? ''];
    }
    return $rows;
}

/** Hex colour for an attribute term (ACF "color" field on pa_color), or ''. */
function gl_lp_term_color($taxonomy, $term) {
    if (!function_exists('get_field') || !$term) {
        return '';
    }
    $c = get_field('color', $taxonomy . '_' . $term->term_id);
    return is_string($c) && preg_match('/^#[0-9a-f]{3,8}$/i', $c) ? $c : '';
}

/**
 * Everything the landing needs, assembled once.
 */
function gl_lp_data(WC_Product $product) {
    $id  = $product->get_id();
    $set = gl_lp_settings($id);

    // Images: featured + gallery (+ variation images later, deduped).
    $image_ids = array_values(array_unique(array_filter(array_merge([$product->get_image_id()], $product->get_gallery_image_ids()))));

    // Attributes → options for the picker (variable) or plain specs.
    $options = [];
    $specs   = [];
    foreach ($product->get_attributes() as $attr) {
        $name  = $attr->get_name();
        $label = wc_attribute_label($name, $product);
        if ($attr->is_taxonomy()) {
            $terms = wc_get_product_terms($id, $name, ['fields' => 'all']);
        } else {
            $terms = array_map(function ($v) {
                return (object) ['slug' => $v, 'name' => $v, 'term_id' => 0];
            }, $attr->get_options());
        }
        $values = [];
        foreach ($terms as $t) {
            $values[] = [
                'slug'  => $attr->is_taxonomy() ? $t->slug : $t->name,
                'name'  => $t->name,
                'color' => $attr->is_taxonomy() ? gl_lp_term_color($name, $t) : '',
            ];
        }
        if ($attr->get_variation() && $product->is_type('variable')) {
            $options[] = ['key' => 'attribute_' . sanitize_title($name), 'label' => $label, 'values' => $values];
        } elseif ($attr->get_visible()) {
            $specs[] = [$label, implode('، ', wp_list_pluck($values, 'name'))];
        }
    }

    // Variations (only for variable products).
    $variations = [];
    if ($product->is_type('variable')) {
        foreach ($product->get_available_variations('objects') as $v) {
            if (!$v || !$v->variation_is_visible()) {
                continue;
            }
            $img = $v->get_image_id();
            if ($img && !in_array($img, $image_ids, true)) {
                $image_ids[] = $img;
            }
            $attrs = [];
            foreach ($v->get_variation_attributes() as $k => $val) {
                $attrs[$k] = $val;
            }
            $variations[] = [
                'id'      => $v->get_id(),
                'attrs'   => $attrs,
                'price'   => (float) wc_get_price_to_display($v),
                'regular' => (float) wc_get_price_to_display($v, ['price' => $v->get_regular_price()]),
                'stock'   => $v->is_in_stock(),
                'max'     => $v->get_max_purchase_quantity(),
                'image'   => $img ? (int) $img : 0,
            ];
        }
    }

    // Prices.
    if ($product->is_type('variable')) {
        $price   = (float) $product->get_variation_price('min', true);
        $regular = (float) $product->get_variation_regular_price('min', true);
    } else {
        $price   = (float) wc_get_price_to_display($product);
        $regular = (float) wc_get_price_to_display($product, ['price' => $product->get_regular_price()]);
    }
    $discount = ($regular > $price && $regular > 0) ? (int) round(100 - ($price / $regular * 100)) : 0;

    // Countdown: explicit setting, else product sale end.
    $deadline = 0;
    if ($set['countdown']) {
        $dt = date_create($set['countdown'], wp_timezone());
        $deadline = $dt ? $dt->getTimestamp() : 0;
    } elseif ($product->get_date_on_sale_to()) {
        $deadline = $product->get_date_on_sale_to()->getTimestamp();
    } elseif ($product->is_type('variable')) {
        foreach ($product->get_children() as $child_id) {
            $child = wc_get_product($child_id);
            if ($child && $child->get_date_on_sale_to()) {
                $deadline = max($deadline, $child->get_date_on_sale_to()->getTimestamp());
            }
        }
    }
    if ($deadline && $deadline < time()) {
        $deadline = 0;
    }

    // Reviews (approved, newest first).
    $reviews = [];
    foreach (get_comments(['post_id' => $id, 'status' => 'approve', 'type' => 'review', 'number' => 8, 'parent' => 0]) as $c) {
        $reviews[] = [
            'name'   => $c->comment_author,
            'text'   => wp_trim_words(wp_strip_all_tags($c->comment_content), 40),
            'rating' => (int) get_comment_meta($c->comment_ID, 'rating', true),
            'date'   => function_exists('shamsi_date') ? shamsi_date('j F Y', strtotime($c->comment_date)) : get_comment_date('', $c),
        ];
    }

    // Accent: setting → first colour swatch → brand red.
    $accent = $set['accent'];
    if (!$accent) {
        foreach ($options as $o) {
            foreach ($o['values'] as $v) {
                if ($v['color'] && !in_array(strtolower($v['color']), ['#fff', '#ffffff', '#000', '#000000'], true)) {
                    $accent = $v['color'];
                    break 2;
                }
            }
        }
    }
    if (!$accent) {
        $accent = '#9d0617';
    }

    // Benefits: setting, else sensible defaults from data.
    $benefits = gl_lp_pairs($set['benefits']);
    if (!$benefits) {
        $benefits = [
            ['ارسال به سراسر ایران', 'بسته‌بندی شیک و آماده‌ی هدیه، ارسال سریع تا درِ خانه'],
            ['ضمانت تعویض ۷ روزه', 'اگر رنگ یا مدل به دلت ننشست، تعویض می‌کنیم'],
            ['دوخت و متریال باکیفیت', 'یراق‌آلات محکم و دوخت تمیز برای استفاده‌ی هرروزه'],
            ['مشاوره‌ی رنگ و استایل', 'قبل از خرید با ما مشورت کن تا بهترین ست را انتخاب کنی'],
        ];
    }

    $faq = gl_lp_pairs($set['faq']);
    if (!$faq) {
        $faq = [
            ['ابعاد و جادار بودن کیف چقدر است؟', 'ابعاد دقیق در بخش مشخصات همین صفحه آمده است. اگر می‌خواهید بدانید وسایل مشخصی (مثل گوشی، کیف پول یا تبلت) جا می‌شود یا نه، از پشتیبانی بپرسید.'],
            ['ارسال سفارش چقدر طول می‌کشد؟', 'سفارش‌ها معمولاً ظرف ۱ تا ۲ روز کاری آماده و ارسال می‌شوند و بسته به شهر شما ۲ تا ۵ روز کاری به دستتان می‌رسند.'],
            ['اگر رنگ یا مدل را نپسندیدم چه کنم؟', 'تا ۷ روز پس از دریافت، در صورت سالم و استفاده‌نشده بودن کیف و بسته‌بندی، امکان تعویض یا مرجوعی طبق قوانین فروشگاه وجود دارد.'],
            ['رنگ واقعی کیف با عکس‌ها یکی است؟', 'عکس‌ها در نور طبیعی گرفته شده‌اند تا رنگ تا حد ممکن واقعی باشد؛ ممکن است بسته به تنظیمات صفحه‌نمایش کمی تفاوت دیده شود.'],
        ];
    }

    $short = wp_strip_all_tags($product->get_short_description());
    $cats  = get_the_terms($id, 'product_cat');

    $related_ids = array_slice(array_unique(array_merge($product->get_upsell_ids(), wc_get_related_products($id, 8))), 0, 4);

    return [
        'id'          => $id,
        'set'         => $set,
        'name'        => $product->get_name(),
        'headline'    => $set['headline'] ?: $product->get_name(),
        'tagline'     => $set['tagline'] ?: ($short ? wp_trim_words($short, 30) : wp_trim_words(wp_strip_all_tags($product->get_description()), 30)),
        'eyebrow'     => $set['eyebrow'] ?: ($cats && !is_wp_error($cats) ? $cats[0]->name : 'کالکشن گیلدا'),
        'cta'         => $set['cta'] ?: 'همین حالا سفارش بده',
        'final_title' => $set['final_title'] ?: 'استایل امروزت را با ' . $product->get_name() . ' کامل کن',
        'accent'      => $accent,
        'mode'        => $set['mode'] === 'dark' ? 'dark' : 'light',
        'image_ids'   => $image_ids,
        'options'     => $options,
        'specs'       => $specs,
        'variations'  => $variations,
        'price'       => $price,
        'regular'     => $regular,
        'discount'    => $discount,
        'in_stock'    => $product->is_in_stock(),
        'deadline'    => $deadline,
        'reviews'     => $reviews,
        'rating'      => (float) $product->get_average_rating(),
        'rating_n'    => (int) $product->get_review_count(),
        'benefits'    => $benefits,
        'faq'         => $faq,
        'video'       => esc_url_raw($set['video']),
        'offer'       => $set['offer'] ?: ($discount ? gl_fa_num($discount) . '٪ تخفیف ویژه' : ''),
        'description' => $product->get_description(),
        'related'     => $related_ids,
        'permalink'   => get_permalink($id),
        'sku'         => $product->get_sku(),
        'type'        => $product->get_type(),
    ];
}

/* -------------------------------------------------------------------------
 * Purchase from landing → straight to checkout, tagged with the source
 * ---------------------------------------------------------------------- */

function gl_lp_clean_source($s) {
    $s = strtolower(sanitize_key((string) $s));
    return $s !== '' ? substr($s, 0, 32) : 'direct';
}

add_filter('woocommerce_add_cart_item_data', function ($data, $product_id) {
    if (!empty($_POST['gl_lp']) && (int) $_POST['gl_lp'] === (int) $product_id) {
        $data['gl_lp'] = [
            'id'     => (int) $product_id,
            'source' => gl_lp_clean_source(wp_unslash($_POST['gl_lp_source'] ?? '')),
        ];
    }
    return $data;
}, 10, 2);

add_filter('woocommerce_add_to_cart_redirect', function ($url) {
    if (!empty($_POST['gl_lp'])) {
        $id  = (int) $_POST['gl_lp'];
        $set = gl_lp_settings($id);
        if ($set['coupon'] && WC()->cart && !WC()->cart->has_discount($set['coupon'])) {
            WC()->cart->apply_coupon(wc_format_coupon_code($set['coupon']));
        }
        gl_lp_bump($id, 'cart', gl_lp_clean_source(wp_unslash($_POST['gl_lp_source'] ?? '')));
        return wc_get_checkout_url();
    }
    return $url;
}, 20);

add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_key, $values) {
    if (!empty($values['gl_lp']['id'])) {
        $item->add_meta_data('_gl_lp', $values['gl_lp'], true);
    }
}, 10, 3);

/** Count landing orders + revenue once, when the order is placed. */
add_action('woocommerce_checkout_order_processed', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order || $order->get_meta('_gl_lp_counted')) {
        return;
    }
    $hit = false;
    foreach ($order->get_items() as $item) {
        $lp = $item->get_meta('_gl_lp');
        if (is_array($lp) && !empty($lp['id'])) {
            gl_lp_bump((int) $lp['id'], 'order', $lp['source'] ?? 'direct', (float) $item->get_total());
            $hit = true;
        }
    }
    if ($hit) {
        $order->update_meta_data('_gl_lp_counted', 1);
        $order->save();
    }
});

/* -------------------------------------------------------------------------
 * Stats
 * ---------------------------------------------------------------------- */

/**
 * Increment a counter. $type: view | cta | cart | order. Revenue is tracked for orders.
 */
function gl_lp_bump($product_id, $type, $source = 'direct', $revenue = 0.0) {
    $stats = get_post_meta($product_id, GL_LP_STATS, true);
    $stats = is_array($stats) ? $stats : [];
    $source = gl_lp_clean_source($source);
    $stats[$type]['total']   = ($stats[$type]['total'] ?? 0) + 1;
    $stats[$type][$source]   = ($stats[$type][$source] ?? 0) + 1;
    if ($revenue) {
        $stats['revenue']['total'] = ($stats['revenue']['total'] ?? 0) + $revenue;
        $stats['revenue'][$source] = ($stats['revenue'][$source] ?? 0) + $revenue;
    }
    $day = wp_date('Y-m-d');
    $stats['days'][$day][$type] = ($stats['days'][$day][$type] ?? 0) + 1;
    // keep 60 days of daily history
    if (count($stats['days']) > 60) {
        ksort($stats['days']);
        $stats['days'] = array_slice($stats['days'], -60, null, true);
    }
    update_post_meta($product_id, GL_LP_STATS, $stats);
}

function gl_lp_stats($product_id) {
    $s = get_post_meta($product_id, GL_LP_STATS, true);
    return is_array($s) ? $s : [];
}

/** Beacon endpoint for views / CTA clicks (public, rate-limited, no PII stored). */
add_action('rest_api_init', function () {
    register_rest_route('gilllda/v1', '/lp-event', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'args'                => [
            'id'     => ['type' => 'integer', 'required' => true],
            'type'   => ['type' => 'string', 'required' => true, 'enum' => ['view', 'cta']],
            'source' => ['type' => 'string', 'required' => false],
        ],
        'callback'            => function (WP_REST_Request $r) {
            $id = (int) $r['id'];
            if (get_post_type($id) !== 'product' || !gl_lp_enabled($id)) {
                return new WP_REST_Response(['ok' => false], 404);
            }
            $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
            if ($ua === '' || preg_match('/bot|crawl|spider|preview|facebookexternalhit|telegrambot|whatsapp/', $ua)) {
                return ['ok' => true];
            }
            $key = 'gl_lp_' . md5($id . '|' . $r['type'] . '|' . ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . $ua);
            if (get_transient($key)) {
                return ['ok' => true];
            }
            set_transient($key, 1, 30 * MINUTE_IN_SECONDS);
            gl_lp_bump($id, $r['type'], $r['source'] ?: 'direct');
            return ['ok' => true];
        },
    ]);
});

/* -------------------------------------------------------------------------
 * SEO: landing shares the product canonical; tell Rank Math the URL is the product.
 * ---------------------------------------------------------------------- */

add_filter('rank_math/frontend/canonical', function ($canonical) {
    return gl_is_landing() ? get_permalink(get_queried_object_id()) : $canonical;
});

add_filter('get_canonical_url', function ($canonical, $post) {
    return gl_is_landing() ? get_permalink($post) : $canonical;
}, 10, 2);

add_filter('body_class', function ($classes) {
    if (gl_is_landing()) {
        $classes[] = 'gl-landing-page';
    }
    return $classes;
});
