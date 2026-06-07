<?php
/**
 * Amazing Offer Slider (Digikala Style)
 */

global $woo_active;

$productOffer = get_field('product-offer');

// 1. Setup Arguments
$class        = $args['class'] ?? '';
$perPage      = $args['perPage'] ?? '5.2';
$mobile       = $args['mobilePerPage'] ?? '2.2';
$tablet       = $args['tabletPerPage'] ?? '3.1';
$lapTop       = $args['lapTopPerPage'] ?? '4.1';
$show         = $productOffer['show'] ?? false;
$category_obj = $productOffer['category'] ?? null; // This is a Term Object from ACF
$titleImg     = $productOffer['image'] ?? '';
$titleText    = $productOffer['text'] ?? '';
$timer        = $productOffer['timer'] ?? '';

if ($woo_active && $show):

    // Handle section IDs safely if no category is selected
    $slug_id    = ($category_obj && !is_wp_error($category_obj)) ? $category_obj->slug : 'on-sale';
    $term_id_id = ($category_obj && !is_wp_error($category_obj)) ? $category_obj->term_id : '0';

    // Safely generate the "View All" link (defaults to shop page)
    $view_all_link = get_permalink(wc_get_page_id('shop'));
    if ($category_obj && !is_wp_error(get_term_link($category_obj))) {
        $view_all_link = get_term_link($category_obj);
    }

    // 2. Define Query Logic
    $query_args = [
        'post_type'      => 'product',
        'posts_per_page' => 10,
        'post_status'    => 'publish', // Fixed from 'status'
    ];

    // Get WooCommerce products on sale safely
    $on_sale_ids = wc_get_product_ids_on_sale();
    $on_sale_ids = empty($on_sale_ids) ? [0] : $on_sale_ids; // Prevent pulling all products if none are on sale

    if ($category_obj && isset($category_obj->slug)) {
        $query_args['tax_query'] = [[
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $category_obj->slug,
        ]];
    } else {
        // Fallback 1: Show On-Sale Products using native Woo function
        $query_args['post__in'] = $on_sale_ids;
    }

    $amazing_query = new WP_Query($query_args);

    // Fallback 2: if category was selected but has NO products, show Sale items instead
    if ($category_obj && !$amazing_query->have_posts()) {
        unset($query_args['tax_query']);
        $query_args['post__in'] = $on_sale_ids;
        $amazing_query = new WP_Query($query_args);

        // Change the link back to the main shop since the category was empty
        $view_all_link = get_permalink(wc_get_page_id('shop'));
    }

    // Stop completely if there's nothing to show (no category posts AND no sale items)
    if (!$amazing_query->have_posts()) return;
    ?>

    <section class="rtl group/amazing overflow-hidden <?= esc_attr($class); ?>" dir="rtl"
             id="amazing-section-<?= esc_attr($slug_id); ?>">
        <div class="bg-primary lg:rounded-lg p-3 max-lg:pt-8 flex flex-col lg:flex-row gap-8 lg:gap-6 items-stretch shadow-gray-200">

            <div class="lg:w-1/5 relative flex flex-col items-center justify-center gap-2 text-center text-white lg:p-4 shrink-0">
                <?php if (!empty($titleImg)) : ?>
                    <img src="<?= esc_url($titleImg['url']); ?>" alt="Promotion"
                         class="w-16 aspect-square rounded-lg h-auto drop-shadow-xl">
                <?php endif; ?>

                <?php if ($titleText) : ?>
                    <div class="text-2xl font-black leading-[1.7]"><?= esc_html($titleText); ?></div>
                <?php else : ?>
                    <div>
                        <span class="block text-4xl font-black mb-2 leading-tight">پیشنهاد</span>
                        <span class="block text-2xl font-light tracking-[0.2em] opacity-90">شگفت‌انگیز</span>
                    </div>
                <?php endif;

                if ($timer) :
                    get_template_part('template-parts/global/countdown-timer', null, ['timer' => $timer]);
                endif; ?>

                <?php if ($category_obj || !is_shop()): ?>
                    <a href="<?= esc_url($view_all_link); ?>/?on_sale=true"
                       class="<?= empty($titleImg) ? 'mt-5' : 'mt-auto'; ?> flex items-center gap-2 text-sm font-bold border border-white/15 bg-white/10 px-12 lg:px-6 py-3 rounded-xl text-white hover:bg-white hover:text-primary transition-all duration-300 group/btn">
                        <span>مشاهده همه</span>
                        <?php get_template_part('template-parts/svg/chevron-left', null, ['size' => '22', 'class' => 'w-4 h-4 transition-all duration-500 group-hover/btn:-translate-x-2']); ?>
                    </a>
                <?php endif; ?>
            </div>

            <div class="flex-1 w-full lg:w-4/5 pe-6 relative swiper post-slider !overflow-visible [&>.swiper-button-disabled]:hidden"
                 data-index="<?= esc_attr($term_id_id); ?>"
                 data-perfix="amazing"
                 data-space="10"
                 data-autoplay="10000"
                 data-free="1"
                 data-perpage="<?= esc_attr($perPage); ?>"
                 data-laptop="<?= esc_attr($lapTop); ?>"
                 data-tablet="<?= esc_attr($tablet); ?>"
                 data-mobile="<?= esc_attr($mobile); ?>">

                <ul class="swiper-wrapper items-center lg:bg-primary ">
                    <?php while ($amazing_query->have_posts()) : $amazing_query->the_post(); ?>
                        <?php get_template_part('template-parts/product/card/product-card', null, ['class' => 'swiper-slide h-auto w-7/12 lg:w-1/4']); ?>
                    <?php endwhile; wp_reset_postdata(); ?>

                    <?php if ($amazing_query->post_count > 4): ?>
                        <li class="swiper-slide h-auto">
                            <?php if ($category_obj || !is_shop()): ?>
                                <a href="<?= esc_url($view_all_link); ?>"
                                   class="flex flex-col items-center justify-center group hover:text-white text-white/70 transition-all duration-500">
                                    <div class="w-16 h-16 rounded-full border-2 border-current flex items-center justify-center mb-4 transition-transform group-hover:scale-125">
                                        <?php get_template_part('template-parts/svg/chevron-left', null, ['size' => '22', 'class' => 'w-8 h-8']); ?>
                                    </div>
                                    <span class="font-bold text-lg">مشاهده همه</span>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endif; ?>
                </ul>

                <button class="amazing-prev-<?= esc_attr($term_id_id); ?> cursor-pointer absolute top-1/2 -right-6 z-30 w-12 h-12 bg-white shadow-2xl rounded-sm flex items-center justify-center text-gray-800 -translate-y-1/2 opacity-0 group-hover/amazing:opacity-100 transition-all hover:bg-gray-100 max-lg:hidden">
                    <?php get_template_part('template-parts/svg/chevron-right', null, ['size' => '22']); ?>
                </button>
                <button class="amazing-next-<?= esc_attr($term_id_id); ?> cursor-pointer absolute top-1/2 left-4 z-30 w-12 h-12 bg-white shadow-2xl rounded-sm flex items-center justify-center text-gray-800 -translate-y-1/2 opacity-0 group-hover/amazing:opacity-100 transition-all hover:bg-gray-100 max-lg:hidden">
                    <?php get_template_part('template-parts/svg/chevron-left', null, ['size' => '22']); ?>
                </button>
            </div>
        </div>
    </section>
<?php endif; ?>