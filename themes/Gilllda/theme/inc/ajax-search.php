<?php
/**
 * Site search: normalised product index, instant-search REST endpoint, search page query.
 *
 * - GET /wp-json/gilllda/v1/search?q=…  (public, read-only, cached per normalised keyword)
 *   → { q, products[≤5], cats[≤5], posts[≤5] }
 * - Products are matched in PHP against a small cached index (name, SKU + variation SKUs,
 *   categories, tags, pa_color names) after Persian/Arabic normalisation, so «مشکی», «كيف»,
 *   «۱۲» and partial words all work. The index is rebuilt on product/term changes.
 * - Main search query (search.php) lists magazine articles; products come from
 *   gl_search_product_ids() so both use the same matching.
 */

if (!defined('ABSPATH')) {
    exit;
}

const GL_SEARCH_INDEX_KEY = 'gl_search_index_v1';
const GL_SEARCH_MAX_INDEX = 500;

/** Normalise text for matching: Arabic → Persian letters, digits → Latin, no ZWNJ/diacritics, lowercase. */
function gl_search_norm($text) {
    static $map = null;
    if (null === $map) {
        $map = [
            'ي' => 'ی', 'ى' => 'ی', 'ئ' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه',
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ؤ' => 'و',
            "\u{200C}" => '', "\u{200D}" => '', 'ـ' => '',
        ];
        foreach (['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'] as $i => $d) {
            $map[$d] = (string) $i;
        }
        foreach (['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'] as $i => $d) {
            $map[$d] = (string) $i;
        }
    }
    $text = strtr(wp_strip_all_tags((string) $text), $map);
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text);
    $text = mb_strtolower((string) $text, 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', (string) $text);
    return trim((string) preg_replace('/\s+/u', ' ', (string) $text));
}

/** Light normalisation for WordPress' own LIKE search (keeps spacing/ZWNJ, fixes Arabic letters). */
function gl_search_fa_chars($text) {
    return strtr((string) $text, ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک']);
}

/** Validate a raw keyword: returns the trimmed keyword (2–64 chars) or ''. */
function gl_search_clean_keyword($raw) {
    $q = trim(sanitize_text_field(wp_unslash((string) $raw)));
    $len = mb_strlen($q, 'UTF-8');
    if ($len < 2) {
        return '';
    }
    if ($len > 64) {
        $q = trim(mb_substr($q, 0, 64, 'UTF-8'));
    }
    return '' === gl_search_norm($q) ? '' : $q;
}

/** ACF `color` hex of a pa_color term ('' when not set). */
function gl_search_term_hex($term) {
    if (!function_exists('get_field') || !$term) {
        return '';
    }
    $hex = get_field('color', $term->taxonomy . '_' . $term->term_id);
    return is_string($hex) && preg_match('/^#[0-9a-f]{3,8}$/i', trim($hex)) ? trim($hex) : '';
}

/** Cached product + product-category index. */
function gl_search_index() {
    $cached = get_transient(GL_SEARCH_INDEX_KEY);
    if (is_array($cached) && isset($cached['products'], $cached['cats'])) {
        return $cached;
    }
    $index = ['products' => [], 'cats' => []];
    if (!function_exists('wc_get_products')) {
        return $index;
    }

    $products = wc_get_products([
        'status'     => 'publish',
        'visibility' => 'search',
        'limit'      => GL_SEARCH_MAX_INDEX,
        'orderby'    => 'menu_order',
        'order'      => 'ASC',
    ]);

    // Variation SKUs per parent, one query.
    global $wpdb;
    $child_skus = [];
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT p.post_parent AS parent, pm.meta_value AS sku FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
         WHERE p.post_type = %s AND p.post_status = %s AND pm.meta_value <> ''",
        '_sku', 'product_variation', 'publish'
    ));
    foreach ((array) $rows as $row) {
        $child_skus[(int) $row->parent][] = (string) $row->sku;
    }

    foreach ($products as $product) {
        $id = $product->get_id();
        if ($product->is_type('variable')) {
            $price   = (float) $product->get_variation_price('min', true);
            $regular = (float) $product->get_variation_regular_price('min', true);
        } else {
            $price   = (float) wc_get_price_to_display($product);
            $regular = (float) wc_get_price_to_display($product, ['price' => $product->get_regular_price()]);
        }
        $colors = [];
        $color_names = [];
        $terms = wc_get_product_terms($id, 'pa_color', ['fields' => 'all']);
        if (!is_wp_error($terms)) {
            foreach ($terms as $t) {
                $colors[] = ['n' => $t->name, 'h' => gl_search_term_hex($t)];
                $color_names[] = $t->name;
            }
        }
        $cats = wp_get_post_terms($id, 'product_cat', ['fields' => 'names']);
        $tags = wp_get_post_terms($id, 'product_tag', ['fields' => 'names']);
        $cats = is_wp_error($cats) ? [] : $cats;
        $tags = is_wp_error($tags) ? [] : $tags;
        $image_id = $product->get_image_id();

        $index['products'][] = [
            'id'       => $id,
            'name'     => $product->get_name(),
            'url'      => $product->get_permalink(),
            'img'      => $image_id ? (string) wp_get_attachment_image_url($image_id, 'thumbnail') : '',
            'price'    => $price,
            'regular'  => $regular > $price ? $regular : 0,
            'in_stock' => $product->is_in_stock(),
            'colors'   => $colors,
            'cat'      => $cats[0] ?? '',
            'hay_name' => gl_search_norm($product->get_name()),
            'hay'      => gl_search_norm(implode(' ', array_merge(
                [$product->get_sku(), $product->get_slug()],
                $child_skus[$id] ?? [],
                $cats,
                $tags,
                $color_names
            ))),
        ];
    }

    $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 200]);
    if (!is_wp_error($terms)) {
        foreach ($terms as $t) {
            if ('uncategorized' === $t->slug) {
                continue;
            }
            $link = get_term_link($t);
            if (is_wp_error($link)) {
                continue;
            }
            $index['cats'][] = ['name' => $t->name, 'url' => $link, 'count' => (int) $t->count, 'hay' => gl_search_norm($t->name)];
        }
    }

    set_transient(GL_SEARCH_INDEX_KEY, $index, 12 * HOUR_IN_SECONDS);
    return $index;
}

