<?php
/**
 * SEO fixes enforced in code (independent of Rank Math UI settings).
 *
 * @package bluebox
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rank Math JSON-LD cleanup:
 * - home / shop / product archives must not describe themselves as an Article written by a Person
 * - shop & product categories are CollectionPage
 * - Product.name without the " - site name" title suffix
 */
add_filter('rank_math/json_ld', function ($data) {
    if (!is_array($data)) {
        return $data;
    }
    $is_listing = is_front_page() || (function_exists('is_shop') && (is_shop() || is_product_taxonomy())) || is_home();

    foreach ($data as $key => $entity) {
        if (!is_array($entity) || empty($entity['@type'])) {
            continue;
        }
        $types = (array) $entity['@type'];

        if ($is_listing && array_intersect($types, ['Article', 'BlogPosting', 'NewsArticle'])) {
            unset($data[$key]);
            continue;
        }
        if ((is_front_page() || (function_exists('is_shop') && is_shop())) && in_array('Person', $types, true)) {
            unset($data[$key]);
            continue;
        }
        if (in_array('WebPage', $types, true) && function_exists('is_shop') && (is_shop() || is_product_taxonomy())) {
            $data[$key]['@type'] = 'CollectionPage';
        }
        if (in_array('Product', $types, true) && is_singular('product')) {
            $product = wc_get_product(get_queried_object_id());
            if ($product) {
                $data[$key]['name'] = $product->get_name();
                if (empty($data[$key]['sku']) && $product->get_sku()) {
                    $data[$key]['sku'] = $product->get_sku();
                }
                if ($product->get_review_count() > 0 && empty($data[$key]['aggregateRating'])) {
                    $data[$key]['aggregateRating'] = [
                        '@type'       => 'AggregateRating',
                        'ratingValue' => (float) $product->get_average_rating(),
                        'reviewCount' => (int) $product->get_review_count(),
                    ];
                }
            }
        }
    }
    return $data;
}, 99);

/** Keep utility pages out of the sitemap (they are noindex / redirect anyway). */
add_filter('rank_math/sitemap/posts_to_exclude', function ($ids) {
    $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string) $ids)));
    if (function_exists('wc_get_page_id')) {
        foreach (['cart', 'checkout', 'myaccount'] as $page) {
            $id = wc_get_page_id($page);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
    }
    foreach (['login', 'compare'] as $slug) {
        $p = get_page_by_path($slug);
        if ($p) {
            $ids[] = $p->ID;
        }
    }
    return array_values(array_unique($ids));
});

/**
 * Keyword-led default titles/descriptions for products and product categories when no
 * custom Rank Math value is set (the API refuses to edit those fields).
 *   product:  «کیف دستی زنانه دنیز مینی | خرید آنلاین از گیلدا»
 *   category: «خرید کیف دوشی زنانه | مدل‌های جدید و قیمت روز | گیلدا»
 */
function gl_seo_product_cat_name($product_id) {
    $terms = get_the_terms($product_id, 'product_cat');
    if (!$terms || is_wp_error($terms)) {
        return 'کیف زنانه';
    }
    $name = $terms[0]->name;
    return $name === 'کوله پشتی' ? 'کوله پشتی زنانه' : $name;
}

add_filter('rank_math/frontend/title', function ($title) {
    if (is_singular('product')) {
        $id = get_queried_object_id();
        if (!get_post_meta($id, 'rank_math_title', true)) {
            $name = get_the_title($id);
            $cat  = gl_seo_product_cat_name($id);
            return (mb_strpos($name, 'کیف') === 0 || mb_strpos($name, 'کوله') === 0 ? $name : $cat . ' ' . $name) . ' | خرید آنلاین از گیلدا';
        }
    } elseif (function_exists('is_product_category') && is_product_category()) {
        $term = get_queried_object();
        if ($term && !get_term_meta($term->term_id, 'rank_math_title', true)) {
            return 'خرید ' . $term->name . ' | مدل‌های جدید و قیمت روز | گیلدا';
        }
    }
    return $title;
}, 20);

add_filter('rank_math/frontend/description', function ($desc) {
    if (is_singular('product')) {
        $id = get_queried_object_id();
        if (!get_post_meta($id, 'rank_math_description', true)) {
            $product = wc_get_product($id);
            if ($product) {
                $short = wp_strip_all_tags($product->get_short_description() ?: $product->get_description());
                $text  = 'خرید ' . gl_seo_product_cat_name($id) . ' ' . $product->get_name() . ' از گیلدا: ' . $short;
                return mb_substr(trim(preg_replace('/\s+/u', ' ', $text)), 0, 155);
            }
        }
    }
    return $desc;
}, 20);
