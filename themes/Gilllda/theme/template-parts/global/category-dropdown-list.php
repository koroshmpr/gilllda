<?php
// Ensure arguments are extracted correctly
$taxonomy = $args['taxonomy'] ?? 'category';
$perPage = $args['per_page'] ?? -1;
$class = $args['class'] ?? '';
$currentId = $args['currentId'] ?? '';

// Fetch terms dynamically based on taxonomy
$terms = get_terms([
	'taxonomy' => $taxonomy,
	'orderby' => 'name',
	'hide_empty' => true,
	'post_per_page' => $perPage, // Fix: Correct parameter for unlimited terms
	'parent' => 0, // For hierarchical taxonomies, this gets top-level terms
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
<nav class="flex flex-col gap-y-2 justify-start <?= $class ?? ''; ?>">
	<?php
	if (!empty($terms) && !is_wp_error($terms)): ?>
		<?php foreach ($terms as $term):
			?>
			<a class="<?= $currentId === $term->term_id ? 'bg-primary/10' : 'bg-white/80 hover:bg-primary/5'; ?> transition-all  border border-gray-200 rounded-sm group flex justify-between gap-2 pl-3 items-center duration-200 overflow-hidden"
			   href="<?= esc_url(get_term_link($term, $taxonomy)); ?>">
				<p class="flex gap-x-2 text-sm">
					<span class="w-8 aspect-square text-xs <?= $currentId === $term->term_id ? 'bg-primary/30' : 'bg-primary/10 group-hover:bg-primary/20 '; ?> transition-all flex justify-center items-center"><?= $term->count ?></span>
					<span class="py-2"><?= esc_html($term->name); ?></span>
				</p>
				<?php
				$args = array(
					'size' => '18',
					'class' => 'rotate-180 py-2 box-content shrink-0 group-hover:-translate-x-0.5 transition-all',
				);
				get_template_part('template-parts/svg/chevron-right', null, $args);
				?>
			</a>
		<?php
		endforeach;
		wp_reset_postdata();
	endif; ?>
</nav>

<?php
//$args = array(
//    'taxonomy' => '',
//    'class' => '',
//    'per_page' -1,
//    'currentId' => get_the_id()
//);
//get_template_part('template-parts/svg/category-dropdown-list', null, $args);
?>
