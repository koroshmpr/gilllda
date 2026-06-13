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
// 🔴 ۱. قالب کامنت اصلی (تغییر یافته برای دکمه مدال)
function bluebox_html5_comment($comment, $args, $depth) {
    $tag = ('div' === $args['style']) ? 'div' : 'li';
    $is_author = $comment->user_id === get_post_field('post_author', $comment->comment_post_ID);

    $li_classes = 'max-lg:w-[70vw] max-lg:shrink-0';

    // دریافت تعداد پاسخ‌های (فرزندان) این کامنت
    $replies_count = get_comments(array(
        'parent' => $comment->comment_ID,
        'count'  => true,
        'status' => 'approve'
    ));
    ?>

    <<?php echo esc_attr($tag); ?> id="comment-<?php comment_ID(); ?>"  <?php comment_class($li_classes, $comment); ?>>
    <article class="group flex flex-col h-full gap-3 bg-white/5 border border-white/10 hover:border-white/30 transition-colors rounded-lg max-lg:border-white/15 p-4 lg:p-5 lg:pb-2">

        <header class="flex items-center justify-between border-b text-white border-white/15 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gray-200 text-primary flex items-center justify-center overflow-hidden shrink-0">
                    <?php
                    // Try to get avatar, fallback to SVG
                    $avatar = get_avatar($comment, 40, '', '', array('class' => 'w-full h-full object-cover'));
                    if ($avatar) {
                        echo $avatar;
                    } else {
                        get_template_part('template-parts/svg/person', null, ['size' => 20]);
                    }
                    ?>
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <b class="text-sm font-bold text-white"><?= get_comment_author($comment); ?></b>
                        <?php if ($is_author) : ?><span class="bg-white/10 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">نویسنده</span><?php endif; ?>
                    </div>
                    <time datetime="<?= get_comment_time('U'); ?>" class="text-xs text-gray-300 mt-0.5 flex items-center gap-1">
                        <?php get_template_part('template-parts/blog/single/date', null, ['time' => get_comment_time('U')]); ?>
                    </time>
                </div>
            </div>
        </header>

        <div class="text-sm text-gray-400 leading-7 pt-1 flex-grow text-justify">
            <?php comment_text(); ?>
        </div>

        <footer class="flex flex-wrap items-center justify-between gap-3 mt-2 pt-3 border-t border-white/5">
            <div>
                <?php if ($replies_count > 0) : ?>
                    <button type="button" class="view-replies-btn cursor-pointer flex items-center gap-1.5 text-xs font-bold text-blue-300 hover:bg-white/5 hover:text-blue-400 px-3 py-1.5 rounded-lg transition-colors" data-comment-id="<?php comment_ID(); ?>" data-author="<?php echo esc_attr(get_comment_author($comment)); ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                        مشاهده پاسخ‌ها (<?= $replies_count; ?>)
                    </button>
                <?php endif; ?>
            </div>

            <button type="button" class="custom-reply-action-btn flex items-center gap-1 text-xs font-bold text-white/70 hover:text-white hover:bg-white/5 px-3 py-1.5 rounded-lg transition-colors cursor-pointer" data-comment-id="<?php comment_ID(); ?>" data-author="<?php echo esc_attr(get_comment_author($comment)); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                پاسخ دادن
            </button>
        </footer>
    </article>
    </<?php echo esc_attr($tag); ?>>
    <?php
}

// 🔴 ۲. قالب کامنت پاسخ (برای نمایش زیباتر و عمودی داخل Modal)
function bluebox_html5_reply_comment($comment, $args, $depth) {
    $is_author = $comment->user_id === get_post_field('post_author', $comment->comment_post_ID);
    ?>
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-full overflow-hidden shrink-0">
                <?php echo get_avatar($comment, 32, '', '', array('class' => 'w-full h-full object-cover')); ?>
            </div>
            <div>
                <b class="text-xs font-bold text-gray-800"><?= get_comment_author($comment); ?></b>
                <?php if ($is_author) : ?><span class="bg-primary/10 text-primary text-[9px] font-bold px-1.5 rounded-sm mr-1">نویسنده</span><?php endif; ?>
            </div>
            <time datetime="<?= get_comment_time('U'); ?>" class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                <?php get_template_part('template-parts/blog/single/date', null, ['time' => get_comment_time('U')]); ?>
            </time>
        </div>
        <div class="text-xs text-gray-600 leading-6">
            <?php comment_text(); ?>
        </div>
    </div>
    <?php
}

// 🔴 ۳. پردازشگر AJAX برای دریافت پاسخ‌ها
add_action('wp_ajax_nopriv_fetch_comment_replies', 'bluebox_fetch_comment_replies');
add_action('wp_ajax_fetch_comment_replies', 'bluebox_fetch_comment_replies');
function bluebox_fetch_comment_replies() {
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    if (!$parent_id) wp_send_json_error();

    $replies = get_comments(array(
        'parent' => $parent_id,
        'status' => 'approve',
        'order'  => 'ASC'
    ));

    if(empty($replies)) wp_send_json_error('No replies');

    ob_start();
    wp_list_comments(array(
        'style'    => 'div',
        'callback' => 'bluebox_html5_reply_comment',
    ), $replies);
    $html = ob_get_clean();

    wp_send_json_success($html);
}
