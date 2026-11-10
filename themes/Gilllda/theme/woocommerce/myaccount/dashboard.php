<?php
/**
 * My Account Dashboard
 *
 * Gilllda: greeting, KPI tiles, latest order with a live progress tracker, quick actions,
 * profile-completion nudge and recent orders.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$gl_uid     = get_current_user_id();
$gl_user    = isset( $current_user ) && $current_user instanceof WP_User ? $current_user : wp_get_current_user();
$gl_stats   = gl_acc_customer_stats( $gl_uid );
$gl_profile = gl_acc_profile_status( $gl_uid );
$gl_support = gl_acc_support();
$gl_shop    = apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

// Featured order: newest in-progress order, otherwise the newest order.
$gl_active = wc_get_orders(
	array(
		'customer_id' => $gl_uid,
		'status'      => gl_acc_active_statuses(),
		'limit'       => 1,
		'orderby'     => 'date',
		'order'       => 'DESC',
	)
);
$gl_recent = wc_get_orders(
	array(
		'customer_id' => $gl_uid,
		'limit'       => 4,
		'orderby'     => 'date',
		'order'       => 'DESC',
	)
);
$gl_featured = $gl_active ? $gl_active[0] : ( $gl_recent ? $gl_recent[0] : null );
$gl_others   = array_slice(
	array_values(
		array_filter(
			$gl_recent,
			static fn( $o ) => ! $gl_featured || $o->get_id() !== $gl_featured->get_id()
		)
	),
	0,
	3
);

$gl_hour     = (int) current_time( 'G' );
$gl_greeting = $gl_hour < 5 ? 'شب بخیر' : ( $gl_hour < 12 ? 'صبح بخیر' : ( $gl_hour < 17 ? 'ظهر بخیر' : ( $gl_hour < 20 ? 'عصر بخیر' : 'شب بخیر' ) ) );

$gl_kpis = array(
	array(
		'icon'  => 'orders',
		'label' => 'کل سفارش‌ها',
		'value' => gl_acc_num( number_format_i18n( $gl_stats['orders'] ) ),
		'url'   => wc_get_account_endpoint_url( 'orders' ),
		'tone'  => 'bg-primary/5 text-primary',
	),
	array(
		'icon'  => 'truck',
		'label' => 'در جریان',
		'value' => gl_acc_num( number_format_i18n( $gl_stats['active'] ) ),
		'url'   => wc_get_account_endpoint_url( 'orders' ),
		'tone'  => 'bg-sky-50 text-sky-700',
	),
	array(
		'icon'  => 'wallet',
		'label' => 'مجموع خرید',
		'value' => html_entity_decode( wp_strip_all_tags( wc_price( $gl_stats['spent'] ) ), ENT_QUOTES, 'UTF-8' ),
		'url'   => wc_get_account_endpoint_url( 'orders' ),
		'tone'  => 'bg-emerald-50 text-emerald-700',
	),
	array(
		'icon'  => 'address',
		'label' => 'آدرس ارسال',
		'value' => $gl_profile['address_ok'] ? 'ثبت شده' : 'ناقص',
		'url'   => wc_get_account_endpoint_url( 'edit-address' ),
		'tone'  => $gl_profile['address_ok'] ? 'bg-violet-50 text-violet-700' : 'bg-amber-50 text-amber-700',
	),
);
?>

<div class="gl-dash space-y-5 lg:space-y-6">

	<!-- Greeting -->
	<section class="mag-in relative overflow-hidden rounded-3xl bg-gradient-to-l from-primary via-[#173257] to-[#1f3d68] p-5 text-white shadow-[0_18px_40px_-20px_rgba(16,37,66,0.7)] sm:p-7">
		<span class="pointer-events-none absolute -top-16 -left-10 size-48 rounded-full bg-secondary/40 blur-3xl" aria-hidden="true"></span>
		<span class="pointer-events-none absolute -bottom-20 left-1/3 size-40 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></span>
		<div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
			<div class="min-w-0">
				<p class="text-sm text-white/70"><?php echo esc_html( $gl_greeting ); ?></p>
				<h2 class="mt-1 text-xl font-black leading-9 sm:text-2xl"><?php echo esc_html( gl_acc_first_name( $gl_user ) . ' عزیز، خوش آمدید' ); ?></h2>
				<p class="mt-2 max-w-md text-sm leading-6 text-white/75">
					<?php echo esc_html( $gl_stats['active'] ? 'وضعیت سفارش‌هایتان را لحظه‌به‌لحظه از همین‌جا دنبال کنید.' : 'سفارش‌ها، آدرس‌ها و اطلاعات حسابتان همه این‌جاست.' ); ?>
				</p>
			</div>
			<div class="flex shrink-0 flex-wrap gap-2">
				<a href="<?php echo esc_url( $gl_shop ); ?>" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-white px-5 text-sm font-bold text-primary transition hover:bg-white/90">
					<?php echo gl_acc_icon( 'bag', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					ادامهٔ خرید
				</a>
			</div>
		</div>
		<p class="relative mt-4 text-xs text-white/60">
			<?php
			printf(
				/* translators: 1: user display name 2: logout url */
				wp_kses( '%1$s نیستید؟ <a href="%2$s" class="underline underline-offset-4 hover:text-white">خروج از حساب</a>', array( 'a' => array( 'href' => array(), 'class' => array() ) ) ),
				esc_html( $gl_user->display_name ),
				esc_url( wc_logout_url() )
			);
			?>
		</p>
	</section>

	<!-- KPIs -->
	<section class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="خلاصهٔ حساب">
		<?php foreach ( $gl_kpis as $gl_i => $gl_kpi ) : ?>
			<a href="<?php echo esc_url( $gl_kpi['url'] ); ?>" data-reveal style="--d:<?php echo (int) $gl_i; ?>"
				class="group flex min-w-0 flex-col gap-3 rounded-3xl border border-gray-100 bg-white p-4 shadow-[0_8px_30px_-16px_rgba(16,37,66,0.18)] transition hover:-translate-y-0.5 hover:border-primary/20">
				<span class="grid size-10 place-items-center rounded-2xl <?php echo esc_attr( $gl_kpi['tone'] ); ?>"><?php echo gl_acc_icon( $gl_kpi['icon'], 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="min-w-0">
					<span class="block text-xs text-gray-500"><?php echo esc_html( $gl_kpi['label'] ); ?></span>
					<span class="mt-1 block truncate text-lg font-black text-primary"><?php echo esc_html( $gl_kpi['value'] ); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
	</section>

	<?php if ( $gl_profile['percent'] < 100 ) : ?>
		<!-- Profile nudge -->
		<section class="flex flex-col gap-4 rounded-3xl border border-amber-100 bg-amber-50/60 p-4 sm:flex-row sm:items-center sm:p-5" data-reveal>
			<div class="relative grid size-14 shrink-0 place-items-center" aria-hidden="true">
				<svg class="size-14 -rotate-90" viewBox="0 0 36 36"><circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" class="text-amber-100"/><circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" pathLength="100" stroke-dasharray="<?php echo (int) $gl_profile['percent']; ?> 100" class="text-amber-500"/></svg>
				<span class="absolute text-xs font-black text-amber-700"><?php echo esc_html( gl_acc_num( $gl_profile['percent'] ) . '٪' ); ?></span>
			</div>
			<div class="min-w-0 flex-1">
				<p class="font-black text-amber-900">پروفایلتان را کامل کنید</p>
				<p class="mt-1 text-sm leading-6 text-amber-800/80">
					<?php echo esc_html( 'برای ارسال سریع‌تر و بی‌دردسر، این موارد را تکمیل کنید: ' . implode( '، ', $gl_profile['missing'] ) . '.' ); ?>
				</p>
			</div>
			<div class="flex flex-wrap gap-2">
				<?php if ( ! $gl_profile['address_ok'] || ! $gl_profile['phone_ok'] ) : ?>
					<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'billing', wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-amber-500 px-4 text-sm font-bold text-white transition hover:bg-amber-600">
						<?php echo gl_acc_icon( 'address', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>تکمیل آدرس و موبایل
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-amber-500 px-4 text-sm font-bold text-white transition hover:bg-amber-600">
						<?php echo gl_acc_icon( 'user', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>تکمیل اطلاعات
					</a>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<!-- Latest order -->
	<?php if ( $gl_featured ) : ?>
		<section aria-labelledby="gl-dash-order">
			<div class="mb-3 flex items-center justify-between gap-3">
				<h2 id="gl-dash-order" class="text-base font-black text-primary"><?php echo esc_html( $gl_active ? 'سفارش در جریان' : 'آخرین سفارش' ); ?></h2>
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="inline-flex min-h-11 items-center gap-1 text-sm font-bold text-gray-500 hover:text-primary">
					همهٔ سفارش‌ها<?php echo gl_acc_icon( 'chevron', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			</div>

			<div class="rounded-3xl border border-gray-100 bg-white shadow-[0_8px_30px_-12px_rgba(16,37,66,0.15)]" data-reveal>
				<div class="flex flex-wrap items-center gap-3 border-b border-gray-100 p-4 sm:p-5">
					<?php gl_acc_order_thumbs( $gl_featured, 3, 'size-12' ); ?>
					<div class="min-w-0 flex-1">
						<p class="font-black text-primary"><?php echo esc_html( 'سفارش #' . gl_acc_num( $gl_featured->get_order_number() ) ); ?></p>
						<p class="mt-0.5 text-xs text-gray-500">
							<?php echo esc_html( gl_acc_date( $gl_featured->get_date_created() ) ); ?>
							<span class="mx-1 text-gray-300">•</span>
							<?php echo wp_kses_post( $gl_featured->get_formatted_order_total() ); ?>
						</p>
					</div>
					<?php echo gl_acc_status_chip( $gl_featured ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="p-4 sm:p-5">
					<?php gl_order_progress( $gl_featured, array( 'card' => false ) ); ?>
				</div>
				<div class="flex flex-wrap gap-2 border-t border-gray-100 p-4 sm:p-5">
					<a href="<?php echo esc_url( $gl_featured->get_view_order_url() ); ?>" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-primary px-5 text-sm font-bold text-white transition hover:bg-primary/90">
						جزئیات سفارش<?php echo gl_acc_icon( 'chevron', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
					<?php if ( $gl_featured->needs_payment() ) : ?>
						<a href="<?php echo esc_url( $gl_featured->get_checkout_payment_url() ); ?>" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-secondary px-5 text-sm font-bold text-white transition hover:bg-secondary/90">
							<?php echo gl_acc_icon( 'wallet', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>پرداخت سفارش
						</a>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php else : ?>
		<section class="flex flex-col items-center rounded-3xl border border-dashed border-gray-200 bg-white px-5 py-10 text-center" data-reveal>
			<span class="grid size-16 place-items-center rounded-full bg-primary/5 text-primary"><?php echo gl_acc_icon( 'bag', 'size-8' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<p class="mt-4 text-lg font-black text-primary">هنوز سفارشی ثبت نکرده‌اید</p>
			<p class="mt-1 max-w-sm text-sm leading-6 text-gray-500">کیف محبوبتان را پیدا کنید؛ مراحل سفارش را همین‌جا قدم‌به‌قدم دنبال خواهید کرد.</p>
			<a href="<?php echo esc_url( $gl_shop ); ?>" class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-full bg-primary px-6 text-sm font-bold text-white transition hover:bg-primary/90">
				مشاهدهٔ محصولات<?php echo gl_acc_icon( 'chevron', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</section>
	<?php endif; ?>

	<!-- Quick actions -->
	<section aria-labelledby="gl-dash-quick">
		<h2 id="gl-dash-quick" class="mb-3 text-base font-black text-primary">دسترسی سریع</h2>
		<div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
			<?php
			$gl_quick = array(
				array( 'icon' => 'bag', 'label' => 'ادامهٔ خرید', 'url' => $gl_shop ),
				array( 'icon' => 'orders', 'label' => 'سفارش‌های من', 'url' => wc_get_account_endpoint_url( 'orders' ) ),
				array( 'icon' => 'address', 'label' => 'ویرایش آدرس', 'url' => wc_get_account_endpoint_url( 'edit-address' ) ),
				array( 'icon' => 'user', 'label' => 'جزئیات حساب', 'url' => wc_get_account_endpoint_url( 'edit-account' ) ),
				array( 'icon' => 'compare', 'label' => 'مقایسهٔ محصولات', 'url' => home_url( '/compare/' ), 'compare' => true ),
			);
			if ( $gl_support['whatsapp'] ) {
				$gl_quick[] = array( 'icon' => 'chat', 'label' => 'پشتیبانی واتساپ', 'url' => $gl_support['whatsapp'], 'external' => true );
			} elseif ( $gl_support['tel'] ) {
				$gl_quick[] = array( 'icon' => 'phone', 'label' => 'تماس با پشتیبانی', 'url' => 'tel:' . $gl_support['tel'] );
			} elseif ( $gl_support['contact'] ) {
				$gl_quick[] = array( 'icon' => 'headset', 'label' => 'تماس با ما', 'url' => $gl_support['contact'] );
			}
			foreach ( $gl_quick as $gl_i => $gl_q ) :
				?>
				<a href="<?php echo esc_url( $gl_q['url'], array( 'http', 'https', 'tel' ) ); ?>"<?php echo ! empty( $gl_q['external'] ) ? ' target="_blank" rel="noopener"' : ''; ?>
					data-reveal style="--d:<?php echo (int) $gl_i; ?>"
					class="group relative flex min-h-[4.5rem] items-center gap-3 rounded-3xl border border-gray-100 bg-white p-3 shadow-[0_8px_30px_-16px_rgba(16,37,66,0.18)] transition hover:-translate-y-0.5 hover:border-primary/20 sm:p-4"
					<?php if ( ! empty( $gl_q['compare'] ) ) : ?>x-data="{ n: 0 }" x-init="try { n = (JSON.parse(localStorage.getItem('compare_products') || '[]') || []).length } catch (e) {}"<?php endif; ?>>
					<span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-gray-50 text-primary transition group-hover:bg-primary group-hover:text-white"><?php echo gl_acc_icon( $gl_q['icon'], 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="min-w-0 flex-1 text-sm font-bold leading-6 text-gray-700"><?php echo esc_html( $gl_q['label'] ); ?></span>
					<?php if ( ! empty( $gl_q['compare'] ) ) : ?>
						<span x-show="n > 0" x-text="n.toLocaleString('fa-IR')" style="display:none" class="absolute top-2 left-2 grid min-w-5 place-items-center rounded-full bg-secondary px-1.5 text-[11px] font-bold leading-5 text-white"></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>
	</section>

	<?php if ( $gl_others ) : ?>
		<!-- Recent orders -->
		<section aria-labelledby="gl-dash-recent">
			<h2 id="gl-dash-recent" class="mb-3 text-base font-black text-primary">سفارش‌های اخیر</h2>
			<ul class="m-0 list-none divide-y divide-gray-100 overflow-hidden rounded-3xl border border-gray-100 bg-white p-0 shadow-[0_8px_30px_-16px_rgba(16,37,66,0.18)]" data-reveal>
				<?php foreach ( $gl_others as $gl_o ) : ?>
					<li>
						<a href="<?php echo esc_url( $gl_o->get_view_order_url() ); ?>" class="flex min-h-16 items-center gap-3 p-3 transition hover:bg-gray-50 sm:px-4">
							<?php gl_acc_order_thumbs( $gl_o, 1, 'size-11' ); ?>
							<span class="min-w-0 flex-1">
								<span class="block text-sm font-bold text-primary"><?php echo esc_html( 'سفارش #' . gl_acc_num( $gl_o->get_order_number() ) ); ?></span>
								<span class="block text-xs text-gray-500"><?php echo esc_html( gl_acc_date( $gl_o->get_date_created() ) ); ?></span>
							</span>
							<?php echo gl_acc_status_chip( $gl_o, 'max-sm:hidden' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="text-gray-300"><?php echo gl_acc_icon( 'chevron', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( current_user_can( 'edit_shop_orders' ) ) : ?>
		<?php
		$gl_hpos = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		?>
		<a href="<?php echo esc_url( admin_url( $gl_hpos ? 'admin.php?page=wc-orders' : 'edit.php?post_type=shop_order' ) ); ?>" class="flex min-h-11 items-center justify-center gap-2 rounded-full border border-gray-200 bg-white px-5 text-sm font-bold text-gray-600 transition hover:border-primary hover:text-primary">
			<?php echo gl_acc_icon( 'dashboard', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>مدیریت سفارش‌های فروشگاه
		</a>
	<?php endif; ?>

	<?php gl_acc_help_card(); ?>
</div>

<?php
/**
 * My Account dashboard.
 *
 * @since 2.6.0
 */
do_action( 'woocommerce_account_dashboard' );

/**
 * Deprecated woocommerce_before_my_account action.
 *
 * @deprecated 2.6.0
 */
do_action( 'woocommerce_before_my_account' );

/**
 * Deprecated woocommerce_after_my_account action.
 *
 * @deprecated 2.6.0
 */
do_action( 'woocommerce_after_my_account' );
