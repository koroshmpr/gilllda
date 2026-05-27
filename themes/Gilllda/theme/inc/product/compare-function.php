<?php
add_action('wp_ajax_get_compare_products', 'get_compare_products');
add_action('wp_ajax_nopriv_get_compare_products', 'get_compare_products');

function get_compare_products() {

	if (empty($_POST['ids'])) {
		wp_send_json([]);
	}

	// Ensure array
	$ids = $_POST['ids'];

	if (!is_array($ids)) {
		$ids = explode(',', $ids);
	}

	$ids = array_map('intval', $ids);

	$query = new WP_Query([
		'post_type'      => 'product',
		'post__in'       => $ids,
		'posts_per_page' => -1,
		'orderby'        => 'post__in',
	]);

	$products = [];

	while ($query->have_posts()) {
		$query->the_post();

		$product = wc_get_product(get_the_ID());

		$products[] = [
			'id'     => get_the_ID(),
			'name'   => get_the_title(),
			'image'  => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
			'price' => wp_kses_post($product->get_price_html()),
			'url'    => get_permalink(),
			'weight' => $product ? $product->get_weight() : '',
			'dims'   => $product ? wc_format_dimensions($product->get_dimensions(false)) : '',
		];
	}

	wp_reset_postdata();
	wp_send_json($products);
}
