<?php /* Template Name: Home */
get_header();
get_template_part('template-parts/homepage/hero');
get_template_part('template-parts/homepage/banners');
$args = array(
    'class' => 'px-0 container',
    'mobilePerPage' => '1.7',
    'tabletPerPage' => '2.3',
    'lapTopPerPage' => '3.3',
    'perPage' => '4.5',
);
get_template_part('template-parts/product/colored-product-slider', null, $args);

$args = array(
    'post_type' => 'product',
    'taxonomy' => 'product_cat',
);
get_template_part('template-parts/product/category-list', null, $args);


echo '<div class="[content-visibility:auto] [contain-intrinsic-size:auto_600px]">';
get_template_part('template-parts/homepage/aboutus');
echo '</div>';

echo '<div class="[content-visibility:auto] [contain-intrinsic-size:auto_400px]">';
get_template_part('template-parts/homepage/portfolios-slider');
echo '</div>';

echo '<div class="[content-visibility:auto] [contain-intrinsic-size:auto_500px]">';
get_template_part('template-parts/blog/latest-blog');
echo '</div>';

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
        echo '<div class="[content-visibility:auto] [contain-intrinsic-size:auto_400px]">';
        get_template_part('template-parts/product/category-product-slider', null, $args);
        echo '</div>';
    endforeach;
endif;

get_footer();
