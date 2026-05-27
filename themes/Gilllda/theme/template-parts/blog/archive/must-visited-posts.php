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
    <section class="grid container relative max-lg:px-2 grid-cols-2 lg:grid-cols-4 my-5 gap-3">
        <h2 class="lg:col-span-4 col-span-2 text-center font-bold text-3xl">پر بازدید‌ها</h2>
        <?php foreach ($posts as $i => $post) : ?>
            <a href="<?php the_permalink(); ?>" class="overflow-hidden group relative rounded-lg <?=  $i === 0 ? 'col-span-2 row-span-2' : ' col-span-1' ?>">
                <img class="w-full group-hover:scale-110 transition-all duration-300 aspect-[4/3] object-cover"  src="<?= the_post_thumbnail_url(); ?>"
                      alt="image of the <?= get_the_title(); ?> post">
                <p class="absolute bottom-0 group-hover:bg-black/50 inset-x-0 transition-all duration-300 bg-black/20 backdrop-blur-[2px] text-center text-white p-3"><?php the_title() ?></p>
            </a>
        <?php endforeach; ?>
    </section>
<?php endif;
wp_reset_postdata();;
?>

