<?php /* Template Name: Home */
get_header();
get_template_part('template-parts/homepage/hero');
get_template_part('template-parts/homepage/banners');
$productCategoryList = get_field('product_category_list');
get_template_part('template-parts/product/category-list', null, ['productCategoryList' => $productCategoryList]);

$args = array(
    'class' => 'px-0 container',
    'mobilePerPage' => '1.7',
    'tabletPerPage' => '2.3',
    'lapTopPerPage' => '3.3',
    'perPage' => '4.5',
);
get_template_part('template-parts/product/colored-product-slider', null, $args);
get_template_part('template-parts/homepage/aboutus');
get_template_part('template-parts/homepage/portfolios-slider');
get_template_part('template-parts/blog/latest-blog');

$categories = get_field('product_category');
if ($categories) :
    foreach ($categories as $category) :
        $args = array(
            'category' => $category,
            'mobilePerPage' => '1.7',
            'tabletPerPage' => '2.3',
            'lapTopPerPage' => '3.3',
            'perPage' => '4.2',
        );
        get_template_part('template-parts/product/category-product-slider', null, $args);
    endforeach;
endif;

get_footer();
