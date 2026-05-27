<?php
function optimize_site() {
	// Disable Emojis
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

	// Remove jQuery Migrate (Not needed for modern themes/plugins)
	add_filter( 'wp_default_scripts', function ( $scripts ) {
		if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
			$scripts->registered['jquery']->deps = array_diff(
				$scripts->registered['jquery']->deps,
				[ 'jquery-migrate' ]
			);
		}
	} );

	// Disable Embeds (Improves speed by removing embed script and related styles)
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
	add_filter( 'embed_oembed_discover', '__return_false' );
	add_filter( 'tiny_mce_plugins', function ( $plugins ) {
		return array_diff( $plugins, [ 'wpembed' ] );
	} );
	remove_action( 'wp_head', 'wp_generator' ); // Remove WordPress version
	remove_action( 'wp_head', 'wlwmanifest_link' ); // Remove Windows Live Writer link
	remove_action( 'wp_head', 'rsd_link' ); // Remove RSD (Really Simple Discovery) link
	remove_action( 'wp_head', 'feed_links_extra', 3 ); // Remove feed links
	remove_action( 'wp_head', 'feed_links', 2 ); // Remove general feed links

	// Disable Dashicons for non-admins
	if ( ! is_admin() ) {
		add_action( 'wp_enqueue_scripts', function () {
			wp_dequeue_style( 'dashicons' );
		} );
	}

	// Dequeue Block Library CSS (Gutenberg Styles)
	add_action( 'wp_enqueue_scripts', function () {
		wp_dequeue_style( 'wp-block-library' ); // Default block styles
		wp_deregister_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' ); // Extra styles for block themes
		wp_deregister_style( 'wp-block-library-theme' );
		wp_deregister_style( 'woocommerce-rtl' );
	}, 100 ); // Ensure it runs late
}

add_action( 'init', 'optimize_site' );
