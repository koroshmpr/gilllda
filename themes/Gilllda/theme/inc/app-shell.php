<?php
/**
 * App-shell behaviour: instant-feeling navigation and a light first-visit splash.
 *
 * - <html class="js"> + safety net so reveal animations never hide content if JS fails
 * - Speculation Rules: prefetch internal pages on hover/touch (moderate) → near-instant clicks
 * - theme-color + mobile web-app meta
 *
 * @package bluebox
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', function () {
    if (function_exists('gl_is_landing') && gl_is_landing()) {
        return;
    }
    ?>
    <script>document.documentElement.classList.add('js');setTimeout(function(){if(!window.glReveal){document.documentElement.classList.remove('js');}},4000);</script>
    <meta name="theme-color" content="#102542">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <?php
}, 1);

/**
 * WordPress 6.8+ prints speculation rules (conservative prefetch by default).
 * Moderate = prefetch on hover (desktop) / pointer-down (mobile). Cart, checkout,
 * account and add-to-cart URLs are excluded so nothing stateful is prefetched.
 */
add_filter('wp_speculation_rules_configuration', function ($config) {
    if (is_user_logged_in() && current_user_can('edit_posts')) {
        return $config; // keep editors on the safe default
    }
    return ['mode' => 'prefetch', 'eagerness' => 'moderate'];
});

add_filter('wp_speculation_rules_href_exclude_paths', function ($paths) {
    return array_merge($paths, ['/cart/*', '/checkout/*', '/my-account/*', '/login/*', '/*\\?*add-to-cart=*', '/*\\?*remove_item=*', '/l/*']);
});
