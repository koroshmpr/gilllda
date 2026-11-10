<?php
/**
 * پنل کاربری (حساب من): ابزارهای مشترک قالب‌های myaccount/ و order/.
 *
 * - css/account.css فقط در صفحات حساب، رسید سفارش و رهگیری سفارش بارگذاری می‌شود.
 * - تب «دانلودها» فقط وقتی کاربر فایل قابل‌دانلود دارد نمایش داده می‌شود.
 * - توابع کمکی: آیکون‌ها، رنگ وضعیت، تاریخ شمسی، آمار مشتری، راه‌های تماس.
 *
 * @package bluebox
 */

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/order-progress.php';

/* -------------------------------------------------------------------------
 * Assets
 * ---------------------------------------------------------------------- */

/** آیا صفحهٔ فعلی از قالب‌های حساب/سفارش استفاده می‌کند؟ */
function gl_acc_is_screen() {
	if ( ! function_exists( 'is_account_page' ) ) {
		return false;
	}
	if ( is_account_page() || is_wc_endpoint_url( 'order-received' ) ) {
		return true;
	}
	$post = get_post();
	return $post && has_shortcode( (string) $post->post_content, 'woocommerce_order_tracking' );
}

function gl_acc_enqueue_style() {
	if ( wp_style_is( 'gl-account', 'enqueued' ) ) {
		return;
	}
	wp_enqueue_style(
		'gl-account',
		get_template_directory_uri() . '/css/account.css',
		array( 'bluebox-style' ),
		function_exists( 'bluebox_asset_ver' ) ? bluebox_asset_ver( 'css/account.css' ) : null
	);
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( gl_acc_is_screen() ) {
			gl_acc_enqueue_style();
		}
	},
	30
);

/* -------------------------------------------------------------------------
 * Menu: hide «دانلودها» when the customer has nothing to download.
 * ---------------------------------------------------------------------- */

add_filter(
	'woocommerce_account_menu_items',
	function ( $items ) {
		if ( isset( $items['downloads'] ) && is_user_logged_in() && ! gl_acc_has_downloads() ) {
			unset( $items['downloads'] );
		}
		return $items;
	},
	20
);

function gl_acc_has_downloads() {
	static $has = null;
	if ( null === $has ) {
		$has = function_exists( 'wc_get_customer_available_downloads' ) && ! empty( wc_get_customer_available_downloads( get_current_user_id() ) );
	}
	return $has;
}

/* -------------------------------------------------------------------------
 * Formatting helpers
 * ---------------------------------------------------------------------- */

/** ارقام فارسی. */
function gl_acc_num( $value ) {
	return function_exists( 'gl_fa_num' ) ? gl_fa_num( $value ) : (string) $value;
}

/** ارقام فارسی/عربی → انگلیسی. */
function gl_acc_latin_digits( $value ) {
	return strtr(
		(string) $value,
		array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		)
	);
}

/**
 * تاریخ شمسی کوتاه («۱۲ آبان ۱۴۰۵»).
 *
 * @param WC_DateTime|int|null $date      WC date object or a local-offset timestamp.
 * @param bool                 $with_time Append «، ساعت ۱۴:۳۰».
 * @param bool                 $with_year Include the year.
 */
function gl_acc_date( $date, $with_time = false, $with_year = true ) {
	if ( ! $date ) {
		return '';
	}
	$ts = $date instanceof WC_DateTime ? $date->getOffsetTimestamp() : (int) $date;
	if ( function_exists( 'shamsi_date' ) ) {
		$out = ltrim( shamsi_date( $with_year ? 'd F Y' : 'd F', $ts ), '0' );
	} else {
		$out = date_i18n( $with_year ? 'j F Y' : 'j F', $ts );
	}
	if ( $with_time ) {
		$out .= '، ساعت ' . gmdate( 'H:i', $ts );
	}
	return gl_acc_num( $out );
}

/** حروف اول نام برای آواتار. */
function gl_acc_initials( WP_User $user ) {
	$first = trim( (string) $user->first_name );
	$last  = trim( (string) $user->last_name );
	if ( $first || $last ) {
		return mb_substr( $first, 0, 1, 'UTF-8' ) . ( $last ? '‌' . mb_substr( $last, 0, 1, 'UTF-8' ) : '' );
	}
	$name = trim( (string) $user->display_name ) ?: (string) $user->user_login;
	return mb_strtoupper( mb_substr( $name, 0, 1, 'UTF-8' ), 'UTF-8' );
}

