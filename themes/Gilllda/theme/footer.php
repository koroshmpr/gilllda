</main>
<?php
get_template_part('template-parts/layout/footer-content');
if (is_front_page()):
    get_template_part('template-parts/layout/sticky-social');
endif;
get_template_part('template-parts/global/backToTop');
if (!is_search()):
    $args = array(
        'class' => 'lg:hidden fixed bottom-34',
        'attr' => ':class="scrolled ? "right-4" : "-right-full"'
    );
    get_template_part('template-parts/layout/header/search-button', null, $args);
endif;

//Mobile Menu Modal
get_template_part('template-parts/layout/header/menu-modal');
//search modal
get_template_part('template-parts/layout/header/search-modal');

//product category modal
get_template_part('template-parts/product/category-modal');
$catMode = get_field('catalogue_mode', 'option');
if ($catMode) :
    get_template_part('template-parts/shop/shop-contact-modal');
endif;
get_template_part('template-parts/global/popup');
wp_footer();
?>
</body>
</html>
