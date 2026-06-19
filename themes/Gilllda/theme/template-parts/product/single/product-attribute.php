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
     class="lg:col-span-7 xl:col-span-5 bg-white p-5 max-lg:pb-2 pt-3 lg:py-0 lg:px-6 z-[1] relative flex flex-col rounded-t-4xl max-lg:border-t border-gray-200 rtl"
     dir="rtl">

    <!-- Mobile Drag Handle -->
    <div @click.prevent="document.getElementById('product-attribute').scrollIntoView({behavior: 'smooth'})"
         class="w-12 h-1 mb-4 lg:hidden bg-gray-200 rounded-full mx-auto cursor-pointer"></div>

    <!-- Breadcrumb -->
    <div class="text-[12px] flex justify-between items-center text-gray-500 mb-4">
        <?php woocommerce_breadcrumb(); ?>
        <div class="flex items-center justify-end gap-1">
            <?php
            // Share Button
            get_template_part('template-parts/product/single/share-button');

            // Compare Button
            $args_compare = [
                'id' => get_the_ID(),
                'class' => 'w-8 h-8 flex items-center justify-center border-none text-gray-500 hover:bg-gray-100 transition-colors',
                'textClass' => 'hidden'
            ];
            get_template_part('template-parts/product/compare-button', null, $args_compare);
            ?>
        </div>
    </div>

    <!-- Main Header Section -->
    <div class="flex justify-between items-start pb-5 border-b border-gray-100 gap-4">
        <!-- Right Content: Title, Stock, SKU -->
        <div class="flex flex-col flex-1">
            <!-- Stock Status -->
            <?php if ($product->is_in_stock()) : ?>
                <div class="flex items-center mb-2 gap-1.5 text-emerald-700 text-[11px] font-bold">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    سفارش گرفته می‌شود
                </div>
            <?php else : ?>
                <div class="flex items-center mb-2 gap-1.5 text-red-500 text-[11px] font-bold">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                    </span>
                    غیرقابل سفارش
                </div>
            <?php endif; ?>

            <!-- Title -->
            <h1 class="text-2xl lg:text-3xl font-black text-gray-800 leading-tight">
                <?php the_title(); ?>
            </h1>

            <!-- Meta / SKU -->
            <div class="flex items-center divide-x divide-x-reverse divide-gray-300 mt-3 text-xs text-gray-400">
                <div class="flex items-center gap-1 pl-3">
                    <span class="font-light">کد محصول:</span>
                    <span class="font-medium text-gray-600 uppercase"><?= $product->sku ?: '-'; ?></span>
                </div>
                <?php if ($product->is_in_stock() && $product->stock_quantity) : ?>
                    <div class="flex items-center gap-1 pr-3">
                        <span class="font-light">موجودی:</span>
                        <span class="font-medium text-gray-600"><?= $product->stock_quantity; ?> عدد</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Left Content: Action Buttons (Matches your screenshot) -->
        <div class="flex flex-col shrink-0 items-end justify-end gap-2">
            <!-- Comments -->
            <button @click.prevent="document.getElementById('comments').scrollIntoView({ behavior: 'smooth' })"
                    class="flex items-center gap-1 px-2 py-1 bg-gray-50 border border-gray-200 rounded-md text-[11px] text-gray-500 hover:bg-gray-100 transition-colors">
                <?php get_template_part('template-parts/svg/message', null, ['size' => 14, 'class' => 'text-gray-400']); ?>
                <span><?= $review_count; ?></span>
            </button>
            <!-- Rating -->
            <?php if ($average_rating > 0) : ?>
                <button @click.prevent="document.getElementById('product-rating').scrollIntoView({ behavior: 'smooth' })"
                        class="flex items-center gap-1 px-2 py-1 bg-yellow-50 border border-yellow-200 rounded-md text-[11px] font-black text-yellow-700 hover:bg-yellow-100 transition-colors">
                    <?php get_template_part('template-parts/svg/star-fill', null, ['size' => 14, 'class' => 'fill-yellow-400']); ?>
                    <span><?= number_format($average_rating, 1); ?></span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Short Description -->
    <?php if ($short_desc) : ?>
        <div class="p-3 rounded-lg bg-gray-50 border border-gray-100 text-[13px] leading-7 text-gray-600 font-light text-justify">
            <?= wp_kses_post($short_desc); ?>
        </div>
    <?php endif; ?>

    <!-- Attributes Section (Digikala Style Features List) -->
    <div class="mt-8 mb-4 border-s-2 ps-5 relative border-gray-100 before:absolute before:rounded-lg before:-start-0.5 before:h-7 before:w-0.5 before:bg-primary">
        <p class="text-sm font-black text-gray-800 mb-4 flex items-center gap-2">
            ویژگی‌های محصول
        </p>

        <ul class="flex flex-col gap-3 text-sm">

            <!-- Weight -->
            <?php if ($product->has_weight()) : ?>
                <li class="flex items-baseline gap-2">
                    <span class="text-gray-500 min-w-max">وزن:</span>
                    <span class="font-medium text-gray-800"><?= wc_format_weight($product->get_weight()); ?></span>
                </li>
            <?php endif; ?>

            <!-- Dimensions -->
            <?php $dimensions = $product->get_dimensions(false);
            if (!empty($dimensions)) : ?>
                <li class="flex items-baseline gap-2">
                    <span class="text-gray-500 min-w-max">ابعاد:</span>
                    <span class="font-medium text-gray-800 dir-ltr text-right inline-block">
                        <?= is_array($dimensions) ? wc_format_dimensions($dimensions) : esc_html($dimensions); ?>
                    </span>
                </li>
            <?php endif; ?>

            <!-- Custom Attributes (e.g., Colors) -->
            <?php
            $attributes = $product->get_attributes();
            foreach ($attributes as $attribute) :
                if (!$attribute->is_taxonomy()) continue;
                $taxonomy = $attribute->get_name();
                $terms = wc_get_product_terms($product->get_id(), $taxonomy, array('fields' => 'all'));
                ?>
                <li class="flex items-start gap-2 pt-1">
                    <span class="text-gray-500 min-w-max mt-0.5"><?= wc_attribute_label($taxonomy); ?>:</span>

                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($terms as $term) :
                            $acf_color = get_field('color', $taxonomy . '_' . $term->term_id);
                            ?>
                            <div class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-full text-xs font-medium text-gray-700">
                                <?php if ($acf_color) : ?>
                                    <span class="w-3.5 h-3.5 rounded-full border border-gray-300 shadow-inner block"
                                          style="background-color: <?= esc_attr($acf_color); ?>;"></span>
                                <?php endif; ?>
                                <span><?= esc_html($term->name); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </li>
            <?php endforeach; ?>

            <!-- Categories -->
            <?php $categories = get_the_terms($post_id, 'product_cat');
            if ($categories && !is_wp_error($categories)) : ?>
                <li class="flex items-center gap-2 pt-1">
                    <span class="text-gray-500 min-w-max mt-0.5">دسته‌بندی:</span>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($categories as $category) : ?>
                            <a href="<?= esc_url(get_term_link($category)); ?>"
                               class="text-xs font-medium bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-full hover:bg-gray-100 transition-all">
                                <?= esc_html($category->name); ?>
                            </a>
                            <?php if (next($categories)) echo '<span class="text-gray-300 text-xs px-0.5">/</span>'; ?>
                        <?php endforeach; ?>
                    </div>
                </li>
            <?php endif; ?>

        </ul>
    </div>
</div>