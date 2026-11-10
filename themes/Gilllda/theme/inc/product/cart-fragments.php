<?php
/**
 * Cart drawer markup + WooCommerce fragments.
 *
 * `#gl-cart-body` (drawer content) and `span.header-cart-count` (header badge)
 * are returned as fragments by every AJAX cart response (see ajax-cart.php)
 * and by WooCommerce's own fragment refreshes.
 *
 * The body contains no Alpine directives on purpose: javascript/cart.js uses
 * event delegation on data-gl-* attributes, so swapping the HTML is safe.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_add_to_cart_fragments', 'gilllda_cart_fragments' );
function gilllda_cart_fragments( $fragments ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $fragments;
	}

	$fragments['span.header-cart-count'] = gl_header_cart_count_html();

	ob_start();
	gl_cart_drawer_body();
	$fragments['#gl-cart-body'] = ob_get_clean();

	return $fragments;
}

/**
 * Header badge (same classes as template-parts/layout/header-content.php).
 */
function gl_header_cart_count_html() {
	$count = WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
	return '<span class="header-cart-count absolute top-0 start-2 lg:start-0 lg:translate-x-1/2 lg:-translate-y-1/2 translate-y-1 bg-secondary/80 text-white flex leading-auto justify-center items-center pt-1 p-0.5 rounded-sm text-xs size-4" data-count="' . esc_attr( $count ) . '">' . esc_html( $count ) . '</span>';
}

/**
 * Human readable variation attributes of a cart item (label, value, ACF swatch colour).
 */
function gl_cart_item_attrs( $cart_item, $product ) {
	$attrs = array();
	foreach ( (array) ( $cart_item['variation'] ?? array() ) as $attr_key => $value ) {
		if ( '' === $value || null === $value ) {
			continue;
		}
		$taxonomy = wc_attribute_taxonomy_name( str_replace( array( 'attribute_pa_', 'attribute_' ), '', $attr_key ) );
		$label    = $value;
		$swatch   = '';
		if ( taxonomy_exists( $taxonomy ) ) {
			$term = get_term_by( 'slug', $value, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$label  = $term->name;
				$swatch = function_exists( 'get_field' ) ? (string) get_field( 'color', $taxonomy . '_' . $term->term_id ) : '';
			}
			$attr_label = wc_attribute_label( $taxonomy, $product );
		} else {
			$attr_label = wc_attribute_label( str_replace( 'attribute_', '', $attr_key ), $product );
		}
		$attrs[] = array(
			'label'  => $attr_label,
			'value'  => $label,
			'swatch' => $swatch,
		);
	}
	return $attrs;
}

/**
 * Normalised data for every visible cart item.
 */
function gl_cart_items_view() {
	$items = array();
	if ( ! WC()->cart ) {
		return $items;
	}

	foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $key );
		if ( ! $product || ! $product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $key ) ) {
			continue;
		}

		$name      = function_exists( 'gl_cart_item_base_name' ) ? gl_cart_item_base_name( $product ) : $product->get_name();
		$permalink = apply_filters( 'woocommerce_cart_item_permalink', $product->is_visible() ? $product->get_permalink( $cart_item ) : '', $cart_item, $key );

		$attrs = gl_cart_item_attrs( $cart_item, $product );

		$max = $product->is_sold_individually() ? 1 : (int) $product->get_max_purchase_quantity();

		$items[] = array(
			'key'       => $key,
			'name'      => $name,
			'permalink' => $permalink,
			'thumbnail' => $product->get_image(
				'woocommerce_thumbnail',
				array(
					'class'   => 'size-full object-cover',
					'alt'     => $name,
					'loading' => 'lazy',
				)
			),
			'attrs'     => $attrs,
			'qty'       => (int) $cart_item['quantity'],
			'max'       => $max,
			'price'     => (float) wc_get_price_to_display( $product ),
			'line'      => (float) wc_get_price_to_display( $product, array( 'qty' => $cart_item['quantity'] ) ),
		);
	}

	return $items;
}

/**
 * Inner, swappable part of the drawer.
 */
