<?php
/**
 * نوار پیشرفت سفارش (Order progress tracker).
 *
 * مراحل: ثبت سفارش ← پرداخت ← در حال آماده‌سازی ← ارسال شد ← تحویل شد
 *
 * نگاشت وضعیت‌های ووکامرس:
 *   pending / on-hold / checkout-draft  → مرحلهٔ «پرداخت» (جاری)
 *   processing                          → «در حال آماده‌سازی» (جاری)
 *   processing + کد رهگیری              → «ارسال شد» (جاری)
 *   shipped / sent / … (افزونه‌ها)       → «ارسال شد» (جاری)
 *   completed                           → «تحویل شد» (همه مراحل کامل)
 *   cancelled / refunded / failed       → حالت قرمز: مرحلهٔ متوقف‌شده با علامت ×
 *
 * فیلترها: gl_order_progress_shipped_statuses، gl_order_progress_current،
 *          gl_order_tracking_meta_keys، gl_order_tracking_code، gl_order_tracking_url.
 *
 * استفاده: gl_order_progress( $order ) یا gl_order_progress( $order, array( 'variant' => 'mini' ) ).
 *
 * @package bluebox
 */

defined( 'ABSPATH' ) || exit;

/** وضعیت‌های سفارشیِ «ارسال شد» (افزونه‌های حمل‌ونقل). */
function gl_order_progress_shipped_statuses() {
	return (array) apply_filters( 'gl_order_progress_shipped_statuses', array( 'shipped', 'wc-shipped', 'sent', 'shipping', 'in-transit', 'dispatched', 'posted', 'delivering' ) );
}

/** برچسب مراحل. */
function gl_order_progress_steps() {
	return array(
		array( 'key' => 'placed', 'label' => 'ثبت سفارش', 'icon' => 'receipt' ),
		array( 'key' => 'paid', 'label' => 'پرداخت', 'icon' => 'wallet' ),
		array( 'key' => 'preparing', 'label' => 'در حال آماده‌سازی', 'icon' => 'box' ),
		array( 'key' => 'shipped', 'label' => 'ارسال شد', 'icon' => 'truck' ),
		array( 'key' => 'delivered', 'label' => 'تحویل شد', 'icon' => 'home' ),
	);
}

/**
 * کد رهگیری مرسوله (از متای سفارش یا یادداشت‌های مشتری).
 *
 * @return array{code:string, date:?int} date = local timestamp of the note, when found in a note.
 */
function gl_order_tracking( WC_Order $order ) {
	static $cache = array();
	$id = $order->get_id();
	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}

	$code = '';
	$date = null;
	$keys = (array) apply_filters( 'gl_order_tracking_meta_keys', array( '_tracking_code', 'tracking_code', 'post_tracking_code', '_post_tracking_code', '_shipping_tracking_code', 'shipping_tracking_code', '_tracking_number', 'tracking_number' ) );
	foreach ( $keys as $key ) {
		$value = $order->get_meta( $key );
		if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
			$code = trim( (string) $value );
			break;
		}
	}

	// WooCommerce Shipment Tracking extension.
	if ( ! $code ) {
		$items = $order->get_meta( '_wc_shipment_tracking_items' );
		if ( is_array( $items ) && $items ) {
			$last = end( $items );
			$code = trim( (string) ( $last['tracking_number'] ?? '' ) );
			if ( ! empty( $last['date_shipped'] ) && is_numeric( $last['date_shipped'] ) ) {
				$date = (int) $last['date_shipped'];
			}
		}
	}

	// Customer notes: «کد رهگیری: 1234…» or a bare 20–26 digit Iran Post barcode.
	if ( ! $code ) {
		foreach ( $order->get_customer_order_notes() as $note ) { // Newest first.
			$text = gl_acc_latin_digits( wp_strip_all_tags( (string) $note->comment_content ) );
			if ( preg_match( '/(?:کد\s*رهگیری|رهگیری|بارکد|tracking(?:\s*(?:code|number))?)\s*[:：\-]?\s*([A-Za-z0-9\-]{8,30})/iu', $text, $m )
				|| preg_match( '/(?<!\d)(\d{20,26})(?!\d)/', $text, $m ) ) {
				$code = $m[1];
				$date = strtotime( $note->comment_date );
				break;
			}
		}
	}

	$cache[ $id ] = array(
		'code' => (string) apply_filters( 'gl_order_tracking_code', $code, $order ),
		'date' => $date,
	);
	return $cache[ $id ];
}

