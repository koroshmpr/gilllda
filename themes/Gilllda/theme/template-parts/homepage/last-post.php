<?php
$lastWork = get_field('last_work');
$type = $lastWork['type'];

$args = array();

if ($type == 'auto') {
	$args = array(
		'post_type' => 'portfolio',
		'posts_per_page' => 1,
		'orderby' => 'date',
		'order' => 'DESC',
	);
} elseif ($type == 'custom' && !empty($lastWork['custom'])) {
	$args = array(
		'post_type' => 'portfolio',
		'p' => $lastWork['custom']->ID, // Ensure it's an object
	);
}

$query = !empty($args) ? new WP_Query($args) : null;
?>

<section class="container my-10 lg:my-32">
	<div class="relative flex justify-center lg:mb-24 mb-16">
		<h3 class="lg:text-4xl text-lg text-center font-bold text-black"><?= $lastWork['title'] ?? ''; ?></h3>
		<span
			class="absolute inset-0 text-center -translate-y-full text-black/5 lg:text-8xl uppercase text-3xl"><?= $lastWork['subtitle'] ?? ''; ?></span>
	</div>

	<?php if ($query && $query->have_posts()) : ?>
		<?php while ($query->have_posts()) : $query->the_post(); ?>
			<article class="flex gap-8 max-lg:flex-wrap justify-center items-center text-black">
				<div class="lg:basis-1/3 flex flex-col gap-y-2">
					<span><?= shamsi_date('d F, Y', get_the_modified_time('U')); ?></span>
					<h4 class="text-2xl"><?php the_title(); ?></h4>
					<?php
					$categories = get_the_terms(get_the_ID(), 'portfolio_categories');
					if ($categories && !is_wp_error($categories)) : ?>
						<p class="text-sm mt-2">
							<?php foreach ($categories as $category) : ?>
								<a href="<?= get_term_link($category); ?>"
								   class="text-blue-400 hover:underline"><?= $category->name; ?></a>
							<?php endforeach; ?>
						</p>
					<?php endif; ?>
					<a href="<?php the_permalink(); ?>" class="text-black transition-all mt-3 h-8 duration-500 relative items-center flex gap-x-2 group">
						<span class="group-hover:p-3 rounded-full duration-500 bg-foreground duration text-white border group-hover:border-white/5 p-0.5">
							<svg width="20" height="20" fill="currentColor"
								 class="bi bi-chevron-right scale-0 size-0 transition-all duration-500 origin-top-right group-hover:scale-100 group-hover:size-fit" viewBox="0 0 16 16">
  									<path fill-rule="evenodd"
										  d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
							</svg>
						</span>
						<span>ادامه مطلب</span>
					</a>
				</div>
				<?php if (has_post_thumbnail()) : ?>
					<div class="lg:basis-1/2 max-lg:order-first">
						<img src="<?php the_post_thumbnail_url('large'); ?>" alt="<?php the_title_attribute(); ?>"
							 class="object-cover w-full aspect-video">
					</div>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>
		<?php wp_reset_postdata(); ?>
	<?php endif; ?>
</section>
