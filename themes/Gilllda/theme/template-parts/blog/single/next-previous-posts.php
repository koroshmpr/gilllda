<?php
$prev_post = get_adjacent_post(FALSE, '', TRUE);
$next_post = get_adjacent_post(FALSE, '', FALSE);
?>
<nav class="navigation container max-lg:px-3 max-w-content mt-8"
	 role="navigation">
	<div class="nav-links flex <?= empty($prev_post) ? 'justify-end' : 'justify-between'; ?>">
		<?php if (!empty($prev_post)) { ?>
			<a class="nav-previous text-gray-500 cursor-pointer hover:text-black transition-all hover:lg:gap-x-3 flex gap-x-2 h-fit items-center justify-center text-center"
			   title="<?= esc_attr($prev_post->post_title); ?>"
			   href="<?= esc_url(get_permalink($prev_post->ID)); ?>"
			   rel="prev">
				<svg width="30" height="30" fill="currentColor" class="bi bi-chevron-right stroke-2 text-black"
					 viewBox="0 0 16 16">
					<path fill-rule="evenodd"
						  d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
				</svg>
				<div class="flex items-start text-sm flex-col gap-y-0.5">
					<span class="meta-nav" aria-hidden="true">قبلی</span>
					<span class="text-start line-clamp-1"><?= esc_html(wp_trim_words($prev_post->post_title, 10)); ?></span>
				</div>
			</a>
		<?php } ?>
		<?php if (!empty($next_post)) { ?>
			<a class="nav-next text-gray-500 cursor-pointer hover:text-black transition-all flex gap-x-2 hover:lg:gap-x-3 h-fit items-center justify-center text-center"
			   title="<?= esc_attr($next_post->post_title); ?>"
			   href="<?= esc_url(get_permalink($next_post->ID)); ?>"
			   rel="next">
				<div class="flex items-end text-sm flex-col gap-y-0.5">
					<span class="meta-nav" aria-hidden="true">بعدی</span>
					<span class="text-end line-clamp-1"><?= esc_html(wp_trim_words($next_post->post_title, 10)); ?></span>
				</div>
				<svg width="30" height="30" fill="currentColor" class="bi bi-chevron-left text-black stroke-2"
					 viewBox="0 0 16 16">
					<path fill-rule="evenodd"
						  d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
				</svg>
			</a>
		<?php } ?>
	</div>
</nav>

<?php
//get_template_part('template-parts/blog/single/next-previous-posts');
?>
