<?php

$args2 = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'meta_key' => 'post_views',
    'orderby' => 'meta_value_num',

);
$posts = get_posts($args2);
if (!empty($posts)) :?>
    <section class="grid container border-b border-primary/50 relative max-lg:px-2 grid-cols-2 lg:grid-cols-4 mt-5 mb-2 pb-5 gap-3">
        <h2 class="lg:col-span-4 col-span-2 text-center font-bold text-3xl">پر بازدید‌ها</h2>
        <?php foreach ($posts as $i => $post) : ?>
            <a href="<?php the_permalink(); ?>" class="overflow-hidden  group relative rounded-lg col-span-2 <?=  $i === 0 ? 'lg:col-span-2 lg:row-span-2' : 'lg:col-span-1' ?>">
                <?php
                $post_id = get_the_ID();
                $view = get_post_meta($post_id, 'post_views', true);
                $args = array(
                    'size' => 20
                );
                if ($view): ?>
                    <span class="absolute top-0 start-2 p-2 bg-primary text-white z-1 flex gap-2 rounded-b-lg items-center text-xs">
                        <?php get_template_part('template-parts/svg/eye', null, $args); ?>
                        <span><?= $view; ?></span>
                    </span>
                <?php endif; ?>
                <img class="w-full group-hover:scale-110 transition-all duration-300 <?=  $i > 0 ? 'aspect-2/1 lg:aspect-[4/3]' : 'aspect-[4/3]' ?> object-cover"  src="<?= the_post_thumbnail_url(); ?>"
                      alt="image of the <?= get_the_title(); ?> post">
                <p class="absolute bottom-0 group-hover:bg-black/50 inset-x-0 transition-all duration-300 line-clamp-1 bg-black/20 backdrop-blur-[2px] leading-[1.7] text-center text-white p-3"><?= wp_trim_words(get_the_title(), 9); ?></p>
            </a>
        <?php endforeach; ?>
    </section>
<?php endif;
wp_reset_postdata();;
?>

