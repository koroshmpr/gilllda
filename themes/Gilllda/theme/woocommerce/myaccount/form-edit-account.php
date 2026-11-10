<?php
/**
 * Edit account form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook - woocommerce_before_edit_account_form.
 *
 * @since 2.6.0
 */
do_action( 'woocommerce_before_edit_account_form' );

$gl_card = 'rounded-3xl border border-gray-100 bg-white p-4 shadow-[0_8px_30px_-16px_rgba(16,37,66,0.18)] sm:p-6';
?>

<form class="woocommerce-EditAccountForm edit-account gl-form space-y-5" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?> >

	<?php do_action( 'woocommerce_edit_account_form_start' ); ?>

	<section class="<?php echo esc_attr( $gl_card ); ?> mag-in">
		<h2 class="mb-1 flex items-center gap-2 text-base font-black text-primary"><?php echo gl_acc_icon( 'user', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>اطلاعات شخصی</h2>
		<p class="mb-5 text-sm text-gray-500">این اطلاعات در فاکتور و ارتباط با شما استفاده می‌شود.</p>

		<div class="gl-form-grid">
			<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
				<label for="account_first_name"><?php esc_html_e( 'First name', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>" aria-required="true" />
			</p>
			<p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
				<label for="account_last_name"><?php esc_html_e( 'Last name', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>" aria-required="true" />
			</p>
			<div class="clear"></div>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="account_display_name"><?php esc_html_e( 'Display name', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_display_name" id="account_display_name" aria-describedby="account_display_name_description" value="<?php echo esc_attr( $user->display_name ); ?>" aria-required="true" />
				<span id="account_display_name_description"><em><?php echo wc_reviews_enabled() ? esc_html__( 'This will be how your name will be displayed in the account section and in reviews', 'woocommerce' ) : esc_html__( 'This will be how your name will be displayed in the account section', 'woocommerce' ); ?></em></span>
			</p>
			<div class="clear"></div>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="account_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" autocomplete="email" dir="ltr" value="<?php echo esc_attr( $user->user_email ); ?>" aria-required="true" />
			</p>

			<?php
				/**
				 * Hook where additional fields should be rendered.
				 *
				 * @since 8.7.0
				 */
				do_action( 'woocommerce_edit_account_form_fields' );
			?>
		</div>
	</section>

	<fieldset class="<?php echo esc_attr( $gl_card ); ?> m-0" data-reveal>
		<legend class="sr-only"><?php esc_html_e( 'Password change', 'woocommerce' ); ?></legend>
		<p class="mb-1 flex items-center gap-2 text-base font-black text-primary" aria-hidden="true"><?php echo gl_acc_icon( 'key', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Password change', 'woocommerce' ); ?></p>
		<p class="mb-5 text-sm text-gray-500">اگر نمی‌خواهید رمز عبور را تغییر دهید، این بخش را خالی بگذارید.</p>

		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="password_current"><?php esc_html_e( 'Current password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
			<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" autocomplete="current-password" />
		</p>
		<div class="gl-form-grid">
			<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
				<label for="password_1"><?php esc_html_e( 'New password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
				<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" autocomplete="new-password" />
			</p>
			<p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
				<label for="password_2"><?php esc_html_e( 'Confirm new password', 'woocommerce' ); ?></label>
				<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" autocomplete="new-password" />
			</p>
		</div>
	</fieldset>
	<div class="clear"></div>

	<?php
		/**
		 * My Account edit account form.
		 *
		 * Gilllda: inc/otp-login-form.php renders the phone-OTP box here when SMS is configured.
		 *
		 * @since 2.6.0
		 */
		do_action( 'woocommerce_edit_account_form' );
	?>

	<p class="gl-form-submit m-0">
		<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
		<button type="submit" class="woocommerce-Button gl-btn inline-flex min-h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-primary px-8 text-sm font-bold text-white transition hover:bg-primary/90 sm:w-auto<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="save_account_details" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php echo gl_acc_icon( 'check', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
		<input type="hidden" name="action" value="save_account_details" />
	</p>

	<?php do_action( 'woocommerce_edit_account_form_end' ); ?>
</form>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
