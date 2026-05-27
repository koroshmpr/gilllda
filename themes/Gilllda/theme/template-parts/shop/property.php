<?php
$property = get_field('property', 'options');
$show = $property['show'] ?? false;
$propertiesList = $property['property_list'] ?? [];

if ($show && !empty($propertiesList)) :
	?>
	<div class="flex flex-col items-center mt-4 rtl <?= $args['class'] ?? ''; ?>" dir="rtl">
		<?php foreach ($propertiesList as $index => $item) : ?>
			<div
				class="flex relative w-10/12 lg:w-11/12 items-start gap-x-4 <?= $index > 0 ? 'pt-3' : '' ?> group transition-all duration-300">
				<?php if ($index < count($propertiesList) - 1): ?>
					<span
						class="h-5 border-dashed border start-2.5  absolute bottom-0 translate-y-1 border-black/30"></span>
				<?php endif; ?>
				<div class="flex-shrink-0 items-start transition-colors duration-300">
					<?php if (!empty($item['logo']['url'])) : ?>
						<img class="size-6 aspect-square object-cover transition-transform group-hover:scale-105"
							 src="<?= esc_url($item['logo']['url']) ?>"
							 alt="<?= esc_attr($item['logo']['title'] ?? '') ?>">
					<?php endif; ?>
				</div>

				<div class="flex flex-col flex-1 <?= ($index < count($propertiesList) - 1) ? 'border-b pb-3 border-black/5' : '' ?> gap-y-0.5 justify-center">
                    <span class="font-black text-xs text-gray-600 group-hover:text-gray-900 transition-colors">
                        <?= esc_html($item['title'] ?? '') ?>
                    </span>
					<?php if (!empty($item['content'])): ?>
						<span class="text-xs text-gray-400 font-medium leading-tight">
                            <?= esc_html($item['content']); ?>
                        </span>
					<?php endif; ?>
				</div>

				<?php if ($index < count($propertiesList) - 1) : ?>
					<div class="hidden md:block h-8 w-px bg-gray-100 mx-2"></div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
