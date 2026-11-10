<?php
/**
 * View Order
 *
 * Shows the details of a particular order on the account page.
 *
 * Gilllda: header card + live progress tracker + order updates timeline, then the
 * order details (woocommerce_view_order) and a support card.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/view-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.6.0
 */

defined( 'ABSPATH' ) || exit;

$notes = $order->get_customer_order_notes();
?>
<div class="gl-view-order space-y-5 lg:space-y-6">

	<!-- Header -->
	<header class="mag-in flex flex-wrap items-center gap-3 rounded-3xl border border-gray-100 bg-white p-4 shadow-[0_8px_30px_-16px_rgba(16,37,66,0.18)] sm:p-5">
		<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="grid size-11 shrink-0 place-items-center rounded-full border border-gray-100 text-gray-500 transition hover:border-primary hover:text-primary" aria-label="بازگشت به سفارش‌ها">
			<span class="inline-flex rotate-180"><?php echo gl_acc_icon( 'chevron', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</a>
		<div class="min-w-0 flex-1">
			<h2 class="text-lg font-black text-primary sm:text-xl"><?php echo esc_html( 'سفارش #' . gl_acc_num( $order->get_order_number() ) ); ?></h2>
			<p class="gl-order-status-text mt-0.5 text-xs leading-6 text-gray-500 [&_mark]:bg-transparent [&_mark]:font-bold [&_mark]:text-gray-700">
				<?php
				echo wp_kses_post(
					/**
					 * Filter to modify order detiails status text.
					 *
					 * @param string $order_status The order status text.
					 *
					 * @since 10.1.0
					 */
					apply_filters(
						'woocommerce_order_details_status',
						sprintf(
						/* translators: 1: order number 2: order date 3: order status */
							esc_html__( 'Order #%1$s was placed on %2$s and is currently %3$s.', 'woocommerce' ),
							'<mark class="order-number">' . $order->get_order_number() . '</mark>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'<mark class="order-date">' . gl_acc_date( $order->get_date_created() ) . '</mark>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'<mark class="order-status">' . wc_get_order_status_name( $order->get_status() ) . '</mark>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						),
						$order
					)
				);
				?>
			</p>
		</div>
		<?php echo gl_acc_status_chip( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( $order->needs_payment() ) : ?>
			<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-secondary px-5 text-sm font-bold text-white transition hover:bg-secondary/90 sm:w-auto">
				<?php echo gl_acc_icon( 'wallet', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>پرداخت سفارش
			</a>
		<?php endif; ?>
	</header>

	<!-- Progress -->
	<?php gl_order_progress( $order, array( 'reveal' => 1 ) ); ?>

	<?php if ( $notes ) : ?>
		<!-- Order updates timeline -->
		<section class="rounded-3xl border border-gray-100 bg-white p-4 shadow-[0_8px_30px_-16px_rgba(16,37,66,0.18)] sm:p-6" data-reveal style="--d:2">
			<h2 class="mb-5 flex items-center gap-2 text-base font-black text-primary">
				<?php echo gl_acc_icon( 'note', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Order updates', 'woocommerce' ); ?>
			</h2>
			<ol class="woocommerce-OrderUpdates commentlist notes gl-timeline m-0 list-none p-0">
				<?php foreach ( $notes as $gl_n => $note ) : ?>
				<li class="woocommerce-OrderUpdate comment note gl-timeline__item<?php echo 0 === $gl_n ? ' is-latest' : ''; ?>">
					<div class="woocommerce-OrderUpdate-inner comment_container">
						<div class="woocommerce-OrderUpdate-text comment-text">
							<p class="woocommerce-OrderUpdate-meta meta text-xs text-gray-400">
								<time datetime="<?php echo esc_attr( mysql2date( 'c', $note->comment_date ) ); ?>"><?php echo esc_html( gl_acc_date( strtotime( $note->comment_date ), true ) ); ?></time>
							</p>
							<div class="woocommerce-OrderUpdate-description description mt-1 text-sm leading-7 text-gray-700 [&_p]:m-0">
								<?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?>
							</div>
						</div>
					</div>
				</li>
				<?php endforeach; ?>
			</ol>
		</section>
	<?php endif; ?>

	<?php do_action( 'woocommerce_view_order', $order_id ); ?>

	<?php gl_acc_help_card( $order ); ?>
</div>
