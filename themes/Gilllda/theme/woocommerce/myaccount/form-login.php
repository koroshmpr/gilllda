<?php
/**
 * Login Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

	<style>
		[x-cloak] { display: none !important; }
		.rtl { direction: rtl; }
	</style>

<?php do_action( 'woocommerce_before_customer_login_form' ); ?>

	<div id="customer_login" class="lg:w-1/2 mx-auto my-4 lg:h-[70dvh] overflow-hidden lg:px-4 rtl" dir="rtl" x-data="{ tab: 'login' }">

		<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
			<div class="flex mb-6 bg-gray-100 p-1.5 rounded-2xl relative z-10">
				<button
					type="button"
					@click="tab = 'login'"
					:class="tab === 'login' ? 'bg-white shadow-md text-gray-900' : 'text-gray-500 hover:text-gray-700'"
					class="flex-1 py-3 text-sm font-black rounded-xl cursor-pointer transition-all duration-300 transform"
					:class="tab === 'login' ? 'scale-100' : 'scale-95'"
				>
					<?php esc_html_e( 'ورود به حساب', 'woocommerce' ); ?>
				</button>
				<button
					type="button"
					@click="tab = 'register'"
					:class="tab === 'register' ? 'bg-white shadow-md text-gray-900' : 'text-gray-500 hover:text-gray-700'"
					class="flex-1 py-3 text-sm font-black rounded-xl cursor-pointer transition-all duration-300 transform"
					:class="tab === 'register' ? 'scale-100' : 'scale-95'"
				>
					<?php esc_html_e( 'ثبت‌نام جدید', 'woocommerce' ); ?>
				</button>
			</div>
		<?php endif; ?>

		<div class="bg-gray-50 rounded-lg border border-gray-100 overflow-hidden relative">

			<div x-show="tab === 'login'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4">
				<div class="p-3 lg:p-5">
					<div class="mb-2 p-3">
						<h2 class="text-3xl !mt-0 font-black text-gray-900 mb-2">خوش آمدید!</h2>
						<div class="h-1 w-12 bg-primary rounded-full"></div>
					</div>

					<form class="woocommerce-form woocommerce-form-login !border-none !my-0 !p-0 login space-y-4" method="post">
						<?php do_action( 'woocommerce_login_form_start' ); ?>

						<div class="space-y-2">
							<label class="text-sm font-bold text-gray-700 pr-1" for="username">نام کاربری یا ایمیل</label>
							<input type="text" class="w-full px-5 py-4 rounded-2xl border border-gray-200 focus:border-primary focus:ring-4 focus:ring-primary/10 outline-none transition-all placeholder:text-gray-300" name="username" id="username" placeholder="example@mail.com" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
						</div>

						<div class="space-y-2">
							<label class="text-sm font-bold text-gray-700 pr-1" for="password">رمز عبور</label>
							<input class="w-full px-5 py-4 rounded-2xl border border-gray-200 focus:border-primary focus:ring-4 focus:ring-primary/10 outline-none transition-all placeholder:text-gray-300" type="password" name="password" id="password" placeholder="••••••••" />
						</div>

						<div class="flex items-center justify-between">
							<label class="flex items-center gap-2 cursor-pointer group">
								<input class="w-5 h-5 rounded-lg border-gray-300 text-primary focus:ring-primary transition-all" name="rememberme" type="checkbox" id="rememberme" value="forever" />
								<span class="text-xs text-gray-500 font-medium">مرا به خاطر بسپار</span>
							</label>
							<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="text-xs text-primary hover:text-pink-700 font-bold transition-colors">فراموشی رمز؟</a>
						</div>

						<?php do_action( 'woocommerce_login_form' ); ?>

						<div class="pt-2">
							<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
							<button type="submit" class="w-full py-4 bg-primary cursor-pointer text-white rounded-2xl font-black text-lg shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300" name="login" value="ورود">
								ورود به پنل کاربری
							</button>
						</div>

						<?php do_action( 'woocommerce_login_form_end' ); ?>
					</form>
				</div>
			</div>

			<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
				<div x-show="tab === 'register'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4">
					<div class="p-3 lg:p-5">
						<div class="mb-2 p-3">
							<h2 class="text-3xl !mt-0 font-black text-gray-900 mb-2">عضویت سریع</h2>
							<div class="h-1 w-12 bg-gray-900 rounded-full"></div>
						</div>

						<form method="post" class="woocommerce-form woocommerce-form-register !border-none !my-0 !p-0 register space-y-4" <?php do_action( 'woocommerce_register_form_tag' ); ?> >
							<?php do_action( 'woocommerce_register_form_start' ); ?>

							<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
								<div class="space-y-2">
									<label class="text-sm font-bold text-gray-700 pr-1" for="reg_username">نام کاربری انتخابی</label>
									<input type="text" class="w-full px-5 py-4 rounded-2xl border border-gray-200 focus:border-primary focus:ring-4 focus:ring-primary/10 outline-none transition-all" name="username" id="reg_username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
								</div>
							<?php endif; ?>

							<div class="space-y-2">
								<label class="text-sm font-bold text-gray-700 pr-1" for="reg_email">ایمیل شما</label>
								<input type="email" class="w-full px-5 py-4 rounded-2xl border border-gray-200 focus:border-primary focus:ring-4 focus:ring-primary/10 outline-none transition-all" name="email" id="reg_email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" />
							</div>

							<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
								<div class="space-y-2">
									<label class="text-sm font-bold text-gray-700 pr-1" for="reg_password">رمز عبور</label>
									<input type="password" class="w-full px-5 py-4 rounded-2xl border border-gray-200 focus:border-primary focus:ring-4 focus:ring-primary/10 outline-none transition-all" name="password" id="reg_password" autocomplete="new-password" />
								</div>
							<?php endif; ?>

							<?php do_action( 'woocommerce_register_form' ); ?>

							<div class="pt-4">
								<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
								<button type="submit" class="w-full py-4 bg-gray-900 cursor-pointer text-white rounded-2xl font-black text-lg shadow-xl shadow-gray-900/20 hover:bg-black hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300" name="register" value="عضویت">
									ساخت حساب کاربری
								</button>
							</div>

							<?php do_action( 'woocommerce_register_form_end' ); ?>
						</form>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