/**
 * وضعیت پیشرفت سفارش.
 *
 * @return array{current:int, error:bool, title:string, hint:string, dates:array<int,string>, tracking:array}
 */
function gl_order_progress_state( WC_Order $order ) {
	$status   = $order->get_status();
	$tracking = gl_order_tracking( $order );
	$error    = in_array( $status, array( 'cancelled', 'refunded', 'failed' ), true );
	$steps    = gl_order_progress_steps();

	if ( 'completed' === $status ) {
		$current = 5;
	} elseif ( in_array( $status, gl_order_progress_shipped_statuses(), true ) ) {
		$current = 3;
	} elseif ( 'processing' === $status ) {
		$current = $tracking['code'] ? 3 : 2;
	} elseif ( in_array( $status, array( 'pending', 'on-hold', 'checkout-draft' ), true ) ) {
		$current = 1;
	} elseif ( $error ) {
		$current = ( 'refunded' === $status || $order->get_date_paid() ) ? 2 : 1;
	} else {
		$current = $order->is_paid() ? 2 : 1;
	}
	$current = max( 0, min( 5, (int) apply_filters( 'gl_order_progress_current', $current, $order ) ) );

	// Dates of reached steps.
	$dates = array( 0 => gl_acc_date( $order->get_date_created(), true ) );
	if ( $order->get_date_paid() ) {
		$dates[1] = gl_acc_date( $order->get_date_paid(), true );
	}
	$shipped = $order->get_meta( '_gl_shipped_date' ) ?: $order->get_meta( '_date_shipped' );
	if ( $shipped ) {
		$dates[3] = gl_acc_date( is_numeric( $shipped ) ? (int) $shipped : strtotime( (string) $shipped ), true );
	} elseif ( $tracking['date'] ) {
		$dates[3] = gl_acc_date( $tracking['date'], true );
	}
	if ( $order->get_date_completed() ) {
		$dates[4] = gl_acc_date( $order->get_date_completed(), true );
	}

	$hints = array(
		0 => 'سفارش شما ثبت شد.',
		1 => 'on-hold' === $status
			? 'پرداخت شما در حال بررسی است؛ پس از تأیید، آماده‌سازی سفارش آغاز می‌شود.'
			: 'سفارش ثبت شده و در انتظار پرداخت است.',
		2 => 'سفارش شما با دقت در حال بسته‌بندی و آماده‌سازی برای ارسال است.',
		3 => 'مرسوله تحویل پست شده و در راه رسیدن به شماست.',
		4 => 'سفارش در مرحلهٔ تحویل است.',
		5 => 'سفارش تحویل شد. امیدواریم از خریدتان لذت ببرید.',
	);
	$title = 5 === $current ? $steps[4]['label'] : $steps[ min( $current, 4 ) ]['label'];
	$hint  = $hints[ $current ] ?? '';
	if ( 1 === $current ) {
		$title = wc_get_order_status_name( $status );
	}

	if ( $error ) {
		$title = wc_get_order_status_name( $status );
		$hint  = array(
			'cancelled' => 'این سفارش لغو شده است. اگر مبلغی پرداخت کرده‌اید، با پشتیبانی در تماس باشید.',
			'refunded'  => 'مبلغ این سفارش به شما بازگردانده شده است.',
			'failed'    => 'پرداخت این سفارش ناموفق بود. می‌توانید دوباره پرداخت کنید یا با پشتیبانی تماس بگیرید.',
		)[ $status ];
	}

	return array(
		'current'  => $current,
		'error'    => $error,
		'title'    => $title,
		'hint'     => $hint,
		'dates'    => $dates,
		'tracking' => $tracking,
	);
}

