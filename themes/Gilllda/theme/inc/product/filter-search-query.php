<?php
function searchfilter($query)
{
    if ($query->is_search && $query->is_main_query() && !is_admin()) {
        $query->set('post_type', array('product'));
    }

    return $query;
}
add_filter('pre_get_posts', 'searchfilter');
