</main>

<?php
global $woo_active;

// Safely check if WooCommerce is active AND if it is the account page
$is_woo_account = $woo_active && function_exists('is_account_page') && is_account_page();

if ($is_woo_account):
    echo '';
else:
    get_template_part('template-parts/layout/footer-content');
endif;

if (is_front_page()):
    get_template_part('template-parts/layout/sticky-social');
endif;

get_template_part('template-parts/global/backToTop');

// Mobile Menu Modal
get_template_part('template-parts/layout/header/menu-modal');

// Search modal
get_template_part('template-parts/layout/header/search-modal');

// Product category modal
get_template_part('template-parts/product/category-modal');

// Safely check for Advanced Custom Fields (ACF) before calling get_field()
if (function_exists('get_field')) {
    $catMode = get_field('catalogue_mode', 'option');
    if ($catMode):
        get_template_part('template-parts/shop/shop-contact-modal');
    endif;
}

get_template_part('template-parts/global/popup');
$preloader = get_field('preloader', 'option');
if ($preloader):
    get_template_part('template-parts/global/preloader');
endif;
wp_footer();
?>

</body>
</html>