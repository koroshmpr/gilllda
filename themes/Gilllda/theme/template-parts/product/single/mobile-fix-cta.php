<?php
/**
 * Mobile / tablet sticky buy bar + bottom sheet with the buy form.
 * Always rendered (no wp_is_mobile(): page-cache safe); hidden with CSS on xl+,
 * where the sidebar buy form is shown instead.
 */
global $product;
if ( ! $product ) {
	return;
}
$catMode      = function_exists( 'get_field' ) ? get_field( 'catalogue_mode', 'option' ) : false;
$can_buy      = ! $catMode && $product->is_in_stock() && $product->is_purchasable();
$is_simple    = $product->is_type( 'simple' );
$thumb_url    = (string) wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' );
$isSale       = $product->is_on_sale();
$regular_price = (float) $product->get_regular_price();
$sale_price    = (float) $product->get_sale_price();
$percentage    = 0;

if ( $isSale && $regular_price > 0 ) {
	$percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
}
?>
<nav :class="[intro ? '!translate-y-0' : '', scrollingDown ? 'max-lg:scale-85 !translate-y-2' : '']"
     class="fixed md:px-24 translate-y-full border border-gray-300 xl:hidden p-1 transition-all z-10 bg-gray-50 duration-500 flex justify-between items-center bottom-17 lg:bottom-0 inset-x-3 rounded-3xl"
     aria-label="خرید سریع">

	<div class="flex flex-col ps-2 flex-1 relative">
		<?php if ( $percentage > 0 ) : ?>
			<div class="absolute top-0 -translate-y-1/2 end-3 z-0 flex items-center justify-center">
				<div class="bg-red-400 text-white px-2 py-1  rounded-tr-2xl rounded-bl-2xl rounded-tl-sm rounded-br-sm shadow-lg">
					<span class="text-xs font-black leading-none"><?php echo esc_html( $percentage ); ?>% -</span>
				</div>
			</div>
		<?php endif; ?>
		<?php get_template_part( 'template-parts/product/single/product', 'price' ); ?>
	</div>

	<?php if ( $catMode ) :
		get_template_part( 'template-parts/shop/shop-contact' );
	elseif ( ! $product->is_in_stock() ) : ?>
		<a href="<?php echo esc_url( home_url( '/make-available/?' . $product->get_id() ) ); ?>"
		   class="flex min-h-12 items-center rounded-2xl bg-primary px-5 text-sm font-bold text-white">
			درخواست موجود کردن
		</a>
	<?php elseif ( $can_buy && $is_simple ) : ?>
		<?php // Simple product: add instantly, no sheet needed. ?>
		<form action="<?php echo esc_url( $product->get_permalink() ); ?>" method="post"
		      data-gl-cart-form
		      data-product_id="<?php echo absint( $product->get_id() ); ?>"
		      data-variable="0"
		      data-gl-image="<?php echo esc_url( $thumb_url ); ?>">
			<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>">
			<input type="hidden" name="quantity" value="1">
			<button type="submit"
			        class="gl-atc relative flex min-h-12 cursor-pointer items-center justify-center overflow-hidden rounded-2xl bg-primary px-5 text-white transition hover:brightness-125 active:scale-[0.98]">
				<span class="gl-atc-idle text-sm font-bold">افزودن به سبد خرید</span>
				<span class="gl-atc-loading" aria-hidden="true"><span class="gl-spinner"></span></span>
				<span class="gl-atc-done text-sm font-bold" aria-hidden="true">
					<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path class="gl-check-path" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
					اضافه شد
				</span>
			</button>
		</form>
	<?php else : ?>
		<button @click="openForm = true" type="button" aria-haspopup="dialog" aria-controls="openForm"
		        class="flex min-h-12 cursor-pointer items-center gap-2 rounded-2xl bg-primary px-5 text-sm font-bold text-white transition hover:brightness-125 active:scale-[0.98]">
			<?php get_template_part( 'template-parts/svg/shop', null, array( 'size' => '18', 'class' => 'text-white' ) ); ?>
			<?php echo $product->is_purchasable() ? 'افزودن به سبد خرید' : esc_html( $product->add_to_cart_text() ); ?>
		</button>
	<?php endif; ?>
</nav>

<?php if ( ! $catMode && $product->is_in_stock() && ! ( $can_buy && $is_simple ) ) : ?>
	<div id="openForm"
	     @keydown.escape.window="openForm = false"
	     :class="openForm ? '!z-50 !opacity-100 !visible' : ''"
	     class="invisible fixed inset-0 z-[-1] flex items-end justify-center bg-primary/30 opacity-0 backdrop-blur-[2px] transition-all duration-300 xl:hidden"
	     @click.self="openForm = false">
		<div :class="openForm ? '!translate-y-0' : ''"
		     class="relative w-full translate-y-full rounded-t-3xl bg-white px-4 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-2 shadow-2xl transition-transform duration-300 ease-out"
		     role="dialog" aria-modal="true" aria-labelledby="gl-sheet-title-<?php echo esc_attr( $product->get_id() ); ?>">
			<div class="flex justify-center pb-2" aria-hidden="true"><span class="h-1.5 w-12 rounded-full bg-gray-300"></span></div>

			<div class="mb-4 flex items-center gap-3 border-b border-gray-100 pb-3">
				<?php if ( $thumb_url ) : ?>
					<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" width="64" height="64" loading="lazy"
					     class="size-16 shrink-0 rounded-2xl border border-gray-100 bg-gray-50 object-cover">
				<?php endif; ?>
				<div class="min-w-0 flex-1">
					<p id="gl-sheet-title-<?php echo esc_attr( $product->get_id() ); ?>" class="line-clamp-1 text-sm font-bold text-gray-800"><?php echo esc_html( $product->get_name() ); ?></p>
					<p class="text-xs text-gray-500">رنگ را انتخاب کنید و به سبد اضافه کنید</p>
				</div>
				<button @click="openForm = false" type="button" aria-label="بستن"
				        class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-2xl bg-gray-100 text-gray-600">
					<?php get_template_part( 'template-parts/svg/close', null, array( 'size' => '20', 'class' => '' ) ); ?>
				</button>
			</div>

			<?php get_template_part( 'template-parts/product/single/product', 'buyform', array( 'context' => 'sheet' ) ); ?>
		</div>
	</div>
<?php endif; ?>
