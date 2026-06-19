<?php
// Hook for logged-in users
add_action('wp_ajax_custom_ajax_search', 'handle_custom_ajax_search');
// Hook for guests
add_action('wp_ajax_nopriv_custom_ajax_search', 'handle_custom_ajax_search');

function handle_custom_ajax_search()
{
    // Sanitize the search keyword
    $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';

    if (empty($keyword)) {
        wp_send_json_success('');
    }

    // Set up the WP_Query (Adjust post_type if you are not using WooCommerce)
    $args = array(
        's' => $keyword,
        'post_type' => 'product', // Assumes you are searching for products
        'post_status' => 'publish',
        'posts_per_page' => 5,         // Limit results in the dropdown
    );

    $search_query = new WP_Query($args);

    // We use Output Buffering to capture the HTML from your template part
    ob_start();

    if ($search_query->have_posts()) {
        while ($search_query->have_posts()) {
            $search_query->the_post();

            $args = [
                'isArchive' => true,
                'eager' => $search_query->current_post < 4
            ];

            get_template_part('template-parts/product/card/product-card', null, $args);
        }
        wp_reset_postdata(); // Always a good practice to include this after a custom query loop
    } else {
        echo '<p class="text-center text-sm text-gray-500 py-4">نتیجه‌ای یافت نشد.</p>';
    }

    wp_reset_postdata();

    // Get the buffered HTML and send it back to JavaScript
    $response_html = ob_get_clean();
    wp_send_json_success($response_html);
}