/** نام نمایشی کاربر (نام کوچک در صورت وجود). */
function gl_acc_first_name( WP_User $user ) {
	$name = trim( (string) $user->first_name );
	if ( ! $name ) {
		$name = explode( ' ', trim( (string) $user->display_name ) )[0];
	}
	return $name ?: (string) $user->user_login;
}

/* -------------------------------------------------------------------------
 * Order status → colour
 * ---------------------------------------------------------------------- */

/** کلاس‌های چیپ وضعیت سفارش. */
function gl_acc_status_classes( $status ) {
	$status = str_replace( 'wc-', '', (string) $status );
	if ( in_array( $status, gl_order_progress_shipped_statuses(), true ) ) {
		return 'bg-indigo-50 text-indigo-700 ring-indigo-200';
	}
	$map = array(
		'pending'    => 'bg-amber-50 text-amber-700 ring-amber-200',
		'on-hold'    => 'bg-amber-50 text-amber-700 ring-amber-200',
		'processing' => 'bg-sky-50 text-sky-700 ring-sky-200',
		'completed'  => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
		'cancelled'  => 'bg-red-50 text-secondary ring-red-200',
		'failed'     => 'bg-red-50 text-secondary ring-red-200',
		'refunded'   => 'bg-red-50 text-secondary ring-red-200',
	);
	return $map[ $status ] ?? 'bg-gray-100 text-gray-700 ring-gray-200';
}

/** چیپ وضعیت سفارش (HTML امن). */
function gl_acc_status_chip( WC_Order $order, $extra = '' ) {
	$status = $order->get_status();
	return sprintf(
		'<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset whitespace-nowrap %1$s %2$s"><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>%3$s</span>',
		esc_attr( gl_acc_status_classes( $status ) ),
		esc_attr( $extra ),
		esc_html( wc_get_order_status_name( $status ) )
	);
}

/* -------------------------------------------------------------------------
 * Customer data
 * ---------------------------------------------------------------------- */

/** وضعیت‌هایی که «در جریان» حساب می‌شوند. */
function gl_acc_active_statuses() {
	return array_values( array_unique( array_merge( array( 'pending', 'on-hold', 'processing' ), gl_order_progress_shipped_statuses() ) ) );
}

/** آمار مشتری برای داشبورد. */
function gl_acc_customer_stats( $user_id ) {
	$active = wc_get_orders(
		array(
			'customer_id' => $user_id,
			'status'      => gl_acc_active_statuses(),
			'limit'       => -1,
			'return'      => 'ids',
		)
	);
	return array(
		'orders' => (int) wc_get_customer_order_count( $user_id ),
		'active' => is_array( $active ) ? count( $active ) : 0,
		'spent'  => (float) wc_get_customer_total_spent( $user_id ),
	);
}

/**
 * تکمیل بودن پروفایل: درصد و فیلدهای ناقص.
 *
 * @return array{percent:int, missing:string[], address_ok:bool, phone_ok:bool}
 */
function gl_acc_profile_status( $user_id ) {
	$customer = new WC_Customer( $user_id );
	$fields   = array(
		'نام'          => $customer->get_first_name() ?: $customer->get_billing_first_name(),
		'نام خانوادگی' => $customer->get_last_name() ?: $customer->get_billing_last_name(),
		'ایمیل'        => $customer->get_email(),
		'موبایل'       => $customer->get_billing_phone(),
		'استان و شهر'  => $customer->get_billing_city(),
		'آدرس'         => $customer->get_billing_address_1(),
		'کد پستی'      => $customer->get_billing_postcode(),
	);
	$missing = array_keys( array_filter( $fields, static fn( $v ) => '' === trim( (string) $v ) ) );
	return array(
		'percent'    => (int) round( ( count( $fields ) - count( $missing ) ) / count( $fields ) * 100 ),
		'missing'    => $missing,
		'address_ok' => '' !== trim( $customer->get_billing_address_1() . '' ) && '' !== trim( $customer->get_billing_city() . '' ),
		'phone_ok'   => '' !== trim( $customer->get_billing_phone() . '' ),
	);
}

