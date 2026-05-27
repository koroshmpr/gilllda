<?php
function track_post_views($post_id)
{
    if (!isset($_COOKIE['post_viewed_' . $post_id])) {
        $current_views = get_post_meta($post_id, 'post_views', true);
        if ($current_views == '') {
            $current_views = 0;
        }
        $new_views = $current_views + 1;
        update_post_meta($post_id, 'post_views', $new_views);
        setcookie('post_viewed_' . $post_id, 'true', time() + 3600 * 24, '/');
    }
}