<?php
global $product;
if (!$product) return;
$average_rating = $product->get_average_rating();
$review_count = $product->get_review_count();
$short_desc = $product->get_short_description();
?>

<div id="product-attribute"
     style="scroll-margin-top: 0;"
     class="lg:col-span-7 xl:col-span-5 bg-white p-5 max-lg:pb-2 pt-3 lg:py-0 lg:px-5 z-[1] relative flex flex-col gap-3 lg:gap-2 rounded-t-2xl max-lg:border-t border-black/10 -mt-2 rtl"
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
        <div class="flex gap-3 items-center self-end md:self-start">
            <?php if ($average_rating > 0) : ?>
                <div class="flex items-center gap-1.5 bg-yellow-50 px-3 py-1.5 rounded-full border border-yellow-100">
					<span
                            class="font-black text-yellow-700 text-sm leading-none"><?= number_format($average_rating, 1); ?></span>
                    <?php
                    // Pass the class key explicitly in the array
                    get_template_part('template-parts/svg/star-fill', null, ['class' => 'w-4 h-4 fill-yellow-400']);
                    ?>
                </div>
                <button @click.prevent="document.getElementById('reviews').scrollIntoView({ behavior: 'smooth' })"
                        class="text-gray-400 text-xs hover:text-primary transition-colors underline underline-offset-4">
                    <?= $review_count; ?> دیدگاه
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
        <?php endif; ?>
    </div>

    <?php if ($short_desc) : ?>
        <div class="bg-primary/5 p-4 rounded-lg border border-primary/10 leading-7 text-sm text-gray-600 mb-3">
            <?= wp_kses_post($short_desc); ?>
        </div>
    <?php endif; ?>

    <?php get_template_part('template-parts/shop/property', null, ['class' => 'lg:hidden !mt-0 border-b pb-3 border-gray-200']); ?>
</div>
