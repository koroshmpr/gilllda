<?php
/**
 * The header for our theme
 *
 * This is the template that displays the `head` element and everything up
 * until the `#content` element.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Maia Flower
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <?php
    $focus_keyword = get_post_meta(get_the_ID(), 'rank_math_focus_keyword', true);
    ?>
    <meta name="keywords" content="<?= $focus_keyword ?? get_bloginfo('name'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <!-- jQuery Fallback Queue to prevent errors when jQuery is deferred -->
    <script id="jquery-fallback-queue">
        (function() {
            var queue = [];
            var realJQuery = null;

            // Stable mock reference that never changes
            var jQueryMock = function(selector, context) {
                if (realJQuery) {
                    return realJQuery(selector, context);
                }
                if (typeof selector === 'function') {
                    queue.push(selector);
                }
                return jQueryMock;
            };

            jQueryMock.isFallback = true;
            jQueryMock.fn = jQueryMock.prototype = {};
            jQueryMock.ready = function(fn) {
                if (realJQuery) {
                    return realJQuery(fn);
                }
                if (typeof fn === 'function') {
                    queue.push(fn);
                }
                return jQueryMock;
            };

            // Set globals early
            window.jQuery = window.$ = jQueryMock;

            // Monitor when the real jQuery is loaded
            var checkJQuery = setInterval(function() {
                if (window.jQuery && window.jQuery !== jQueryMock && !window.jQuery.isFallback) {
                    clearInterval(checkJQuery);
                    realJQuery = window.jQuery;

                    // Copy static properties/methods from real jQuery to our mock reference
                    for (var prop in realJQuery) {
                        if (realJQuery.hasOwnProperty(prop)) {
                            jQueryMock[prop] = realJQuery[prop];
                        }
                    }

                    // Align prototype methods
                    jQueryMock.prototype = realJQuery.prototype;
                    jQueryMock.fn = realJQuery.fn;

                    // Keep globals pointing to our mock reference for continuity
                    window.jQuery = window.$ = jQueryMock;

                    // Execute queued ready handlers
                    for (var i = 0; i < queue.length; i++) {
                        try {
                            queue[i](jQueryMock);
                        } catch (e) {
                            console.error('Queued jQuery error:', e);
                        }
                    }
                }
            }, 20);
        })();
    </script>

    <?php wp_head();
    if (is_front_page() || is_page_template('theme/homepage.php')) {
        $hero = get_field('hero', get_the_ID());
        if ($hero && !empty($hero['image'])) {
            $image = $hero['image'];
            $desktop_url = $image['desktop']['sizes']['1536x1536'] ?? $image['desktop']['sizes']['large'] ?? $image['desktop']['url'] ?? '';
            $mobile_url = $image['mobile']['sizes']['large'] ?? $image['mobile']['url'] ?? $image['desktop']['sizes']['large'] ?? $image['desktop']['url'] ?? '';
            
            if ($desktop_url) {
                echo '<link rel="preload" as="image" href="' . esc_url($desktop_url) . '" media="(min-width: 961px)" fetchpriority="high">' . "\n";
            }
            if ($mobile_url) {
                echo '<link rel="preload" as="image" href="' . esc_url($mobile_url) . '" media="(max-width: 960px)" fetchpriority="high">' . "\n";
            }
        }
    }
    $scripts = get_field('header-scripts', 'option');
    echo $scripts ?? '';
    ?>
    <noscript><style>#alpine-preloader { display: none !important; }</style></noscript>
</head>

<body   <?php body_class('font-peyda'); ?>
        :class="searchOpen || categoryOpen || shopContact || openForm || menuOpen || cart ? 'overflow-hidden' : ''"
        x-data="{atBottom: false, scrolled: false, lastScroll: 0, scrollingDown: false, openForm: false, scrollingUp: false, menuOpen: false, searchOpen: false, shopContact: false, categoryOpen: false, cart: false, intro : false ,
        gridView: (document.cookie.match(/gridView=([^;]+)/) || [])[1] || 'large', ticking: false}"
        x-init="
        $watch('gridView', value => document.cookie = `gridView=${value}; path=/; max-age=31536000`);
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    let currentScroll = window.pageYOffset || window.scrollY;
                    scrollingDown = currentScroll > lastScroll && currentScroll > 20;
                    scrollingUp = currentScroll < lastScroll && currentScroll > 20;
                    lastScroll = currentScroll;
                    scrolled = currentScroll > 150;
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
        setTimeout(() => { intro = true }, 200)">
<?php
$scripts = get_field('body-scripts', 'option');
echo $scripts ?? '';
wp_body_open();
global $woo_active;
get_template_part('template-parts/layout/header-content');

?>
<main :class="menuOpen ? 'mt-8' : ''" class="relative lg:pt-16 <?= $woo_active && (is_shop() || is_cart() || is_checkout() || is_product_category() || is_account_page()) ? 'container max-lg:px-2' : ''; ?> duration-300 transition-all"
      id="<?= get_post_type() ?? ''; ?>-<?= the_ID(); ?>">
