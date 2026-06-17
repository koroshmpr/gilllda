<?php
/**
 * bluebox functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package bluebox
 */

if ( ! defined( 'BLUEBOX_VERSION' ) ) {
	/*
	 * Set the theme’s version number.
	 *
	 * This is used primarily for cache busting. If you use `npm run bundle`
	 * to create your production build, the value below will be replaced in the
	 * generated zip file with a timestamp, converted to base 36.
	 */
	define( 'BLUEBOX_VERSION', '0.1.0' );
}

if ( ! defined( 'BLUEBOX_TYPOGRAPHY_CLASSES' ) ) {
	/*
	 * Set Tailwind Typography classes for the front end, block editor and
	 * classic editor using the constant below.
	 *
	 * For the front end, these classes are added by the `bluebox_content_class`
	 * function. You will see that function used everywhere an `entry-content`
	 * or `page-content` class has been added to a wrapper element.
	 *
	 * For the block editor, these classes are converted to a JavaScript array
	 * and then used by the `./javascript/block-editor.js` file, which adds
	 * them to the appropriate elements in the block editor (and adds them
	 * again when they’re removed.)
	 *
	 * For the classic editor (and anything using TinyMCE, like Advanced Custom
	 * Fields), these classes are added to TinyMCE’s body class when it
	 * initializes.
	 */
	define(
		'BLUEBOX_TYPOGRAPHY_CLASSES',
		'prose prose-neutral max-w-none prose-a:text-primary'
	);
}

if ( ! function_exists( 'bluebox_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function bluebox_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on bluebox, use a find and replace
		 * to change 'bluebox' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'bluebox', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in two locations.
		register_nav_menus(
			array(
				'menu-1' => __( 'Primary', 'bluebox' ),
				'menu-2' => __( 'Footer Menu', 'bluebox' ),
				'menu-3' => __( 'Mobile Menu', 'bluebox' ),
				'menu-4' => __( 'Footer 2', 'bluebox' ),
			)
		);

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			)
		);

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		// Add support for editor styles.
		add_theme_support( 'editor-styles' );

		// Enqueue editor styles.
		add_editor_style( 'style-editor.css' );
		add_editor_style( 'style-editor-extra.css' );

		// Add support for responsive embedded content.
		add_theme_support( 'responsive-embeds' );

		// Remove support for block templates.
		remove_theme_support( 'block-templates' );
	}
endif;
add_action( 'after_setup_theme', 'bluebox_setup' );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function bluebox_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Footer', 'bluebox' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Add widgets here to appear in your footer.', 'bluebox' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'bluebox_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function bluebox_scripts() {
    // 1. Load your main stylesheet
    wp_enqueue_style( 'bluebox-style', get_stylesheet_uri(), array(), BLUEBOX_VERSION );

    // 2. Load script with the modern 'defer' strategy
    wp_enqueue_script( 'bluebox-script', get_template_directory_uri() . '/js/script.min.js', array('jquery'), BLUEBOX_VERSION, array(
        'in_footer' => true,
        'strategy'  => 'defer'
    ));

    // 3. BUG FIX: Changed 'main-js' to 'bluebox-script' so the data attaches to your actual script
    wp_localize_script('bluebox-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    wp_localize_script('bluebox-script', 'jsData', array('root_url' => get_site_url(), 'nonce' => wp_create_nonce('my-nonce')));

    // 4. Defer conditional scripts
    if (is_product()) {
        wp_enqueue_script('single-product', get_template_directory_uri() . '/js/single-product.js', array('jquery'), BLUEBOX_VERSION, array(
            'in_footer' => true,
            'strategy'  => 'defer'
        ));
    }

    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'bluebox_scripts' );


// 5. Programmatically preload your main font to break the CSS chain
function bluebox_preload_fonts() {
    echo '<link rel="preload" href="' . get_template_directory_uri() . '/fonts/Peyda/fonts/woff2/PeydaWebFaNum-Regular.woff2" as="font" type="font/woff2" crossorigin="anonymous">';
}
// Using priority 5 to ensure it loads high up in the <head>
add_action( 'wp_head', 'bluebox_preload_fonts', 5 );

/**
 * Enqueue the block editor script.
 */
function bluebox_enqueue_block_editor_script() {
	if ( is_admin() ) {
		wp_enqueue_script(
			'bluebox-editor',
			get_template_directory_uri() . '/js/block-editor.min.js',
			array(
				'wp-blocks',
				'wp-edit-post',
			),
			BLUEBOX_VERSION,
			true
		);
		wp_add_inline_script( 'bluebox-editor', "tailwindTypographyClasses = '" . esc_attr( BLUEBOX_TYPOGRAPHY_CLASSES ) . "'.split(' ');", 'before' );
	}
}
add_action( 'enqueue_block_assets', 'bluebox_enqueue_block_editor_script' );

/**
 * Add the Tailwind Typography classes to TinyMCE.
 *
 * @param array $settings TinyMCE settings.
 * @return array
 */
function bluebox_tinymce_add_class( $settings ) {
	$settings['body_class'] = BLUEBOX_TYPOGRAPHY_CLASSES;
	return $settings;
}
add_filter( 'tiny_mce_before_init', 'bluebox_tinymce_add_class' );

//post types functions
require get_template_directory() . '/inc/post-type-functions/persian-date.php';
require get_template_directory() . '/inc/post-type-functions/reading-time.php';
require get_template_directory() . '/inc/post-type-functions/post-view.php';
require get_template_directory() . '/inc/post-type-functions/post-rating.php';
require get_template_directory() . '/inc/post-type-functions/toc.php';
require get_template_directory() . '/inc/post-type-functions/comments-field.php';
require get_template_directory() . '/inc/post-type-functions/inline-ads.php';
//product functions
require get_template_directory() . '/inc/product/check-woocommerce-active.php';
require get_template_directory() . '/inc/product/compare-function.php';
require get_template_directory() . '/inc/product/product-filters.php';
// Remove the result count and catalog ordering from the default WooCommerce hooks
//require get_template_directory() . '/inc/product/wc-variations-radio-buttons.php';
//require get_template_directory() . '/inc/product/variation-function.php';
require get_template_directory() . '/inc/product/out-stock-product.php';
require get_template_directory() . '/inc/product/filter-search-query.php';
require get_template_directory() . '/inc/product/return-policy-schema.php';
require get_template_directory() . '/inc/product/shop-actions.php';
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/walker.php';
require get_template_directory() . '/inc/custom-post-type.php';
require get_template_directory() . '/inc/gravity-form.php';
require get_template_directory() . '/inc/optimize.php';
require get_template_directory() . '/inc/otp-login-form.php';
include_once get_template_directory() . '/inc/search-route.php';
include_once get_template_directory() . '/inc/ajax-search.php';

