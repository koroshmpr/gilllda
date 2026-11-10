<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'gl_checkout_steps' ) ) {
	gl_checkout_steps( 2 );
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

?>

<form name="checkout" method="post" class="checkout woocommerce-checkout gl-checkout grid items-start gap-5 pb-44 lg:grid-cols-3 lg:gap-8 lg:pb-12" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">

	<?php if ( $checkout->get_checkout_fields() ) : ?>

		<?php // One grid column: hook output (e.g. <wc-order-attribution-inputs>) must not become extra grid cells. ?>
		<div class="min-w-0 lg:col-span-2">
			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

			<div class="space-y-4" id="customer_details">
				<div class="col-1 space-y-4">
					<?php do_action( 'woocommerce_checkout_billing' ); ?>
				</div>

				<div class="col-2">
					<?php do_action( 'woocommerce_checkout_shipping' ); ?>
				</div>
			</div>

			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
		</div>

	<?php endif; ?>

	<aside class="gl-summary min-w-0 rounded-2xl border border-gray-200 bg-white p-4 lg:sticky lg:top-28 lg:p-5" aria-labelledby="order_review_heading">
		<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

		<h3 id="order_review_heading" class="mb-3 flex items-center justify-between text-base font-bold text-primary">
			<?php esc_html_e( 'Your order', 'woocommerce' ); ?>
			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="flex min-h-11 items-center text-xs font-normal text-gray-500 hover:text-primary">ویرایش سبد</a>
		</h3>

		<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php do_action( 'woocommerce_checkout_order_review' ); ?>
		</div>

		<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
	</aside>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
