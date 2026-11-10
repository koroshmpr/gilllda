<?php
/**
 * AJAX cart (app-like add to cart, drawer quantity/remove/undo, live refresh).
 *
 * Endpoints (WooCommerce AJAX, POST only, require the `X-GL-Cart: 1` header):
 *   ?wc-ajax=gl_cart_refresh   -> current fragments (drawer body, header badge)
 *   ?wc-ajax=gl_add_to_cart    -> product_id, quantity, variation_id?, attribute_*?
 *   ?wc-ajax=gl_cart_set_qty   -> key, quantity (0 removes)
 *   ?wc-ajax=gl_cart_remove    -> key (can be undone)
 *   ?wc-ajax=gl_cart_undo      -> key
 *
 * Every response: { success: bool, data: { fragments, count, cart_hash, subtotal, message?, code?, key? } }
 *
 * The custom header is the CSRF guard: browsers can not send it cross-origin
 * without a CORS pre-flight that WordPress never approves. Nonces are avoided
 * on purpose because pages are served from a full-page cache.
 *
 * @package bluebox
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

/**
 * Format a number the way the theme shows prices (digits only, the stacked
 * "تومان" glyph is printed separately).
 */
function gl_cart_number( $amount ) {
	return number_format( (float) $amount );
}

/**
 * Price + stacked toman glyph.
 */
function gl_cart_money_html( $amount, $size = 'md' ) {
	$num_class = 'sm' === $size ? 'text-sm font-bold text-gray-800' : ( 'lg' === $size ? 'text-lg font-black text-gray-900' : 'text-[15px] font-bold text-gray-800' );
	ob_start();
	?>
	<span class="inline-flex items-center gap-1 whitespace-nowrap">
		<span class="<?php echo esc_attr( $num_class ); ?> tracking-tight"><?php echo esc_html( gl_cart_number( $amount ) ); ?></span>
		<span class="flex flex-col items-center justify-center text-[9px] font-black leading-[0.85] text-gray-500 mt-0.5" aria-label="تومان">
			<span aria-hidden="true">ن</span>
			<span class="-mb-[2px]" aria-hidden="true">توما</span>
		</span>
	</span>
	<?php
	return trim( ob_get_clean() );
}

/**
 * Lowest "free shipping" minimum amount configured in any shipping zone
 * (requires = min_amount | either). 0 when there is no such threshold.
 * Cached per WooCommerce shipping transient version, so editing zones
 * or methods invalidates it automatically.
 */
function gl_free_shipping_threshold() {
	static $min = null;
	if ( null !== $min ) {
		return $min;
	}

	$version = class_exists( 'WC_Cache_Helper' ) ? WC_Cache_Helper::get_transient_version( 'shipping' ) : '';
	$cached  = get_transient( 'gl_free_ship_min' );
	if ( is_array( $cached ) && isset( $cached['v'], $cached['min'] ) && $cached['v'] === $version ) {
		$min = (float) $cached['min'];
		return $min;
	}

	$min = 0.0;
	if ( class_exists( 'WC_Shipping_Zones' ) ) {
		$zones   = WC_Shipping_Zones::get_zones();
		$methods = array();
		foreach ( $zones as $zone ) {
			if ( ! empty( $zone['shipping_methods'] ) ) {
				$methods = array_merge( $methods, (array) $zone['shipping_methods'] );
			}
		}
		$rest_of_world = new WC_Shipping_Zone( 0 );
		$methods       = array_merge( $methods, (array) $rest_of_world->get_shipping_methods( true ) );

		foreach ( $methods as $method ) {
			if ( ! is_object( $method ) || 'free_shipping' !== $method->id || ! $method->is_enabled() ) {
				continue;
			}
			$requires = $method->get_option( 'requires' );
			$amount   = (float) $method->get_option( 'min_amount' );
			if ( in_array( $requires, array( 'min_amount', 'either' ), true ) && $amount > 0 ) {
				$min = $min > 0 ? min( $min, $amount ) : $amount;
			}
		}
	}

	set_transient( 'gl_free_ship_min', array( 'v' => $version, 'min' => $min ), DAY_IN_SECONDS );
	return $min;
}

