<?php
/**
 * Single product buy form.
 *
 * Rendered twice on the product page (desktop sidebar + mobile bottom sheet,
 * switched with CSS), so every id is suffixed with the render context.
 *
 * - With JS: javascript/cart.js intercepts submit ([data-gl-cart-form]) and adds
 *   via ?wc-ajax=gl_add_to_cart without a reload.
 * - Without JS: normal POST to the product page (variation resolved server-side
 *   from the colour radio, see inc/product/ajax-cart.php).
 *
 * @var array $args { context?: string }
 */
global $product;
if ( ! $product ) {
	return;
}

$context = sanitize_key( $args['context'] ?? 'main' );
$uid     = $context . '-' . $product->get_id();

if ( $product->is_in_stock() ) :
	$is_variable = $product->is_type( 'variable' );
	$variations  = $is_variable ? $product->get_available_variations() : array();

	// Which attribute values have at least one purchasable, in-stock variation.
	$value_in_stock = array();
	$any_value      = array();
	foreach ( $variations as $variation ) {
		foreach ( (array) $variation['attributes'] as $attr_key => $attr_value ) {
			if ( '' === $attr_value ) {
				$any_value[ $attr_key ] = ! empty( $any_value[ $attr_key ] ) || ! empty( $variation['is_in_stock'] );
				continue;
			}
			if ( ! empty( $variation['is_in_stock'] ) && ! empty( $variation['is_purchasable'] ) ) {
				$value_in_stock[ $attr_key ][ $attr_value ] = true;
			}
		}
	}

	$attr_labels = array();
	if ( $is_variable ) {
		foreach ( $product->get_attributes() as $attribute ) {
			if ( $attribute->get_variation() && $attribute->is_taxonomy() ) {
				$name                 = 'attribute_' . sanitize_title( $attribute->get_name() );
				$attr_labels[ $name ] = wc_attribute_label( $attribute->get_name(), $product );
			}
		}
	}

	$thumb_url = (string) wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' );
	$show_qty  = ! $product->is_sold_individually();
	?>

	<form class="<?php echo $is_variable ? 'variations_form ' : ''; ?>cart gl-buyform"
	      action="<?php echo esc_url( $product->get_permalink() ); ?>"
	      method="post"
	      enctype="multipart/form-data"
	      data-gl-cart-form
	      data-product_id="<?php echo absint( $product->get_id() ); ?>"
	      data-variable="<?php echo $is_variable ? '1' : '0'; ?>"
	      data-gl-image="<?php echo esc_url( $thumb_url ); ?>"
	      data-gl-attrs="<?php echo esc_attr( wp_json_encode( $attr_labels ) ); ?>"
	      <?php if ( $is_variable ) : ?>
	      data-product_variations="<?php echo esc_attr( wp_json_encode( $variations ) ); ?>"
	      <?php endif; ?>>

		<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>"/>
		<input type="hidden" name="variation_id" class="variation_id" value="0"/>

		<?php
		if ( $is_variable ) :
			foreach ( $product->get_attributes() as $attribute ) :
				if ( ! $attribute->get_variation() || ! $attribute->is_taxonomy() ) {
					continue;
				}

				$taxonomy  = $attribute->get_name(); // e.g. pa_color.
				$attr_name = 'attribute_' . sanitize_title( $taxonomy );
				$label     = wc_attribute_label( $taxonomy, $product );
				$terms     = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'all' ) );
				?>
				<input type="hidden" name="<?php echo esc_attr( $attr_name ); ?>" value=""/>

				<fieldset class="gl-attr mb-3" data-gl-attr="<?php echo esc_attr( $attr_name ); ?>">
					<legend class="mb-2 flex w-full items-center justify-between text-sm text-gray-700">
						<span>انتخاب <?php echo esc_html( $label ); ?></span>
						<span class="text-xs font-bold text-primary" data-gl-attr-chosen aria-live="polite"></span>
					</legend>

					<div class="flex flex-wrap gap-2">
						<?php foreach ( $terms as $term ) :
							$input_id  = 'var-' . $taxonomy . '-' . $term->slug . '-' . $context;
							$acf_color = function_exists( 'get_field' ) ? get_field( 'color', $term->taxonomy . '_' . $term->term_id ) : '';
							$in_stock  = ! empty( $value_in_stock[ $attr_name ][ $term->slug ] ) || ! empty( $any_value[ $attr_name ] );
							?>
							<label for="<?php echo esc_attr( $input_id ); ?>"
							       class="gl-swatch group relative flex min-h-11 cursor-pointer items-center gap-2 rounded-2xl border border-gray-200 bg-white py-1.5 pe-3.5 ps-1.5 text-sm text-gray-700 transition-all hover:border-primary/40 has-checked:border-primary has-checked:bg-primary has-checked:text-white has-checked:shadow-md has-checked:shadow-primary/20 has-focus-visible:ring-2 has-focus-visible:ring-primary/40 has-disabled:cursor-not-allowed has-disabled:opacity-50">
								<input class="variation-input sr-only"
								       type="radio"
								       id="<?php echo esc_attr( $input_id ); ?>"
								       name="<?php echo esc_attr( $attr_name ); ?>"
								       value="<?php echo esc_attr( $term->slug ); ?>"
								       data-attribute_name="<?php echo esc_attr( $attr_name ); ?>"
								       data-label="<?php echo esc_attr( $term->name ); ?>"
								       <?php disabled( ! $in_stock ); ?>
								       required/>
								<?php if ( $acf_color ) : ?>
									<span class="size-8 rounded-full ring-1 ring-black/10 group-has-checked:ring-white/60"
									      style="background-color: <?php echo esc_attr( $acf_color ); ?>;" aria-hidden="true"></span>
								<?php endif; ?>
								<span class="font-medium"><?php echo esc_html( $term->name ); ?></span>
								<?php if ( ! $in_stock ) : ?>
									<span class="text-[11px]">(ناموجود)</span>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endforeach; ?>

			<div class="woocommerce-variation-price mb-3 rounded-2xl bg-gray-700 py-3 text-center text-white" style="display:none;">
				<?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>

		<p hidden class="gl-form-error mb-3 flex items-center gap-2 rounded-2xl border border-secondary/20 bg-secondary/5 px-3 py-2.5 text-sm text-secondary" data-gl-form-error role="alert"></p>

		<div class="flex flex-col gap-2">
			<?php if ( $show_qty ) :
				$max_qty = $product->get_max_purchase_quantity();
				?>
				<div class="flex items-center justify-between gap-3">
				<span class="text-sm text-gray-700" aria-hidden="true">تعداد</span>
				<div class="flex shrink-0 items-center rounded-2xl border border-gray-200 bg-white" role="group" aria-label="تعداد">
					<button type="button" aria-label="افزایش تعداد" data-gl-step="1"
					        class="flex size-11 cursor-pointer items-center justify-center rounded-2xl text-primary transition hover:bg-primary/5">
						<?php get_template_part( 'template-parts/svg/plus', null, array( 'size' => '18' ) ); ?>
					</button>
					<label for="quantity-<?php echo esc_attr( $uid ); ?>" class="sr-only">تعداد</label>
					<input type="number" name="quantity" value="1" id="quantity-<?php echo esc_attr( $uid ); ?>" min="1" step="1"
					       <?php echo $max_qty > 0 ? 'max="' . esc_attr( $max_qty ) . '"' : ''; ?>
					       inputmode="numeric"
					       class="w-9 border-none bg-transparent p-0 text-center text-sm font-bold focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"/>
					<button type="button" aria-label="کاهش تعداد" data-gl-step="-1"
					        class="flex size-11 cursor-pointer items-center justify-center rounded-2xl text-primary transition hover:bg-primary/5">
						<?php get_template_part( 'template-parts/svg/dash', null, array( 'size' => '18' ) ); ?>
					</button>
				</div>
				</div>
			<?php endif; ?>

			<button type="submit"
			        class="gl-atc relative flex min-h-12 w-full cursor-pointer items-center justify-center overflow-hidden rounded-2xl bg-primary px-4 text-white shadow-sm shadow-primary/20 transition-all duration-300 hover:brightness-110 active:scale-[0.98]">
				<span class="gl-atc-idle flex items-center gap-2 whitespace-nowrap text-sm font-bold">
					<?php get_template_part( 'template-parts/svg/shop', null, array( 'size' => '18', 'class' => 'text-white' ) ); ?>
					<?php echo $product->is_purchasable() ? 'افزودن به سبد خرید' : esc_html( $product->add_to_cart_text() ); ?>
				</span>
				<span class="gl-atc-loading" aria-hidden="true"><span class="gl-spinner"></span></span>
				<span class="gl-atc-done whitespace-nowrap text-sm font-bold" aria-hidden="true">
					<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path class="gl-check-path" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
					به سبد اضافه شد
				</span>
			</button>
		</div>
	</form>
<?php else : ?>
	<a href="<?php echo esc_url( home_url( '/make-available/?' . $product->get_id() ) ); ?>"
	   aria-label="link to available product page"
	   class="w-full bg-primary rounded-2xl p-6 lg:py-5 lg:mt-4 group flex relative flex-col hover:brightness-125 justify-center text-center text-white">
		<span class="text-xs absolute bg-secondary group-hover:px-3 group-hover:translate-y-0.5 transition-all rounded-sm px-2 py-1 -top-3 start-1/2 translate-x-1/2">ناموجود</span>
		<span class="text-sm font-bold">درخواست موجود کردن!</span>
	</a>
<?php endif; ?>
