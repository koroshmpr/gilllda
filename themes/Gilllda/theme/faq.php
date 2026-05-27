<?php
/* Template Name: faq */

get_header();

$faqCat = get_field('faq_cat');

?>
<header class="container border-b mt-3 mb-5 border-black/10">
    <h1 class="text-black text-3xl border-b-2 pb-2 border-primary w-fit"><?php single_post_title(); ?></h1>
</header>
<?php if ($faqCat): ?>
    <section class="container mt-3 grid lg:grid-cols-3 xl:grid-cols-4 md:grid-cols-2 gap-3">
        <?php
        foreach ($faqCat as $i => $faq):?>
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
        <?php endforeach;
        ?>
    </section>
    <section class="container mt-12 mb-5 flex flex-col gap-y-12">
        <?php
        foreach ($faqCat as $i => $faq):?>
            <div id="faqList-<?= $i; ?>"
                 class="">
                <div class="border-b flex border-black/10">
                    <h3 class="text-black text-3xl border-b-2 pb-2 border-primary w-fit"><?= $faq['title'] ?? ''; ?></h3>
                    <?php
                    $args = array(
                        'size' => 40,
                        'class' => 'opacity-10'
                    );
                    get_template_part('template-parts/svg/faq', null, $args);
                    ?>
                </div>
                <?php
                $faqList = $faq['faq_list'];
                $args = array(
                    'items' => $faqList
                );
                get_template_part('template-parts/global/faq-list', null, $args);
                ?>
            </div>
        <?php endforeach; ?>
    </section>
<?php else : ?>
    <section class=" h-[50vh] flex flex-col justify-center opacity-50 items-center gap-y-4">
        <?php
        $args = array(
            'size' => 100,
            'class' => ''
        );
        get_template_part('template-parts/svg/faq', null, $args);
        ?>
        <h2 class="text-3xl text-center">لیستی وجود ندارد!</h2>
    </section>

<?php endif;
get_footer(); ?>
