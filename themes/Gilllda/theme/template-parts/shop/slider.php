<?php
$slider = function_exists('get_field') ? get_field('slider') : null;

// Count the items in the slider array
$slide_count = is_array($slider) ? count($slider) : 0;

if ($slide_count > 0):
	$unique_id = 'Slider-' . get_the_ID();
	?>
	<section
		class="swiper container px-0 rounded-3xl overflow-hidden relative post-slider aspect-[2/1] lg:aspect-[4/1] ltr mb-4 group/slider"
			 data-index="<?= esc_attr($unique_id); ?>"
			 data-perfix="<?= esc_attr($unique_id); ?>"
			 data-space="0"
			 data-perpage="1"
			 data-mobile="1"
			 data-autoplay="5000"
			 data-speed="700"
			 data-loop="<?= $slide_count > 1 ? '1' : '0'; ?>"
			 data-free="0">

		<div class="swiper-wrapper swiper-container">
			<?php foreach ($slider as $index => $slide):
				$image = $slide['image'];
				$link  = $slide['link']['url'] ?? '#';
				?>
				<a href="<?= esc_url($link); ?>" class="swiper-slide block h-full">
					<?php
					$img_id = (int) ($image['ID'] ?? $image['id'] ?? 0);
					$alt    = ($image['alt'] ?? '') ?: 'کیف زنانه گیلدا';
					if ($img_id) {
						echo wp_get_attachment_image($img_id, '1536x1536', false, [
							'class'         => 'h-full w-full object-cover',
							'loading'       => $index === 0 ? 'eager' : 'lazy',
							'fetchpriority' => $index === 0 ? 'high' : 'auto',
							'decoding'      => 'async',
							'sizes'         => '(min-width: 1280px) 1280px, 100vw',
							'alt'           => esc_attr($alt),
						]);
					} else { ?>
						<img src="<?= esc_url($image['url'] ?? ''); ?>" alt="<?= esc_attr($alt); ?>" class="h-full w-full object-cover" <?= $index ? 'loading="lazy"' : 'fetchpriority="high"'; ?>>
					<?php } ?>
				</a>
			<?php endforeach; ?>
		</div>

		<?php if ($slide_count > 1) : ?>
			<div class="swiper-pagination bg-black/50 rounded-[3px] gap-1 flex items-center p-1"></div>
		<?php endif; ?>

	</section>
<?php endif; ?>
