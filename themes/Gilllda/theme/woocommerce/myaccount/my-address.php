<?php
/**
 * My Addresses
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/my-address.php.
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

defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();

if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing'  => __( 'Billing address', 'woocommerce' ),
			'shipping' => __( 'Shipping address', 'woocommerce' ),
		),
		$customer_id
	);
} else {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing' => __( 'Billing address', 'woocommerce' ),
		),
		$customer_id
	);
}

$oldcol = 1;
$col    = 1;
?>

<div class="mb-4">
	<h2 class="text-lg font-black text-primary">آدرس‌های من</h2>
	<p class="mt-0.5 text-sm text-gray-500">
		<?php echo apply_filters( 'woocommerce_my_account_my_address_description', esc_html__( 'The following addresses will be used on the checkout page by default.', 'woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</p>
</div>

<?php if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) : ?>
	<div class="u-columns woocommerce-Addresses col2-set addresses grid gap-4 sm:grid-cols-2">
<?php else : ?>
	<div class="woocommerce-Addresses grid gap-4">
<?php endif; ?>

<?php
$gl_i = 0;
foreach ( $get_addresses as $name => $address_title ) :
	?>
	<?php
		$address = wc_get_account_formatted_address( $name );
		$col     = $col * -1;
		$oldcol  = $oldcol * -1;
	?>

	<div class="u-column<?php echo $col < 0 ? 1 : 2; ?> col-<?php echo $oldcol < 0 ? 1 : 2; ?> woocommerce-Address flex flex-col rounded-3xl border bg-white p-4 shadow-[0_8px_30px_-16px_rgba(16,37,66,0.18)] sm:p-5 <?php echo $address ? 'border-gray-100' : 'border-dashed border-gray-200'; ?>" data-reveal style="--d:<?php echo (int) $gl_i++; ?>">
		<header class="woocommerce-Address-title title mb-3 flex items-center gap-3">
			<span class="grid size-10 shrink-0 place-items-center rounded-2xl <?php echo $address ? 'bg-primary/5 text-primary' : 'bg-amber-50 text-amber-700'; ?>"><?php echo gl_acc_icon( 'billing' === $name ? 'receipt' : 'truck', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<h2 class="min-w-0 flex-1 text-base font-black text-primary"><?php echo esc_html( $address_title ); ?></h2>
			<?php if ( $address ) : ?>
				<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700">ثبت شده</span>
			<?php endif; ?>
		</header>
		<address class="flex-1 text-sm not-italic leading-7 text-gray-600">
			<?php
				echo $address ? wp_kses_post( $address ) : esc_html_e( 'You have not set up this type of address yet.', 'woocommerce' );

				/**
				 * Used to output content after core address fields.
				 *
				 * @param string $name Address type.
				 * @since 8.7.0
				 */
				do_action( 'woocommerce_my_account_after_my_address', $name );
			?>
		</address>
		<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', $name ) ); ?>" class="edit mt-4 inline-flex min-h-11 items-center justify-center gap-2 rounded-full px-5 text-sm font-bold transition <?php echo $address ? 'border border-gray-200 text-primary hover:border-primary' : 'bg-primary text-white hover:bg-primary/90'; ?>">
			<?php echo gl_acc_icon( 'address', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php
				printf(
					/* translators: %s: Address title */
					$address ? esc_html__( 'Edit %s', 'woocommerce' ) : esc_html__( 'Add %s', 'woocommerce' ),
					esc_html( $address_title )
				);
			?>
		</a>
	</div>

<?php endforeach; ?>

</div>
