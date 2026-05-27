<?php
function save_rating() {
    if(isset($_POST['post_id']) && isset($_POST['rating_value'])) {
        $post_id = $_POST['post_id'];
        $rating_value = $_POST['rating_value'];
        $total_ratings = get_post_meta($post_id, 'total_ratings', true);
        $total_rating_value = get_post_meta($post_id, 'total_rating_value', true);
        if(!$total_ratings) {
            $total_ratings = 0;
        }
        if(!$total_rating_value) {
            $total_rating_value = 0;
        }
        update_post_meta($post_id, 'rating_value', $rating_value);
        update_post_meta($post_id, 'total_ratings', ++$total_ratings);
        update_post_meta($post_id, 'total_rating_value', ($total_rating_value + $rating_value));
    }
    wp_die();
}

add_action('wp_ajax_save_rating', 'save_rating');
add_action('wp_ajax_nopriv_save_rating', 'save_rating');
