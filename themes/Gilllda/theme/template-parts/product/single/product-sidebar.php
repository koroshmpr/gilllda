<?php
global $product;

if (!$product) return;
$isSale = $product->is_on_sale();
$catMode = get_field('catalogue_mode', 'option');
$stock_status = $product->get_stock_status();
$is_purchasable = $product->is_purchasable();
?>

<aside :class="scrollingDown ? 'shadow scale-95' :''"
       class="lg:col-span-3 origin-top duration-500 sticky bg-gray-100/50 border border-gray-200 rounded-xl max-xl:hidden <?= current_user_can('administrator') ? 'top-28' : 'top-20'; ?> p-3 flex flex-col justify-center transition-all">

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
    ?>
</aside>