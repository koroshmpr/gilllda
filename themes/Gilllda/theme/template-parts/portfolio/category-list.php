<?php
// Ensure arguments are extracted correctly
$taxonomy = $args['taxonomy'] ?? 'portfolio-category';

// Fetch terms dynamically based on taxonomy
$terms = get_terms([
	'taxonomy'   => $taxonomy,
	'orderby'    => 'name',
	'hide_empty' => true,
	'post_per_page'     => -1, // Fix: Correct parameter for unlimited terms
	'parent'     => 0, // For hierarchical taxonomies, this gets top-level terms
]);

$current_term_id = 0;
$i = 0;

// Detect current term in archive pages
if (is_tax($taxonomy)) {
	$term = get_queried_object();
	if ($term) {
		$current_term_id = $term->term_id;
	}
}
?>
<section class="swiper container max-w-content post-slider gap-x-3 mb-8"
		 data-autoplay="2000"
		 data-perpage="4.9"
		 data-mobile="1.2">
	<div class="swiper-wrapper">
		<?php
		if (!empty($terms) && !is_wp_error($terms)): ?>
			<?php foreach ($terms as $term):
				// Get ACF field for term thumbnail
				$thumbnail = get_field('image_cover', "{$taxonomy}_{$term->term_id}");
				$thumbnail_url = is_array($thumbnail) ? $thumbnail['url'] : (is_numeric($thumbnail) ? wp_get_attachment_url($thumbnail) : $thumbnail);

				// Fallback: get term meta thumbnail
				if (!$thumbnail_url) {
					$thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
					$thumbnail_url = wp_get_attachment_url($thumbnail_id);
				}

				// Highlight current taxonomy term
				$term_class = 'border border-white/10';
				if ($term->term_id === $current_term_id) {
					$term_class = 'border-4 border-white';
				}
				?>
				<a class="swiper-slide transition-all duration-500 lg:w-1/4 w-5/6 relative overflow-hidden"
				   href="<?= esc_url(get_term_link($term, $taxonomy)); ?>">
					<?php if ($thumbnail_url): ?>
						<img class="w-full object-cover aspect-video <?= $term_class; ?>"
							 src="<?= esc_url($thumbnail_url); ?>"
							 alt="<?= esc_attr($term->name); ?>">
					<?php endif; ?>
					<h6 class="absolute inset-0 bg-gradient-to-t from-black/90 text-white/60 lg:hover:text-xl flex items-end pb-3 justify-center transition-all text-center text-lg">
						<?= esc_html($term->name); ?>
					</h6>
				</a>
				<?php
			endforeach;
			wp_reset_postdata();
		endif; ?>
	</div>
</section>
