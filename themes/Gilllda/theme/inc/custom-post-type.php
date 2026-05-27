<?php
$postTypes = ['portfolio', 'forum'];
function service_post_types()
{
    // services post-type
    register_post_type('services', array(
        'supports' => array('title', 'editor', 'comments', 'excerpt', 'custom-fields', 'thumbnail'),
        'rewrite' => array('slug' => 'services'),
        'has_archive' => true,
        'public' => true,
        'labels' => array(
            'name' => 'خدمات ما',
            'add_new' => 'افزودن خدمات',
            'add_new_item' => 'افزودن خدمات جدید',
            'edit_item' => 'ویرایش خدمات',
            'all_items' => 'همه ی خدمات',
            'singular_name' => 'خدمات'
        ),
        'menu_icon' => 'dashicons-list-view'
    ));
    register_taxonomy(
        'services_categories',
        'services',             // post type name
        array(
            'hierarchical' => true,
            'label' => 'دسته بندی خدمات', // display name
            'query_var' => true,
        )
    );
    $labels = array(
        'name' => _x('Tags', 'taxonomy general name'),
        'singular_name' => _x('Tag', 'taxonomy singular name'),
        'search_items' => __('Search Tags'),
        'popular_items' => __('Popular Tags'),
        'all_items' => __('All Tags'),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __('Edit Tag'),
        'update_item' => __('Update Tag'),
        'add_new_item' => __('Add New Tag'),
        'new_item_name' => __('New Tag Name'),
        'separate_items_with_commas' => __('Separate tags with commas'),
        'add_or_remove_items' => __('Add or remove tags'),
        'choose_from_most_used' => __('Choose from the most used tags'),
        'menu_name' => __('برچسب خدمات'),
    );

    register_taxonomy('service_tag', 'services', array(
        'hierarchical' => false,
        'labels' => $labels,
        'show_ui' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'rewrite' => array('slug' => 'tag-services'),
    ));
    // gallery post-type
}

//add_action('init', 'service_post_types');
function portfolio_post_types()
{
    // Portfolio post-type
    register_post_type('portfolio', array(
        'supports' => array('title', 'editor', 'comments', 'excerpt', 'custom-fields', 'thumbnail'),
        'rewrite' => array('slug' => 'portfolio'),
        'has_archive' => 'portfolios',
        'public' => true,
        'labels' => array(
            'name' => 'نمونه کارها',
            'add_new' => 'افزودن نمونه کار',
            'add_new_item' => 'افزودن نمونه کار جدید',
            'edit_item' => 'ویرایش نمونه کار',
            'all_items' => 'همه نمونه کارها',
            'singular_name' => 'نمونه کار'
        ),
        'menu_icon' => 'dashicons-portfolio'
    ));

    // Portfolio categories taxonomy
    register_taxonomy('portfolio-categories', 'portfolio', array(
            'hierarchical' => true,
            'label' => 'دسته‌بندی نمونه کارها',
            'query_var' => true,
            'rewrite' => array('slug' => 'portfolio', 'with_front' => false),
        )
    );

    $labels = array(
        'name' => _x('برچسب‌ها', 'taxonomy general name'),
        'singular_name' => _x('برچسب', 'taxonomy singular name'),
        'search_items' => __('جستجوی برچسب‌ها'),
        'popular_items' => __('برچسب‌های محبوب'),
        'all_items' => __('همه برچسب‌ها'),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __('ویرایش برچسب'),
        'update_item' => __('بروزرسانی برچسب'),
        'add_new_item' => __('افزودن برچسب جدید'),
        'new_item_name' => __('نام برچسب جدید'),
        'separate_items_with_commas' => __('برچسب‌ها را با کاما جدا کنید'),
        'add_or_remove_items' => __('افزودن یا حذف برچسب‌ها'),
        'choose_from_most_used' => __('انتخاب از بین پرکاربردترین برچسب‌ها'),
        'menu_name' => __('برچسب‌های نمونه کارها'),
    );

    register_taxonomy('portfolio_tag', 'portfolio', array(
        'hierarchical' => false,
        'labels' => $labels,
        'show_ui' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'rewrite' => array('slug' => 'portfolio_tag'),
    ));
}

// Hook into WordPress
//add_action('init', 'portfolio_post_types');

// Hook into WordPress
function customers_post_types()
{
    // Customers post-type
    register_post_type('customers', array(
        'supports' => array('title', 'editor', 'comments', 'excerpt', 'custom-fields', 'thumbnail'),
        'rewrite' => array('slug' => 'customers'),
        'has_archive' => true,
        'public' => true,
        'labels' => array(
            'name' => 'مشتریان',
            'add_new' => 'افزودن مشتری',
            'add_new_item' => 'افزودن مشتری جدید',
            'edit_item' => 'ویرایش مشتری',
            'all_items' => 'همه مشتریان',
            'singular_name' => 'مشتری'
        ),
        'menu_icon' => 'dashicons-businessman'
    ));

    // Customers categories taxonomy
    register_taxonomy(
        'customers_categories',
        'customers',
        array(
            'hierarchical' => true,
            'label' => 'دسته‌بندی مشتریان',
            'query_var' => true,
        )
    );

    $labels = array(
        'name' => _x('برچسب‌ها', 'taxonomy general name'),
        'singular_name' => _x('برچسب', 'taxonomy singular name'),
        'search_items' => __('جستجوی برچسب‌ها'),
        'popular_items' => __('برچسب‌های محبوب'),
        'all_items' => __('همه برچسب‌ها'),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __('ویرایش برچسب'),
        'update_item' => __('بروزرسانی برچسب'),
        'add_new_item' => __('افزودن برچسب جدید'),
        'new_item_name' => __('نام برچسب جدید'),
        'separate_items_with_commas' => __('برچسب‌ها را با کاما جدا کنید'),
        'add_or_remove_items' => __('افزودن یا حذف برچسب‌ها'),
        'choose_from_most_used' => __('انتخاب از بین پرکاربردترین برچسب‌ها'),
        'menu_name' => __('برچسب‌های مشتریان'),
    );

    register_taxonomy('customers_tag', 'customers', array(
        'hierarchical' => false,
        'labels' => $labels,
        'show_ui' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'rewrite' => array('slug' => 'tag-customers'),
    ));
}

//add_action('init', 'customers_post_types');


function custom_post_type_args($args, $post_type)
{
    global $postTypes;
    foreach ($postTypes as $Type) :
        // Change 'project' to the slug of your custom post type
        if ($Type === $post_type) {
            // Set the with_front parameter to false
            $args['rewrite']['with_front'] = false;
        }
    endforeach;
    return $args;
}

add_filter('register_post_type_args', 'custom_post_type_args', 10, 2);