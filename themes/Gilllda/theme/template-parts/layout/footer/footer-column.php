<nav class="max-lg:my-5 flex lg:border-s border-white/10  flex-col max-lg:items-center gap-y-4 text-white/70'">
    <p class="lg:bg-gradient-to-l select-none max-lg:text-center from-white/5 py-0.5 text-xl lg:text-2xl lg:ps-5 w-full lg:border-s-2 max-lg:text-white/85 border-white/75 text-white-75">
        <?= $args['title'] ?? ''; ?>
    </p>
    <ul class="flex w-full max-lg:justify-center max-lg:divide-x-2 divide-white/40 max-lg:list-disc lg:flex-col max-lg:flex-wrap gap-y-2.5">
        <?php
        $args = array(
            'post_type' => $args['post_type'] ?? 'product',
            'post_status' => 'publish',
            'orderby' => 'comment_count',
            'order' => 'ASC',
            'posts_per_page' => 4,
            'post__not_in' => array($args['id'] ?? ''),
            'ignore_sticky_posts' => true
        );
        $loop = new WP_Query($args);
        $posts = $loop->posts;
        if (!empty($posts)) :
            // Load posts loop.
            foreach ($posts as $i => $post):?>
                <li class="flex gap-x-2 px-3 items-center group">
                    <a href="<?php the_permalink(); ?>"
                       class="hover:text-white text-nowrap max-lg:text-sm text-white/70 lg:w-fit lg:ps-4"><?= get_the_title(); ?></a>
                    <?php
                    $args = array(
                        'size' => 12,
                        'class' => 'max-lg:hidden group-hover:-translate-x-1 transition-all'
                    );
                    get_template_part('template-parts/svg/chevron-left', null, $args); ?>
                </li>
            <?php
            endforeach;
        endif;
        ?>
    </ul>
</nav>