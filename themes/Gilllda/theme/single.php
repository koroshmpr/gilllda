<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package bluebox
 */

get_header();
track_post_views(get_the_ID());
?>
    <section x-data="{ active: null, toc:false, stickyMenu : false, share : false }"
             class="grid lg:grid-cols-12 px-0 gap-x-5 gap-y-1 items-start container">
        <?php
        $args = array(
            'class' => 'lg:col-span-12 order-0 px-4 bg-primary/5 border border-primary/15 lg:w-fit m-1.5 rounded-lg p-2'
        );
        get_template_part('template-parts/global/breadcrumb', null, $args);
        get_template_part('template-parts/blog/single/post-header');
        get_template_part('template-parts/blog/single/post-content');
        get_template_part('template-parts/blog/single/sidebar-post');
        get_template_part('template-parts/blog/single/sticky-navbar');
        ?>
    </section>
    <section class="container px-3 lg:px-0 my-5 flex flex-col gap-y-5">
        <?php
        $args = array(
            'class' => 'xl:w-3/4',
            'title' => 'سوالات متداول'
        );
        get_template_part('template-parts/global/faq-list', null, $args);
        get_template_part('template-parts/blog/single/author-box');
        get_template_part('template-parts/blog/single/rating', null, ['class' => 'xl:w-3/4 ']);

        if (comments_open() || get_comments_number()) {
            comments_template();
        }
        ?>
    </section>
<?php
get_template_part('template-parts/blog/single/related-posts');
get_template_part('template-parts/blog/single/related-product', null, ['class' => 'lg:hidden p-2']);

get_footer();