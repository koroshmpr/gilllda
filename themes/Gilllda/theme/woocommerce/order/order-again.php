<?php
/**
 * Order again button
 *
 * Gilllda: rounded «سفارش مجدد» button.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-again.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.8.0
 */

defined( 'ABSPATH' ) || exit;
?>

<p class="order-again mt-5 flex">
	<a href="<?php echo esc_url( $order_again_url ); ?>" class="gl-btn inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-primary px-6 text-sm font-bold text-white transition hover:bg-primary/90 sm:w-auto<?php echo esc_attr( $wp_button_class ); ?>">
		<?php echo function_exists( 'gl_acc_icon' ) ? gl_acc_icon( 'refresh', 'size-5' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php esc_html_e( 'Order again', 'woocommerce' ); ?>
	</a>
</p>
