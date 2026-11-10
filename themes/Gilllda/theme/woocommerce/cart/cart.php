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
 * Gilllda: app-like card layout (div based). Every upstream hook, filter and
 * the class names WooCommerce's cart.js relies on (.woocommerce-cart-form,
 * .woocommerce-cart-form__contents, .cart_item, .product-remove > a,
 * input.qty, [name="update_cart"]) are kept.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'gl_checkout_steps' ) ) {
	gl_checkout_steps( 1 );
}

do_action( 'woocommerce_before_cart' ); ?>

<div class="gl-cart grid items-start gap-5 pb-44 lg:grid-cols-3 lg:gap-8 lg:pb-12">
	<form class="woocommerce-cart-form min-w-0 lg:col-span-2" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
		<?php do_action( 'woocommerce_before_cart_table' ); ?>

		<div class="mb-3 flex items-baseline justify-between gap-3">
			<h2 class="text-base font-bold text-primary lg:text-lg">سبد خرید شما</h2>
			<span class="text-xs text-gray-500"><?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?> کالا</span>
		</div>

		<div class="woocommerce-cart-form__contents space-y-3">
			<?php do_action( 'woocommerce_before_cart_contents' ); ?>

			<?php
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
				/**
				 * Filter the product name.
				 *
				 * @since 2.1.0
				 * @param string $product_name Name of the product in the cart.
				 * @param array $cart_item The product in the cart.
				 * @param string $cart_item_key Key for the product in the cart.
				 */
				$product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );

				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
					$base_name         = function_exists( 'gl_cart_item_base_name' ) ? gl_cart_item_base_name( $_product ) : $_product->get_name();
					$item_attrs        = function_exists( 'gl_cart_item_attrs' ) ? gl_cart_item_attrs( $cart_item, $_product ) : array();
					?>
					<div class="woocommerce-cart-form__cart-item gl-cart-item relative flex gap-3 rounded-2xl border border-gray-200 bg-white p-3 transition lg:gap-4 lg:p-4 <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

						<div class="product-thumbnail size-24 shrink-0 overflow-hidden rounded-xl border border-gray-100 bg-gray-50 lg:size-28 [&_img]:size-full [&_img]:object-cover">
							<?php
							/**
							 * Filter the product thumbnail displayed in the WooCommerce cart.
							 *
							 * This filter allows developers to customize the HTML output of the product
							 * thumbnail. It passes the product image along with cart item data
							 * for potential modifications before being displayed in the cart.
							 *
							 * @param string $thumbnail     The HTML for the product image.
							 * @param array  $cart_item     The cart item data.
							 * @param string $cart_item_key Unique key for the cart item.
							 *
							 * @since 2.1.0
							 */
							$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail', array( 'alt' => $base_name ) ), $cart_item, $cart_item_key );

							if ( ! $product_permalink ) {
								echo $thumbnail; // PHPCS: XSS ok.
							} else {
								printf( '<a href="%s" tabindex="-1" aria-hidden="true">%s</a>', esc_url( $product_permalink ), $thumbnail ); // PHPCS: XSS ok.
							}
							?>
						</div>

						<div class="flex min-w-0 flex-1 flex-col gap-1.5">
							<div class="flex items-start justify-between gap-2">
								<div class="product-name min-w-0 text-sm leading-6 lg:text-base" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
									<?php
									if ( ! $product_permalink ) {
										echo wp_kses_post( $base_name . '&nbsp;' );
									} else {
										/**
										 * This filter is documented above.
										 *
										 * @since 2.1.0
										 */
										echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s" class="font-bold text-gray-800 hover:text-primary">%s</a>', esc_url( $product_permalink ), $base_name ), $cart_item, $cart_item_key ) );
									}

									do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

									foreach ( $item_attrs as $attr ) {
										echo '<span class="mt-1 flex items-center gap-1.5 text-xs text-gray-500">';
										if ( $attr['swatch'] ) {
											echo '<span class="size-3 rounded-full ring-1 ring-black/10" style="background-color:' . esc_attr( $attr['swatch'] ) . '" aria-hidden="true"></span>';
										}
										echo esc_html( $attr['label'] . ': ' . $attr['value'] ) . '</span>';
									}

									// Meta data.
									echo wc_get_formatted_cart_item_data( $cart_item ); // PHPCS: XSS ok.

									// Backorder notification.
									if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
										echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification text-xs text-amber-700">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $product_id ) );
									}
									?>
								</div>

								<div class="product-remove shrink-0">
									<?php
									echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										'woocommerce_cart_item_remove_link',
										sprintf(
											'<a role="button" href="%s" class="gl-remove -me-2 -mt-2 flex size-11 items-center justify-center rounded-xl text-gray-400 transition hover:bg-secondary/5 hover:text-secondary" aria-label="%s" data-product_id="%s" data-product_sku="%s"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></a>',
											esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
											/* translators: %s is the product name */
											esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
											esc_attr( $product_id ),
											esc_attr( $_product->get_sku() )
										),
										$cart_item_key
									);
									?>
								</div>
							</div>

							<div class="mt-auto flex flex-wrap items-end justify-between gap-3">
								<div class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
									<?php
									if ( $_product->is_sold_individually() ) {
										$min_quantity = 1;
										$max_quantity = 1;
									} else {
										$min_quantity = 0;
										$max_quantity = $_product->get_max_purchase_quantity();
									}

									$product_quantity = woocommerce_quantity_input(
										array(
											'input_name'   => "cart[{$cart_item_key}][qty]",
											'input_value'  => $cart_item['quantity'],
											'max_value'    => $max_quantity,
											'min_value'    => $min_quantity,
											'product_name' => $product_name,
											'classes'      => array( 'input-text', 'qty', 'text' ),
										),
										$_product,
										false
									);

									$product_quantity = apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );

									if ( $_product->is_sold_individually() ) :
										echo $product_quantity; // PHPCS: XSS ok.
										echo '<span class="text-xs text-gray-500">۱ عدد</span>';
									else :
										?>
										<div class="gl-qty flex items-center rounded-xl border border-gray-200 bg-gray-50" data-gl-cart-qty>
											<button type="button" data-gl-cart-step="1" aria-label="افزایش تعداد"
											        class="hidden size-11 cursor-pointer items-center justify-center rounded-xl text-primary transition hover:bg-white [.gl-js_&]:flex">
												<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2.4" d="M12 5v14M5 12h14"/></svg>
											</button>
											<?php echo $product_quantity; // PHPCS: XSS ok. ?>
											<button type="button" data-gl-cart-step="-1" aria-label="کاهش تعداد"
											        class="hidden size-11 cursor-pointer items-center justify-center rounded-xl text-primary transition hover:bg-white [.gl-js_&]:flex">
												<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2.4" d="M5 12h14"/></svg>
											</button>
										</div>
									<?php endif; ?>
								</div>

								<div class="flex flex-col items-end gap-0.5 text-end">
									<span class="product-price text-[11px] text-gray-500" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
										<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // PHPCS: XSS ok. ?>
									</span>
									<span class="product-subtotal text-base font-black text-gray-900" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
										<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // PHPCS: XSS ok. ?>
									</span>
								</div>
							</div>
						</div>
					</div>
					<?php
				}
			}
			?>

			<?php do_action( 'woocommerce_cart_contents' ); ?>

			<div class="actions flex flex-col gap-3 rounded-2xl border border-dashed border-gray-300 bg-white p-3 sm:flex-row sm:items-center sm:justify-between">
				<?php if ( wc_coupons_enabled() ) { ?>
					<div class="coupon flex w-full flex-wrap items-center gap-2 sm:max-w-md">
						<label for="coupon_code" class="text-sm font-bold text-gray-700"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label>
						<input type="text" name="coupon_code" class="input-text min-w-0 flex-1" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" />
						<button type="submit" class="min-h-11 cursor-pointer rounded-xl bg-gray-800 px-4 text-sm font-bold text-white transition hover:brightness-125<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?></button>
						<?php do_action( 'woocommerce_cart_coupon' ); ?>
					</div>
				<?php } ?>

				<button type="submit" class="gl-nojs-only min-h-11 cursor-pointer rounded-xl border border-gray-300 bg-white px-4 text-sm font-bold text-gray-700<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>"><?php esc_html_e( 'Update cart', 'woocommerce' ); ?></button>

				<?php do_action( 'woocommerce_cart_actions' ); ?>

				<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
			</div>

			<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		</div>
		<?php do_action( 'woocommerce_after_cart_table' ); ?>
	</form>

	<div class="min-w-0 space-y-4 lg:sticky lg:top-28">
		<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

		<div class="cart-collaterals space-y-4">
			<?php
				/**
				 * Cart collaterals hook.
				 *
				 * @hooked woocommerce_cross_sell_display
				 * @hooked woocommerce_cart_totals - 10
				 */
				do_action( 'woocommerce_cart_collaterals' );
			?>
		</div>
	</div>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