/** تصاویر کوچک اقلام سفارش. */
function gl_acc_order_thumbs( WC_Order $order, $max = 4, $size_class = 'size-12' ) {
	$items = array_values( $order->get_items() );
	if ( ! $items ) {
		return;
	}
	$extra = count( $items ) - $max;
	echo '<div class="flex items-center -space-x-3">';
	foreach ( array_slice( $items, 0, $max ) as $i => $item ) {
		$product  = $item->get_product();
		$image_id = $product ? $product->get_image_id() : 0;
		echo '<span class="relative shrink-0 overflow-hidden rounded-2xl bg-gray-50 ring-2 ring-white ' . esc_attr( $size_class ) . '" style="z-index:' . (int) ( 10 - $i ) . '" title="' . esc_attr( $item->get_name() ) . '">';
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, 'woocommerce_gallery_thumbnail', false, array( 'class' => 'size-full object-cover', 'loading' => 'lazy', 'alt' => $item->get_name() ) );
		} else {
			echo wc_placeholder_img( 'woocommerce_gallery_thumbnail', array( 'class' => 'size-full object-cover' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</span>';
	}
	if ( $extra > 0 ) {
		echo '<span class="relative grid shrink-0 place-items-center rounded-2xl bg-primary/5 text-xs font-bold text-primary ring-2 ring-white ' . esc_attr( $size_class ) . '">+' . esc_html( gl_acc_num( $extra ) ) . '</span>';
	}
	echo '</div>';
}

/* -------------------------------------------------------------------------
 * Support / contact
 * ---------------------------------------------------------------------- */

/**
 * راه‌های تماس از تنظیمات قالب (ACF options).
 *
 * @return array{phone:string, tel:string, whatsapp:string, contact:string}
 */
function gl_acc_support() {
	static $out = null;
	if ( null !== $out ) {
		return $out;
	}
	$out = array( 'phone' => '', 'tel' => '', 'whatsapp' => '', 'contact' => '' );
	if ( function_exists( 'get_field' ) ) {
		$phone = (string) get_field( 'phone', 'option' );
		if ( $phone ) {
			$out['phone'] = $phone;
			$out['tel']   = preg_replace( '/[^\d+]/', '', gl_acc_latin_digits( $phone ) );
		}
		$socials = get_field( 'social', 'option' );
		if ( is_array( $socials ) ) {
			foreach ( $socials as $social ) {
				$url  = is_array( $social['link'] ?? null ) ? ( $social['link']['url'] ?? '' ) : (string) ( $social['link'] ?? '' );
				$name = strtolower( (string) ( $social['name'] ?? '' ) );
				if ( $url && ( false !== strpos( $name, 'whatsapp' ) || preg_match( '~wa\.me|whatsapp\.com~i', $url ) ) ) {
					$out['whatsapp'] = $url;
					break;
				}
			}
		}
	}
	$pages = get_pages(
		array(
			'meta_key'   => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => 'contact-us.php', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'number'     => 1,
		)
	);
	if ( $pages ) {
		$out['contact'] = get_permalink( $pages[0] );
	}
	return $out;
}

/** کارت «نیاز به راهنمایی دارید؟» */
function gl_acc_help_card( $order = null ) {
	$s = gl_acc_support();
	if ( ! $s['tel'] && ! $s['whatsapp'] && ! $s['contact'] ) {
		return;
	}
	$ref = $order instanceof WC_Order ? ' (سفارش #' . gl_acc_num( $order->get_order_number() ) . ')' : '';
	?>
	<div class="mt-6 flex flex-col gap-4 rounded-3xl border border-gray-100 bg-gradient-to-l from-primary/[0.04] to-white p-4 shadow-[0_8px_30px_-12px_rgba(16,37,66,0.12)] sm:flex-row sm:items-center sm:p-5" data-reveal>
		<span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-primary text-white"><?php echo gl_acc_icon( 'headset', 'size-6' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<div class="min-w-0 flex-1">
			<p class="font-black text-primary">سؤالی دربارهٔ سفارش دارید؟</p>
			<p class="mt-1 text-sm leading-6 text-gray-500"><?php echo esc_html( 'تیم پشتیبانی گیلدا کنار شماست' . $ref . '.' ); ?></p>
		</div>
		<div class="flex flex-wrap gap-2">
			<?php if ( $s['whatsapp'] ) : ?>
				<a href="<?php echo esc_url( $s['whatsapp'] ); ?>" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-emerald-600 px-4 text-sm font-bold text-white transition hover:bg-emerald-700"><?php echo gl_acc_icon( 'chat', 'size-5' ); // phpcs:ignore ?>واتساپ</a>
			<?php endif; ?>
			<?php if ( $s['tel'] ) : ?>
				<a href="tel:<?php echo esc_attr( $s['tel'] ); ?>" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-primary px-4 text-sm font-bold text-white transition hover:bg-primary/90"><?php echo gl_acc_icon( 'phone', 'size-5' ); // phpcs:ignore ?><span dir="ltr"><?php echo esc_html( gl_acc_num( $s['phone'] ) ); ?></span></a>
			<?php elseif ( $s['contact'] ) : ?>
				<a href="<?php echo esc_url( $s['contact'] ); ?>" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-primary px-4 text-sm font-bold text-white transition hover:bg-primary/90">تماس با ما</a>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Icons (24px outline, currentColor)
 * ---------------------------------------------------------------------- */

/**
 * آیکون SVG خطی. خروجی ثابت و امن است.
 *
 * @param string $name  Icon key.
 * @param string $class Classes.
 */
function gl_acc_icon( $name, $class = 'size-5' ) {
	$paths = array(
		'dashboard' => '<path d="M4 5a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1zM13 5a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1zM4 16a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1zM13 13a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1z"/>',
		'orders'    => '<path d="M6 7h12l1 13H5L6 7z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/>',
		'address'   => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'card'      => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 10h18M7 15h3"/>',
		'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'logout'    => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l-5-5 5-5M5 12h11"/>',
		'download'  => '<path d="M12 4v11m0 0l-4-4m4 4l4-4M5 20h14"/>',
		'check'     => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
		'x'         => '<path d="M6 6l12 12M18 6L6 18"/>',
		'receipt'   => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/>',
		'wallet'    => '<path d="M4 7a2 2 0 0 1 2-2h11v4"/><rect x="4" y="7" width="16" height="13" rx="2"/><path d="M16 13.5h.01"/>',
		'box'       => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8M12 13v8"/>',
		'truck'     => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
		'home'      => '<path d="M4 11l8-7 8 7v8a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1z"/>',
		'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'bag'       => '<path d="M5 8h14l-1 12H6L5 8z"/><path d="M9 8a3 3 0 0 1 6 0"/>',
		'chevron'   => '<path d="M15 6l-6 6 6 6"/>',
		'copy'      => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>',
		'external'  => '<path d="M10 5H5v14h14v-5"/><path d="M14 4h6v6M20 4l-9 9"/>',
		'phone'     => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1z"/>',
		'chat'      => '<path d="M4 20l1.5-4A8 8 0 1 1 9 19.2L4 20z"/>',
		'headset'   => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/>',
		'compare'   => '<path d="M7 4v16M17 4v16M3 8h8M13 16h8"/>',
		'spark'     => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/>',
		'refresh'   => '<path d="M20 11a8 8 0 0 0-14.9-3M4 4v4h4M4 13a8 8 0 0 0 14.9 3M20 20v-4h-4"/>',
		'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
		'key'       => '<circle cx="8" cy="15" r="4"/><path d="M11 12l8-8M16 7l2 2"/>',
		'note'      => '<path d="M5 4h14v12l-4 4H5z"/><path d="M15 20v-4h4M9 9h6M9 13h3"/>',
	);
	$d = $paths[ $name ] ?? $paths['info'];
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
}

/** آیکون هر تب منوی حساب. */
function gl_acc_menu_icon( $endpoint ) {
	$map = array(
		'dashboard'       => 'dashboard',
		'orders'          => 'orders',
		'downloads'       => 'download',
		'edit-address'    => 'address',
		'payment-methods' => 'card',
		'edit-account'    => 'user',
		'customer-logout' => 'logout',
	);
	return gl_acc_icon( $map[ $endpoint ] ?? 'spark', 'size-5 shrink-0' );
}
