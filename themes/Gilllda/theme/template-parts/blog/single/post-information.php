<?php
$color = 'black';
$borderColor = $color == 'black' ? 'border-black/5' : 'border-white/5';
$bgColor = $color == 'black' ? 'bg-black/5' : 'bg-white/5';
$textColor = $color == 'black' ? 'text-black/80' : 'text-white/80';
$svgColor = $color == 'black' ? 'text-black/30' : 'text-white/30';

// svg
$boxClass = 'flex items-center lg:gap-x-2 gap-x-3';
$svgClass = 'rounded-lg ' . $bgColor . ' border ' . $borderColor . ' p-1.5 ' . $svgColor . ' box-content';
$svgSize = '17';
$args = array(
    'class' => $svgClass,
    'size' => $svgSize,
);

//user information
$post_id = get_the_ID();
$author_id = get_post_field('post_author', $post_id);
$display_name = get_the_author_meta('display_name', $author_id);
$author_url = get_author_posts_url($author_id);
$commentCount = get_comments_number();
$rating_value = get_post_meta($post_id, 'rating_value', true);

// Get total ratings and average rating
$total_ratings = get_post_meta($post_id, 'total_ratings', true);
$total_rating_value = get_post_meta($post_id, 'total_rating_value', true);
$average_rating = 0;

if (is_numeric($total_ratings) && is_numeric($total_rating_value) && $total_ratings > 0) {
    $average_rating = round($total_rating_value / $total_ratings, 1);
}
// Display the stars and average rating


// other data
$categories = get_the_terms(get_the_ID(), 'category');

?>

    <div class="grid grid-cols-2 items-center gap-2 justify-center text-sm">
        <?php
        if (get_the_modified_time('U') > get_the_time('U')) : ?>
            <time datetime="<?= shamsi_date('d F, Y', get_the_modified_time('U')); ?>" itemprop="modified"
                  class="<?= $boxClass; ?> col-span-2 pb-2 border-b border-black/10 flex justify-start gap-x-2">
                <span class=" rounded-lg p-1.5 bg-green-100 border border-green-200 text-green-600"><?php get_template_part('template-parts/svg/check', null, $args); ?></span>
                <span>آخرین بروز رسانی:</span>
                <span><?= shamsi_date('d F, Y', get_the_modified_time('U')); ?></span>
            </time>
        <?php endif; ?>
        <div aria-label="author information" class="<?= $boxClass; ?>">
            <?php get_template_part('template-parts/svg/person-fill', null, $args); ?>
            <a href="<?= esc_url($author_url); ?>"
               rel="author"
               aria-label="View all posts by <?= esc_attr($display_name); ?>"
               title="View all posts by <?= esc_attr($display_name); ?>"
               class="<?= $textColor; ?> hover:<?= $color == 'black' ? 'text-black/90' : 'text-white/90'; ?> transition">
                <?= esc_html($display_name); ?>
            </a>
        </div>
        <time datetime="<?= shamsi_date('d F, Y', get_the_time('U')); ?>" itemprop="created"
              class="<?= $boxClass; ?>">
            <?php get_template_part('template-parts/svg/calendar-check', null, $args); ?>
            <?= shamsi_date('d F, Y', get_the_time('U')); ?>
        </time>
        <span aria-label="reading time" class="<?= $boxClass; ?>">
		  <?php get_template_part('template-parts/svg/stopwatch-fill', null, $args); ?>
		<span><?= do_shortcode('[reading_time]') ?></span>
		<span>دقیقه</span>
	</span>
        <div aria-label="category list" class="<?= $boxClass; ?>">
            <?php get_template_part('template-parts/svg/tag-fill', null, $args);
            if ($categories && !is_wp_error($categories)) : ?>
                <p class="text-sm items-center !my-0 flex overflow-x-scroll flex-1 gap-x-3">
                    <?php foreach ($categories as $index => $category) : ?>
                        <a href="<?= get_term_link($category); ?>"
                           class="<?= $textColor; ?> text-sm text-nowrap lg:text-xs no-underline hover:underline"><?= $category->name; ?></a>
                    <?php
                    endforeach; ?>
                </p>
            <?php
            endif; ?>
        </div>
        <div class="col-span-2 border border-black/10 rounded-lg mt-2 overflow-hidden grid grid-cols-3 divide-x divide-black/10 items-center">
            <?php $class = 'flex items-center justify-center gap-2 py-3 hover:bg-gray-50 bg-white';
            $args = array(
                'class' => 'text-gray-400',
                'size' => 20,
            );
            ?>
            <button @click.prevent="document.getElementById('comments').scrollIntoView({ behavior: 'smooth' })"
                    aria-label="comment count and scroll to comment section" class="<?= $class; ?> cursor-pointer">
                <?php get_template_part('template-parts/svg/message', null, $args); ?>
                <span><?= $commentCount; ?></span>
            </button>
            <?php $view = get_post_meta($post_id, 'post_views', true); ?>
            <div class="<?= $class; ?> text-xs">
                <?php get_template_part('template-parts/svg/eye', null, $args); ?>
                <span><?= $view; ?></span>
            </div>
            <button @click.prevent="document.getElementById('rating').scrollIntoView({ behavior: 'smooth' })"
                    aria-label="rating average and scroll to rating section" class="<?= $class; ?> cursor-pointer">
                <?php
                $args = array(
                    'class' => 'text-amber-300 mb-1',
                    'size' => 18,
                );
                get_template_part('template-parts/svg/star-fill', null, $args); ?>
                <div>
                    <span class="text-sm"><?= $average_rating; ?></span>
                    <span class="text-[10px] ms-0.5"> از ۵ </span>
                </div>
            </button>
        </div>
    </div>
<?php
//get_template_part('template-parts/blog/post-information');
?>