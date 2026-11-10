<?php
/**
 * Lost password confirmation text.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/lost-password-confirmation.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.9.0
 */

defined( 'ABSPATH' ) || exit;

wc_print_notice( esc_html__( 'Password reset email has been sent.', 'woocommerce' ) );
?>

<?php do_action( 'woocommerce_before_lost_password_confirmation_message' ); ?>

<div class="gl-auth-card mag-in mx-auto my-6 w-full max-w-md rounded-3xl border border-gray-100 bg-white p-5 text-center shadow-[0_18px_40px_-20px_rgba(16,37,66,0.35)] sm:p-7">
	<span class="mx-auto mb-4 grid size-14 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"><?php echo gl_acc_icon( 'check', 'size-7' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<h2 class="text-lg font-black text-primary">ایمیل بازیابی ارسال شد</h2>
	<p class="mt-2 text-sm leading-7 text-gray-500"><?php echo esc_html( apply_filters( 'woocommerce_lost_password_confirmation_message', esc_html__( 'A password reset email has been sent to the email address on file for your account, but may take several minutes to show up in your inbox. Please wait at least 10 minutes before attempting another reset.', 'woocommerce' ) ) ); ?></p>
	<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-full border border-gray-200 px-6 text-sm font-bold text-primary transition hover:border-primary">بازگشت به ورود</a>
</div>

<?php do_action( 'woocommerce_after_lost_password_confirmation_message' ); ?>