/**
 * چاپ نوار پیشرفت.
 *
 * @param WC_Order $order Order.
 * @param array    $args  variant: full|mini, card: bool (wrap in card), tracking: bool, reveal: int (stagger index).
 */
function gl_order_progress( $order, $args = array() ) {
	if ( ! $order instanceof WC_Order ) {
		$order = wc_get_order( $order );
	}
	if ( ! $order ) {
		return;
	}
	if ( function_exists( 'gl_acc_enqueue_style' ) ) {
		gl_acc_enqueue_style(); // Safety net when used outside the account screens.
	}

	$args  = wp_parse_args( $args, array( 'variant' => 'full', 'card' => true, 'tracking' => true, 'reveal' => 0 ) );
	$state = gl_order_progress_state( $order );
	$steps = gl_order_progress_steps();
	$cur   = $state['current'];
	$err   = $state['error'];

	$step_class = static function ( $i ) use ( $cur, $err ) {
		if ( $err ) {
			return $i < $cur ? 'is-done' : ( $i === $cur ? 'is-fail' : 'is-todo' );
		}
		return $i < $cur ? 'is-done' : ( $i === $cur ? 'is-current' : 'is-todo' );
	};

	if ( 'mini' === $args['variant'] ) {
		?>
		<div class="gl-mini<?php echo $err ? ' is-error' : ''; ?>" data-reveal style="--d:<?php echo (int) $args['reveal']; ?>">
			<div class="gl-mini__bar" role="progressbar" aria-valuemin="0" aria-valuemax="5" aria-valuenow="<?php echo (int) $cur; ?>" aria-label="<?php echo esc_attr( 'وضعیت سفارش: ' . $state['title'] ); ?>">
				<?php foreach ( $steps as $i => $step ) : ?>
					<span class="gl-mini__seg <?php echo esc_attr( $step_class( $i ) ); ?>" style="--i:<?php echo (int) $i; ?>"><i></i></span>
				<?php endforeach; ?>
			</div>
			<p class="mt-2 flex items-center gap-1.5 text-xs font-bold <?php echo $err ? 'text-secondary' : 'text-primary'; ?>">
				<?php echo gl_acc_icon( $err ? 'x' : ( 5 === $cur ? 'check' : $steps[ min( $cur, 4 ) ]['icon'] ), 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo esc_html( $state['title'] ); ?>
			</p>
		</div>
		<?php
		return;
	}

	$wrap = $args['card'] ? 'rounded-3xl border border-gray-100 bg-white p-4 shadow-[0_8px_30px_-12px_rgba(16,37,66,0.15)] sm:p-6' : '';
	?>
	<section class="gl-prog <?php echo $err ? 'is-error' : ''; ?> <?php echo esc_attr( $wrap ); ?>" data-reveal style="--d:<?php echo (int) $args['reveal']; ?>" aria-label="مراحل سفارش">
		<div class="mb-6 flex items-start gap-3">
			<span class="grid size-11 shrink-0 place-items-center rounded-2xl <?php echo $err ? 'bg-red-50 text-secondary' : 'bg-primary/5 text-primary'; ?>">
				<?php echo gl_acc_icon( $err ? 'info' : ( 5 === $cur ? 'check' : $steps[ min( $cur, 4 ) ]['icon'] ), 'size-6' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
			<div class="min-w-0 flex-1">
				<p class="text-xs text-gray-500">وضعیت فعلی سفارش</p>
				<p class="text-lg font-black <?php echo $err ? 'text-secondary' : 'text-primary'; ?>"><?php echo esc_html( $state['title'] ); ?></p>
				<p class="mt-1 text-sm leading-6 text-gray-500"><?php echo esc_html( $state['hint'] ); ?></p>
			</div>
			<?php if ( ! $err ) : ?>
				<span class="hidden shrink-0 rounded-full bg-gray-50 px-3 py-1 text-xs font-bold text-gray-500 ring-1 ring-inset ring-gray-200 sm:inline-flex">
					<?php echo esc_html( gl_acc_num( 'مرحلهٔ ' . min( $cur + 1, 5 ) . ' از ۵' ) ); ?>
				</span>
			<?php endif; ?>
		</div>

		<ol class="gl-prog__steps">
			<?php
			foreach ( $steps as $i => $step ) :
				$cls   = $step_class( $i );
				$label = 'is-fail' === $cls ? wc_get_order_status_name( $order->get_status() ) : $step['label'];
				$icon  = 'is-done' === $cls ? 'check' : ( 'is-fail' === $cls ? 'x' : $step['icon'] );
				?>
				<li class="gl-prog__step <?php echo esc_attr( $cls ); ?>" style="--i:<?php echo (int) $i; ?>"<?php echo in_array( $cls, array( 'is-current', 'is-fail' ), true ) ? ' aria-current="step"' : ''; ?>>
					<?php if ( $i < count( $steps ) - 1 ) : ?>
						<span class="gl-prog__seg" aria-hidden="true"><i></i></span>
					<?php endif; ?>
					<span class="gl-prog__dot"><?php echo gl_acc_icon( $icon, 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="gl-prog__txt">
						<span class="gl-prog__label"><?php echo esc_html( $label ); ?></span>
						<?php if ( 'is-done' === $cls || 'is-current' === $cls ) : ?>
							<?php if ( ! empty( $state['dates'][ $i ] ) ) : ?>
								<span class="gl-prog__date"><?php echo esc_html( $state['dates'][ $i ] ); ?></span>
							<?php elseif ( 'is-current' === $cls ) : ?>
								<span class="gl-prog__date gl-prog__now">در جریان</span>
							<?php endif; ?>
						<?php endif; ?>
						<span class="sr-only"><?php echo esc_html( 'is-done' === $cls ? '(انجام شد)' : ( 'is-current' === $cls ? '(مرحلهٔ فعلی)' : ( 'is-fail' === $cls ? '(متوقف شد)' : '(در انتظار)' ) ) ); ?></span>
					</span>
				</li>
			<?php endforeach; ?>
		</ol>

		<?php
		if ( $args['tracking'] && $state['tracking']['code'] ) {
			gl_order_tracking_box( $order, $state['tracking']['code'] );
		}
		?>
	</section>
	<?php
}

/** جعبهٔ کد رهگیری با دکمهٔ کپی و لینک سامانهٔ پست. */
function gl_order_tracking_box( WC_Order $order, $code ) {
	$url = (string) apply_filters( 'gl_order_tracking_url', 'https://tracking.post.ir/', $code, $order );
	?>
	<div class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-dashed border-primary/20 bg-primary/[0.03] p-3 sm:p-4"
		 x-data="{ copied: false, code: <?php echo esc_attr( wp_json_encode( (string) $code ) ); ?>,
			copy() {
				const done = () => { this.copied = true; setTimeout(() => this.copied = false, 1800); };
				if (navigator.clipboard) { navigator.clipboard.writeText(this.code).then(done, () => window.prompt('', this.code)); }
				else { window.prompt('', this.code); }
			} }">
		<span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-white text-primary shadow-sm"><?php echo gl_acc_icon( 'truck', 'size-6' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<div class="min-w-0 flex-1">
			<p class="text-xs text-gray-500">کد رهگیری مرسوله</p>
			<p class="mt-0.5 break-all text-right font-mono text-sm font-bold tracking-wider text-primary sm:text-base" dir="ltr"><?php echo esc_html( $code ); ?></p>
		</div>
		<div class="flex w-full gap-2 sm:w-auto">
			<button type="button" @click="copy()" class="inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center gap-2 rounded-full border border-gray-200 bg-white px-4 text-sm font-bold text-primary transition hover:border-primary sm:flex-none">
				<?php echo gl_acc_icon( 'copy', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span x-text="copied ? 'کپی شد' : 'کپی کد'">کپی کد</span>
			</button>
			<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-full bg-primary px-4 text-sm font-bold text-white transition hover:bg-primary/90 sm:flex-none">
				رهگیری در سایت پست
				<?php echo gl_acc_icon( 'external', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>
		<p class="w-full text-xs leading-5 text-gray-400">کد را کپی کنید و در سامانهٔ رهگیری پست وارد کنید.</p>
	</div>
	<?php
}
