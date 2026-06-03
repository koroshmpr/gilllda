<?php
/* Template Name: faq */
get_header();

$faqCat = get_field('faq_cat');
$all_faq_items = []; // Array to collect all questions for the master schema
?>

    <header class="container border-b mt-3 mb-5 border-black/10">
        <h1 class="text-black text-3xl border-b-2 pb-2 border-primary w-fit"><?php single_post_title(); ?></h1>
    </header>

<?php if ($faqCat): ?>
    <section class="container mt-3 grid lg:grid-cols-3 xl:grid-cols-4 md:grid-cols-2 gap-3">
        <?php foreach ($faqCat as $i => $faq): ?>
            <button
                    @click.prevent="document.getElementById('faqList-<?= $i; ?>').scrollIntoView({behavior: 'smooth'})"
                    class="border group relative overflow-hidden cursor-pointer rounded-lg border-gray-200">
                <div class="lg:py-14 flex max-lg:ps-8 p-5 transition-all duration-300 <?= $faq['image'] ? 'text-white bg-black/50 hover:bg-black/70' : 'text-black bg-gray-50 hover:bg-gray-100' ?> relative z-1  gap-3 justify-between items-center ">
                    <div class="flex gap-2 group-hover:gap-3 transition-all max-lg:flex-col lg:items-center">
                        <?php
                        $args = array(
                            'size' => 40,
                            'class' => 'group-hover:scale-110 transition-all size-14 lg:size-36 start-1 lg:start-3 translate-x-1/2 top-1/2 -translate-y-1/2 absolute opacity-10 lg:opacity-5'
                        );
                        get_template_part('template-parts/svg/faq', null, $args);
                        ?>
                        <h2 class="font-bold"><?= $faq['title'] ?? ''; ?></h2>
                    </div>
                    <?php
                    $args = array(
                        'size' => 30,
                        'class' => 'group-hover:-rotate-90 transition-all'
                    );
                    get_template_part('template-parts/svg/chevron-left', null, $args);
                    ?>
                </div>
                <?php if ($faq['image']): ?>
                    <img class="absolute z-0 top-0 w-full aspect-video  object-cover" src="<?= $faq['image']['url'] ?? ''; ?>" alt="<?= $faq['image']['title'] ?? ''; ?>">
                <?php endif; ?>
            </button>
        <?php endforeach; ?>
    </section>

    <section class="container mt-12 mb-5 flex flex-col gap-y-12">
        <?php foreach ($faqCat as $i => $faq): ?>
            <div id="faqList-<?= $i; ?>">
                <div class="border-b flex border-black/10">
                    <h3 class="text-black text-3xl border-b-2 pb-2 border-primary w-fit"><?= $faq['title'] ?? ''; ?></h3>
                </div>

                <?php
                $faqList = $faq['faq_list'];

                // Collect items for the master schema
                if (!empty($faqList)) {
                    $all_faq_items = array_merge($all_faq_items, $faqList);
                }

                $args = array(
                    'items' => $faqList,
                    'disable_schema' => true // Prevent the child from outputting schema
                );
                get_template_part('template-parts/global/faq-list', null, $args);
                ?>
            </div>
        <?php endforeach; ?>
    </section>

    <?php if (!empty($all_faq_items)) : ?>
        <script type="application/ld+json">
            <?php
            $master_schema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => []
            ];

            foreach ($all_faq_items as $item) {
                $question = $item['question'] ?? '';
                $answer = $item['answer'] ?? '';
                if (empty($question) || empty($answer)) continue;

                $master_schema['mainEntity'][] = [
                    '@type' => 'Question',
                    'name' => wp_strip_all_tags($question),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => wp_strip_all_tags($answer),
                    ]
                ];
            }

            echo wp_json_encode($master_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            ?>
        </script>
    <?php endif; ?>

<?php else : ?>
<?php endif;

get_footer();
?>