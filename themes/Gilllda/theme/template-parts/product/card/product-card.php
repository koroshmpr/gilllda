<?php
/**
 * Product card.
 *
 * - The main link has a real server-side href (product permalink).
 * - The add-to-cart buttons are NOT inside the <a>: they submit a hidden
 *   per-card <form data-gl-cart-form> via the `form` attribute
 *   (AJAX in javascript/cart.js, plain POST without JS).
 * - Prices are server-rendered inside the x-text spans.
 * - Variation data comes from gl_card_variations() (cached transient),
 *   not get_available_variations().
 *
 * @var array $args { isArchive?: bool, eager?: bool, class?: string }
 */
global $product;

if ( ! $product ) {
	return;
}
$catMode      = function_exists( 'get_field' ) ? get_field( 'catalogue_mode', 'option' ) : false;
$isArchive    = $args['isArchive'] ?? false;
$product_id   = $product->get_id();
$product_name = $product->get_name();
$permalink    = $product->get_permalink();
$is_variable  = $product->is_type( 'variable' );
$stock_status = $product->get_stock_status();
$in_stock     = 'outofstock' !== $stock_status;

if ( $is_variable ) {
	$price_val = (float) $product->get_variation_price( 'min', true );
	$reg_val   = (float) $product->get_variation_regular_price( 'min', true );
} else {
	$price_val = (float) wc_get_price_to_display( $product );
	$reg_val   = (float) wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
}
$is_on_sale = $reg_val > 0 && $price_val < $reg_val;
$percentage = $is_on_sale ? round( ( ( $reg_val - $price_val ) / $reg_val ) * 100 ) : 0;

// Light variation map for Alpine.
$available_variations = array();
$color_in_stock       = array();
if ( $is_variable && function_exists( 'gl_card_variations' ) ) {
	foreach ( gl_card_variations( $product ) as $var ) {
		$color = $var['attributes']['attribute_pa_color'] ?? '';
		$available_variations[] = array(
			'id'           => $var['id'],
			'color'        => $color,
			'attributes'   => $var['attributes'],
			'price'        => number_format( $var['price'] ),
			'regular'      => number_format( $var['regular'] ),
			'is_on_sale'   => $var['regular'] > 0 && $var['price'] < $var['regular'],
			'percentage'   => $var['regular'] > 0 ? round( ( ( $var['regular'] - $var['price'] ) / $var['regular'] ) * 100 ) : 0,
			'image'        => $var['image'],
			'in_stock'     => (bool) $var['in_stock'],
		);
		if ( $var['in_stock'] ) {
			$color_in_stock[ $color ] = true;
		}
	}
}

$can_buy = ! $catMode && $in_stock && $product->is_purchasable() && ( $is_variable || $product->is_type( 'simple' ) );
$form_id = 'gl-card-form-' . $product_id . '-' . wp_unique_id();

$args_svg = array(
	'size'  => '20',
	'class' => 'group-hover/add:delay-100 text-white duration-300 rotate-45 group-hover/add:rotate-0 translate-x-2 opacity-0 transition-all group-hover/add:opacity-100 group-hover/add:translate-x-0',
);

$image_id        = $product->get_image_id();
$image_url       = (string) wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );
$gallery_ids     = $product->get_gallery_image_ids();
$hover_image_url = ! empty( $gallery_ids ) ? wp_get_attachment_image_url( $gallery_ids[0], 'woocommerce_thumbnail' ) : '';

$is_eager       = $args['eager'] ?? false;
$loading_attr   = $is_eager ? 'eager' : 'lazy';
$fetch_priority = $is_eager ? 'fetchpriority="high"' : '';

$alpine = array(
	'variations'          => $available_variations,
	'selectedVar'         => null,
	'selectedColor'       => '',
	'mainImageUrl'        => $image_url,
	'currentPrice'        => number_format( $price_val ),
	'currentRegularPrice' => number_format( $reg_val ),
	'isOnSale'            => $is_on_sale,
	'currentDiscount'     => $percentage,
	'permalink'           => $permalink,
);
?>

