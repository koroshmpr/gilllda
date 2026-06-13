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
    <section class="container px-3 lg:px-0 my-5 flex max-xl:flex-col items-start gap-5">
        <div class="xl:w-3/4 flex flex-col">
            <?php
            $args = array(
                    'class' => 'lg:py-0',
                'title' => 'سوالات متداول'
            );
            get_template_part('template-parts/global/faq-list', null, $args);
            get_template_part('template-parts/blog/single/author-box');

            ?>
        </div>
        <?php get_template_part('template-parts/blog/single/rating', null, ['class' => 'xl:sticky top-24 w-full flex-1 border-b-4 border-b-amber-400 rounded-lg flex flex-col border-amber-400/30 ']);?>
    </section>
        <?php
        if (comments_open() || get_comments_number()) {
            comments_template();
        }
        ?>
<?php
get_template_part('template-parts/blog/single/related-posts');
get_template_part('template-parts/blog/single/related-product', null, ['class' => 'lg:hidden p-2']);

get_footer();