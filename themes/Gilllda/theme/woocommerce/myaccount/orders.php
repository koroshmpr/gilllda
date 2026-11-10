<?php
/**
 * Orders
 *
 * Shows orders on the account page.
 *
 * Gilllda: one card per order (number, date, status chip, thumbnails, total, mini progress bar, actions).
 * Custom columns added through `woocommerce_account_orders_columns` + `woocommerce_my_account_my_orders_column_{id}`
 * still render inside each card.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/orders.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.5.0
 */

defined( 'ABSPATH' ) || exit;

$wp_button_class = isset( $wp_button_class ) ? $wp_button_class : ( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' );
$gl_core_columns = array( 'order-number', 'order-date', 'order-status', 'order-total', 'order-actions' );

do_action( 'woocommerce_before_account_orders', $has_orders ); ?>

<?php if ( $has_orders ) : ?>

	<div class="mb-4 flex items-end justify-between gap-3">
		<div>
			<h2 class="text-lg font-black text-primary">سفارش‌های من</h2>
			<p class="mt-0.5 text-sm text-gray-500">وضعیت هر سفارش را از نوار مراحل دنبال کنید.</p>
		</div>
	</div>

	<ul class="woocommerce-orders-table woocommerce-MyAccount-orders my_account_orders account-orders-table m-0 grid list-none gap-3 p-0 lg:gap-4">
		<?php
		foreach ( $customer_orders->orders as $gl_index => $customer_order ) :
			$order      = wc_get_order( $customer_order ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			if ( ! $order ) {
				continue;
			}
			$item_count = $order->get_item_count() - $order->get_item_count_refunded();
			$gl_actions = wc_get_account_orders_actions( $order );
			?>
			<li class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $order->get_status() ); ?> order overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-[0_8px_30px_-16px_rgba(16,37,66,0.18)] transition hover:border-primary/15"
				data-reveal style="--d:<?php echo (int) min( $gl_index, 6 ); ?>">

				<!-- Header: number, date, status -->
				<div class="flex flex-wrap items-center gap-x-3 gap-y-2 p-4 pb-0 sm:p-5 sm:pb-0">
					<div class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number min-w-0 flex-1">
						<?php if ( has_action( 'woocommerce_my_account_my_orders_column_order-number' ) ) : ?>
							<?php do_action( 'woocommerce_my_account_my_orders_column_order-number', $order ); ?>
						<?php else : ?>
							<?php /* translators: %s: the order number, usually accompanied by a leading # */ ?>
							<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="text-base font-black text-primary hover:underline underline-offset-4" aria-label="<?php echo esc_attr( sprintf( __( 'View order number %s', 'woocommerce' ), $order->get_order_number() ) ); ?>">
								<?php echo esc_html( 'سفارش ' . _x( '#', 'hash before order number', 'woocommerce' ) . gl_acc_num( $order->get_order_number() ) ); ?>
							</a>
						<?php endif; ?>

						<div class="woocommerce-orders-table__cell-order-date mt-0.5 text-xs text-gray-500">
							<?php if ( has_action( 'woocommerce_my_account_my_orders_column_order-date' ) ) : ?>
								<?php do_action( 'woocommerce_my_account_my_orders_column_order-date', $order ); ?>
							<?php elseif ( $order->get_date_created() ) : ?>
								<time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>"><?php echo esc_html( gl_acc_date( $order->get_date_created(), true ) ); ?></time>
							<?php endif; ?>
						</div>
					</div>

					<div class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status">
						<?php if ( has_action( 'woocommerce_my_account_my_orders_column_order-status' ) ) : ?>
							<?php do_action( 'woocommerce_my_account_my_orders_column_order-status', $order ); ?>
						<?php else : ?>
							<?php echo gl_acc_status_chip( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					</div>
				</div>

				<!-- Body: items, total, progress -->
				<div class="grid gap-4 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,16rem)] sm:items-center sm:p-5">
					<div class="flex min-w-0 items-center gap-3">
						<?php gl_acc_order_thumbs( $order, 4, 'size-12' ); ?>
						<div class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total min-w-0">
							<?php if ( has_action( 'woocommerce_my_account_my_orders_column_order-total' ) ) : ?>
								<?php do_action( 'woocommerce_my_account_my_orders_column_order-total', $order ); ?>
							<?php else : ?>
								<p class="text-xs text-gray-500"><?php echo esc_html( gl_acc_num( $item_count ) . ' کالا' ); ?></p>
								<p class="font-black text-primary"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></p>
							<?php endif; ?>
						</div>
					</div>
					<?php gl_order_progress( $order, array( 'variant' => 'mini', 'reveal' => min( $gl_index, 6 ) + 1 ) ); ?>
				</div>

				<?php
				// Columns added by plugins.
				foreach ( wc_get_account_orders_columns() as $column_id => $column_name ) :
					if ( in_array( $column_id, $gl_core_columns, true ) || ! has_action( 'woocommerce_my_account_my_orders_column_' . $column_id ) ) {
						continue;
					}
					?>
					<div class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr( $column_id ); ?> flex flex-wrap items-center gap-2 px-4 pb-4 text-sm sm:px-5" data-title="<?php echo esc_attr( $column_name ); ?>">
						<span class="text-gray-500"><?php echo esc_html( $column_name ); ?>:</span>
						<?php do_action( 'woocommerce_my_account_my_orders_column_' . $column_id, $order ); ?>
					</div>
				<?php endforeach; ?>

				<!-- Actions -->
				<div class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions flex flex-wrap gap-2 border-t border-gray-100 bg-gray-50/50 p-3 sm:px-5">
					<?php if ( has_action( 'woocommerce_my_account_my_orders_column_order-actions' ) ) : ?>
						<?php do_action( 'woocommerce_my_account_my_orders_column_order-actions', $order ); ?>
					<?php elseif ( ! empty( $gl_actions ) ) : ?>
						<?php
						foreach ( $gl_actions as $key => $action ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							if ( empty( $action['aria-label'] ) ) {
								// Generate the aria-label based on the action name.
								/* translators: %1$s Action name, %2$s Order number. */
								$action_aria_label = sprintf( __( '%1$s order number %2$s', 'woocommerce' ), $action['name'], $order->get_order_number() );
							} else {
								$action_aria_label = $action['aria-label'];
							}
							$gl_btn = 'view' === $key
								? 'bg-primary text-white hover:bg-primary/90'
								: ( 'pay' === $key ? 'bg-secondary text-white hover:bg-secondary/90' : ( 'cancel' === $key ? 'border border-gray-200 bg-white text-gray-500 hover:border-secondary/40 hover:text-secondary' : 'border border-gray-200 bg-white text-primary hover:border-primary' ) );
							echo '<a href="' . esc_url( $action['url'] ) . '" class="woocommerce-button gl-btn inline-flex min-h-11 items-center justify-center gap-1.5 rounded-full px-5 text-sm font-bold transition ' . esc_attr( $gl_btn . $wp_button_class ) . ' ' . sanitize_html_class( $key ) . '" aria-label="' . esc_attr( $action_aria_label ) . '">' . esc_html( $action['name'] ) . '</a>';
							unset( $action_aria_label );
						}
						?>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

	<?php if ( 1 < $customer_orders->max_num_pages ) : ?>
		<nav class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination mt-5 flex items-center justify-between gap-3" aria-label="صفحه‌بندی سفارش‌ها">
			<?php if ( 1 !== $current_page ) : ?>
				<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous inline-flex min-h-11 items-center gap-1 rounded-full border border-gray-200 bg-white px-5 text-sm font-bold text-primary transition hover:border-primary<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>">
					<span class="inline-flex rotate-180"><?php echo gl_acc_icon( 'chevron', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php esc_html_e( 'Previous', 'woocommerce' ); ?>
				</a>
			<?php else : ?>
				<span></span>
			<?php endif; ?>

			<span class="text-xs text-gray-500"><?php echo esc_html( gl_acc_num( 'صفحهٔ ' . (int) $current_page . ' از ' . (int) $customer_orders->max_num_pages ) ); ?></span>

			<?php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
				<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next inline-flex min-h-11 items-center gap-1 rounded-full bg-primary px-5 text-sm font-bold text-white transition hover:bg-primary/90<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>">
					<?php esc_html_e( 'Next', 'woocommerce' ); ?><?php echo gl_acc_icon( 'chevron', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			<?php else : ?>
				<span></span>
			<?php endif; ?>
		</nav>
	<?php endif; ?>

<?php else : ?>

	<div class="woocommerce-info-empty flex flex-col items-center rounded-3xl border border-dashed border-gray-200 bg-white px-5 py-12 text-center mag-in">
		<span class="grid size-20 place-items-center rounded-full bg-primary/5 text-primary"><?php echo gl_acc_icon( 'bag', 'size-10' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<p class="mt-5 text-lg font-black text-primary"><?php esc_html_e( 'No order has been made yet.', 'woocommerce' ); ?></p>
		<p class="mt-1 max-w-sm text-sm leading-6 text-gray-500">بعد از اولین خرید، مراحل ثبت، پرداخت، آماده‌سازی و ارسال سفارش را این‌جا می‌بینید.</p>
		<a class="woocommerce-Button wc-forward mt-6 inline-flex min-h-11 items-center gap-2 rounded-full bg-primary px-6 text-sm font-bold text-white transition hover:bg-primary/90<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment ?>">
			<?php esc_html_e( 'Browse products', 'woocommerce' ); ?><?php echo gl_acc_icon( 'chevron', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	</div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
