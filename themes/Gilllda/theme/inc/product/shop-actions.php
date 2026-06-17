<?php
add_action( 'wp_enqueue_scripts', function() {
    if ( is_product() ) {
        wp_enqueue_script( 'wc-add-to-cart-variation' );
    }
});
add_filter( 'woocommerce_localisation_address_formats', 'wc_single_line_address_format', 20 );

function wc_single_line_address_format( $formats ) {
    // تغییر فرمت برای کشور ایران (IR)
    $formats['IR'] = "{name} {country} {city} ,{address_1}, {address_2}, {postcode}";
    return $formats;
}
//{company}<br>