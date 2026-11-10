<?php
/**
 * Product price (single product sidebar + mobile sticky CTA).
 * data-gl-price-* hooks let javascript/cart.js show the selected colour's price.
 */
global $product;
if ( ! $product ) {
	return;
}

if ( $product->is_type( 'variable' ) ) {
	// Starting price of the variable product.
	$price         = (float) $product->get_variation_price( 'min', true );
	$regular_price = (float) $product->get_variation_regular_price( 'min', true );
	$is_on_sale    = $price < $regular_price;
} else {
	$price         = (float) wc_get_price_to_display( $product );
	$regular_price = (float) wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
	$is_on_sale    = $product->is_on_sale() && $regular_price > $price;
}

$percentage = ( $is_on_sale && $regular_price > 0 ) ? round( ( ( $regular_price - $price ) / $regular_price ) * 100 ) : 0;
?>

<div class="flex flex-col w-full" dir="rtl" data-gl-price data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
	<div class="flex justify-start items-start min-h-[50px] w-full gap-2">
		<div class="flex flex-col items-start gap-0.5">

			<div class="flex items-center justify-start gap-1.5 w-full">
				<span class="text-lg lg:text-2xl font-bold text-gray-800 tracking-tight" data-gl-price-now><?php echo esc_html( number_format( $price ) ); ?></span>
				<div class="flex flex-col items-center justify-center text-[11px] font-black leading-[0.85] text-gray-600" aria-label="تومان">
					<span aria-hidden="true">ن</span>
					<span class="-mb-[2px]" aria-hidden="true">توما</span>
				</div>
			</div>

			<div class="h-5 w-full flex items-center gap-1 justify-start">
				<del class="text-[13px] text-gray-500 font-medium decoration-red-600" data-gl-price-was <?php echo ( $is_on_sale && $regular_price > 0 ) ? '' : 'hidden'; ?>><?php echo esc_html( number_format( $regular_price ) ); ?></del>
				<div class="w-12 shrink-0 text-right">
					<div class="bg-secondary text-white text-[13px] font-bold px-2 py-0.5 rounded-lg inline-flex items-center justify-center" data-gl-price-off <?php echo ( $is_on_sale && $percentage > 0 ) ? '' : 'hidden'; ?>>
						<span data-gl-price-pct><?php echo esc_html( $percentage ); ?></span>٪
					</div>
				</div>
			</div>

		</div>
	</div>
</div>
