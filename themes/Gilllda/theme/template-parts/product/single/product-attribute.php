<?php
global $product;
if (!$product) return;
$post_id = get_the_ID();
$rating_value = get_post_meta($post_id, 'rating_value', true);

// Get total ratings and average rating
$total_ratings = get_post_meta($post_id, 'total_ratings', true);
$total_rating_value = get_post_meta($post_id, 'total_rating_value', true);
$average_rating = 0;

if (is_numeric($total_ratings) && is_numeric($total_rating_value) && $total_ratings > 0) {
    $average_rating = round($total_rating_value / $total_ratings, 1);
}
$review_count = $product->get_review_count();
$short_desc = $product->get_short_description();
?>

<div id="product-attribute"
     style="scroll-margin-top: 0;"
     class="lg:col-span-7 xl:col-span-5 bg-gray-50 lg:bg-white p-5 max-lg:pb-2 pt-3 lg:py-0 lg:px-5 z-[1] relative flex flex-col gap-3 lg:gap-2 rounded-t-4xl max-lg:border-t border-gray-100 rtl"
     dir="rtl">
    <div @click.prevent="document.getElementById('product-attribute').scrollIntoView({behavior: 'smooth'})"
         class="w-28 h-1.5 mb-3 lg:hidden bg-gray-200 rounded-full mx-auto"></div>

    <div class="text-sm">
        <?php woocommerce_breadcrumb(); ?>
    </div>

    <div class="flex justify-between items-start border-b pb-2  border-black/10 gap-y-4">
        <div>
            <?php if ($product->is_in_stock()) : ?>
                <span class="flex items-center mb-2 lg:mb-1 gap-1.5 text-green-600 text-xs font-bold">
                        <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                        سفارش گرفته می‌شود
                    </span>
            <?php else : ?>
                <span class="flex items-center mb-2 lg:mb-1 gap-1.5 text-red-500 text-xs font-bold">
                        <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                    </span>
                        غیرقابل سفارش
                    </span>
            <?php endif; ?>
            <h1 class="text-2xl lg:text-4xl font-black text-gray-900"><?php the_title(); ?></h1>
            <div class="flex items-center divide-x divide-gray-300 mt-3">
                <div class="flex gap-2 pe-3 items-center text-xs opacity-50">
                    <span>کد محصول</span>
                    <span><?= $product->sku ?></span>
                </div>
                <?php if ($product->is_in_stock() && $product->stock_quantity) : ?>
                    <div class="flex gap-1 ps-3 items-center text-xs opacity-75">
                        <span>موجودی</span>
                        <span>(<?= $product->stock_quantity; ?>)</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex flex-col gap-2 items-end self-end md:self-start">
            <?php if ($average_rating > 0) : ?>
                <button @click.prevent="document.getElementById('product-rating').scrollIntoView({ behavior: 'smooth' })"
                        class="flex items-center gap-1.5 bg-yellow-50 hover:bg-yellow-100 transition-all px-3 py-1.5 rounded-xl border border-yellow-200 font-black text-yellow-700 text-sm cursor-pointer leading-none">
                    <?php
                    // Pass the class key explicitly in the array
                    get_template_part('template-parts/svg/star-fill', null, ['class' => 'w-4 h-4 fill-yellow-400']);
                    ?>
                    <?= number_format($average_rating, 1); ?>

                </button>
                <button @click.prevent="document.getElementById('comments').scrollIntoView({ behavior: 'smooth' })"
                        class="bg-primary/5 border border-primary/10 hover:bg-primary/10 rounded-xl gap-1.5 flex items-center py-1 px-3 text-gray-400 text-sm cursor-pointer transition-colors">
                    <?php get_template_part('template-parts/svg/message', null, ['class' => 'w-4 h-4 text-primary/70']); ?>
                    <?= $review_count; ?>
                </button>
            <?php endif; ?>
            <?php
            $args = ['id' => get_the_ID(), 'class' => 'mr-auto hover:bg-gray-50 lg:px-2 p-1.5 '];
            get_template_part('template-parts/product/compare-button', null, $args);
            ?>
        </div>
    </div>
    <div class="product-details flex flex-wrap gap-3">
        <?php
        $boxClass = 'flex  gap-2 bg-gray-50 items-center border p-1 flex-grow border-gray-200 rounded-sm transition-all';
        $labelClass = 'text-[11px] text-gray-400 font-bold ps-2';
        $valueClass = 'text-xs text-white bg-primary px-2 py-1 rounded-sm font-black';

        // Weight & Dimensions
        if ($product->has_weight()) : ?>
            <div class="<?= $boxClass ?>">
                <span class="<?= $labelClass ?>">وزن:</span>
                <span class="<?= $valueClass ?>"><?= wc_format_weight($product->get_weight()); ?></span>
            </div>
        <?php endif;

        // 2. Dimensions
        $dimensions = $product->get_dimensions(false);

        if (!empty($dimensions)) : ?>
            <div class="<?= $boxClass ?>">
                <span class="<?= $labelClass ?>">ابعاد:</span>
                <span class="<?= $valueClass ?>">
          <?php
          // If it's an array, format it; if it's already a string, just echo it.
          echo is_array($dimensions) ? wc_format_dimensions($dimensions) : esc_html($dimensions);
          ?>
       </span>
            </div>
        <?php endif;
        $attributes = $product->get_attributes();

        foreach ($attributes as $attribute) :
            // We only want to process taxonomy-based attributes (like pa_color)
            if (!$attribute->is_taxonomy()) {
                continue;
            }

            $taxonomy = $attribute->get_name(); // e.g., "pa_color"
            ?>
            <div class="w-full <?= esc_attr($boxClass) ?>">
                <span class="<?= esc_attr($labelClass) ?>"><?= wc_attribute_label($taxonomy); ?>:</span>
                <div class="flex divide-x divide-white/50">
                    <?php
                    // Get all term objects for this specific attribute on this product
                    // Changing 'fields' to 'all' returns WP_Term objects instead of just string names
                    $terms = wc_get_product_terms($product->get_id(), $taxonomy, array('fields' => 'all'));

                    foreach ($terms as $term) :
                        // Now we have access to the actual term data
                        $term_name = $term->name;
                        $term_id = $term->term_id;

                        // Construct the ACF target ID (Format: taxonomy_termID)
                        $acf_target = $taxonomy . '_' . $term_id;

                        // Get the ACF color field
                        $acf_color = get_field('color', $acf_target);
                        ?>
                        <div class="px-2 flex items-center">
                            <?php
                            // If the color exists, output a small color swatch next to the name
                            if ($acf_color) :
                                ?>
                                <span class="w-6 h-full rounded-s-sm border-l-2 border-white/70 inline-block"
                                      style="background-color: <?= esc_attr($acf_color); ?>;"></span>
                            <?php endif; ?>

                            <span class="<?= esc_attr($valueClass) ?>  <?= $acf_color ? 'rounded-s-none' : ''; ?>"><?= esc_html($term_name); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($short_desc) : ?>
        <div class="bg-primary/5 p-4 rounded-lg border border-primary/10 leading-7 text-sm text-gray-600">
            <?= wp_kses_post($short_desc); ?>
        </div>
    <?php endif; ?>
    <?php
    // Get product categories
    $categories = get_the_terms($post_id, 'product_cat');

    if ($categories && !is_wp_error($categories)) : ?>
        <div class="<?= $boxClass; ?> mb-3">
            <div class="text-[11px] text-gray-500 flex items-center gap-1 font-bold ps-1">
                <?php get_template_part('template-parts/svg/tag', null, ['size' => 17]); ?>
                <span>دسته‌بندی:</span>
            </div>
            <div class="flex flex-wrap gap-2 flex-1 max-w-full overflow-x-scroll">
                <?php foreach ($categories as $category) : ?>
                    <a href="<?= esc_url(get_term_link($category)); ?>"
                       class="flex items-center text-nowrap bg-white hover:bg-primary hover:text-white transition-colors duration-300 text-gray-600 text-xs font-bold px-3 py-1.5 rounded-md border border-gray-200 hover:border-primary">
                        <?= esc_html($category->name); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <?php
    if (wp_is_mobile()):
        get_template_part('template-parts/shop/property', null, ['class' => 'lg:hidden !mt-0 border-b pb-3 border-gray-200']);
    endif;
    ?>
</div>
