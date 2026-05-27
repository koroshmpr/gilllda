<?php
$slider = get_field('slider');

// Count the items in the slider array
$slide_count = is_array($slider) ? count($slider) : 0;

if ($slide_count > 0):
	$unique_id = 'Slider-' . get_the_ID();
	?>
	<section
		:class="scrolled ? '-translate-y-2': ''"
		class="swiper container px-0 rounded-md transition-all duration-300 relative post-slider aspect-[2/1] lg:aspect-[4/1] ltr mb-2 group/slider"
			 data-index="<?= esc_attr($unique_id); ?>"
			 data-perfix="<?= esc_attr($unique_id); ?>"
			 data-space="0"
			 data-perpage="1"
			 data-mobile="1"
			 data-autoplay="5000"
			 data-scroll="0"
			 data-direction="vertical"
			 data-free="1">

		<div class="swiper-wrapper swiper-container">
			<?php foreach ($slider as $index => $slide):
				$image = $slide['image'];
				$link  = $slide['link']['url'] ?? '#';
				?>
				<a href="<?= esc_url($link); ?>" class="swiper-slide block">
					<img src="<?= esc_url($image['url']); ?>"
						 alt="<?= esc_attr($image['title'] ?: 'slide-' . ($index + 1)); ?>"
						 class="w-full aspect-[2/1] lg:aspect-[4/1] object-cover">
				</a>
			<?php endforeach; ?>
		</div>

		<?php if ($slide_count > 1) : ?>
			<div class="swiper-pagination bg-black/50 rounded-[3px] gap-1 flex items-center p-1"></div>
		<?php endif; ?>

	</section>
<?php endif; ?>
