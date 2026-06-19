<?php
/* Template Name: About Us */
get_header();
$owners = get_field('owners');
?>

    <!-- 1. Hero Section -->
    <section class="container mx-auto mb-16 max-w-7xl px-4 lg:px-8 grid lg:grid-cols-2 items-center gap-12 lg:gap-20">

        <!-- Text Content -->
        <header class="order-2 lg:order-1 flex flex-col justify-center z-10">
            <div class="border-b flex justify-start w-full border-primary/10 mb-6 relative">
                <h1 class="text-2xl lg:text-4xl border-b-2 pb-2 font-extrabold text-gray-900 tracking-tight">
                    <?php the_title() ?>
                </h1>
            </div>
            <article class="prose prose-sm max-w-none text-gray-600 text-justify leading-loose lg:ps-5">
                <?php the_content(); ?>
            </article>
        </header>

        <!-- Featured Image -->
        <div class="order-1 lg:order-2 relative group w-full max-lg:mt-6">
            <!-- Decorative offset background frame -->
            <div class="absolute inset-0 bg-primary/5 transform translate-x-4 translate-y-4 rounded-xl -z-10 transition-transform duration-500 group-hover:translate-x-2 group-hover:translate-y-2"></div>
            <div class="overflow-hidden rounded-xl shadow-sm border border-gray-100 bg-white">
                <img src="<?php the_post_thumbnail_url('large'); ?>"
                     alt="<?php the_title_attribute(); ?>"
                     fetchpriority="high"
                     decoding="async"
                     class="w-full h-auto object-cover aspect-[4/3] object-left lg:aspect-square transform transition-transform duration-700 hover:scale-105">
            </div>
        </div>
    </section>

    <!-- 2. Owners/Team Section -->
<?php if ($owners): ?>
    <section class="container mx-auto max-w-7xl px-4 lg:px-8 grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 py-10 lg:py-16">
        <?php foreach ($owners as $owner): ?>
            <div class="flex items-center bg-white hover:bg-gray-50 transition-colors duration-300 gap-5 rounded-xl p-4 border border-gray-100 shadow-sm hover:shadow-md">
                <img class="size-20 rounded-full object-cover border-2 border-primary/10"
                     src="<?= esc_url($owner['image']['url'] ?? '') ?>"
                     alt="<?= esc_attr($owner['image']['title'] ?? '') ?>"
                     loading="lazy">
                <div class="flex flex-col">
                    <h2 class="font-bold text-gray-900 text-lg"><?= esc_html($owner['name'] ?? ''); ?></h2>
                    <span class="text-sm text-gray-500 font-medium"><?= esc_html($owner['position'] ?? ''); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

    <!-- 3. Portfolio Slider Section -->
<?php
$portfolioSection = get_field('portfolio');
if ($portfolioSection):
    $portfolios = $portfolioSection['portfolios'];
    if ($portfolios):
        ?>
        <section class="overflow-hidden py-8">
            <div class="swiper relative lg:overflow-visible container mx-auto px-0 ltr mb-1 post-slider"
                 data-index="portfolios" data-perfix="portfolio" data-space="20" data-loop="1" data-speed="4000"
                 data-perpage="<?= esc_attr($row ?? '4.2'); ?>" data-mobile="1.3" data-autoplay="1" data-scroll="0"
                 data-free="1">
                <div class="swiper-wrapper !ease-linear min-h-[40vh] swiper-container group/slider">
                    <?php
                    foreach ($portfolios as $portfolio):
                        if (!$portfolio) continue;

                        $post_id = $portfolio->ID;
                        $related = get_field('related_product', $post_id);
                        $title   = get_the_title($post_id);
                        $link    = get_permalink($post_id);
                        $image   = get_the_post_thumbnail_url($post_id, 'large'); // Switched from 'full' to 'large' for better performance
                        ?>
                        <div class="swiper-slide rounded-xl overflow-hidden shadow-sm relative h-[45vh] lg:h-[50vh] group cursor-pointer">
                            <?php if ($image): ?>
                                <img src="<?= esc_url($image); ?>"
                                     alt="<?= esc_attr($title); ?>"
                                     loading="lazy"
                                     class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110">
                            <?php endif; ?>

                            <!-- Gradient Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-gray-900/90 via-gray-900/40 to-transparent opacity-80 transition-opacity duration-500 group-hover:opacity-100"></div>

                            <!-- Content -->
                            <div class="absolute bottom-0 inset-x-0 p-6 flex flex-col justify-end items-end text-right z-10">
                                <h6 class="text-white text-xl font-semibold mb-3 transform transition-transform duration-500 translate-y-2 group-hover:translate-y-0"><?= esc_html($title); ?></h6>

                                <?php if ($related && !empty($related[0])): ?>
                                    <div class="flex w-full justify-between items-center opacity-0 group-hover:opacity-100 transition-opacity duration-500 delay-100">
                                        <p class="text-xs text-gray-800 font-medium rounded px-2 py-1 bg-white/90 backdrop-blur-sm">
                                            <?= esc_html($related[0]->post_title); ?>
                                        </p>
                                        <a href="<?= get_the_permalink($related[0]->ID); ?>"
                                           class="flex items-center gap-2 text-sm text-white hover:text-primary transition-colors">
                                            <span>سفارش</span>
                                            <?php
                                            $args = array('size' => '16', 'class' => 'fill-current');
                                            get_template_part('template-parts/svg/arrow-right', null, $args);
                                            ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php
    endif;
endif;
get_footer();
?>