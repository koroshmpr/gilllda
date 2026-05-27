<?php
/**
 * Handle Custom Alpine.js Filters for WooCommerce
 */
add_action( 'woocommerce_product_query', 'maya_flowers_custom_query', 100 );
function maya_flowers_custom_query( $q ) {
	if ( is_admin() ) return;

	// 1. Filter by Category
	if ( isset( $_GET['product_cat'] ) && ! empty( $_GET['product_cat'] ) ) {
		$cats = explode( ',', sanitize_text_field( $_GET['product_cat'] ) );
		$tax_query = (array) $q->get( 'tax_query' );
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $cats,
			'operator' => 'IN',
		);
		$q->set( 'tax_query', $tax_query );
	}

	// 2. Filter by Price
	if ( isset( $_GET['max_price'] ) && ! empty( $_GET['max_price'] ) ) {
		$meta_query = (array) $q->get( 'meta_query' );
		$meta_query[] = array(
			'key'     => '_price',
			'value'   => sanitize_text_field( $_GET['max_price'] ),
			'compare' => '<=',
			'type'    => 'NUMERIC',
		);
		$q->set( 'meta_query', $meta_query );
	}

	// 3. Filter by Weight
	if ( isset( $_GET['min_weight'] ) && ! empty( $_GET['min_weight'] ) ) {
		$meta_query = (array) $q->get( 'meta_query' );
		$meta_query[] = array(
			'key'     => '_weight',
			'value'   => sanitize_text_field( $_GET['min_weight'] ),
			'compare' => '>=',
			'type'    => 'NUMERIC',
		);
		$q->set( 'meta_query', $meta_query );
	}
}
