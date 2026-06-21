<?php
/**
 * Template part for displaying the footer content
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bluebox
 */

$id = get_the_id() ?? '';
global $woo_active;
?>

<footer id="footer"
        x-intersect:enter="atBottom = true"
        x-intersect:leave="atBottom = false"
        class="<?= $woo_active && (is_singular() || is_shop() || is_product_category()) ? 'max-lg:pb-36' : 'max-lg:pb-24'; ?> max-lg:pt-12 bg-primary text-white">
    <div class="grid md:grid-cols-3 xl:grid-cols-4 items-start lg:justify-between gap-3 lg:gap-6 container max-lg:px-3 lg:py-24">
        <?php
        get_template_part('template-parts/layout/footer/logo-slogan');

        //        post list
        $args = array(
            'post_type' => 'post',
            'title' => 'مقالات پر بازدید',
            'orderby' => 'comment_count',
            'id' => $id
        );
        get_template_part('template-parts/layout/footer/footer-column', null, $args);
        if ($woo_active) :

            //        product list
            $args = array(
                'post_type' => 'product',
                'title' => 'پرفروش‌ها',
                'orderby' => 'comment_count',
                'id' => $id
            );
            get_template_part('template-parts/layout/footer/footer-column', null, $args);

        endif;
        //        contact list
        get_template_part('template-parts/layout/footer/contact-list');
        ?>
    </div>
    <?php get_template_part('template-parts/layout/footer/copyright-social'); ?>
</footer>