/** Score one indexed product against normalised tokens (0 = no match). */
function gl_search_score($item, $tokens, $norm) {
    $score = 0;
    foreach ($tokens as $tok) {
        $pos = mb_strpos($item['hay_name'], $tok);
        if (false !== $pos) {
            $score += 0 === $pos || ' ' === mb_substr($item['hay_name'], $pos - 1, 1) ? 4 : 3;
        } elseif (false !== mb_strpos($item['hay'], $tok)) {
            $score += 1;
        } else {
            return 0;
        }
    }
    if ($item['hay_name'] === $norm) {
        $score += 10;
    }
    return $score + ($item['in_stock'] ? 0.5 : 0);
}

/** Matching indexed products (best first). */
function gl_search_products($keyword, $limit = 5) {
    $norm = gl_search_norm($keyword);
    if ('' === $norm) {
        return [];
    }
    $tokens = array_slice(array_unique(explode(' ', $norm)), 0, 6);
    $hits = [];
    foreach (gl_search_index()['products'] as $item) {
        $s = gl_search_score($item, $tokens, $norm);
        if ($s > 0) {
            $item['_s'] = $s;
            $hits[] = $item;
        }
    }
    usort($hits, function ($a, $b) {
        return $b['_s'] <=> $a['_s'];
    });
    return array_slice($hits, 0, max(1, (int) $limit));
}

/** IDs of matching products, for the full results page. */
function gl_search_product_ids($keyword, $limit = 48) {
    return array_map(function ($p) {
        return (int) $p['id'];
    }, gl_search_products($keyword, $limit));
}

/** Matching product categories. */
function gl_search_cats($keyword, $limit = 5) {
    $norm = gl_search_norm($keyword);
    if ('' === $norm) {
        return [];
    }
    $tokens = explode(' ', $norm);
    $out = [];
    foreach (gl_search_index()['cats'] as $cat) {
        foreach ($tokens as $tok) {
            if (false === mb_strpos($cat['hay'], $tok)) {
                continue 2;
            }
        }
        unset($cat['hay']);
        $out[] = $cat;
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}

/** Popular suggestions (top product categories + top colours) for empty states. */
function gl_search_popular() {
    $cached = get_transient('gl_search_popular_v1');
    if (is_array($cached)) {
        return $cached;
    }
    $out = ['cats' => [], 'colors' => []];
    $cats = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 7]);
    if (!is_wp_error($cats)) {
        foreach ($cats as $t) {
            $link = get_term_link($t);
            if ('uncategorized' !== $t->slug && !is_wp_error($link)) {
                $out['cats'][] = ['name' => $t->name, 'url' => $link];
            }
        }
    }
    if (taxonomy_exists('pa_color')) {
        $colors = get_terms(['taxonomy' => 'pa_color', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 6]);
        if (!is_wp_error($colors)) {
            foreach ($colors as $t) {
                $out['colors'][] = ['name' => $t->name, 'hex' => gl_search_term_hex($t)];
            }
        }
    }
    set_transient('gl_search_popular_v1', $out, 12 * HOUR_IN_SECONDS);
    return $out;
}

