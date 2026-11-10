<?php
/**
 * «مجله گیلدا» — blog helpers.
 *
 * Post meta used by single.php (all plain keys, editable via REST/MCP):
 *   gl_tldr         2–3 sentence summary
 *   gl_takeaways    key takeaways, one per line
 *   gl_series       series name (same string on every part)
 *   gl_series_part  part number (1..n)
 * ACF (existing): faq_list (question/answer repeater), related_product (relationship → products)
 *
 * @package bluebox
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Reading time in minutes (Persian ≈ 180 wpm). */
function gl_reading_minutes($post_id = null) {
    $text  = wp_strip_all_tags(strip_shortcodes(get_post_field('post_content', $post_id ?: get_the_ID())));
    $words = count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY));
    return max(1, (int) ceil($words / 180));
}

/** Non-empty trimmed lines of a textarea meta. */
function gl_meta_lines($post_id, $key) {
    $raw = (string) get_post_meta($post_id, $key, true);
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw))));
}

/**
 * Series info for a post, or null.
 * Returns: name, parts (ordered post IDs), index (0-based), number, total, prev, next.
 */
function gl_series_info($post_id) {
    $name = trim((string) get_post_meta($post_id, 'gl_series', true));
    if ($name === '') {
        return null;
    }
    $parts = gl_series_parts($name);
    if (count($parts) < 1) {
        return null;
    }
    $index = array_search((int) $post_id, $parts, true);
    if ($index === false) {
        return null;
    }
    return [
        'name'   => $name,
        'parts'  => $parts,
        'index'  => $index,
        'number' => $index + 1,
        'total'  => count($parts),
        'prev'   => $parts[$index - 1] ?? 0,
        'next'   => $parts[$index + 1] ?? 0,
    ];
}

/** Ordered published post IDs of a series (cached). */
function gl_series_parts($name) {
    $key   = 'gl_series_' . md5($name);
    $parts = get_transient($key);
    if (is_array($parts)) {
        return $parts;
    }
    $ids = get_posts([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'fields'         => 'ids',
        'meta_key'       => 'gl_series',
        'meta_value'     => $name,
        'no_found_rows'  => true,
    ]);
    usort($ids, function ($a, $b) {
        $pa = (int) get_post_meta($a, 'gl_series_part', true) ?: 999;
        $pb = (int) get_post_meta($b, 'gl_series_part', true) ?: 999;
        return $pa === $pb ? strcmp(get_post_field('post_date', $a), get_post_field('post_date', $b)) : $pa <=> $pb;
    });
    $parts = array_map('intval', $ids);
    set_transient($key, $parts, DAY_IN_SECONDS);
    return $parts;
}

/** All series: [name => ordered IDs], biggest first (cached). */
function gl_all_series() {
    $all = get_transient('gl_series_all');
    if (is_array($all)) {
        return $all;
    }
    global $wpdb;
    $names = $wpdb->get_col(
        "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = 'gl_series' AND pm.meta_value <> '' AND p.post_status = 'publish' AND p.post_type = 'post'"
    );
    $all = [];
    foreach ($names as $n) {
        $all[$n] = gl_series_parts($n);
    }
    uasort($all, function ($a, $b) {
        return count($b) <=> count($a);
    });
    set_transient('gl_series_all', $all, DAY_IN_SECONDS);
    return $all;
}

/** Invalidate series caches whenever a post or its series meta changes. */
function gl_series_flush() {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_gl\\_series\\_%' OR option_name LIKE '\\_transient\\_timeout\\_gl\\_series\\_%'");
    delete_transient('gl_series_all');
}
add_action('save_post_post', 'gl_series_flush');
add_action('deleted_post', 'gl_series_flush');
add_action('transition_post_status', function ($new, $old, $post) {
    if ($post->post_type === 'post' && $new !== $old) {
        gl_series_flush();
    }
}, 10, 3);
foreach (['added_post_meta', 'updated_post_meta', 'deleted_post_meta'] as $hook) {
    add_action($hook, function ($meta_id, $post_id, $key) {
        if (in_array($key, ['gl_series', 'gl_series_part'], true)) {
            gl_series_flush();
        }
    }, 10, 3);
}

/**
 * Count a view before any output (the old tracker ran inside the template, after headers
 * were sent, so its cookie never stuck). Bots are skipped.
 */
add_action('template_redirect', function () {
    if (!is_singular('post') || is_preview()) {
        return;
    }
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match('/bot|crawl|spider|slurp|preview|lighthouse/', $ua)) {
        return;
    }
    $id = get_queried_object_id();
    if (isset($_COOKIE['gl_v_' . $id])) {
        return;
    }
    update_post_meta($id, 'post_views', (int) get_post_meta($id, 'post_views', true) + 1);
    setcookie('gl_v_' . $id, '1', time() + DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true);
});

