<?php
add_action( 'woocommerce_product_query', 'maya_flowers_custom_query', 100 );
function maya_flowers_custom_query( $q ) {
    if ( is_admin() || ! $q->is_main_query() ) {
        return;
    }

    // Safely get existing queries to avoid empty array bugs
    $meta_query = $q->get( 'meta_query' );
    $meta_query = is_array( $meta_query ) ? $meta_query : array();

    $tax_query = $q->get( 'tax_query' );
    $tax_query = is_array( $tax_query ) ? $tax_query : array();

    $filters_applied = false;

    // 1. Filter by Category
    if ( ! empty( $_GET['product_cat'] ) ) {
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => explode( ',', sanitize_text_field( $_GET['product_cat'] ) ),
            'operator' => 'IN',
        );
        $filters_applied = true;
    }

    // 2. Filter by Price
    if ( ! empty( $_GET['max_price'] ) ) {
        $meta_query[] = array(
            'key'     => '_price',
            'value'   => sanitize_text_field( $_GET['max_price'] ),
            'compare' => '<=',
            'type'    => 'NUMERIC',
        );
        $filters_applied = true;
    }

    // 3. Filter by Weight
    if ( ! empty( $_GET['min_weight'] ) ) {
        $meta_query[] = array(
            'key'     => '_weight',
            'value'   => sanitize_text_field( $_GET['min_weight'] ),
            'compare' => '>=',
            'type'    => 'NUMERIC',
        );
        $filters_applied = true;
    }

    // 4. Filter by Attributes (Color, Size, etc.)
    foreach ( $_GET as $key => $value ) {
        if ( strpos( $key, 'pa_' ) === 0 && ! empty( $value ) ) {
            $tax_query[] = array(
                'taxonomy' => sanitize_key( $key ),
                'field'    => 'slug',
                'terms'    => explode( ',', sanitize_text_field( $value ) ),
                'operator' => 'IN',
            );
            $filters_applied = true;
        }
    }

    // 5. Filter by In Stock
    if ( isset( $_GET['in_stock'] ) && $_GET['in_stock'] === 'true' ) {
        $meta_query[] = array(
            'key'     => '_stock_status',
            'value'   => 'instock',
            'compare' => '='
        );
        $filters_applied = true;
    }

    // 6. Filter by On Sale
    if ( isset( $_GET['on_sale'] ) && $_GET['on_sale'] === 'true' ) {
        $product_ids_on_sale = wc_get_product_ids_on_sale();
        $product_ids_on_sale = empty( $product_ids_on_sale ) ? array( 0 ) : $product_ids_on_sale;
        $q->set( 'post__in', $product_ids_on_sale );
        $filters_applied = true;
    }

    // ONLY apply if a filter was actually selected
    if ( $filters_applied ) {
        if ( ! empty( $meta_query ) ) {
            $meta_query['relation'] = 'AND';
            $q->set( 'meta_query', $meta_query );
        }
        if ( ! empty( $tax_query ) ) {
            $tax_query['relation'] = 'AND';
            $q->set( 'tax_query', $tax_query );
        }
    }
}