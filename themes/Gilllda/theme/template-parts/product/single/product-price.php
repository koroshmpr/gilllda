<?php
global $product;
$attributes = $product->get_attributes();
?>
<div class="flex flex-col">
    <?php
    $isSale = $product->is_on_sale();
    if ($isSale) :?>
        <span><?= wc_price($product->get_sale_price()); ?></span>
    <?php endif;
    if ($attributes):?>
        <span class="text-xs lg:text-sm flex flex-wrap">
                    <?= $product->get_price_html(); ?>
                </span>
    <?php else:
        ?>
        <span class="<?= $isSale ? 'opacity-50 line-through lg:text-xl' : ' lg:text-2xl'; ?>">
                    <?= wc_price($product->get_regular_price()); ?>
                </span>
    <?php endif; ?>
</div>
