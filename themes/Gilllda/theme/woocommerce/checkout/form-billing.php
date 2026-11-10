<?php
/**
 * Checkout billing information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-billing.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-billing-fields gl-card rounded-2xl border border-gray-200 bg-white p-4 lg:p-6">
	<div class="mb-4 flex items-center gap-3">
		<span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary" aria-hidden="true">
			<svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
		</span>
		<div>
			<?php if ( wc_ship_to_billing_address_only() && WC()->cart->needs_shipping() ) : ?>

				<h3 class="!m-0 text-base font-bold text-gray-900"><?php esc_html_e( 'Billing &amp; Shipping', 'woocommerce' ); ?></h3>

			<?php else : ?>

				<h3 class="!m-0 text-base font-bold text-gray-900">اطلاعات گیرنده و آدرس ارسال</h3>

			<?php endif; ?>
			<p class="text-xs text-gray-500">سفارش به همین نام و آدرس ارسال می‌شود.</p>
		</div>
	</div>

	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="woocommerce-billing-fields__field-wrapper">
		<?php
		$fields = $checkout->get_checkout_fields( 'billing' );

		foreach ( $fields as $key => $field ) {
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>

<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
	<div class="woocommerce-account-fields gl-card gl-account-card rounded-2xl border border-primary/15 bg-gradient-to-l from-primary/[0.04] to-white p-4 lg:p-6">
		<div class="mb-4 flex items-start gap-3">
			<span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary text-white" aria-hidden="true">
				<svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
			</span>
			<div>
				<h3 class="!m-0 text-base font-bold text-gray-900">حساب کاربری شما ساخته می‌شود</h3>
				<p class="mt-1 text-xs leading-6 text-gray-600">
					با <b>ایمیلی که بالا وارد کردید</b> و رمزی که اینجا انتخاب می‌کنید، حسابتان همزمان با ثبت سفارش ساخته می‌شود تا وضعیت سفارش را پیگیری کنید و خرید بعدی سریع‌تر باشد.
				</p>
			</div>
		</div>

		<?php if ( ! $checkout->is_registration_required() ) : ?>

			<p class="form-row form-row-wide create-account">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e( 'Create an account?', 'woocommerce' ); ?></span>
				</label>
			</p>

		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

		<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>

			<div class="create-account">
				<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
				<div class="clear"></div>
			</div>

		<?php endif; ?>

		<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>

		<p class="mt-2 flex items-center gap-1.5 text-[11px] text-gray-500">
			<svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
			یک رمز قوی انتخاب کنید؛ اطلاعات شما نزد ما محفوظ است.
		</p>
	</div>
<?php endif; ?>
