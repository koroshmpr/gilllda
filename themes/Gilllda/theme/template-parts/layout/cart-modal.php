<?php
/**
 * Cart drawer: bottom sheet on mobile, side drawer on desktop.
 *
 * Open/close state is the global `cart` flag on <body x-data> (header.php).
 * Behaviour (focus trap, swipe-to-close, qty/remove/undo) lives in
 * javascript/cart.js (Alpine component `glCartDrawer` + event delegation).
 * The swappable content (#gl-cart-body) is rendered by gl_cart_drawer_body().
 */
global $woo_active;
if ( ! $woo_active || ! function_exists( 'WC' ) || ! WC()->cart || ! function_exists( 'gl_cart_drawer_body' ) ) {
	return;
}
$cart_count = (int) WC()->cart->get_cart_contents_count();
?>
<div x-data="glCartDrawer"
     x-show="cart"
     x-cloak
     @keydown.escape.window="cart && (cart = false)"
     class="fixed inset-0 z-[100]"
     style="display: none;">

	<div x-show="cart"
	     x-transition:enter="transition ease-out duration-300"
	     x-transition:enter-start="opacity-0"
	     x-transition:enter-end="opacity-100"
	     x-transition:leave="transition ease-in duration-200"
	     x-transition:leave-start="opacity-100"
	     x-transition:leave-end="opacity-0"
	     @click="cart = false"
	     class="absolute inset-0 bg-primary/40 backdrop-blur-[2px]"
	     aria-hidden="true"></div>

	<section x-ref="panel"
	         x-show="cart"
	         x-transition:enter="transition ease-out duration-300 transform"
	         x-transition:enter-start="translate-y-full lg:translate-y-0 lg:-translate-x-full"
	         x-transition:enter-end="translate-y-0 lg:translate-x-0"
	         x-transition:leave="transition ease-in duration-200 transform"
	         x-transition:leave-start="translate-y-0 lg:translate-x-0"
	         x-transition:leave-end="translate-y-full lg:translate-y-0 lg:-translate-x-full"
	         @keydown.tab="trap($event)"
	         role="dialog"
	         aria-modal="true"
	         aria-labelledby="gl-cart-title"
	         tabindex="-1"
	         class="gl-cart-panel absolute bottom-0 left-0 right-0 flex max-h-[88dvh] flex-col overflow-hidden rounded-t-3xl bg-gray-50 shadow-2xl outline-none lg:right-auto lg:max-h-none lg:w-[420px] lg:rounded-none <?php echo is_admin_bar_showing() ? 'lg:top-8' : 'lg:top-0'; ?>">

		<div data-gl-drag class="flex cursor-grab touch-none justify-center pb-1 pt-2.5 lg:hidden" aria-hidden="true">
			<span class="h-1.5 w-12 rounded-full bg-gray-300"></span>
		</div>

		<header class="flex items-center justify-between gap-3 border-b border-gray-100 bg-white px-4 py-2 lg:py-3">
			<h2 id="gl-cart-title" class="flex items-center gap-2 text-base font-bold text-primary">
				سبد خرید
				<span data-gl-count-chip <?php echo $cart_count ? '' : 'hidden'; ?> class="rounded-lg bg-secondary px-2 py-0.5 text-xs font-bold text-white">
					<?php echo esc_html( $cart_count ); ?> کالا
				</span>
			</h2>
			<button type="button" data-gl-close x-ref="close"
			        class="flex size-11 cursor-pointer items-center justify-center rounded-xl bg-gray-50 text-gray-500 transition hover:bg-gray-100 hover:text-gray-800"
			        aria-label="بستن سبد خرید">
				<svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
				</svg>
			</button>
		</header>

		<div data-gl-undo-bar hidden class="mx-4 mt-3 flex items-center justify-between gap-3 rounded-2xl bg-primary px-4 py-2 text-sm text-white shadow-lg" role="status">
			<span data-gl-undo-text class="line-clamp-1"></span>
			<button type="button" data-gl-undo class="min-h-11 shrink-0 cursor-pointer rounded-lg px-3 font-bold text-amber-300 transition hover:bg-white/10">
				بازگرداندن
			</button>
		</div>

		<?php gl_cart_drawer_body(); ?>
	</section>
</div>

<div id="gl-toasts" class="pointer-events-none fixed left-3 right-3 top-3 z-[120] flex flex-col items-center gap-2 lg:right-auto lg:bottom-6 lg:left-6 lg:top-auto lg:w-96" aria-live="assertive" aria-atomic="true"></div>
<div id="gl-cart-live" class="sr-only" role="status" aria-live="polite" aria-atomic="true"></div>