/**
 * Product name without the variation suffix ("دنیز مینی" instead of "دنیز مینی - کرم").
 */
function gl_cart_item_base_name( $product ) {
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return '';
	}
	if ( $product->is_type( 'variation' ) ) {
		$parent = wc_get_product( $product->get_parent_id() );
		if ( $parent ) {
			return $parent->get_name();
		}
	}
	return $product->get_name();
}

/**
 * Static shipping note shown in the drawer / cart (filterable).
 */
function gl_cart_shipping_note() {
	return apply_filters( 'gl_cart_shipping_note', 'تهران ۲۰۰ هزار تومان · سایر شهرها ۳۰۰ هزار تومان' );
}

/**
 * Checkout step indicator: 1 = سبد, 2 = اطلاعات و ثبت‌نام, 3 = پرداخت.
 * Pass 4 to mark every step as done.
 */
function gl_checkout_steps( $current = 1 ) {
	$steps = array(
		1 => array( 'label' => 'سبد خرید', 'url' => wc_get_cart_url() ),
		2 => array( 'label' => 'اطلاعات و ثبت‌نام', 'url' => wc_get_checkout_url() ),
		3 => array( 'label' => 'پرداخت', 'url' => '' ),
	);
	$fa = array( 1 => '۱', 2 => '۲', 3 => '۳' );
	?>
	<nav class="gl-steps mb-5 lg:mb-7" aria-label="مراحل خرید">
		<ol class="flex items-start justify-between gap-1 max-w-xl mx-auto">
			<?php foreach ( $steps as $n => $step ) :
				$done    = $n < $current;
				$active  = $n === $current;
				$circle  = $done ? 'bg-primary text-white border-primary' : ( $active ? 'bg-white text-primary border-primary ring-4 ring-primary/10' : 'bg-white text-gray-400 border-gray-200' );
				$label   = $active ? 'text-primary font-bold' : ( $done ? 'text-gray-700' : 'text-gray-400' );
				$is_link = $done && $step['url'];
				?>
				<li class="relative flex flex-1 flex-col items-center gap-1.5 text-center" <?php echo $active ? 'aria-current="step"' : ''; ?>>
					<?php if ( $n < 3 ) : ?>
						<span class="absolute top-[17px] start-[calc(50%+22px)] end-[calc(-50%+22px)] h-0.5 rounded-full <?php echo $done ? 'bg-primary' : 'bg-gray-200'; ?>" aria-hidden="true"></span>
					<?php endif; ?>
					<?php if ( $is_link ) : ?><a href="<?php echo esc_url( $step['url'] ); ?>" class="flex flex-col items-center gap-1.5 min-h-11"><?php endif; ?>
					<span class="relative z-[1] flex size-9 items-center justify-center rounded-full border-2 text-sm font-bold transition-all <?php echo esc_attr( $circle ); ?>">
						<?php if ( $done ) : ?>
							<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
							<span class="sr-only">انجام شد:</span>
						<?php else : ?>
							<?php echo esc_html( $fa[ $n ] ); ?>
						<?php endif; ?>
					</span>
					<span class="text-[11px] lg:text-xs leading-5 <?php echo esc_attr( $label ); ?>"><?php echo esc_html( $step['label'] ); ?></span>
					<?php if ( $is_link ) : ?></a><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/* -------------------------------------------------------------------------
 * Assets
 * ---------------------------------------------------------------------- */

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'gl-shop-flow',
			get_template_directory_uri() . '/css/shop-flow.css',
			array( 'bluebox-style' ),
			function_exists( 'bluebox_asset_ver' ) ? bluebox_asset_ver( 'css/shop-flow.css' ) : null
		);

		// Static values only: pages are full-page cached.
		wp_localize_script(
			'bluebox-script',
			'glCart',
			array(
				'ajax'     => WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'cart'     => wc_get_cart_url(),
				'checkout' => wc_get_checkout_url(),
				'shop'     => wc_get_page_permalink( 'shop' ),
			)
		);
	},
	20
);

/* -------------------------------------------------------------------------
 * No-JS fallback: a plain form POST of a variable product with only the
 * colour radio (variation_id = 0) is resolved to the matching variation
 * before WooCommerce's own handler runs (wp_loaded @ 20).
 * ---------------------------------------------------------------------- */

