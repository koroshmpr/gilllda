<?php
global $product;
if (!$product) return;

// 1. Get raw price numbers regardless of product type
if ($product->is_type('variable')) {
    // For variable products, we typically show the minimum starting price
    $price = (float) $product->get_variation_price('min', true);
    $regular_price = (float) $product->get_variation_regular_price('min', true);
    $is_on_sale = $price < $regular_price;
} else {
    // For simple products
    $price = (float) $product->get_price();
    $regular_price = (float) $product->get_regular_price();
    $is_on_sale = $product->is_on_sale();
}

// 2. Calculate Discount Percentage
$percentage = ($is_on_sale && $regular_price > 0) ? round((($regular_price - $price) / $regular_price) * 100) : 0;
?>

<div class="flex flex-col w-full rtl" dir="rtl">
    <div class="flex justify-start items-start min-h-[50px] w-full gap-2">
        <!-- Left Side (End): Pricing & Toman -->
        <div class="flex flex-col items-start gap-0.5">

            <!-- Main Price Row -->
            <div class="flex items-center justify-start gap-1.5 w-full">
                <!-- Raw Number Format (No WooCommerce currency injection) -->
                <span class="text-lg lg:text-2xl font-bold text-gray-800 tracking-tight">
                    <?= number_format($price); ?>
                </span>

                <!-- Custom Stacked Toman CSS Icon -->
                <div class="flex flex-col items-center justify-center text-[11px] font-black leading-[0.85] text-gray-600">
                    <span>ن</span>
                    <span class="-mb-[2px]">توما</span>
                </div>
            </div>

            <!-- Old Price Row (Strikethrough) -->
            <div class="h-5 w-full flex items-center gap-1 justify-start">
                <?php if ($is_on_sale && $regular_price > 0) : ?>
                    <del class="text-[13px] text-gray-400 font-medium decoration-gray-400">
                        <?= number_format($regular_price); ?>
                    </del>
                <?php endif; ?>
                <!-- Right Side (Start): Discount Badge -->
                <div class="w-12 shrink-0 text-right">
                    <?php if ($is_on_sale && $percentage > 0) : ?>
                        <div class="bg-[#ef394e] text-white text-[13px] font-bold px-2 py-0.5 rounded-lg inline-flex items-center justify-center">
                            <?= $percentage; ?>٪
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>