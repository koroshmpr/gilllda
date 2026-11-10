<?php
/**
 * Cart totals
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-totals.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.3.6
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="cart_totals gl-summary !w-full !float-none rounded-2xl border border-gray-200 bg-white p-4 lg:p-5 <?php echo ( WC()->customer->has_calculated_shipping() ) ? 'calculated_shipping' : ''; ?>">

    <?php do_action( 'woocommerce_before_cart_totals' ); ?>
    <h2 class="mb-3 text-base font-bold text-primary"><?php esc_html_e( 'Cart totals', 'woocommerce' ); ?></h2>

    <?php
    // Free-shipping progress (only when a free-shipping minimum is configured).
    $gl_threshold = function_exists( 'gl_free_shipping_threshold' ) ? gl_free_shipping_threshold() : 0;
    if ( $gl_threshold > 0 ) :
        $gl_basis     = max( 0, (float) WC()->cart->get_displayed_subtotal() - (float) WC()->cart->get_discount_total() );
        $gl_remaining = max( 0, $gl_threshold - $gl_basis );
        $gl_percent   = min( 100, (int) round( ( $gl_basis / $gl_threshold ) * 100 ) );
        ?>
        <div class="mb-4 rounded-xl bg-gray-50 px-3 py-2.5">
            <p class="mb-2 text-xs text-gray-600">
                <?php if ( $gl_remaining > 0 ) : ?>
                    فقط <b class="font-bold text-primary"><?php echo esc_html( number_format( $gl_remaining ) ); ?> تومان</b> تا ارسال رایگان
                <?php else : ?>
                    <b class="font-bold text-emerald-700">ارسال این سفارش رایگان است.</b>
                <?php endif; ?>
            </p>
            <div class="h-1.5 overflow-hidden rounded-full bg-gray-200" role="progressbar" aria-label="پیشرفت تا ارسال رایگان" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $gl_percent ); ?>">
                <div class="h-full rounded-full <?php echo $gl_remaining > 0 ? 'bg-secondary' : 'bg-emerald-600'; ?>" style="width: <?php echo esc_attr( $gl_percent ); ?>%"></div>
            </div>
        </div>
    <?php endif; ?>

    <table cellspacing="0" class="shop_table shop_table_responsive gl-totals-table text-sm">

        <tr class="cart-subtotal">
            <th><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
            <td data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>"><?php wc_cart_totals_subtotal_html(); ?></td>
        </tr>

        <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
            <tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
                <th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
                <td data-title="<?php echo esc_attr( wc_cart_totals_coupon_label( $coupon, false ) ); ?>"><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
            </tr>
        <?php endforeach; ?>

        <?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

            <?php do_action( 'woocommerce_cart_totals_before_shipping' ); ?>

            <?php wc_cart_totals_shipping_html(); ?>

            <?php do_action( 'woocommerce_cart_totals_after_shipping' ); ?>

        <?php elseif ( WC()->cart->needs_shipping() && 'yes' === get_option( 'woocommerce_enable_shipping_calc' ) ) : ?>

            <tr class="shipping">
                <th><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></th>
                <td data-title="<?php esc_attr_e( 'Shipping', 'woocommerce' ); ?>"><?php woocommerce_shipping_calculator(); ?></td>
            </tr>

        <?php endif; ?>

        <?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
            <tr class="fee">
                <th><?php echo esc_html( $fee->name ); ?></th>
                <td data-title="<?php echo esc_attr( $fee->name ); ?>"><?php wc_cart_totals_fee_html( $fee ); ?></td>
            </tr>
        <?php endforeach; ?>

        <?php
        if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
            $taxable_address = WC()->customer->get_taxable_address();
            $estimated_text  = '';

            if ( WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping() ) {
                /* translators: %s location. */
                $estimated_text = sprintf( ' <small>' . esc_html__( '(estimated for %s)', 'woocommerce' ) . '</small>', WC()->countries->estimated_for_prefix( $taxable_address[0] ) . WC()->countries->countries[ $taxable_address[0] ] );
            }

            if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
                foreach ( WC()->cart->get_tax_totals() as $code => $tax ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                    ?>
                    <tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
                        <th><?php echo esc_html( $tax->label ) . $estimated_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
                        <td data-title="<?php echo esc_attr( $tax->label ); ?>"><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr class="tax-total">
                    <th><?php echo esc_html( WC()->countries->tax_or_vat() ) . $estimated_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
                    <td data-title="<?php echo esc_attr( WC()->countries->tax_or_vat() ); ?>"><?php wc_cart_totals_taxes_total_html(); ?></td>
                </tr>
                <?php
            }
        }
        ?>

        <?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>

        <tr class="order-total">
            <th><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
            <td data-title="<?php esc_attr_e( 'Total', 'woocommerce' ); ?>"><?php wc_cart_totals_order_total_html(); ?></td>
        </tr>

        <?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>

    </table>

    <p class="mt-3 flex items-start gap-2 text-xs leading-5 text-gray-500">
        <svg class="mt-0.5 size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>هزینه ارسال: <?php echo esc_html( function_exists( 'gl_cart_shipping_note' ) ? gl_cart_shipping_note() : '' ); ?></span>
    </p>

    <div class="wc-proceed-to-checkout gl-paybar mt-4 flex items-center gap-3 max-lg:fixed max-lg:inset-x-3 max-lg:bottom-17 max-lg:z-40 max-lg:mt-0 max-lg:rounded-2xl max-lg:border max-lg:border-gray-200 max-lg:bg-white/95 max-lg:p-2 max-lg:shadow-[0_8px_30px_rgba(16,37,66,0.18)] max-lg:backdrop-blur">
        <div class="shrink-0 ps-2 lg:hidden">
            <span class="block text-[11px] text-gray-500">مبلغ قابل پرداخت</span>
            <span class="block text-sm font-black text-gray-900 [&_small]:hidden"><?php wc_cart_totals_order_total_html(); ?></span>
        </div>
        <div class="flex-1">
            <?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
        </div>
    </div>

    <?php do_action( 'woocommerce_after_cart_totals' ); ?>

</div>
