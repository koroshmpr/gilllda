<?php

$args = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'posts_per_page' => 4,
    'ignore_sticky_posts' => true
);
$loop = new WP_Query($args);
if ($loop->have_posts()) :?>

    <section class="container max-lg:px-3 my-12 overflow-hidden">
        <div class="flex justify-between items-center border-b mb-4 border-black/10">
            <h2 class="pb-2 text-lg lg:text-2xl border-b-2 border-primary/90">مطالب خواندنی</h2>
            <a class="flex gap-1 items-center group" href="/blog" aria-label="go to blog archive page">
                <span class="group-hover:translate-x-1 transition-all">مشاهده همه</span>
                <?php
                $args = array(
                    'size' => '18',
                    'class' => 'rotate-180 group-hover:-translate-x-1 transition-all'
                );
                get_template_part('template-parts/svg/chevron-right', null, $args); ?>
            </a>
        </div>
        <div class="flex max-w-full flex-nowrap overflow-x-scroll md:grid grid-cols-2 lg:grid-cols-4 gap-2 lg:gap-4">
            <?php
            // Load posts loop.
            while ($loop->have_posts()) :
                $loop->the_post();

                $args = array(
                    'class' => 'max-lg:min-w-[250px] block',
                    'eager' => $loop->current_post < 4 // Eager load the first 4 blog posts
                );

                get_template_part('template-parts/blog/archive-card', null, $args);
            endwhile;
            // Reset query
            wp_reset_postdata();
            ?>
        </div>
    </section>
<?php
endif;
//get_template_part('template-parts/blog/latest-blog');
?>
