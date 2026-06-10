<?php
function save_rating() {
    if (isset($_POST['post_id']) && isset($_POST['rating_value'])) {
        $post_id = intval($_POST['post_id']);
        $rating_value = floatval($_POST['rating_value']);

        // --- NEW: Check if user already voted using a cookie ---
        $cookie_name = 'voted_on_post_' . $post_id;
        if (isset($_COOKIE[$cookie_name])) {
            wp_send_json_error('شما قبلا به این مطلب امتیاز داده‌اید.'); // "You already voted"
            wp_die();
        }

        if ($rating_value < 1 || $rating_value > 5) {
            wp_send_json_error('Invalid rating');
            wp_die();
        }

        $total_ratings = intval(get_post_meta($post_id, 'total_ratings', true));
        $total_rating_value = floatval(get_post_meta($post_id, 'total_rating_value', true));

        $new_total_ratings = $total_ratings + 1;
        $new_total_rating_value = $total_rating_value + $rating_value;
        $new_average_rating = round(($new_total_rating_value / $new_total_ratings), 1);

        update_post_meta($post_id, 'rating_value', $new_average_rating);
        update_post_meta($post_id, 'total_ratings', $new_total_ratings);
        update_post_meta($post_id, 'total_rating_value', $new_total_rating_value);

        // --- NEW: Set a cookie for 30 days so they can't vote again ---
        setcookie($cookie_name, 'true', time() + (30 * 24 * 60 * 60), "/");

        wp_send_json_success();
    } else {
        wp_send_json_error('Missing data');
    }
    wp_die();
}

add_action('wp_ajax_save_rating', 'save_rating');
add_action('wp_ajax_nopriv_save_rating', 'save_rating');

/**
 * Inject Custom Ratings into Rank Math Schema
 */
add_filter( 'rank_math/json_ld', function( $data, $jsonld ) {
    $post_id = get_the_ID();
    if ( ! $post_id ) {
        return $data;
    }

    // Get your custom rating data
    $total = get_post_meta($post_id, 'total_ratings', true);
    $rating_value = get_post_meta($post_id, 'rating_value', true);

    // Only proceed if ratings actually exist
    if ( $total > 0 && $rating_value > 0 ) {

        // Loop through Rank Math's generated schema data
        foreach ( $data as $key => $schema ) {

            // Check if a @type is set
            if ( isset( $schema['@type'] ) ) {

                // RankMath sometimes outputs @type as a string, sometimes as an array
                $type = is_array( $schema['@type'] ) ? $schema['@type'][0] : $schema['@type'];

                // If it's a Product, Article, or Portfolio item, inject the ratings!
                $valid_types = ['Product', 'Article', 'NewsArticle', 'BlogPosting', 'CreativeWork'];

                if ( in_array( $type, $valid_types ) ) {
                    $data[$key]['aggregateRating'] = [
                        '@type'       => 'AggregateRating',
                        'ratingValue' => $rating_value,
                        'reviewCount' => $total,
                        'bestRating'  => '5',
                        'worstRating' => '1',
                    ];
                }
            }
        }
    }

    return $data;
}, 99, 2 );