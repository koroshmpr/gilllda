<header class="relative lg:col-span-8 xl:col-span-9 order-1">
    <?php if (has_post_thumbnail()) : ?>
        <img
            src="<?php the_post_thumbnail_url('large'); ?>" alt="<?php the_title_attribute(); ?>"
            class="object-cover w-full px-0 lg:rounded-lg transition-all duration-500 aspect-square lg:aspect-video">
    <?php endif; ?>
    <h1 class="text-lg lg:text-2xl z-1 leading-[1.7] -translate-y-1/2 mx-auto bg-white/80 p-3 shadow-sm rounded-lg text-center backdrop-blur-sm w-11/12  text-black  font-bold"><?php the_title(); ?></h1>
</header>