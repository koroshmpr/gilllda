<?php
/**
 * Amazing Offer Slider (Digikala Style)
 */

global $woo_active;

$category = $args['category'] ?? '';
if ($category):

// 1. Setup Arguments
    $perPage = $args['perPage'] ?? '4.2';
    $mobile = $args['mobilePerPage'] ?? '1.5';
    $tablet = $args['tabletPerPage'] ?? '3.1';
    $lapTop = $args['lapTopPerPage'] ?? '4.1';
    if ($woo_active):
// Handle section IDs safely if no category is selected
        $slug = ($category) ? $category->slug : null;
        $term_id = ($category) ? $category->term_id : '0';


// 2. Define Query Logic
        $query_args = [
            'post_type' => 'product',
            'posts_per_page' => 10,
            'status' => 'publish',
        ];

        if ($category && isset($slug)) {
            $query_args['tax_query'] = [[
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $slug, // Fixed: use 'terms' not 'slug'
            ]];
        }

        $amazing_query = new WP_Query($query_args);

        if (!$amazing_query->have_posts()) return;
        $count = count($amazing_query->posts);
        ?>

        <section class="rtl container max-lg:px-3 my-5" dir="rtl">
            <div class="flex items-center justify-between mb-2">
                <p class="pl-2"><?= $category->name ?></p>
                <div class="flex-1 h-[1px] bg-gradient-to-r from-primary/10 to-primary/20"></div>
                <a href="<?= get_term_link($category) ?? ''; ?>" aria-label="link to <?= $slug; ?> category"
                   class="flex items-center gap-2 text-primary/90 text-sm border border-primary/10 px-3 py-2 rounded-md bg-gray-50/50 hover:bg-primary hover:text-white transition-all duration-200 group/btn">
                    <span class="max-lg:text-xs">مشاهده همه</span>
                    <?php
                    $args = array(
                        'size' => '22',
                        'class' => 'w-4 h-4 transition-all duration-300 group-hover/btn:-translate-x-1'
                    ); ?>
                    <?php get_template_part('template-parts/svg/chevron-left', null, $args); ?>
                </a>
            </div>
            <div class="flex-1 group/amazing w-full bg-primary border border-black/10 rounded-lg p-4 <?= $count > 3 ? 'lg:px-6' : '' ?> relative swiper post-slider !overflow-visible [&>.swiper-button-disabled]:hidden"
                 data-index="<?= esc_attr($term_id); ?>"
                 data-perfix="amazing"
                 data-space="10"
                 data-autoplay="10000"
                 data-free="1"
                 data-perpage="<?= $perPage; ?>"
                 data-laptop="<?= $lapTop; ?>"
                 data-tablet="<?= $tablet; ?>"
                 data-mobile="<?= $mobile; ?>">

                <ul class="swiper-wrapper items-center">
                    <?php
                    while ($amazing_query->have_posts()) : $amazing_query->the_post();
                        $args = array(
                            'class' => 'swiper-slide h-auto w-7/12 lg:w-1/4'
                        );
                        get_template_part('template-parts/product/card/product-card', null, $args);
                    endwhile;
                    wp_reset_postdata(); ?>
                </ul>
                <?php
                if ($count > 3) :
                    $class = 'cursor-pointer absolute duration-300 top-1/2 z-30 bg-gray-50 border border-primary/15 size-12 shadow-2xl rounded-sm flex items-center justify-center text-gray-800 -translate-y-1/2 opacity-0 group-hover/amazing:opacity-100 group-hover/amazing:translate-x-0 transition-all hover:bg-gray-100 max-lg:hidden';
                    $args = array(
                        'size' => '22',
                    ); ?>
                    <button
                            class="amazing-prev-<?= esc_attr($slug); ?> -right-7 translate-x-2 <?= $class; ?>">
                        <?php get_template_part('template-parts/svg/chevron-right', null, $args); ?>
                    </button>
                    <button
                            class="amazing-next-<?= esc_attr($slug); ?> -left-7 -translate-x-2 <?= $class; ?>">
                        <?php get_template_part('template-parts/svg/chevron-left', null, $args); ?>
                    </button>
                <?php endif; ?>
            </div>
        </section>
    <?php endif;
endif;
?>
