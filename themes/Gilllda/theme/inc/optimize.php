<?php
function optimize_site() {
    // 1. Disable Emojis (Perfect as is)
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', function ( $plugins ) {
        return array_diff( $plugins, [ 'wpemoji' ] );
    } );
    add_filter( 'wp_resource_hints', function ( $urls, $relation_type ) {
        if ( 'dns-prefetch' === $relation_type ) {
            $emoji_url = 'https://s.w.org/images/core/emoji/';
            $urls = array_filter( $urls, function ( $url ) use ( $emoji_url ) {
                return strpos( $url, $emoji_url ) === false;
            } );
        }
        return $urls;
    }, 10, 2 );

    // 2. Remove jQuery Migrate completely on frontend
    add_action( 'wp_default_scripts', function ( $scripts ) {
        if ( ! is_admin() ) {
            if ( isset( $scripts->registered['jquery'] ) ) {
                $scripts->registered['jquery']->deps = array_diff(
                    $scripts->registered['jquery']->deps,
                    [ 'jquery-migrate' ]
                );
            }
        }
    } );
    add_action( 'wp_enqueue_scripts', function () {
        if ( ! is_admin() ) {
            wp_dequeue_script( 'jquery-migrate' );
            wp_deregister_script( 'jquery-migrate' );
            wp_register_script( 'jquery-migrate', false, array(), false, true );
        }
    }, 999 );

    // 3. Disable Embeds & Head Junk (Perfect as is)
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    remove_action( 'wp_head', 'rest_output_link_wp_head' );
    remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
    add_filter( 'embed_oembed_discover', '__return_false' );
    add_filter( 'tiny_mce_plugins', function ( $plugins ) {
        return array_diff( $plugins, [ 'wpembed' ] );
    } );
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'wp_head', 'wlwmanifest_link' );
    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'feed_links_extra', 3 );
    remove_action( 'wp_head', 'feed_links', 2 );

    // 4. IMPROVED: Disable Dashicons for non-admins AND logged-out users
    if ( ! is_admin() && ! is_user_logged_in() ) {
        add_action( 'wp_enqueue_scripts', function () {
            wp_dequeue_style( 'dashicons' );
        } );
    }

    // 5. IMPROVED: Dequeue Block Library CSS & Global Styles
    add_action( 'wp_enqueue_scripts', function () {
        // Core WordPress block styles
        wp_dequeue_style( 'wp-block-library' );
        wp_deregister_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
        wp_deregister_style( 'wp-block-library-theme' );

        // Remove WooCommerce Block Styles
        wp_dequeue_style( 'wc-blocks-style' );
        wp_deregister_style( 'wc-blocks-style' );

        // Remove inline Global Styles CSS variables
        wp_dequeue_style( 'global-styles' );

        // CAUTION: Do not remove woocommerce-rtl unless your custom CSS fully handles RTL layouts.
        // wp_deregister_style( 'woocommerce-rtl' );
    }, 100 );
}
add_action( 'init', 'optimize_site' );

// 6. NEW: Remove massive inline SVG filters injected by WordPress 5.9+
remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );

// 7. NEW: Disable specific WooCommerce CSS on Single Product Pages
add_action( 'wp_enqueue_scripts', 'disable_woo_css_on_single_product', 99 );

function disable_woo_css_on_single_product() {
    // Check if WooCommerce is active and we are on a single product page
    if ( function_exists( 'is_product' ) && is_product() ) {

        // 1. Dequeue Core WooCommerce Styles
        // 'woocommerce-general' handles woocommerce.css and woocommerce-rtl.css
        wp_dequeue_style( 'woocommerce-general' );

        // 'woocommerce-layout' handles woocommerce-layout.css and woocommerce-layout-rtl.css
        wp_dequeue_style( 'woocommerce-layout' );

        // 'woocommerce-smallscreen' handles woocommerce-smallscreen.css and woocommerce-smallscreen-rtl.css
        wp_dequeue_style( 'woocommerce-smallscreen' );

        // 2. Dequeue PhotoSwipe Styles (Product Image Lightbox/Gallery)
        // Handles photoswipe.min.css
        wp_dequeue_style( 'photoswipe' );

        // Handles default-skin.min.css
        wp_dequeue_style( 'photoswipe-default-skin' );
    }
}

// 8. NEW: Defer WooCommerce non-critical scripts and jQuery core
add_filter('script_loader_tag', function($tag, $handle) {
    if (strpos($tag, 'sourcebuster.min.js') !== false || strpos($tag, 'order-attribution.min.js') !== false) {
        return str_replace(' src', ' defer="defer" src', $tag);
    }
    if (!is_admin() && ($handle === 'jquery-core' || $handle === 'jquery')) {
        return str_replace(' src', ' defer="defer" src', $tag);
    }
    return $tag;
}, 10, 2);