add_action(
	'wp_loaded',
	function () {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( empty( $_REQUEST['add-to-cart'] ) || ! is_numeric( wp_unslash( $_REQUEST['add-to-cart'] ) ) || ! empty( $_REQUEST['variation_id'] ) ) {
			return;
		}
		$product = wc_get_product( absint( wp_unslash( $_REQUEST['add-to-cart'] ) ) );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return;
		}
		$attributes = array();
		foreach ( $_REQUEST as $key => $value ) {
			if ( is_string( $key ) && 0 === strpos( $key, 'attribute_' ) && is_string( $value ) && '' !== $value ) {
				$attributes[ sanitize_title( wp_unslash( $key ) ) ] = wc_clean( wp_unslash( $value ) );
			}
		}
		// phpcs:enable
		if ( ! $attributes ) {
			return;
		}
		$variation_id = WC_Data_Store::load( 'product' )->find_matching_product_variation( $product, $attributes );
		if ( $variation_id ) {
			$_REQUEST['variation_id'] = $variation_id;
		}
	},
	19
);

/* -------------------------------------------------------------------------
 * Light variation map for product cards (no get_available_variations()).
 * ---------------------------------------------------------------------- */

function gl_card_variations( $product ) {
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return array();
	}
	$key    = 'gl_card_vars_' . $product->get_id();
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$out = array();
	foreach ( $product->get_children() as $child_id ) {
		$variation = wc_get_product( $child_id );
		if ( ! $variation || ! $variation->exists() || ! $variation->variation_is_visible() ) {
			continue;
		}
		$image_id = $variation->get_image_id();
		$out[]    = array(
			'id'         => (int) $child_id,
			'attributes' => $variation->get_variation_attributes(),
			'price'      => (float) wc_get_price_to_display( $variation ),
			'regular'    => (float) wc_get_price_to_display( $variation, array( 'price' => $variation->get_regular_price() ) ),
			'image'      => $image_id ? (string) wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : '',
			'in_stock'   => $variation->is_in_stock() && $variation->is_purchasable(),
		);
	}
	set_transient( $key, $out, WEEK_IN_SECONDS );
	return $out;
}

function gl_card_variations_flush( $id ) {
	$id = is_object( $id ) && method_exists( $id, 'get_id' ) ? $id->get_id() : absint( $id );
	if ( ! $id ) {
		return;
	}
	delete_transient( 'gl_card_vars_' . $id );
	$parent = wp_get_post_parent_id( $id );
	if ( $parent ) {
		delete_transient( 'gl_card_vars_' . $parent );
	}
}
add_action( 'woocommerce_update_product', 'gl_card_variations_flush' );
add_action( 'woocommerce_update_product_variation', 'gl_card_variations_flush' );
add_action( 'woocommerce_delete_product_transients', 'gl_card_variations_flush' );
add_action( 'woocommerce_product_set_stock', 'gl_card_variations_flush' );
add_action( 'woocommerce_variation_set_stock', 'gl_card_variations_flush' );
add_action( 'woocommerce_product_set_stock_status', 'gl_card_variations_flush' );
add_action( 'woocommerce_variation_set_stock_status', 'gl_card_variations_flush' );

/* -------------------------------------------------------------------------
 * Cart page: our cart-empty.php renders its own .wc-empty-cart-message
 * wrapper (WooCommerce's cart.js needs it when the last item is removed).
 * ---------------------------------------------------------------------- */

add_action(
	'init',
	function () {
		remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
	}
);

/* -------------------------------------------------------------------------
 * AJAX plumbing
 * ---------------------------------------------------------------------- */

function gl_cart_guard() {
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
	if ( 'POST' !== $method || empty( $_SERVER['HTTP_X_GL_CART'] ) ) {
		wp_send_json( array( 'success' => false, 'data' => array( 'message' => 'درخواست نامعتبر است.' ) ), 400 );
	}
	if ( ! WC()->cart ) {
		wp_send_json( array( 'success' => false, 'data' => array( 'message' => 'سبد خرید در دسترس نیست. صفحه را دوباره بارگذاری کنید.' ) ), 500 );
	}
}

