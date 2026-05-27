<?php
global $product;

if (!$product) return;
$isSale = $product->is_on_sale();
$catMode = get_field('catalogue_mode', 'option');
$stock_status = $product->get_stock_status();
$is_purchasable = $product->is_purchasable();
?>

<aside class="lg:col-span-3 duration-500 sticky bg-primary/5 border border-primary/20 rounded-sm max-xl:hidden <?= current_user_can('administrator') ? 'top-28' : 'top-20'; ?> p-3 flex flex-col justify-center transition-all">

    <!-- Header: Image and Price -->
    <?php if ($stock_status === 'instock'): ?>
        <div class="flex gap-3 items-center mb-3">
            <img :class="scrolled ? 'w-16' : '!w-0'"
                 class="object-cover w-0 !h-16 rounded-sm duration-500 aspect-square transition-all"
                 src="<?php the_post_thumbnail_url('large'); ?>" alt="<?php the_title_attribute(); ?>"/>
            <?php get_template_part('template-parts/product/single/product', 'price'); ?>
        </div>
    <?php endif; ?>

    <?php if ($catMode) : ?>
        <?php get_template_part('template-parts/shop/shop-contact'); ?>

    <?php else : ?>
        <?php if ($is_purchasable & !wp_is_mobile()) :
            get_template_part('template-parts/product/single/product', 'buyform');
        endif;
    endif;
    get_template_part('template-parts/shop/property', null, ['class' => 'max-lg:hidden']);

    $regular_price = (float)$product->get_regular_price();
    $sale_price = (float)$product->get_sale_price();
    $percentage = 0;

    if ($isSale && $regular_price > 0) {
        $percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
    }
    if ($percentage > 0 && $stock_status === 'instock') : ?>
        <div class="absolute -top-2 -left-2 z-20 flex items-center justify-center">
            <div
                    class="bg-red-400 text-white px-3 py-1.5 rounded-tr-2xl rounded-bl-2xl rounded-tl-sm rounded-br-sm shadow-lg">
                <span class="text-[10px] font-medium block leading-none opacity-90">تخفیف</span>
                <span class="text-lg font-black leading-none"><?= $percentage; ?>%</span>
            </div>
        </div>
    <?php endif; ?>
</aside>