function gl_cart_drawer_body() {
	$cart     = WC()->cart;
	$count    = $cart ? (int) $cart->get_cart_contents_count() : 0;
	$items    = $cart ? gl_cart_items_view() : array();
	$hash     = $count ? $cart->get_cart_hash() : '';
	$subtotal = $cart ? (float) $cart->get_displayed_subtotal() : 0;
	$discount = $cart ? (float) $cart->get_discount_total() : 0;
	if ( $cart && $cart->display_prices_including_tax() ) {
		$discount += (float) $cart->get_discount_tax();
	}
	$threshold = function_exists( 'gl_free_shipping_threshold' ) ? gl_free_shipping_threshold() : 0;
	$shop_url  = wc_get_page_permalink( 'shop' );
	?>
	<div id="gl-cart-body" class="flex flex-1 min-h-0 flex-col" data-count="<?php echo esc_attr( $count ); ?>" data-hash="<?php echo esc_attr( $hash ); ?>">
		<?php if ( ! $items ) : ?>
			<div class="flex flex-1 flex-col items-center justify-center gap-4 px-6 py-12 text-center">
				<div class="flex size-24 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-gray-100">
					<svg class="size-11 text-primary/40" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
					</svg>
				</div>
				<div class="space-y-1">
					<p class="text-base font-bold text-gray-800">سبد خرید شما خالی است</p>
					<p class="text-sm text-gray-500">کیف محبوبت را پیدا کن و با یک ضربه به سبد اضافه کن.</p>
				</div>
				<a href="<?php echo esc_url( $shop_url ); ?>" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-primary px-8 text-sm font-bold text-white shadow-sm transition hover:brightness-110">
					رفتن به فروشگاه
				</a>
			</div>
		<?php else : ?>
			<?php
			if ( $threshold > 0 ) :
				$basis     = max( 0, $subtotal - $discount );
				$remaining = max( 0, $threshold - $basis );
				$percent   = min( 100, (int) round( ( $basis / $threshold ) * 100 ) );
				?>
				<div class="mx-4 mt-3 rounded-3xl bg-white px-4 py-3 ring-1 ring-gray-100">
					<p class="mb-2 text-xs text-gray-600">
						<?php if ( $remaining > 0 ) : ?>
							فقط <b class="font-bold text-primary"><?php echo esc_html( gl_cart_number( $remaining ) ); ?> تومان</b> تا ارسال رایگان
						<?php else : ?>
							<b class="font-bold text-emerald-700">ارسال این سفارش رایگان است.</b>
						<?php endif; ?>
					</p>
					<div class="h-1.5 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-label="پیشرفت تا ارسال رایگان" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $percent ); ?>">
						<div class="gl-progress h-full rounded-full <?php echo $remaining > 0 ? 'bg-secondary' : 'bg-emerald-600'; ?>" style="width: <?php echo esc_attr( $percent ); ?>%"></div>
					</div>
				</div>
			<?php endif; ?>

			<ul class="flex-1 min-h-0 space-y-3 overflow-y-auto overscroll-contain p-4" aria-label="کالاهای سبد خرید">
				<?php foreach ( $items as $item ) :
					$at_max = $item['max'] > 0 && $item['qty'] >= $item['max'];
					?>
					<li class="gl-item relative flex gap-3 rounded-3xl border border-gray-100 bg-white p-3 transition-all duration-300"
						data-gl-item="<?php echo esc_attr( $item['key'] ); ?>"
						data-qty="<?php echo esc_attr( $item['qty'] ); ?>"
						data-max="<?php echo esc_attr( $item['max'] ); ?>"
						data-name="<?php echo esc_attr( $item['name'] ); ?>">

						<?php if ( $item['permalink'] ) : ?>
							<a href="<?php echo esc_url( $item['permalink'] ); ?>" class="block size-20 shrink-0 overflow-hidden rounded-2xl border border-gray-100 bg-gray-50" tabindex="-1" aria-hidden="true">
								<?php echo $item['thumbnail']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						<?php else : ?>
							<span class="block size-20 shrink-0 overflow-hidden rounded-2xl border border-gray-100 bg-gray-50"><?php echo $item['thumbnail']; // phpcs:ignore ?></span>
						<?php endif; ?>

						<div class="flex min-w-0 flex-1 flex-col gap-1">
							<div class="flex items-start justify-between gap-2">
								<?php if ( $item['permalink'] ) : ?>
									<a href="<?php echo esc_url( $item['permalink'] ); ?>" class="line-clamp-2 pt-0.5 text-sm font-bold leading-6 text-gray-800 hover:text-primary"><?php echo esc_html( $item['name'] ); ?></a>
								<?php else : ?>
									<span class="line-clamp-2 pt-0.5 text-sm font-bold leading-6 text-gray-800"><?php echo esc_html( $item['name'] ); ?></span>
								<?php endif; ?>
								<button type="button" data-gl-remove
										class="-me-2 -mt-2 flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-2xl text-gray-400 transition hover:bg-secondary/5 hover:text-secondary"
										aria-label="<?php echo esc_attr( sprintf( 'حذف %s از سبد', $item['name'] ) ); ?>">
									<svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
								</button>
							</div>

							<?php foreach ( $item['attrs'] as $attr ) : ?>
								<p class="flex items-center gap-1.5 text-xs text-gray-500">
									<?php if ( $attr['swatch'] ) : ?>
										<span class="size-3 rounded-full ring-1 ring-black/10" style="background-color: <?php echo esc_attr( $attr['swatch'] ); ?>;" aria-hidden="true"></span>
									<?php endif; ?>
									<span><?php echo esc_html( $attr['label'] ); ?>: <?php echo esc_html( $attr['value'] ); ?></span>
								</p>
							<?php endforeach; ?>

							<div class="mt-auto flex items-end justify-between gap-2 pt-1">
								<div class="gl-stepper flex items-center rounded-2xl border border-gray-200 bg-gray-50" role="group" aria-label="<?php echo esc_attr( sprintf( 'تعداد %s', $item['name'] ) ); ?>">
									<button type="button" data-gl-qty="1"
											class="flex size-11 cursor-pointer items-center justify-center rounded-2xl text-primary transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-30"
											aria-label="افزایش تعداد" <?php disabled( $at_max ); ?>>
										<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2.4" d="M12 5v14M5 12h14"/></svg>
									</button>
									<output class="w-7 text-center text-sm font-black text-gray-800" data-gl-qty-value aria-live="polite"><?php echo esc_html( $item['qty'] ); ?></output>
									<button type="button" data-gl-qty="-1"
											class="flex size-11 cursor-pointer items-center justify-center rounded-2xl text-primary transition hover:bg-white"
											aria-label="<?php echo 1 === $item['qty'] ? 'حذف از سبد' : 'کاهش تعداد'; ?>">
										<svg class="gl-ico-minus size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2.4" d="M5 12h14"/></svg>
										<svg class="gl-ico-trash size-[18px] text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
									</button>
								</div>

								<div class="flex flex-col items-end gap-0.5">
									<?php if ( $item['qty'] > 1 ) : ?>
										<span class="text-[11px] text-gray-400"><?php echo esc_html( gl_cart_number( $item['price'] ) ); ?> × <?php echo esc_html( $item['qty'] ); ?></span>
									<?php endif; ?>
									<?php echo gl_cart_money_html( $item['line'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
							</div>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="border-t border-gray-100 bg-white px-4 pt-3 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-[0_-10px_20px_-12px_rgba(16,37,66,0.12)]">
				<dl class="mb-3 space-y-1.5 text-sm">
					<div class="flex items-center justify-between gap-3">
						<dt class="text-gray-500">جمع سبد (<?php echo esc_html( $count ); ?> کالا)</dt>
						<dd><?php echo gl_cart_money_html( $subtotal, 'lg' ); // phpcs:ignore ?></dd>
					</div>
					<?php if ( $discount > 0 ) : ?>
						<div class="flex items-center justify-between gap-3 text-emerald-700">
							<dt>سود شما از تخفیف</dt>
							<dd class="font-bold"><?php echo esc_html( gl_cart_number( $discount ) ); ?> تومان</dd>
						</div>
					<?php endif; ?>
					<div class="flex items-start justify-between gap-3 text-xs text-gray-500">
						<dt class="shrink-0">هزینه ارسال</dt>
						<dd class="text-left leading-5"><?php echo esc_html( gl_cart_shipping_note() ); ?></dd>
					</div>
				</dl>
				<div class="grid grid-cols-[1fr_1.5fr] gap-2">
					<button type="button" data-gl-close
							class="min-h-12 cursor-pointer rounded-2xl border border-gray-200 bg-white px-3 text-sm font-bold text-gray-700 transition hover:bg-gray-50">
						ادامه خرید
					</button>
					<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>"
					   class="flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-primary px-3 text-sm font-bold text-white shadow-sm shadow-primary/20 transition hover:brightness-110">
						تسویه حساب
						<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"/></svg>
					</a>
				</div>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="mt-1 flex min-h-11 items-center justify-center text-xs text-gray-500 transition hover:text-primary">
					مشاهده و ویرایش سبد خرید
				</a>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
