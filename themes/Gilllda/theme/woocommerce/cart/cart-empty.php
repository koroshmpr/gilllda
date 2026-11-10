<?php
/**
 * Empty cart page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-empty.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked wc_empty_cart_message - 10 (removed in inc/product/ajax-cart.php:
 * the .wc-empty-cart-message wrapper below replaces it; WooCommerce's cart.js
 * needs that wrapper when the last item is removed on the cart page).
 */
do_action( 'woocommerce_cart_is_empty' );
?>
<div class="wc-empty-cart-message">
	<div class="cart-empty mx-auto my-10 flex max-w-md flex-col items-center gap-5 rounded-3xl border border-gray-100 bg-white px-6 py-12 text-center lg:my-16" role="status">
		<div class="flex size-28 items-center justify-center rounded-full bg-primary/5">
			<?php get_template_part( 'template-parts/svg/cart', null, array( 'size' => 56, 'class' => 'text-primary/40' ) ); ?>
		</div>
		<div class="space-y-1.5">
			<p class="text-xl font-bold text-gray-800">سبد خرید شما خالی است</p>
			<p class="text-sm text-gray-500">هنوز کیفی انتخاب نکرده‌اید؛ مدل‌ها و رنگ‌های جدید را ببینید.</p>
		</div>
		<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
			<p class="return-to-shop">
				<a class="wc-backward inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-primary px-8 text-sm font-bold !text-white shadow-sm shadow-primary/20 transition hover:brightness-110<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
					<?php
					/**
					 * Filter "Return To Shop" text.
					 *
					 * @since 4.6.0
					 * @param string $default_text Default text.
					 */
					echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Return to shop', 'woocommerce' ) ) );
					?>
				</a>
			</p>
		<?php endif; ?>
	</div>
</div>
