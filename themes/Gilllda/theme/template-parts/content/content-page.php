<?php
/**
 * Template part for displaying pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bluebox
 */
global $woo_active;
?>

<?php if ($woo_active && !is_shop()) : ?>
    <header class="entry-header border-b mt-2 mb-4 max-lg:justify-center flex lg:mb-5 border-black/10 overflow-hidden">
        <?php
        if (!is_front_page()) { ?>
            <h1 :class="intro ? 'max-lg:!translate-y-0 !translate-x-0' : '' "
                class="border-b-3 pb-2 text-3xl max-lg:translate-y-full lg:translate-x-full transition-all w-fit duration-300 border-flower"><?php the_title(); ?></h1>
        <?php } else {
            the_title('<h2 class="entry-title">', '</h2>');
        }
        ?>
    </header><!-- .entry-header -->

<?php
endif;
// Check if it's the shop AND the first page
if ($woo_active && is_shop()) :
    get_template_part('template-parts/shop/shop-page-components');
endif;

the_content();

if ($woo_active && is_shop()) :
    get_template_part('template-parts/shop/shop-page-components-loop-end');
endif;
?>

<?php if (get_edit_post_link()) : ?>
    <footer class="entry-footer my-3 [&>a]:py-3  [&>a]:flex-1 [&>a]:rounded-xl text-center [&>a]:bg-icon/50 [&>a]:hover:bg-icon [&>a]:transition-all flex justify-center">
        <?php
        edit_post_link(
            sprintf(
                wp_kses(
                /* translators: %s: Name of current post. Only visible to screen readers. */
                    __('ویرایش <span class="sr-only">%s</span>', 'bluebox'),
                    array(
                        'span' => array(
                            'class' => array(),
                        ),
                    )
                ),
                get_the_title()
            )
        );
        ?>
    </footer><!-- .entry-footer -->
<?php
endif; ?>

