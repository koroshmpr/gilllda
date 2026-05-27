<header
	class="bg-gradient-to-t from-50% from-foreground/80 to-transparent relative lg:mb-8 lg:pb-16">
	<?php if (has_post_thumbnail()) : ?>
		<div :class="scrollingDown ? 'lg:translate-y-6 lg:scale-95' : (scrollingUp ? '' : '')"
			 class="relative container transition-all max-lg:h-[70vh]  px-0 duration-500">
			<img
				src="<?php the_post_thumbnail_url('large'); ?>" alt="<?php the_title_attribute(); ?>"
				class="object-cover w-full px-0 transition-all duration-500 h-full lg:max-h-[80vh] lg:aspect-video">
			<div class="bg-gradient-to-t container from-black/80 lg:from-black to-black/20 absolute inset-0"></div>
		</div>
	<?php endif; ?>
	<div
		:class="scrollingDown ? 'xl:-translate-y-12 opacity-65 scale-95 !lg:text-4xl' : (scrollingUp ? '' : '')"
		class="absolute max-lg:bottom-8 lg:top-1/2 transition-all duration-500 xl:bottom-32 z-[1] gap-y-4 xl:translate-y-1/2 inset-x-0 lg:max-w-3/5 max-lg:px-5 flex flex-col mx-auto">
		<?php
		if (is_singular('post')):
			$categories = get_the_terms(get_the_ID(), 'category');
		endif;
		if ($categories && !is_wp_error($categories)) : ?>
			<p class="text-sm flex gap-x-3">
				<?php foreach ($categories as $category) : ?>
					<a href="<?= get_term_link($category); ?>"
					   class="text-white/70 no-underline hover:text-white"><?= $category->name; ?></a>
				<?php endforeach; ?>
			</p>
		<?php endif; ?>
		<h1 class="text-2xl <?= is_singular('portfolio') ? 'lg:text-4xl' : 'lg:text-5xl xl:text-6xl'; ?> mb-0 transition-all duration-500 leading-[1.3] font-normal lg:text-center text-white"><?php the_title(); ?></h1>
	</div>
</header>