/**
 * Mirror WC_Cart_Session::set_cart_cookies() before output starts
 * (WooCommerce only does it on `shutdown`, after our JSON is sent).
 */
function gl_cart_set_cookies() {
	if ( headers_sent() ) {
		return;
	}
	$set = ! WC()->cart->is_empty();
	if ( $set ) {
		$cookies = array(
			'woocommerce_items_in_cart' => '1',
			'woocommerce_cart_hash'     => WC()->cart->get_cart_hash(),
		);
		foreach ( $cookies as $name => $value ) {
			if ( ! isset( $_COOKIE[ $name ] ) || $_COOKIE[ $name ] !== $value ) {
				wc_setcookie( $name, $value );
				$_COOKIE[ $name ] = $value;
			}
		}
	} else {
		foreach ( array( 'woocommerce_items_in_cart', 'woocommerce_cart_hash' ) as $name ) {
			if ( isset( $_COOKIE[ $name ] ) ) {
				wc_setcookie( $name, 0, time() - HOUR_IN_SECONDS );
				unset( $_COOKIE[ $name ] );
			}
		}
	}
	// Also creates the guest session cookie (WC_Session_Handler listens to this).
	do_action( 'woocommerce_set_cart_cookies', $set );
}

/**
 * Fragments + summary for the client.
 */
function gl_cart_payload() {
	$cart  = WC()->cart;
	$count = (int) $cart->get_cart_contents_count();

	$fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array() );

	return array(
		'fragments' => $fragments,
		'count'     => $count,
		'cart_hash' => $count ? $cart->get_cart_hash() : '',
		'subtotal'  => (float) $cart->get_displayed_subtotal(),
	);
}

/**
 * Collect and clear WooCommerce error notices (plain text).
 */
function gl_cart_pop_errors() {
	$messages = array();
	foreach ( wc_get_notices( 'error' ) as $notice ) {
		$text = is_array( $notice ) ? ( $notice['notice'] ?? '' ) : $notice;
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) ) ) );
		if ( '' !== $text ) {
			$messages[] = $text;
		}
	}
	wc_clear_notices();
	return implode( ' ', array_unique( $messages ) );
}

function gl_cart_respond( $success, $extra = array() ) {
	gl_cart_set_cookies();
	$data = array_merge( gl_cart_payload(), $extra );
	wp_send_json( array( 'success' => (bool) $success, 'data' => $data ) );
}

function gl_cart_fail( $message, $code = 'error', $extra = array() ) {
	wc_clear_notices();
	gl_cart_respond( false, array_merge( array( 'message' => $message, 'code' => $code ), $extra ) );
}

/* -------------------------------------------------------------------------
 * Endpoints
 * ---------------------------------------------------------------------- */

add_action( 'wc_ajax_gl_cart_refresh', 'gl_ajax_cart_refresh' );
function gl_ajax_cart_refresh() {
	gl_cart_guard();
	gl_cart_respond( true );
}

