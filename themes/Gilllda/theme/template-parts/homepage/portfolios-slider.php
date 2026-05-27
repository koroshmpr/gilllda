<?php
$portfolioSection = get_field('portfolio');
$fixClass = 'lg:absolute inset-y-0 select-none lg:!w-[30vw] w-full justify-center text-stroke-3 !stroke-gray-400 text-primary/15 flex items-center lg:text-[7vw] text-center text-6xl max-lg:px-8 max-lg:py-4';
$portfolios = $portfolioSection['portfolios'];
if ($portfolios):
    ?>
    <section class="overflow-hidden mb-4">
        <div class="swiper relative lg:overflow-visible container lg:shadow-sm px-0 ltr lg:mt-24 mt-12 post-slider"
             data-index="portfolios" data-perfix="portfolio" data-space="0"
             data-perpage="<?= $row ?? '3.8'; ?>" data-mobile="1.3" data-autoplay="5000" data-scroll="0" data-free="1">
            <div
                    class="<?= $fixClass; ?> start-0 bg-gradient-to-r from-gray-50">
                <?= $portfolioSection['first-slide'] ?>
            </div>
            <div
                    class="<?= $fixClass; ?> end-0 bg-gradient-to-l from-gray-50">
                <?= $portfolioSection['last-slide'] ?>
            </div>
            <div class="swiper-wrapper min-h-[30vh] swiper-container over group/slider">
                <!-- First Slide -->
                <div class="swiper-slide max-lg:hidden"></div>

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
                            class="swiper-slide border-white hover:!w-[40vw] duration-700 transition-all relative border-x-2 h-[50vh] lg:h-[80vh] overflow-hidden group">
                        <?php if ($image): ?>
                            <img src="<?= esc_url($image); ?>" alt="<?= esc_attr($title); ?>"
                                 class="size-full group-hover:scale-125 duration-700 select-none group-hover/slider:grayscale-50 group-hover:!grayscale-0 transition-all object-cover">
                        <?php endif; ?>
                        <div
                                class="absolute text-white/70 font-bold bg-gradient-to-t from-black/80 via-black/50 select-none transition-all duration-500 bottom-0 p-6 pt-12 inset-x-0 gap-2 flex flex-col justify-end items-end text-center">
                            <p class="text-end text-lg font-semibold"><?= esc_html($title); ?></p>
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
                <div class="swiper-slide max-lg:hidden"></div>
                <!-- Last Slide -->
            </div>
            <?php
            $args = array(
                'size' => '30',
                'class' => 'w-6 h-6',
            );
            $arrowClass = 'lg:absolute top-1/2 lg:-translate-y-1/2 z-[5] flex justify-center bg-gray-100 border w-fit border-black/10 text-gray-600 p-0.5 p-2 hover:brightness-150';
            ?>
            <div
                    class="lg:contents relative flex w-full lg:justify-between -translate-y-2 z-[5] justify-evenly gap-2 items-center">
                <div class="lg:-translate-x-6 start-0 portfolio-prev-portfolios  <?= $arrowClass; ?>">
                    <?php get_template_part('template-parts/svg/chevron-left', null, $args); ?>
                </div>
                <div class="lg:translate-x-6 end-0 portfolio-next-portfolios <?= $arrowClass; ?>">
                    <?php get_template_part('template-parts/svg/chevron-right', null, $args); ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>