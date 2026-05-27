<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package bluebox
 */

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function bluebox_pingback_header()
{
    if (is_singular() && pings_open()) {
        printf('<link rel="pingback" href="%s">', esc_url(get_bloginfo('pingback_url')));
    }
}

add_action('wp_head', 'bluebox_pingback_header');

/**
 * Changes comment form default fields.
 *
 * @param array $defaults The default comment form arguments.
 *
 * @return array Returns the modified fields.
 */
function bluebox_comment_form_defaults($defaults)
{
    $comment_field = $defaults['comment_field'];

    // Adjust height of comment form.
    $defaults['comment_field'] = preg_replace('/rows="\d+"/', 'rows="5"', $comment_field);

    return $defaults;
}

add_filter('comment_form_defaults', 'bluebox_comment_form_defaults');

/**
 * Filters the default archive titles.
 */
function bluebox_get_the_archive_title()
{
    if (is_category()) {
        $title = __('Category Archives: ', 'bluebox') . '<span>' . single_term_title('', false) . '</span>';
    } elseif (is_tag()) {
        $title = __('Tag Archives: ', 'bluebox') . '<span>' . single_term_title('', false) . '</span>';
    } elseif (is_author()) {
        $title = __('Author Archives: ', 'bluebox') . '<span>' . get_the_author_meta('display_name') . '</span>';
    } elseif (is_year()) {
        $title = __('Yearly Archives: ', 'bluebox') . '<span>' . get_the_date(_x('Y', 'yearly archives date format', 'bluebox')) . '</span>';
    } elseif (is_month()) {
        $title = __('Monthly Archives: ', 'bluebox') . '<span>' . get_the_date(_x('F Y', 'monthly archives date format', 'bluebox')) . '</span>';
    } elseif (is_day()) {
        $title = __('Daily Archives: ', 'bluebox') . '<span>' . get_the_date() . '</span>';
    } elseif (is_post_type_archive()) {
        $cpt = get_post_type_object(get_queried_object()->name);
        $title = sprintf(
        /* translators: %s: Post type singular name */
            esc_html__('%s Archives', 'bluebox'),
            $cpt->labels->singular_name
        );
    } elseif (is_tax()) {
        $tax = get_taxonomy(get_queried_object()->taxonomy);
        $title = sprintf(
        /* translators: %s: Taxonomy singular name */
            esc_html__('%s Archives', 'bluebox'),
            $tax->labels->singular_name
        );
    } else {
        $title = __('Archives:', 'bluebox');
    }
    return $title;
}

add_filter('get_the_archive_title', 'bluebox_get_the_archive_title');

/**
 * Determines whether the post thumbnail can be displayed.
 */
function bluebox_can_show_post_thumbnail()
{
    return apply_filters('bluebox_can_show_post_thumbnail', !post_password_required() && !is_attachment() && has_post_thumbnail());
}

/**
 * Returns the size for avatars used in the theme.
 */
function bluebox_get_avatar_size()
{
    return 60;
}

/**
 * Create the continue reading link
 *
 * @param string $more_string The string shown within the more link.
 */
function bluebox_continue_reading_link($more_string)
{

    if (!is_admin()) {
        $continue_reading = sprintf(
        /* translators: %s: Name of current post. */
            wp_kses(__('Continue reading %s', 'bluebox'), array('span' => array('class' => array()))),
            the_title('<span class="sr-only">"', '"</span>', false)
        );

        $more_string = '<a href="' . esc_url(get_permalink()) . '">' . $continue_reading . '</a>';
    }

    return $more_string;
}

// Filter the excerpt more link.
add_filter('excerpt_more', 'bluebox_continue_reading_link');

// Filter the content more link.
add_filter('the_content_more_link', 'bluebox_continue_reading_link');

/**
 * Outputs a comment in the HTML5 format.
 *
 * This function overrides the default WordPress comment output in HTML5
 * format, adding the required class for Tailwind Typography. Based on the
 * `html5_comment()` function from WordPress core.
 *
 * @param WP_Comment $comment Comment to display.
 * @param array $args An array of arguments.
 * @param int $depth Depth of the current comment.
 */