<div
	x-data="{
		...<?php echo esc_attr( wp_json_encode( $alpine ) ); ?>,
		hover: false,
		imgLoaded: false,
		get link() { return this.selectedColor ? this.permalink + (this.permalink.includes('?') ? '&' : '?') + 'attribute_pa_color=' + encodeURIComponent(this.selectedColor) : this.permalink },
		selectColor(slug) {
			const match = this.variations.find(v => v.color === slug);
			if (!match) return;
			this.selectedColor = slug;
			this.selectedVar = match.id;
			this.currentPrice = match.price;
			this.currentRegularPrice = match.regular;
			this.isOnSale = match.is_on_sale;
			this.currentDiscount = match.percentage;
			if (match.image) this.mainImageUrl = match.image;
		}
	}"
	@mouseenter="hover = true"
	@mouseleave="hover = false"
	class="group relative bg-white border rounded-lg border-gray-100 max-lg:border-gray-200 !my-0 transition-all duration-500 hover:shadow-lg rtl flex flex-col <?php echo $isArchive ? 'max-lg:flex-row' : ''; ?> <?php echo esc_attr( $args['class'] ?? '' ); ?>"
	dir="rtl"
	data-gl-card
>
	<?php
	get_template_part(
		'template-parts/product/compare-button',
		null,
		array(
			'id'        => $product_id,
			'class'     => 'mr-auto rounded-full lg:opacity-0 group-hover:opacity-100 lg:translate-y-2 group-hover:translate-y-0 !absolute z-[3] block top-2 end-2 p-2 lg:p-2.5 backdrop-blur-sm !border-gray-300 text-primary hover:bg-gray-800 hover:text-white transition-all',
			'textClass' => 'hidden',
		)
	);
	?>

	<?php if ( $can_buy ) : ?>
		<form id="<?php echo esc_attr( $form_id ); ?>" class="hidden" action="<?php echo esc_url( $permalink ); ?>" method="post"
		      data-gl-cart-form
		      data-product_id="<?php echo esc_attr( $product_id ); ?>"
		      data-variable="<?php echo $is_variable ? '1' : '0'; ?>"
		      data-gl-image="<?php echo esc_url( $image_url ); ?>"
		      <?php if ( $is_variable ) : ?>data-gl-attrs="<?php echo esc_attr( wp_json_encode( array( 'attribute_pa_color' => wc_attribute_label( 'pa_color' ) ) ) ); ?>"<?php endif; ?>>
			<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product_id ); ?>">
			<input type="hidden" name="quantity" value="1">
			<?php if ( $is_variable ) : ?>
				<input type="hidden" name="variation_id" value="0" :value="selectedVar || 0">
				<input type="hidden" name="attribute_pa_color" value="" :value="selectedColor">
			<?php endif; ?>
		</form>
	<?php endif; ?>

	<div class="relative overflow-hidden rounded-t-lg bg-gray-50/50 <?php echo $isArchive ? 'max-lg:w-1/3 max-lg:rounded-e-none max-lg:rounded-t-none max-lg:rounded-s-lg' : ''; ?>">
		<a href="<?php echo esc_url( $permalink ); ?>" :href="link" class="block bg-white relative size-full aspect-square overflow-hidden p-4" tabindex="-1" aria-hidden="true">
			<?php if ( 'outofstock' === $stock_status ) : ?>
				<span class="absolute top-2 start-2 z-[2] bg-red-600/90 text-white text-sm font-bold px-2.5 py-1 text-center rounded-lg">
					ناموجود
				</span>
			<?php endif; ?>

			<div x-show="!imgLoaded" x-transition.opacity.duration.500ms
			     class="absolute inset-0 z-[1] bg-gray-200 animate-pulse"></div>

			<img width="300" height="300"
			     x-ref="mainImg"
			     x-init="if ($refs.mainImg.complete) imgLoaded = true"
			     @load="imgLoaded = true"
			     loading="<?php echo esc_attr( $loading_attr ); ?>"
			     <?php echo $fetch_priority; // phpcs:ignore ?>
			     decoding="<?php echo $is_eager ? 'sync' : 'async'; ?>"
			     src="<?php echo esc_url( $image_url ); ?>"
			     :src="mainImageUrl"
			     alt="<?php echo esc_attr( $product_name ); ?>"
			     data-gl-fly-img
			     class="object-contain size-full !my-0 transition-all duration-700 transform group-hover:scale-105"
			     :class="hover ? '<?php echo $hover_image_url ? 'opacity-0' : ''; ?> scale-105' : 'opacity-100'">

			<?php if ( $hover_image_url ) : ?>
				<img width="300" height="300"
				     loading="<?php echo esc_attr( $loading_attr ); ?>"
				     decoding="<?php echo $is_eager ? 'sync' : 'async'; ?>"
				     alt=""
				     src="<?php echo esc_url( $hover_image_url ); ?>"
				     class="absolute inset-0 size-full aspect-square object-contain transition-all duration-700 opacity-0"
				     :class="hover ? 'opacity-100 scale-105 duration-500' : 'opacity-0'">
			<?php endif; ?>
		</a>

		<?php if ( $can_buy ) : ?>
			<button type="submit" form="<?php echo esc_attr( $form_id ); ?>"
			        aria-label="<?php echo esc_attr( sprintf( 'افزودن %s به سبد خرید', $product_name ) ); ?>"
			        class="gl-atc max-lg:hidden absolute bottom-3 group/add overflow-hidden inset-x-4 duration-300 shadow-md translate-y-[130%] opacity-0 group-hover:translate-y-0 group-hover:opacity-100 focus-visible:translate-y-0 focus-visible:opacity-100 z-20 flex min-h-11 items-center justify-center gap-2 py-3 bg-secondary text-white cursor-pointer rounded-lg font-bold hover:brightness-125 transition-all">
				<span class="gl-atc-idle flex items-center gap-2">
					<?php get_template_part( 'template-parts/svg/shop', null, $args_svg ); ?>
					<span class="text-sm translate-x-4 group-hover/add:translate-x-0 transition-all duration-300">افزودن به سبد</span>
				</span>
				<span class="gl-atc-loading" aria-hidden="true"><span class="gl-spinner"></span></span>
				<span class="gl-atc-done text-sm" aria-hidden="true">
					<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path class="gl-check-path" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
					اضافه شد
				</span>
			</button>
		<?php endif; ?>
	</div>

	<div class="flex flex-1 relative <?php echo $isArchive ? 'max-lg:w-2/3' : 'items-center max-lg:gap-1'; ?> max-lg:flex-col justify-between p-3 lg:px-4 gap-3">
		<div class="flex flex-col <?php echo $isArchive ? '' : 'max-lg:flex-row max-lg:justify-between w-full'; ?> gap-1">

			<a href="<?php echo esc_url( $permalink ); ?>" :href="link"
			   class="text-sm lg:text-base font-semibold text-gray-800 line-clamp-1 w-full text-right leading-relaxed hover:text-primary after:absolute after:inset-0 after:content-[''] after:z-[1]">
				<?php echo esc_html( $product_name ); ?>
			</a>

			<?php
			$colors = wc_get_product_terms( $product_id, 'pa_color', array( 'fields' => 'all' ) );
			if ( ! empty( $colors ) && ! is_wp_error( $colors ) ) : ?>
				<div class="relative z-[2] -mx-2 flex items-center" data-gl-swatches role="group" aria-label="انتخاب رنگ">
					<?php foreach ( $colors as $color ) :
						$acf_color = function_exists( 'get_field' ) ? get_field( 'color', $color->taxonomy . '_' . $color->term_id ) : '';
						if ( ! $acf_color ) {
							continue;
						}
						$color_ok = ! $is_variable || ! empty( $color_in_stock[ $color->slug ] );
						?>
						<button type="button"
						        @click="selectColor('<?php echo esc_js( $color->slug ); ?>')"
						        :aria-pressed="selectedColor === '<?php echo esc_js( $color->slug ); ?>' ? 'true' : 'false'"
						        aria-pressed="false"
						        class="relative -my-2 flex size-11 cursor-pointer items-center justify-center rounded-md transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 <?php echo $color_ok ? '' : 'opacity-40'; ?>"
						        title="<?php echo esc_attr( $color->name . ( $color_ok ? '' : ' (ناموجود)' ) ); ?>"
						        aria-label="<?php echo esc_attr( 'رنگ ' . $color->name . ( $color_ok ? '' : ' (ناموجود)' ) ); ?>">
							<span class="size-6 rounded-sm border border-gray-300 shadow-sm transition-all"
							      :class="selectedColor === '<?php echo esc_js( $color->slug ); ?>' ? 'ring-2 ring-offset-1 ring-primary scale-110' : ''"
							      style="background-color: <?php echo esc_attr( $acf_color ); ?>;"></span>
						</button>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="h-4"></div>
			<?php endif; ?>
		</div>

		<div class="flex justify-between items-start <?php echo $isArchive ? 'max-lg:items-center' : ' flex-wrap'; ?> mt-1 min-h-[50px]">
			<?php if ( $in_stock ) : ?>
				<div class="flex flex-col items-end gap-0.5 <?php echo $isArchive ? '' : 'w-full max-lg:items-center'; ?>">
					<div class="flex items-center gap-1.5">
						<span class="text-lg lg:text-[19px] font-bold text-gray-800 tracking-tight" x-text="currentPrice"><?php echo esc_html( number_format( $price_val ) ); ?></span>
						<div class="flex flex-col items-center justify-center text-[9px] font-black leading-[0.85] text-gray-600 mt-1" aria-label="تومان">
							<span aria-hidden="true">ن</span>
							<span class="-mb-[2px]" aria-hidden="true">توما</span>
						</div>
					</div>
					<div class="h-4 pe-1 flex gap-1 items-center">
						<div x-show="isOnSale && currentDiscount > 0" <?php echo $is_on_sale && $percentage > 0 ? '' : 'style="display:none"'; ?>
						     class="bg-secondary text-white text-[12px] font-bold px-2 py-0.5 rounded-md inline-flex items-center justify-center">
							<span x-text="currentDiscount"><?php echo esc_html( $percentage ); ?></span>٪
						</div>
						<del x-show="isOnSale && currentDiscount > 0" <?php echo $is_on_sale && $percentage > 0 ? '' : 'style="display:none"'; ?>
						     class="text-[12px] text-gray-500 font-medium decoration-red-600"
						     x-text="currentRegularPrice"><?php echo esc_html( number_format( $reg_val ) ); ?></del>
					</div>
				</div>
				<?php if ( $can_buy ) : ?>
					<button type="submit" form="<?php echo esc_attr( $form_id ); ?>"
					        aria-label="<?php echo esc_attr( sprintf( 'افزودن %s به سبد خرید', $product_name ) ); ?>"
					        class="gl-atc lg:hidden relative z-[2] <?php echo $isArchive ? 'w-fit px-3' : 'w-full mt-3'; ?> group/add overflow-hidden duration-300 shadow-sm flex min-h-11 items-center cursor-pointer justify-center gap-2 px-1 rounded-lg bg-primary text-white transition-all active:scale-[0.98]">
						<span class="gl-atc-idle flex items-center gap-2">
							<?php get_template_part( 'template-parts/svg/shop', null, array( 'size' => '18', 'class' => 'text-white' ) ); ?>
							<span class="text-xs text-nowrap">افزودن به سبد</span>
						</span>
						<span class="gl-atc-loading" aria-hidden="true"><span class="gl-spinner"></span></span>
						<span class="gl-atc-done text-xs" aria-hidden="true">
							<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path class="gl-check-path" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
							اضافه شد
						</span>
					</button>
				<?php endif; ?>
			<?php else : ?>
				<span class="text-sm text-gray-500 w-full text-left">ناموجود</span>
			<?php endif; ?>
		</div>
	</div>
</div>
