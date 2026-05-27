<?php
/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.1.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart'); ?>
<div class="grid lg:grid-cols-3 gap-5 items-start">
    <form class="woocommerce-cart-form  lg:col-span-2" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
        <?php do_action('woocommerce_before_cart_table'); ?>

        <table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents max-lg:!border-none" cellspacing="0">
            <thead>
            <tr>
                <th class="product-remove"><span
                            class="screen-reader-text"><?php esc_html_e('Remove item', 'woocommerce'); ?></span></th>
                <th class="product-thumbnail"><span
                            class="screen-reader-text"><?php esc_html_e('Thumbnail image', 'woocommerce'); ?></span>
                </th>
                <th scope="col" class="product-name"><?php esc_html_e('Product', 'woocommerce'); ?></th>
                <th scope="col" class="product-price"><?php esc_html_e('Price', 'woocommerce'); ?></th>
                <th scope="col" class="product-quantity"><?php esc_html_e('Quantity', 'woocommerce'); ?></th>
                <th scope="col" class="product-subtotal"><?php esc_html_e('Subtotal', 'woocommerce'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php do_action('woocommerce_before_cart_contents'); ?>

            <?php
            foreach (WC()->cart->get_cart() as $cart_item_key  => $cart_item) {
                $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                /**
                 * Filter the product name.
                 *
                 * @param string $product_name Name of the product in the cart.
                 * @param array $cart_item The product in the cart.
                 * @param string $cart_item_key Key for the product in the cart.
                 * @since 2.1.0
                 */
                $product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);

                if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
                    $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                    ?>
                    <tr class="woocommerce-cart-form__cart-item max-lg:mb-2 max-lg:border border-black/10 bg-primary/10 lg:bg-gray-50 <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">

                        <td class="product-remove">
                            <?php
                            echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                'woocommerce_cart_item_remove_link',
                                sprintf(
                                    '<a role="button" href="%s" class="remove max-lg:!w-full !rounded-md" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
                                    esc_url(wc_get_cart_remove_url($cart_item_key)),
                                    /* translators: %s is the product name */
                                    esc_attr(sprintf(__('Remove %s from cart', 'woocommerce'), wp_strip_all_tags($product_name))),
                                    esc_attr($product_id),
                                    esc_attr($_product->get_sku())
                                ),
                                $cart_item_key
                            );
                            ?>
                        </td>

                        <td class="product-thumbnail">
                            <?php
                            /**
                             * Filter the product thumbnail displayed in the WooCommerce cart.
                             *
                             * This filter allows developers to customize the HTML output of the product
                             * thumbnail. It passes the product image along with cart item data
                             * for potential modifications before being displayed in the cart.
                             *
                             * @param string $thumbnail The HTML for the product image.
                             * @param array $cart_item The cart item data.
                             * @param string $cart_item_key Unique key for the cart item.
                             *
                             * @since 2.1.0
                             */
                            $thumbnail = apply_filters('woocommerce_cart_item_thumbnail' , $_product->get_image(), $cart_item, $cart_item_key);

                            if (!$product_permalink) {
                                echo $thumbnail; // PHPCS: XSS ok.
                            } else {
                                printf('<a class="[&>img]:!size-16 [&>img]:border-2 [&>img]:p-0.5 [&>img]:border-icon [&>img]:rounded-xl " href="%s">%s</a>', esc_url($product_permalink), $thumbnail); // PHPCS: XSS ok.
                            }
                            ?>
                        </td>

                        <td scope="row" role="rowheader" class="product-name"
                            data-title="<?php esc_attr_e('Product', 'woocommerce'); ?>">
                            <?php
                            // Get the base product name without variation suffix
                            if ($_product->is_type('variation')) {
                                $parent_product = wc_get_product($_product->get_parent_id());
                                $base_name = $parent_product ? $parent_product->get_name() : $_product->get_name();
                            } else {
                                $base_name = $_product->get_name();
                            }

                            if (!$product_permalink) {
                                echo wp_kses_post($base_name);
                            } else {
                                echo wp_kses_post(
                                    apply_filters(
                                        'woocommerce_cart_item_name',
                                        sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $base_name),
                                        $cart_item,
                                        $cart_item_key
                                    )
                                );
                            }

                            $variation_attributes = isset($cart_item['variation']) ? $cart_item['variation'] : array();

                            if (!empty($variation_attributes)) {
                                echo '<br>';
                                foreach ($variation_attributes as $attr_name => $attr_value_slug) {
                                    // Clean attribute name
                                    $clean_name = str_replace(array('attribute_pa_', 'attribute_'), '', $attr_name);

                                    // Get taxonomy name for attribute
                                    $taxonomy = wc_attribute_taxonomy_name($clean_name);

                                    // Get attribute label from taxonomy
                                    if (taxonomy_exists($taxonomy)) {
                                        $label = wc_attribute_label($taxonomy); // this returns translated/custom label
                                    } else {
                                        $label = ucfirst(str_replace('-', ' ', $clean_name));
                                    }

                                    // Get human-readable attribute value
                                    if (taxonomy_exists($taxonomy)) {
                                        $term = get_term_by('slug', $attr_value_slug, $taxonomy);
                                        $value = $term ? $term->name : $attr_value_slug;
                                    } else {
                                        $value = $attr_value_slug;
                                    }

                                    echo '<div>' . esc_html($label) . ': ' . esc_html($value) . '</div>';
                                }
                            }
                            ?>
                        </td>

                        <td class="product-price" data-title="<?php esc_attr_e('Price', 'woocommerce'); ?>">
                            <?php
                            echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); // PHPCS: XSS ok.
                            ?>
                        </td>

                        <td class="product-quantity " data-title="<?php esc_attr_e('Quantity', 'woocommerce'); ?>">
                            <?php
                            if ($_product->is_sold_individually()) {
                                $min_quantity = 1;
                                $max_quantity = 1;
                            } else {
                                $min_quantity = 0;
                                $max_quantity = $_product->get_max_purchase_quantity();
                            }

                            $product_quantity = woocommerce_quantity_input(
                                array(
                                    'input_name' => "cart[{$cart_item_key}][qty]",
                                    'input_value' => $cart_item['quantity'],
                                    'max_value' => $max_quantity,
                                    'min_value' => $min_quantity,
                                    'classes' => 'input-text qty text !w-24 bg-white',
                                    'product_name' => $product_name,
                                ),
                                $_product,
                                false
                            );

                            echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item); // PHPCS: XSS ok.
                            ?>
                        </td>

                        <td class="product-subtotal" data-title="<?php esc_attr_e('Subtotal', 'woocommerce'); ?>">
                            <?php
                            echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); // PHPCS: XSS ok.
                            ?>
                        </td>
                    </tr>
                    <?php
                }
            }
            ?>

            <?php do_action('woocommerce_cart_contents'); ?>

            <tr>
                <td colspan="6" class="actions">

                    <?php if (wc_coupons_enabled()) { ?>
                        <div class="coupon flex flex-wrap">
                            <label for="coupon_code"
                                   class="screen-reader-text"><?php esc_html_e('Coupon:', 'woocommerce'); ?></label>
                            <input type="text" name="coupon_code" class="input-text flex-1" id="coupon_code" value=""
                                   placeholder="<?php esc_attr_e('Coupon code', 'woocommerce'); ?>"/>
                            <button type="submit"
                                    class="text-nowrap button flex-1 !bg-icon rounded-lg grow-0 <?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"
                                    name="apply_coupon"
                                    value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>"><?php esc_html_e('Apply coupon', 'woocommerce'); ?></button>
                            <?php do_action('woocommerce_cart_coupon'); ?>
                        </div>
                    <?php } ?>

                    <button type="submit"
                            class="button h-12 <?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"
                            name="update_cart"
                            value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>"><?php esc_html_e('Update cart', 'woocommerce'); ?></button>

                    <?php do_action('woocommerce_cart_actions'); ?>

                    <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
                </td>
            </tr>

            <?php do_action('woocommerce_after_cart_contents'); ?>
            </tbody>
        </table>
        <?php do_action('woocommerce_after_cart_table'); ?>
    </form>

    <?php do_action('woocommerce_before_cart_collaterals'); ?>

    <div class="cart-collaterals lg:sticky top-24">
        <?php
        /**
         * Cart collaterals hook.
         *
         * @hooked woocommerce_cross_sell_display
         * @hooked woocommerce_cart_totals - 10
         */
        do_action('woocommerce_cart_collaterals');
        ?>
    </div>
</div>

<?php do_action('woocommerce_after_cart'); ?>
