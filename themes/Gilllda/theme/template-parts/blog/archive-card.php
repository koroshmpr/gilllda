<a href="<?php the_permalink(); ?>" class="relative group overflow-hidden <?= $args['class'] ?? '' ?>">
    <div class="text-xs flex z-1 flex-col text-center text-white absolute left-2 p-2  rounded-b-sm pt-3 pb-5 bg-primary/75 group-hover:bg-primary/90 transition-all duration-500 backdrop-blur-sm lg:text-sm">
        <span class="text-lg leading-4"><?= shamsi_date('d', get_the_time('U')); ?></span>
        <span class="text-[10px]"><?= shamsi_date('F', get_the_time('U')); ?></span>
        <span><?= shamsi_date('Y', get_the_time('U')); ?></span>
    </div>
    <div class="absolute bottom-0 z-1 text-white inset-x-0 bg-gradient-to-t lg:translate-y-7/12 group-hover:translate-y-0 duration-500 from-black via-black/70 to-black/20 group-hover:backdrop-blur-sm transition-all flex flex-col justify-between gap-y-2 lg:gap-y-3 p-3 lg:pb-6">
        <div class="flex justify-between gap-1" title="<?= get_the_title() ?? ''; ?>">
            <span class="lg:text-base text-sm line-clamp-1 transition-all group-hover:line-clamp-2 font-bold"><?php the_title(); ?></span>
            <div class="flex gap-x-3 items-center">
                <?php
                $post_id = get_the_ID();
                $view = get_post_meta($post_id, 'post_views', true);
                $args = array(
                        'size' => 17
                );
                if ($view): ?>
                    <span class="flex gap-x-0.5 text-xs">
                        <?php get_template_part('template-parts/svg/eye', null, $args); ?>
                        <span><?= $view; ?></span>
                    </span>
                <?php endif; ?>
                <span class="flex gap-x-1 text-xs">
                    <?php get_template_part('template-parts/svg/message', null, $args); ?>
                    <?= get_comments_number(); ?>
                </span>
            </div>
        </div>
        <p class="text-white/80 text-[11px] leading-[1.7] lg:text-xs text-justify line-clamp-2 transition-all"><?= wp_trim_words(get_the_content(), 25); ?></p>
    </div>
    <img class="w-full object-cover aspect-3/4 group-hover:scale-110 transition-all duration-500" height="250"
         src="<?= the_post_thumbnail_url(); ?>"
         alt="image of the <?= get_the_title(); ?> post">
</a>
<?php
//$args = array(
//        'class' => '',
//);
//get_template_part('template-parts/blog/archive-card',null,$args);
?>
