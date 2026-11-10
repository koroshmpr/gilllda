<?php
/**
 * Order tracking
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/tracking.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.6.0
 */


defined( 'ABSPATH' ) || exit;

$notes = $order->get_customer_order_notes();
?>
<div class="gl-view-order space-y-5">

	<header class="mag-in flex flex-wrap items-center gap-3 rounded-3xl border border-gray-100 bg-white p-4 shadow-[0_8px_30px_-16px_rgba(16,37,66,0.18)] sm:p-5">
		<div class="min-w-0 flex-1">
			<h2 class="text-lg font-black text-primary"><?php echo esc_html( 'سفارش #' . ( function_exists( 'gl_acc_num' ) ? gl_acc_num( $order->get_order_number() ) : $order->get_order_number() ) ); ?></h2>
			<p class="order-info mt-0.5 text-xs leading-6 text-gray-500 [&_mark]:bg-transparent [&_mark]:font-bold [&_mark]:text-gray-700">
				<?php
				echo wp_kses_post(
					/**
					 * Filter to modify order tracking status text.
					 *
					 * @param string $order_status The order status text.
					 *
					 * @since 10.1.0
					 */
					apply_filters(
						'woocommerce_order_tracking_status',
						sprintf(
							/* translators: 1: order number 2: order date 3: order status */
							__( 'Order #%1$s was placed on %2$s and is currently %3$s.', 'woocommerce' ),
							'<mark class="order-number">' . $order->get_order_number() . '</mark>',
							'<mark class="order-date">' . wc_format_datetime( $order->get_date_created() ) . '</mark>',
							'<mark class="order-status">' . wc_get_order_status_name( $order->get_status() ) . '</mark>'
						),
						$order
					)
				);
				?>
			</p>
		</div>
		<?php echo function_exists( 'gl_acc_status_chip' ) ? gl_acc_status_chip( $order ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</header>

	<?php
	if ( function_exists( 'gl_order_progress' ) ) {
		gl_order_progress( $order, array( 'reveal' => 1 ) );
	}
	?>

	<?php if ( $notes ) : ?>
		<section class="rounded-3xl border border-gray-100 bg-white p-4 shadow-[0_8px_30px_-16px_rgba(16,37,66,0.18)] sm:p-6" data-reveal style="--d:2">
			<h2 class="mb-5 text-base font-black text-primary"><?php esc_html_e( 'Order updates', 'woocommerce' ); ?></h2>
			<ol class="commentlist notes gl-timeline m-0 list-none p-0">
				<?php foreach ( $notes as $gl_n => $note ) : ?>
				<li class="comment note gl-timeline__item<?php echo 0 === $gl_n ? ' is-latest' : ''; ?>">
					<div class="comment_container">
						<div class="comment-text">
							<p class="meta text-xs text-gray-400"><?php echo esc_html( function_exists( 'gl_acc_date' ) ? gl_acc_date( strtotime( $note->comment_date ), true ) : date_i18n( 'Y/m/d H:i', strtotime( $note->comment_date ) ) ); ?></p>
							<div class="description mt-1 text-sm leading-7 text-gray-700 [&_p]:m-0">
								<?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?>
							</div>
						</div>
					</div>
				</li>
				<?php endforeach; ?>
			</ol>
		</section>
	<?php endif; ?>

	<?php do_action( 'woocommerce_view_order', $order->get_id() ); ?>
</div>
