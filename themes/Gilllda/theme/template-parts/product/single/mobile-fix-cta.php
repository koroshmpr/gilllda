<?php
global $product;
if (!$product) return;
$attributes = $product->get_attributes();
$catMode = get_field('catalogue_mode', 'option');
$isSale = $product->is_on_sale();
$regular_price = (float)$product->get_regular_price();
$sale_price = (float)$product->get_sale_price();
$percentage = 0;

if ($isSale && $regular_price > 0) {
    $percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
}
?>
<nav :class="[intro ? '!translate-y-0' : '', scrollingDown ? 'max-lg:scale-85 !translate-y-2' : '']"
     class="fixed md:px-24 translate-y-full border border-gray-300 xl:hidden p-1 transition-all z-10 bg-gray-50 duration-500 flex justify-between items-center bottom-17 lg:bottom-0 inset-x-3 rounded-2xl">


    <div class="flex flex-col ps-2 flex-1 relative">
        <?php if ($percentage > 0) : ?>
            <div class="absolute top-0 -translate-y-1/2 end-3 z-0 flex items-center justify-center">
                <div class="bg-red-400 text-white px-2 py-1  rounded-tr-2xl rounded-bl-2xl rounded-tl-sm rounded-br-sm shadow-lg">
                    <span class="text-xs font-black leading-none"><?= $percentage; ?>% -</span>
                </div>
            </div>
        <?php endif; ?>
        <?php get_template_part('template-parts/product/single/product', 'price'); ?>
    </div>

    <?php
    if ($catMode) :
        get_template_part('template-parts/shop/shop-contact');
    else :?>

        <button @click="openForm = true" type="button" aria-label="open add to cart form"
                class="bg-primary px-5 py-3 group/add flex gap-x-2 justify-center hover:brightness-125 transition-all text-center rounded-xl text-white">
            <?= $product->is_purchasable() ? 'افزودن به سبد خرید' : $product->add_to_cart_text(); ?>
        </button>

    <?php
    endif; ?>
</nav>
<?php if (!$catMode): ?>
    <div
            @keydown.escape.window="openForm = false" id="openForm"
            :class="openForm ? '!z-50 !opacity-100' : ''"
            class="fixed inset-0 flex justify-center z-[-1] bg-black/20 opacity-0 items-end backdrop-blur-[1px] transition-all duration-300"
            @click.self="openForm = false">
        <div :class="openForm ? 'delay-200 !translate-y-0' : 'translate-y-full'"
             class="relative w-full p-5 pt-10 transition-all bg-white">
            <button @click="openForm = false" aria-label="close menu"
                    class=" text-black mb-2 absolute -top-4 start-3 bg-gray-100 border border-gray-200 p-2 rounded-xl">
                <?php
                $args = array(
                    'size' => '20',
                    'class' => '',
                );
                get_template_part('template-parts/svg/close', null, $args);
                ?>
            </button>
            <?php
            if (wp_is_mobile()):
                get_template_part('template-parts/product/single/product', 'buyform');
            endif;
            ?>
        </div>

    </div>
<?php endif; ?>
