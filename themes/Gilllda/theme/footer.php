</main>

<?php
global $woo_active;

$is_woo_account = $woo_active && function_exists('is_account_page') && is_account_page();

if ( ! $is_woo_account ) :
    get_template_part('template-parts/layout/footer-content');
endif;

if ( is_front_page() ) :
    get_template_part('template-parts/layout/sticky-social');
endif;

$is_woo_checkout_flow = $woo_active && ( is_cart() || is_checkout() || is_account_page() );
if ( ! $is_woo_checkout_flow ) :
    get_template_part('template-parts/global/backToTop');
endif;


// ۳. مدال‌ها (Modals)
get_template_part('template-parts/layout/header/menu-modal');
get_template_part('template-parts/layout/header/search-modal');
get_template_part('template-parts/global/popup');

if ( $woo_active ) :
    get_template_part('template-parts/product/category-modal');
endif;

if ( function_exists('get_field') ) :

    $catMode = get_field('catalogue_mode', 'option');
    if ( $catMode ) :
        get_template_part('template-parts/shop/shop-contact-modal');
    endif;
    $preloader = get_field('preloader', 'option');
    if ( $preloader ) :
        get_template_part('template-parts/global/preloader');
    endif;

endif;

wp_footer();
?>

</body>
</html>