/** Persian digits. */
if (!function_exists('gl_fa_num')) {
    function gl_fa_num($value) {
        return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }
}

/** Expose magazine meta in REST so editors/tools can manage it. */
add_action('init', function () {
    foreach (['gl_tldr', 'gl_takeaways', 'gl_series'] as $key) {
        register_post_meta('post', $key, ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'auth_callback' => function () {
            return current_user_can('edit_posts');
        }]);
    }
    register_post_meta('post', 'gl_series_part', ['type' => 'integer', 'single' => true, 'show_in_rest' => true, 'auth_callback' => function () {
        return current_user_can('edit_posts');
    }]);
});

/** Simple editor box for the magazine fields (classic editor). */
add_action('add_meta_boxes_post', function () {
    add_meta_box('gl-mag', 'مجله گیلدا: خلاصه، نکات و سری', function ($post) {
        wp_nonce_field('gl_mag_save', 'gl_mag_nonce');
        $v = function ($k) use ($post) {
            return get_post_meta($post->ID, $k, true);
        };
        $series = array_keys(gl_all_series());
        ?>
        <p><label><strong>خلاصه در ۳۰ ثانیه</strong><br><textarea name="gl_tldr" rows="3" style="width:100%"><?= esc_textarea($v('gl_tldr')); ?></textarea></label></p>
        <p><label><strong>نکات کلیدی</strong> (هر خط یک نکته)<br><textarea name="gl_takeaways" rows="4" style="width:100%"><?= esc_textarea($v('gl_takeaways')); ?></textarea></label></p>
        <p style="display:flex;gap:12px;flex-wrap:wrap">
            <label style="flex:1;min-width:220px"><strong>نام سری (پرونده)</strong><br>
                <input type="text" name="gl_series" list="gl-series-list" value="<?= esc_attr($v('gl_series')); ?>" style="width:100%" placeholder="مثلاً راهنمای خرید کیف زنانه">
                <datalist id="gl-series-list"><?php foreach ($series as $s) : ?><option value="<?= esc_attr($s); ?>"><?php endforeach; ?></datalist>
            </label>
            <label style="width:120px"><strong>قسمت</strong><br><input type="number" min="1" name="gl_series_part" value="<?= esc_attr($v('gl_series_part')); ?>" style="width:100%"></label>
        </p>
        <?php
    }, 'post', 'normal', 'high');
});

add_action('save_post_post', function ($post_id) {
    if (!isset($_POST['gl_mag_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gl_mag_nonce'])), 'gl_mag_save') || !current_user_can('edit_post', $post_id)) {
        return;
    }
    update_post_meta($post_id, 'gl_tldr', sanitize_textarea_field(wp_unslash($_POST['gl_tldr'] ?? '')));
    update_post_meta($post_id, 'gl_takeaways', sanitize_textarea_field(wp_unslash($_POST['gl_takeaways'] ?? '')));
    update_post_meta($post_id, 'gl_series', sanitize_text_field(wp_unslash($_POST['gl_series'] ?? '')));
    $part = absint($_POST['gl_series_part'] ?? 0);
    $part ? update_post_meta($post_id, 'gl_series_part', $part) : delete_post_meta($post_id, 'gl_series_part');
}, 5);

/** IDs of the newest posts shown in the blog-home featured block. */
function gl_featured_post_ids($n = 4) {
    static $ids = null;
    if ($ids === null) {
        $ids = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => $n, 'fields' => 'ids', 'no_found_rows' => true, 'suppress_filters' => true]);
    }
    return $ids;
}

/**
 * Blog home main query: 9 per page, never repeating the featured block, so pagination
 * stays consistent (page 2 continues where page 1's grid stopped).
 */
add_action('pre_get_posts', function ($q) {
    if (is_admin() || !$q->is_main_query()) {
        return;
    }
    if ($q->is_home()) {
        $q->set('posts_per_page', 9);
        $q->set('post__not_in', gl_featured_post_ids());
        $q->set('ignore_sticky_posts', true);
    } elseif ($q->is_category() || $q->is_tag()) {
        $q->set('posts_per_page', 12);
    }
});

/** Only decorate headings of the article being read (not product descriptions, feeds, REST). */
add_action('wp', function () {
    if (!is_singular('post') && has_filter('the_content', 'auto_id_headings')) {
        remove_filter('the_content', 'auto_id_headings');
    }
});