add_action( 'wc_ajax_gl_add_to_cart', 'gl_ajax_add_to_cart' );
function gl_ajax_add_to_cart() {
	gl_cart_guard();
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- guarded by gl_cart_guard() (custom header).
	$product_id   = absint( $_POST['product_id'] ?? 0 );
	$variation_id = absint( $_POST['variation_id'] ?? 0 );
	$quantity     = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( $_POST['quantity'] ) );

	$posted = array();
	foreach ( $_POST as $key => $value ) {
		if ( is_string( $key ) && 0 === strpos( $key, 'attribute_' ) && is_string( $value ) && '' !== $value ) {
			$posted[ sanitize_title( wp_unslash( $key ) ) ] = wc_clean( wp_unslash( $value ) );
		}
	}
	// phpcs:enable

	$product = wc_get_product( $product_id );
	if ( $product && $product->is_type( 'variation' ) ) {
		$variation_id = $product->get_id();
		$product      = wc_get_product( $product->get_parent_id() );
	}
	if ( ! $product || 'publish' !== $product->get_status() ) {
		gl_cart_fail( 'این محصول پیدا نشد.', 'not_found' );
	}
	$product_id = $product->get_id();
	$name       = $product->get_name();

	if ( $quantity <= 0 ) {
		gl_cart_fail( 'تعداد انتخاب‌شده معتبر نیست.', 'quantity' );
	}

	$variation     = array();
	$stock_product = $product;

	if ( $product->is_type( 'variable' ) ) {
		$attr_defs = $product->get_variation_attributes();
		$missing   = array();
		foreach ( $attr_defs as $attr_name => $options ) {
			$key = 'attribute_' . sanitize_title( $attr_name );
			if ( isset( $posted[ $key ] ) && taxonomy_exists( $attr_name ) ) {
				$posted[ $key ] = sanitize_title( $posted[ $key ] );
			}
			if ( empty( $posted[ $key ] ) ) {
				$missing[] = wc_attribute_label( $attr_name, $product );
			}
		}

		if ( ! $variation_id && $missing ) {
			gl_cart_fail( sprintf( 'لطفاً %s را انتخاب کنید.', implode( ' و ', $missing ) ), 'choose_option' );
		}
		if ( ! $variation_id ) {
			$variation_id = (int) WC_Data_Store::load( 'product' )->find_matching_product_variation( $product, $posted );
		}

		$variation_product = $variation_id ? wc_get_product( $variation_id ) : null;
		if ( ! $variation_product || ! $variation_product->is_type( 'variation' ) || $variation_product->get_parent_id() !== $product_id ) {
			gl_cart_fail( 'این ترکیب موجود نیست؛ لطفاً گزینه دیگری انتخاب کنید.', 'choose_option' );
		}

		foreach ( wc_get_product_variation_attributes( $variation_id ) as $key => $value ) {
			$variation[ $key ] = ( '' === $value ) ? ( $posted[ $key ] ?? '' ) : $value;
			if ( '' === $variation[ $key ] ) {
				gl_cart_fail( 'لطفاً همه گزینه‌های محصول را انتخاب کنید.', 'choose_option' );
			}
		}

		if ( ! $variation_product->is_in_stock() ) {
			$label = wc_get_formatted_variation( $variation_product, true, false, false );
			gl_cart_fail(
				$label ? sprintf( 'متأسفانه رنگ «%s» در حال حاضر ناموجود است.', wp_strip_all_tags( $label ) ) : 'متأسفانه این گزینه در حال حاضر ناموجود است.',
				'out_of_stock'
			);
		}
		if ( ! $variation_product->is_purchasable() ) {
			gl_cart_fail( 'این محصول در حال حاضر قابل خرید نیست.', 'not_purchasable' );
		}
		$stock_product = $variation_product;
	} elseif ( $product->is_type( 'simple' ) ) {
		if ( ! $product->is_purchasable() ) {
			gl_cart_fail( 'این محصول در حال حاضر قابل خرید نیست.', 'not_purchasable' );
		}
		if ( ! $product->is_in_stock() ) {
			gl_cart_fail( sprintf( 'متأسفانه «%s» در حال حاضر ناموجود است.', $name ), 'out_of_stock' );
		}
	} else {
		gl_cart_fail( 'برای خرید این محصول لطفاً از صفحه محصول اقدام کنید.', 'unsupported', array( 'url' => $product->get_permalink() ) );
	}

	// Friendly stock-quantity message (WooCommerce re-checks in add_to_cart()).
	if ( $stock_product->managing_stock() && ! $stock_product->backorders_allowed() ) {
		$available  = (int) $stock_product->get_stock_quantity();
		$quantities = WC()->cart->get_cart_item_quantities();
		$in_cart    = (int) ( $quantities[ $stock_product->get_stock_managed_by_id() ] ?? 0 );
		if ( $in_cart + $quantity > $available ) {
			if ( $available <= 0 ) {
				gl_cart_fail( 'متأسفانه این محصول در حال حاضر ناموجود است.', 'out_of_stock' );
			}
			gl_cart_fail(
				$in_cart > 0
					? sprintf( 'از این محصول فقط %1$s عدد موجود است و %2$s عدد آن در سبد شماست.', number_format_i18n( $available ), number_format_i18n( $in_cart ) )
					: sprintf( 'از این محصول فقط %s عدد موجود است.', number_format_i18n( $available ) ),
				'stock'
			);
		}
	}

	$passed = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation );
	$key    = $passed ? WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation ) : false;

	if ( ! $key ) {
		$message = gl_cart_pop_errors();
		gl_cart_fail( $message ? $message : 'افزودن به سبد خرید انجام نشد؛ لطفاً دوباره تلاش کنید.', 'rejected' );
	}

	do_action( 'woocommerce_ajax_added_to_cart', $product_id );
	wc_clear_notices();

	gl_cart_respond(
		true,
		array(
			'key'     => $key,
			'message' => sprintf( '«%s» به سبد خرید اضافه شد.', $name ),
		)
	);
}

