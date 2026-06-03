<?php
/**
 * Simplified Reusable FAQ List
 * @param array $args ['items'] The repeater array containing 'question' and 'answer'
 */
$items = !empty($args['items']) ? $args['items'] : get_field('faq_list');
$title = !empty($args['title']) ? $args['title'] : null;

if (empty($items)) return;
?>

<section class="py-5 grid gap-2 bg-white relative <?= $args['class'] ?? ''; ?>">
    <?php
    if ($title):?>
        <div class="border-b border-black/10 flex mb-6 w-full">
            <p class="pb-3 border-b-2 border-primary text-center text-black text-2xl fw-bold">
                <?= $title; ?>
            </p>
        </div>
    <?php endif;?>

	<?php foreach ($items as $index => $item) :
		$question = $item['question'] ?? '';
		$answer = $item['answer'] ?? '';
		if (empty($question) || empty($answer)) continue;
		?>

		<div x-data="{ expanded: false }"
             :class="expanded ? 'border-s-2 bg-gray-100 border-s-primary/70' : 'bg-gray-50/50 border-s-primary/50'"
			 class="border border-gray-200 rounded-md transition-all duration-300">

			<button
				type="button"
				class="flex items-center justify-between w-full p-5 cursor-pointer text-start"
				@click="expanded = !expanded"
				:aria-expanded="expanded">

                <span class="text-gray-900 text-sm lg:text-base leading-tight">
                    <?= esc_html($question); ?>
                </span>

				<span class="shrink-0 ms-3 text-primary transition-transform duration-300">
					<svg x-show="!expanded" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
						<path
							d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
					</svg>
					<svg x-show="expanded" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" x-cloak>
						<path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8"/>
					</svg>
				</span>
			</button>

			<div
				class="grid text-sm text-gray-600 overflow-hidden px-5 transition-all border-dashed duration-300 ease-in-out"
				:class="expanded ? 'grid-rows-[1fr] opacity-100 py-5 border-t border-gray-200' : 'grid-rows-[0fr] opacity-0'"
			>
					<div class="text-justify leading-[1.7] prose-a:bg-primary/10 prose-a:hover:bg-primary/20 prose-a:mx-0.5 prose-a:rounded-md prose-a:p-1 prose overflow-hidden opacity-75">
						<?= wp_kses_post($answer); ?>
					</div>
			</div>
		</div>
	<?php endforeach; ?>

</section>

<?php
//$args = array(
//    'title' => '',
//    'class' => '',
//    'items' '',
//);
//get_template_part('template-parts/svg/faq-list', null, $args);
?>
<?php if (!empty($items) && empty($args['disable_schema'])) : ?>
    <script type="application/ld+json">
        <?php
        $faq_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => []
        ];

        foreach ($items as $item) {
            $question = $item['question'] ?? '';
            $answer = $item['answer'] ?? '';
            if (empty($question) || empty($answer)) continue;

            $faq_schema['mainEntity'][] = [
                '@type' => 'Question',
                'name' => wp_strip_all_tags($question),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => wp_strip_all_tags($answer),
                ]
            ];
        }

        echo wp_json_encode($faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        ?>
    </script>
<?php endif; ?>
