<?php
/**
 * The template for displaying product search form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/product-searchform.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<form role="search" method="get" class="woocommerce-product-search flex gap-2" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="sr-only" for="woocommerce-product-search-field-<?php echo isset( $index ) ? absint( $index ) : 0; ?>"><?php esc_html_e( 'Search for:', 'woocommerce' ); ?></label>
	<input type="search" id="woocommerce-product-search-field-<?php echo isset( $index ) ? absint( $index ) : 0; ?>"
	       class="search-field h-12 min-w-0 flex-1 rounded-2xl border border-gray-200 bg-white px-4 text-base outline-none transition focus:border-primary/40 focus:ring-4 focus:ring-primary/5"
	       placeholder="جستجوی کیف، رنگ یا مقاله…" maxlength="64" enterkeyhint="search"
	       value="<?php echo esc_attr( get_search_query() ); ?>" name="s" />
	<button type="submit" class="min-h-12 shrink-0 cursor-pointer rounded-full bg-primary px-5 text-sm font-bold text-white transition hover:brightness-125">جستجو</button>
</form>
