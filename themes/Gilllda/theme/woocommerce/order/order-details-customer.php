<?php
/**
 * Order Customer Details
 *
 * Gilllda: billing / shipping address cards.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-details-customer.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.7.0
 */

defined( 'ABSPATH' ) || exit;

$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();
$gl_card       = 'rounded-3xl border border-gray-100 bg-white p-4 shadow-[0_8px_30px_-16px_rgba(16,37,66,0.18)] sm:p-5';
$gl_title      = 'woocommerce-column__title mb-3 flex items-center gap-2 text-base font-black text-primary';
$gl_addr       = 'gl-address not-italic text-sm leading-7 text-gray-600 [&_p]:m-0';
$gl_icon       = static function ( $name ) {
	return function_exists( 'gl_acc_icon' ) ? gl_acc_icon( $name, 'size-5' ) : '';
};
?>
<section class="woocommerce-customer-details gl-customer-details mt-5" data-reveal>

	<?php if ( $show_shipping ) : ?>

	<section class="woocommerce-columns woocommerce-columns--2 woocommerce-columns--addresses col2-set addresses grid gap-4 sm:grid-cols-2">
		<div class="woocommerce-column woocommerce-column--1 woocommerce-column--billing-address col-1 <?php echo esc_attr( $gl_card ); ?>">

	<?php else : ?>
		<div class="<?php echo esc_attr( $gl_card ); ?>">
	<?php endif; ?>

	<h2 class="<?php echo esc_attr( $gl_title ); ?>"><?php echo $gl_icon( 'receipt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Billing address', 'woocommerce' ); ?></h2>

	<address class="<?php echo esc_attr( $gl_addr ); ?>">
		<?php echo wp_kses_post( $order->get_formatted_billing_address( esc_html__( 'N/A', 'woocommerce' ) ) ); ?>

		<?php if ( $order->get_billing_phone() ) : ?>
			<p class="woocommerce-customer-details--phone mt-2 flex items-center gap-2"><?php echo $gl_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span dir="ltr"><?php echo esc_html( $order->get_billing_phone() ); ?></span></p>
		<?php endif; ?>

		<?php if ( $order->get_billing_email() ) : ?>
			<p class="woocommerce-customer-details--email mt-1 break-all"><?php echo esc_html( $order->get_billing_email() ); ?></p>
		<?php endif; ?>

		<?php
			/**
			 * Action hook fired after an address in the order customer details.
			 *
			 * @since 8.7.0
			 * @param string $address_type Type of address (billing or shipping).
			 * @param WC_Order $order Order object.
			 */
			do_action( 'woocommerce_order_details_after_customer_address', 'billing', $order );
		?>
	</address>

	<?php if ( $show_shipping ) : ?>

		</div><!-- /.col-1 -->

		<div class="woocommerce-column woocommerce-column--2 woocommerce-column--shipping-address col-2 <?php echo esc_attr( $gl_card ); ?>">
			<h2 class="<?php echo esc_attr( $gl_title ); ?>"><?php echo $gl_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Shipping address', 'woocommerce' ); ?></h2>
			<address class="<?php echo esc_attr( $gl_addr ); ?>">
				<?php echo wp_kses_post( $order->get_formatted_shipping_address( esc_html__( 'N/A', 'woocommerce' ) ) ); ?>

				<?php if ( $order->get_shipping_phone() ) : ?>
					<p class="woocommerce-customer-details--phone mt-2 flex items-center gap-2"><?php echo $gl_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span dir="ltr"><?php echo esc_html( $order->get_shipping_phone() ); ?></span></p>
				<?php endif; ?>

				<?php
					/**
					 * Action hook fired after an address in the order customer details.
					 *
					 * @since 8.7.0
					 * @param string $address_type Type of address (billing or shipping).
					 * @param WC_Order $order Order object.
					 */
					do_action( 'woocommerce_order_details_after_customer_address', 'shipping', $order );
				?>
			</address>
		</div><!-- /.col-2 -->

	</section><!-- /.col2-set -->

	<?php else : ?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>

</section>
