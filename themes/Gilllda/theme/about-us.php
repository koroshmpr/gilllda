<?php /* Template Name: about us */
$owners = get_field('owners');
get_header(); ?>
    <section class="container mb-5 max-lg:px-0 max-w-content grid lg:grid-cols-3 items-center  justify-between gap-x-10">
        <header class="bg-white  max-lg:rounded-t-2xl max-lg:-mt-5   max-lg:px-3 py-5 max-lg:order-2 z-1 lg:col-span-2 lg:border-s-4 border-primary/5">
            <h1 class="text-black relative lg:ps-5 before:absolute before:-start-1 before:h-7 before:animate-bounce before:top-2 before:w-1 lg:before:bg-gradient-to-b before:via-primary max-lg:text-center text-4xl mb-3"><?php the_title() ?></h1>
            <article
                    class="prose prose-sm prose-h1:max-lg:text-2xl max-w-none h-fit duration-500 overflow-hidden text-justify transition-all leading-7 lg:p-5"><?php the_content(); ?></article>
        </header>
        <div class="lg:p-3 max-lg:sticky top-2 overflow-hidden border max-lg:order-1 border-black/10 lg:shadow-md bg-white bg-cover">
            <img class="size-full object-cover max-lg:aspect-square transition-all lg:scale-150 overflow-hidden ease-linear duration-300"
                 :class="intro ? '!scale-100' : ''" src="<?php the_post_thumbnail_url('large'); ?>"
                 alt="<?php the_title_attribute(); ?>">
        </div>
    </section>
<?php if ($owners): ?>
    <section class="container max-w-content grid lg:grid-cols-3 xl-grid-cols-4 gap-5 py-5 lg:py-12">
        <?php foreach ($owners as $owner): ?>
            <div class="flex items-center bg-primary/5 gap-5 text-black rounded-lg p-1 border border-gray-200">
                <img class="size-20 rounded-md" src="<?= $owner['image']['url'] ?? '' ?>"
                     alt="<?= $owner['image']['title'] ?? '' ?>">
                <div class="flex gap-y-1 flex-col">
                    <h2 class="font-bold text-lg"><?= $owner['name'] ?? ''; ?></h2>
                    <span class="text-xs"><?= $owner['position'] ?? ''; ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
<?php
endif;
$portfolioSection = get_field('portfolio');
if ($portfolioSection):
    $portfolios = $portfolioSection['portfolios'];
    if ($portfolios):
        ?>
        <section class="overflow-hidden">
            <div class="swiper relative lg:overflow-visible container lg:shadow-sm px-0 ltr mb-1 post-slider"
                 data-index="portfolios" data-perfix="portfolio" data-space="0" data-loop="1" data-speed="4000"
                 data-perpage="<?= $row ?? '4.2'; ?>" data-mobile="1.3" data-autoplay="1" data-scroll="0"
                 data-free="1">
                <div class="swiper-wrapper !ease-linear min-h-[30vh] swiper-container over group/slider">
                    <?php
                    foreach ($portfolios as $portfolio):
                        if (!$portfolio) continue;
                        // Get post details
                        $post_id = $portfolio->ID;
                        $related = get_field('related_product', $post_id);
                        $title = get_the_title($post_id);
                        $link = get_permalink($post_id);
                        $image = get_the_post_thumbnail_url($post_id, 'full');
                        ?>
                        <div
                                class="swiper-slide w-1/4 border-white duration-700 transition-all relative border-x-2 h-[50vh] overflow-hidden group">
                            <?php if ($image): ?>
                                <img src="<?= esc_url($image); ?>" alt="<?= esc_attr($title); ?>"
                                     class="size-full group-hover:scale-125 duration-700 select-none group-hover/slider:grayscale-50 group-hover:!grayscale-0 transition-all object-cover">
                            <?php endif; ?>
                            <div
                                    class="absolute text-white/70 font-bold bg-gradient-to-t from-black/80 via-black/50 select-none transition-all duration-500 bottom-0 p-6 pt-12 inset-x-0 gap-2 flex flex-col justify-end items-end text-center">
                                <h6 class="text-end text-lg font-semibold"><?= esc_html($title); ?></h6>
                                <?php if ($related && $related[0]): ?>
                                    <div class="flex w-full justify-between items-center">
                                        <p class="text-xs text-black rounded-sm p-1 bg-icon"><?= esc_html($related[0]->post_title); ?></p>
                                        <a href="<?= get_the_permalink($related[0]->ID); ?>"
                                           class="px-3 py-1 relative flex items-center overflow-hidden before:inset-0 group/related before:z-[0] before:transition-all transition-all before:absolute before:translate-y-full hover:before:translate-y-0  before:bg-icon gap-1 rounded-sm border">
                                            <span class="z-0 group-hover/related:text-black ">سفارش</span>
                                            <?php
                                            $args = array(
                                                'size' => '15',
                                                'class' => 'group-hover/related:text-black z-0'
                                            );
                                            get_template_part('template-parts/svg/arrow-right', null, $args);
                                            ?>
                                        </a>
                                    </div>

                                <?php else : ?>
                                    <div class="py-3"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach;
                    ?>
                </div>
            </div>
        </section>

    <?php
    endif;
endif;
get_footer();