/** Instant-search payload for a keyword (cached ~10 min per normalised keyword). */
function gl_search_payload($keyword) {
    $norm = gl_search_norm($keyword);
    $key  = 'gl_srch_' . md5((int) get_option('gl_search_ver', 1) . '|' . $norm);
    $hit  = get_transient($key);
    if (is_array($hit)) {
        return $hit;
    }

    $products = array_map(function ($p) {
        return [
            'id'       => (int) $p['id'],
            'name'     => $p['name'],
            'url'      => $p['url'],
            'img'      => $p['img'],
            'price'    => (float) $p['price'],
            'regular'  => (float) $p['regular'],
            'in_stock' => (bool) $p['in_stock'],
            'colors'   => array_slice($p['colors'], 0, 5),
            'cat'      => $p['cat'],
        ];
    }, gl_search_products($keyword, 5));

    $posts = [];
    $pq = new WP_Query([
        'post_type'              => 'post',
        'post_status'            => 'publish',
        's'                      => gl_search_fa_chars($keyword),
        'posts_per_page'         => 5,
        'no_found_rows'          => true,
        'ignore_sticky_posts'    => true,
        'update_post_term_cache' => true,
        'suppress_filters'       => false,
    ]);
    foreach ($pq->posts as $post) {
        $cat = get_the_category($post->ID);
        $posts[] = [
            'title' => html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
            'url'   => get_permalink($post),
            'img'   => (string) get_the_post_thumbnail_url($post, 'thumbnail'),
            'cat'   => $cat ? $cat[0]->name : '',
        ];
    }

    $data = [
        'q'        => $keyword,
        'products' => $products,
        'cats'     => gl_search_cats($keyword, 5),
        'posts'    => $posts,
    ];
    set_transient($key, $data, 10 * MINUTE_IN_SECONDS);
    return $data;
}

add_action('rest_api_init', function () {
    register_rest_route('gilllda/v1', '/search', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'args'                => [
            'q' => ['type' => 'string', 'required' => true],
        ],
        'callback'            => function (WP_REST_Request $request) {
            $q = gl_search_clean_keyword($request->get_param('q'));
            if ('' === $q) {
                return new WP_REST_Response(['q' => '', 'products' => [], 'cats' => [], 'posts' => []], 200);
            }
            $res = new WP_REST_Response(gl_search_payload($q), 200);
            $res->header('Cache-Control', 'public, max-age=300');
            return $res;
        },
    ]);
});

/** Bust caches when catalogue / articles change. */
function gl_search_flush() {
    delete_transient(GL_SEARCH_INDEX_KEY);
    delete_transient('gl_search_popular_v1');
    update_option('gl_search_ver', (int) get_option('gl_search_ver', 1) + 1, false);
}
add_action('save_post_product', 'gl_search_flush');
add_action('save_post_post', 'gl_search_flush');
add_action('woocommerce_product_set_stock_status', 'gl_search_flush');
add_action('woocommerce_variation_set_stock_status', 'gl_search_flush');
add_action('deleted_post', function ($id, $post = null) {
    if ($post && in_array($post->post_type, ['product', 'post'], true)) {
        gl_search_flush();
    }
}, 10, 2);
foreach (['created_term', 'edited_term', 'delete_term'] as $gl_hook) {
    add_action($gl_hook, function ($term_id, $tt_id = 0, $taxonomy = '') {
        if (in_array($taxonomy, ['product_cat', 'product_tag', 'pa_color'], true)) {
            gl_search_flush();
        }
    }, 10, 3);
}
unset($gl_hook);

/**
 * Main search query = magazine articles (products are rendered separately in search.php).
 * Runs after the legacy product-only filter. An explicit ?post_type=… is left alone.
 */
add_action('pre_get_posts', function ($query) {
    if (is_admin() || !$query->is_main_query() || !$query->is_search() || isset($_GET['post_type'])) { // phpcs:ignore WordPress.Security.NonceVerification
        return;
    }
    $s = (string) $query->get('s');
    if (mb_strlen($s, 'UTF-8') > 64) {
        $s = mb_substr($s, 0, 64, 'UTF-8');
    }
    $query->set('s', gl_search_fa_chars($s));
    $query->set('post_type', 'post');
    $query->set('posts_per_page', 9);
    $query->set('ignore_sticky_posts', true);
}, 20);

/** Front-end config for javascript/search.js + compare.js. */
add_action('wp_enqueue_scripts', function () {
    $compare = get_page_by_path('compare');
    wp_add_inline_script('bluebox-script', 'window.glCfg=' . wp_json_encode([
        'rest'       => esc_url_raw(rest_url('gilllda/v1/')),
        'home'       => esc_url_raw(home_url('/')),
        'compareUrl' => esc_url_raw($compare ? get_permalink($compare) : home_url('/compare/')),
        'shopUrl'    => esc_url_raw(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')),
    ]) . ';', 'before');
}, 20);
