<?php

get_header();

$taxonomy = 'portfolio-category';

// Get the current term
$current_term = get_queried_object();
$current_term_id = $current_term->term_id ?? 0;

// Get the current page
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

?>
	<header class="container max-w-content pb-8 lg:pt-24">
		<h1 class="text-white text-3xl lg:text-8xl"><?= esc_html(single_term_title('', false)); ?></h1>
	</header>
<?php get_template_part('template-parts/portfolio/category-list'); ?>
	<section class="container max-w-content grid lg:grid-cols-3 gap-8">
		<?php
		$posts_per_page = get_option('posts_per_page'); // Get the number of posts per page from WordPress settings

		$args = array(
			'post_type' => 'portfolio',
			'post_status' => 'publish',
			'posts_per_page' => $posts_per_page,
			'paged' => $paged,
			'ignore_sticky_posts' => true,
			'tax_query' => array(
				array(
					'taxonomy' => $taxonomy,
					'field' => 'term_id',
					'terms' => $current_term_id,
				),
			),
		);
		$loop = new WP_Query($args);
		if ($loop->have_posts()) :
			while ($loop->have_posts()) :
				$loop->the_post(); ?>
				<a class="relative overflow-hidden group hover:scale-105 transition-all duration-500"
				   href="<?= get_the_permalink(); ?>">
					<img class="w-full object-cover aspect-square" height="250" src="<?= the_post_thumbnail_url(); ?>"
						 alt="image of the <?= get_the_title(); ?> post">
					<div
						class="lg:absolute inset-0 bg-foreground lg:bg-black/70 transition-all duration-500 lg:opacity-0 group-hover:opacity-100 flex justify-center items-center text-white max-lg:p-4 px-12">
						<p class="text-base lg:text-2xl text-center"><?= get_the_title(); ?></p>
					</div>
				</a>
			<?php
			endwhile;
		endif;
		wp_reset_postdata(); ?>
	</section>

<?php
$total_pages = $loop->max_num_pages;
if ($total_pages > 1) : ?>
	<nav class="mt-10" aria-label="pagination">
		<ul class="pagination justify-center items-center flex gap-x-3 mb-0">
			<?php if (get_previous_posts_link()) : ?>
				<li class="prev p-3 bg-foreground aspect-square text-white">
					<?php previous_posts_link(__('<')); ?>
				</li>
			<?php endif; ?>

			<?php
			$pagination_links = paginate_links(array(
				'base' => get_pagenum_link(1) . '%_%',
				'format' => 'page/%#%/',
				'current' => max(1, get_query_var('paged')),
				'total' => $total_pages,
				'prev_next' => false,
				'type' => 'array', // Ensures it returns an array of links
			));

			if (!empty($pagination_links)) :
				foreach ($pagination_links as $link) :
					echo '<li class="page-item">' . $link . '</li>';
				endforeach;
			endif;
			?>

			<?php if (get_next_posts_link('', $total_pages)) : ?>
				<li class="next p-3 bg-foreground aspect-square text-white">
					<?php next_posts_link(__('>')); ?>
				</li>
			<?php endif; ?>
		</ul>
	</nav>
<?php endif; ?>

<?php
get_footer();
?>