function bluebox_html5_comment($comment, $args, $depth)
{
    $tag = ('div' === $args['style']) ? 'div' : 'li';

    $commenter = wp_get_current_commenter();
    $show_pending_links = !empty($commenter['comment_author']);

    if ($commenter['comment_author_email']) {
        $moderation_note = __('Your comment is awaiting moderation.', 'bluebox');
    } else {
        $moderation_note = __('Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.', 'bluebox');
    }
    ?>
    <<?php echo esc_attr($tag); ?> id="comment-<?php comment_ID(); ?>" <?php comment_class($comment->has_children ? 'parent' : '', $comment); ?>>
    <article id="div-comment-<?php comment_ID(); ?>"
             class="comment-body bg-gray-50 border border-black/5 rounded-sm my-2 pt-4 pr-4">
        <footer class="comment-meta flex items-center gap-2">
            <div class="comment-author vcard flex gap-3 items-center">
               <span class="p-2 mb-2 bg-primary/90 text-white rounded-full flex justify-center items-center">
             <?php
             $args2 = array(
                 'size' => 18,
             );
             get_template_part('template-parts/svg/person', null, $args2);
             ?>
        </span>
                <?php
                $comment_author = get_comment_author_link($comment);

                if ('0' === $comment->comment_approved && !$show_pending_links) {
                    $comment_author = get_comment_author($comment);
                }

                printf(
                /* translators: %s: Comment author link. */
                    wp_kses_post(__('%s <span class="says hidden">says:</span>', 'bluebox')),
                    sprintf('<b class="fn">%s</b>', wp_kses_post($comment_author))
                );
                ?>
            </div><!-- .comment-author -->

            <div class="comment-metadata text-xs flex gap-2">
                <time datetime="<?= get_comment_time('U'); ?>"
                class="flex items-center gap-2 opacity-75">
                <?php get_template_part('template-parts/blog/single/date',null , ['time' => get_comment_time('U')]); ?>
                </time>
                <?php edit_comment_link(__('Edit', 'bluebox'), ' <span class="edit-link bg-primary px-2 py-1 rounded-md text-white">', '</span>');
                ?>
            </div><!-- .comment-metadata -->

            <?php if ('0' === $comment->comment_approved) : ?>
                <em class="comment-awaiting-moderation"><?php echo esc_html($moderation_note); ?></em>
            <?php endif; ?>
        </footer><!-- .comment-meta -->

        <div <?php bluebox_content_class('comment-content pe-5 pb-3'); ?>>
            <?php comment_text(); ?>
        </div><!-- .comment-content -->

        <?php
        if ('1' === $comment->comment_approved || $show_pending_links) {
            comment_reply_link(
                array_merge(
                    $args,
                    array(
                        'add_below' => 'div-comment',
                        'depth' => $depth,
                        'max_depth' => $args['max_depth'],
                        'before' => '<div class="reply w-fit bg-white px-4 py-1 border border-black/10 hover:bg-icon/5 transition-all ms-auto">',
                        'after' => '</div>',
                    )
                )
            );
        }
        ?>
    </article><!-- .comment-body -->
    <?php
}

function forum_comment($comment, $args, $depth)
{
    var_dump($comment);
    $tag = ('div' === $args['style']) ? 'div' : 'li';

    $commenter = wp_get_current_commenter();
    $show_pending_links = !empty($commenter['comment_author']);

    if ($commenter['comment_author_email']) {
        $moderation_note = __('Your comment is awaiting moderation.', 'bluebox');
    } else {
        $moderation_note = __('Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.', 'bluebox');
    }
    ?>
    <<?php echo esc_attr($tag); ?> id="comment-<?php comment_ID(); ?>" <?php comment_class($comment->has_children ? 'parent' : '', $comment); ?>>
    <article x-intersect:enter.margin.-15%.0px.-70%.0px="active = 'comment-<?php comment_ID(); ?>'"
             id="div-comment-<?php comment_ID(); ?>"
             class="comment-body w-full items-start relative flex border-b mb-3 border-black/20 gap-3">
        <span class="lg:sticky size-12 top-24 p-3 mb-2 rounded-full flex justify-center items-center border border-black/20">
             <?php
             $args2 = array(
                 'size' => 22,
                 'class' => '',
             );
             get_template_part('template-parts/svg/person', null, $args2);
             ?>
        </span>
        <footer class="comment-meta flex-1 lg:justify-center ps-5 flex flex-col items-center gap-3">
            <div class="w-full flex justify-between items-center  px-5">
                <div class="comment-author vcard flex">
                    <?php

                    if (0 !== $args['avatar_size']) {
                        echo get_avatar($comment, $args['avatar_size']);
                    }
                    ?>
                    <?php
                    //                    $comment_author = get_comment_author_link($comment);
                    $comment_author = get_comment_author($comment);
                    if ('0' === $comment->comment_approved && !$show_pending_links) {
                        $comment_author = get_comment_author($comment);
                    }

                    printf(
                    /* translators: %s: Comment author link. */
                        wp_kses_post(__('%s <span class="says hidden">says:</span>', 'bluebox')),
                        sprintf('<b class="fn">%s</b>', wp_kses_post($comment_author))
                    );
                    ?>
                </div><!-- .comment-author -->
                <div class="comment-metadata text-xs">

                    <time datetime="<?= get_comment_time('U'); ?>"
                          class="flex items-center gap-2 opacity-75">
                        <?php get_template_part('template-parts/forum/single/date', null, ['time' => get_comment_time('U')]); ?>
                    </time>
                </div><!-- .comment-metadata -->
            </div>
            <div class="w-full flex flex-col flex-1">
                <div <?php bluebox_content_class('comment-content flex-1 p-5 flex flex-col justify-center'); ?>>
                    <?php comment_text(); ?>
                </div><!-- .comment-content -->

                <?php
                if ('1' === $comment->comment_approved || $show_pending_links) {
                    comment_reply_link(
                        array_merge(
                            $args,
                            array(
                                'add_below' => 'div-comment',
                                'depth' => $depth,
                                'max_depth' => $args['max_depth'],
                                'before' => '<div class="reply w-fit bg-white px-4 py-1 border border-black/10 hover:bg-gray-800 hover:text-white transition-all ms-auto">',
                                'after' => '</div>',
                            )
                        )
                    );
                }
                ?>
            </div>
        </footer><!-- .comment-meta -->
    </article><!-- .comment-body -->
    <?php
}
