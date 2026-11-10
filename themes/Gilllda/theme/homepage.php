<?php /* Template Name: Home */
get_header();
get_template_part('template-parts/homepage/hero');
get_template_part('template-parts/homepage/usp');
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
get_template_part('template-parts/homepage/magazine-teaser');
echo '</div>';

$categories = get_field('product_category');
if ($categories) :
    foreach ($categories as $category) :
        $category = is_object($category) ? $category : get_term((int) $category, 'product_cat');
        // A one- or two-product category looks empty in a slider; the shop grid covers it.
        if (!$category || is_wp_error($category) || (int) $category->count < 3) {
            continue;
        }
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
