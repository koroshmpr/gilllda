<nav x-cloak
	 :class="atBottom ? 'opacity-0' : ''"
	 class="fixed max-lg:hidden inset-y-0 end-7 py-20 z-[2] flex flex-col gap-y-5 justify-end items-center transition-opacity duration-300">

	<div
		class="flex flex-col bg-white/95 backdrop-blur-sm border border-black/5 shadow-md overflow-hidden rounded-md items-center justify-center lg:justify-end">
		<?php
		$transition = 'transition-all';
		$class = 'text-black w-full aspect-square px-1.5 py-2 hover:bg-black group hover:text-white ' . $transition;
		$svgClass = $transition;
		$socials = get_field('social', 'option');
		if ($socials):
			foreach ($socials as $social):?>
				<a title="<?= esc_attr($social['name']); ?>" aria-label="<?= esc_attr($social['name']); ?>"
				   class="<?= $class; ?>" target="_blank"
				   href="<?= esc_url($social['link']['url'] ?? ''); ?>">
					<?php
					$args = array('size' => 14, 'class' => $svgClass);
					get_template_part('template-parts/svg/socials/' . esc_attr($social['name']), null, $args); ?>
				</a>
			<?php endforeach;
		endif;
		$phone = get_field('phone', 'options');
		?>
		<a title="call to <?= $phone; ?>"
		   aria-label="<?= $phone; ?>"
		   class="<?= $class; ?>" target="_blank"
		   href="tel:<?= esc_url($phone ?? ''); ?>">
			<?php
			$args = array('size' => 14, 'class' => $svgClass);
			get_template_part('template-parts/svg/call-fill', null, $args); ?>
		</a>
	</div>
</nav>
