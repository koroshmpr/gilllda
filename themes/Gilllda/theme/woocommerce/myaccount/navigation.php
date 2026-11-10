<?php
/**
 * My Account navigation
 *
 * Gilllda: profile card + nav. Desktop = sticky side card; mobile = compact profile row and
 * horizontally scrollable tab chips (active chip scrolled into view).
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/navigation.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gl_user  = wp_get_current_user();
$gl_since = $gl_user->user_registered ? gl_acc_date( strtotime( $gl_user->user_registered ) + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ), false ) : '';
$gl_items = wc_get_account_menu_items();
$gl_out   = $gl_items['customer-logout'] ?? null;
unset( $gl_items['customer-logout'] );

do_action( 'woocommerce_before_account_navigation' );
?>

<nav class="woocommerce-MyAccount-navigation gl-acc-nav min-w-0 lg:sticky <?php echo is_admin_bar_showing() ? 'lg:top-28' : 'lg:top-20'; ?>" aria-label="<?php esc_attr_e( 'Account pages', 'woocommerce' ); ?>">
	<div class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-[0_8px_30px_-12px_rgba(16,37,66,0.15)] mag-in">

		<!-- Profile -->
		<div class="relative flex items-center gap-3 p-3 lg:flex-col lg:items-start lg:gap-4 lg:p-5">
			<div class="pointer-events-none absolute inset-x-0 top-0 hidden h-20 bg-gradient-to-l from-primary to-[#1d3a63] lg:block" aria-hidden="true"></div>
			<span class="relative grid size-12 shrink-0 place-items-center rounded-full bg-gradient-to-br from-primary to-secondary text-base font-black text-white ring-4 ring-white lg:mt-6 lg:size-16 lg:text-xl" aria-hidden="true">
				<?php echo esc_html( gl_acc_initials( $gl_user ) ); ?>
			</span>
			<div class="relative min-w-0 flex-1">
				<p class="truncate font-black text-primary lg:text-lg"><?php echo esc_html( $gl_user->display_name ); ?></p>
				<?php if ( $gl_since ) : ?>
					<p class="mt-0.5 truncate text-xs text-gray-500"><?php echo esc_html( 'عضو گیلدا از ' . $gl_since ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $gl_out ) : ?>
				<a href="<?php echo esc_url( wc_logout_url() ); ?>" class="relative grid size-11 shrink-0 place-items-center rounded-full border border-gray-100 text-gray-500 transition hover:border-secondary/30 hover:text-secondary lg:hidden" aria-label="<?php echo esc_attr( $gl_out ); ?>" title="<?php echo esc_attr( $gl_out ); ?>">
					<?php echo gl_acc_icon( 'logout', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			<?php endif; ?>
		</div>

		<!-- Tabs: chips on mobile, list on desktop -->
		<ul class="gl-acc-tabs m-0 flex list-none gap-2 overflow-x-auto border-t border-gray-100 p-2 lg:flex-col lg:gap-1 lg:overflow-visible lg:p-3"
			x-data x-init="$nextTick(() => { const a = $el.querySelector('[aria-current]'); if (a && window.innerWidth < 1024) { const r = a.getBoundingClientRect(), b = $el.getBoundingClientRect(); $el.scrollLeft += (r.left + r.width / 2) - (b.left + b.width / 2); } })">
			<?php
			foreach ( $gl_items as $endpoint => $label ) :
				$gl_active = wc_is_current_account_menu_item( $endpoint );
				?>
				<li class="<?php echo esc_attr( wc_get_account_menu_item_classes( $endpoint ) ); ?> shrink-0">
					<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"
						class="flex min-h-11 items-center gap-2 whitespace-nowrap rounded-full px-4 text-sm transition-colors lg:gap-3 lg:rounded-2xl lg:px-3 <?php echo $gl_active ? 'bg-primary font-bold text-white shadow-[0_6px_16px_-6px_rgba(16,37,66,0.6)]' : 'bg-gray-50 font-medium text-gray-600 hover:bg-gray-100 hover:text-primary lg:bg-transparent'; ?>"
						<?php echo $gl_active ? 'aria-current="page"' : ''; ?>>
						<?php echo gl_acc_menu_icon( $endpoint ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="lg:flex-1"><?php echo esc_html( $label ); ?></span>
						<?php if ( ! $gl_active ) : ?>
							<span class="hidden text-gray-300 lg:inline"><?php echo gl_acc_icon( 'chevron', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>

			<?php if ( $gl_out ) : ?>
				<li class="<?php echo esc_attr( wc_get_account_menu_item_classes( 'customer-logout' ) ); ?> hidden lg:mt-2 lg:block lg:border-t lg:border-gray-100 lg:pt-2">
					<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'customer-logout' ) ); ?>" class="flex min-h-11 items-center gap-3 rounded-2xl px-3 text-sm font-medium text-gray-500 transition-colors hover:bg-red-50 hover:text-secondary">
						<?php echo gl_acc_menu_icon( 'customer-logout' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $gl_out ); ?></span>
					</a>
				</li>
			<?php endif; ?>
		</ul>
	</div>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