/**
 * Validate the posted cart item key.
 */
function gl_cart_posted_key() {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- guarded by gl_cart_guard().
	$key = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
	return $key;
}

add_action( 'wc_ajax_gl_cart_set_qty', 'gl_ajax_cart_set_qty' );
function gl_ajax_cart_set_qty() {
	gl_cart_guard();
	$key = gl_cart_posted_key();
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$qty  = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : -1;
	$item = $key ? WC()->cart->get_cart_item( $key ) : array();

	if ( ! $item ) {
		gl_cart_fail( 'این کالا دیگر در سبد شما نیست.', 'missing' );
	}
	if ( $qty < 0 ) {
		gl_cart_fail( 'تعداد معتبر نیست.', 'quantity' );
	}

	$product = $item['data'] ?? null;
	$name    = gl_cart_item_base_name( $product );

	if ( 0 === (int) $qty ) {
		WC()->cart->remove_cart_item( $key );
		WC()->cart->calculate_totals();
		gl_cart_respond( true, array( 'removed' => $key, 'name' => $name, 'message' => sprintf( '«%s» از سبد حذف شد.', $name ) ) );
	}

	if ( $product ) {
		if ( $product->is_sold_individually() && $qty > 1 ) {
			gl_cart_fail( 'از این محصول فقط یک عدد در هر سفارش قابل خرید است.', 'stock' );
		}
		$max = $product->get_max_purchase_quantity();
		if ( $max > 0 && $qty > $max ) {
			gl_cart_fail( sprintf( 'از این محصول فقط %s عدد موجود است.', number_format_i18n( $max ) ), 'stock' );
		}
	}

	$passed = apply_filters( 'woocommerce_update_cart_validation', true, $key, $item, $qty );
	if ( ! $passed ) {
		$message = gl_cart_pop_errors();
		gl_cart_fail( $message ? $message : 'تغییر تعداد انجام نشد.', 'rejected' );
	}

	WC()->cart->set_quantity( $key, $qty, true );
	wc_clear_notices();
	gl_cart_respond( true, array( 'key' => $key ) );
}

add_action( 'wc_ajax_gl_cart_remove', 'gl_ajax_cart_remove' );
function gl_ajax_cart_remove() {
	gl_cart_guard();
	$key  = gl_cart_posted_key();
	$item = $key ? WC()->cart->get_cart_item( $key ) : array();
	if ( ! $item ) {
		// Already gone: just resync the client.
		gl_cart_respond( true );
	}
	$name = gl_cart_item_base_name( $item['data'] ?? null );

	WC()->cart->remove_cart_item( $key );
	WC()->cart->calculate_totals();
	wc_clear_notices();
	gl_cart_respond( true, array( 'removed' => $key, 'name' => $name, 'message' => sprintf( '«%s» از سبد حذف شد.', $name ) ) );
}

add_action( 'wc_ajax_gl_cart_undo', 'gl_ajax_cart_undo' );
function gl_ajax_cart_undo() {
	gl_cart_guard();
	$key = gl_cart_posted_key();
	if ( ! $key || ! WC()->cart->restore_cart_item( $key ) ) {
		gl_cart_fail( 'بازگرداندن این کالا ممکن نیست.', 'undo' );
	}
	WC()->cart->calculate_totals();
	wc_clear_notices();
	gl_cart_respond( true, array( 'key' => $key, 'message' => 'کالا به سبد بازگشت.' ) );
}
