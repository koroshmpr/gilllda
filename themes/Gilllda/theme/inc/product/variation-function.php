<?php
add_filter( 'woocommerce_get_price_html', 'custom_price_display', 10, 2 ); // This will change the price display in cart and single product pages
add_action('wp_footer','update_button_action'); // This will update the add to cart button action

function custom_price_display( $price, $product ) {
    $regular_price = $product->get_regular_price();
    if ($product->is_type( 'variable' )) {
        $variations = $product->get_available_variations(); // Get the available variations of a variable product.
        foreach ( $variations as $variation ) {
            $variation_obj = wc_get_product($variation['variation_id']);
            if($regular_price == $variation_obj->get_regular_price()){ // Compare the regular price with variation's regular price.
                $new_price = $variation_obj->get_price();
                return wc_price($new_price);
            }
        }
    } else {
        $new_price = $product->get_price();
        return wc_price($new_price);
    }
}
function update_button_action() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            var run = function () {
                jQuery('form.selector').on('submit', function(e) {
                    e.preventDefault(); // prevent the default form submit action

                    jQuery('input[type="hidden"]').each(function() {
                        var $this = jQuery(this);

                        $this.val($this.siblings(':selected').val());
                    });

                    // now submit the form using AJAX to WooCommerce's add_to_cart function
                    jQuery.post( wc_add_to_cart_params.ajax_url, jQuery(this).serialize() );
                });
            };
            if (window.jQuery) {
                run();
            } else {
                var checkJQuery = setInterval(function () {
                    if (window.jQuery) {
                        clearInterval(checkJQuery);
                        run();
                    }
                }, 50);
            }
        });
    </script>
    <?php
}
