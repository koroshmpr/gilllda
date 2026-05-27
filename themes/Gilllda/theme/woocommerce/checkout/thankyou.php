<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order">

    <?php
    if ( $order ) :

        do_action( 'woocommerce_before_thankyou', $order->get_id() );
        ?>

        <?php if ( $order->has_status( 'failed' ) ) : ?>

        <p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>

        <p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
            <a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
            <?php if ( is_user_logged_in() ) : ?>
                <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
            <?php endif; ?>
        </p>

    <?php else : ?>
        <?php $listClass = 'flex-1 max-lg:border-b border-dotted !border-l-transparent !pl-0 gap-y-2 flex flex-col !ml-0 py-3 lg:py-5 bg-primary/15' ?>
        <div class="text-center text-2xl lg:text-3xl py-5 lg:mx-1 text-white bg-primary">
            <?php wc_get_template( 'checkout/order-received.php', array( 'order' => $order ) ); ?>
        </div>

        <ul class="woocommerce-order-overview max-lg:border-2 mx-0 border-dotted border-black/50 lg:gap-1 justify-between text-center flex max-lg:flex-col woocommerce-thankyou-order-details order_details">

            <li class="woocommerce-order-overview__order order <?= $listClass; ?>">
                <?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
                <strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
            </li>

            <li class="woocommerce-order-overview__date date <?= $listClass; ?>">
                <?php esc_html_e( 'Date:', 'woocommerce' ); ?>
                <strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
            </li>

            <?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
                <li class="woocommerce-order-overview__email email <?= $listClass; ?>">
                    <?php esc_html_e( 'Email:', 'woocommerce' ); ?>
                    <strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
                </li>
            <?php endif; ?>

            <li class="woocommerce-order-overview__total total <?= $listClass; ?>">
                <?php esc_html_e( 'Total:', 'woocommerce' ); ?>
                <strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
            </li>

            <?php if ( $order->get_payment_method_title() ) : ?>
                <li class="woocommerce-order-overview__payment-method method <?= $listClass; ?>">
                    <?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
                    <strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
                </li>
            <?php endif; ?>

        </ul>

    <?php endif; ?>

        <section class="flex max-lg:flex-col gap-5 ">
            <?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
        </section>

    <?php else : ?>

        <?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>

    <?php endif; ?>

</div>
