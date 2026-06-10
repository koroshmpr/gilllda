<?php
global $product;
?>
<div class="flex flex-col">
    <?php
    $is_variable = $product->is_type('variable');
    $is_on_sale  = $product->is_on_sale();

    if ($is_variable) {
        // For variable products, display the standard price range html
        // This avoids the '0' issue and lets WooCommerce handle the range
        echo '<span class="text-base lg:text-lg flex flex-col [&>ins]:no-underline [&>ins]:font-bold [&>del]:text-sm [&>del]:opacity-85 ">' . $product->get_price_html() . '</span>';
    } else {
        // For simple products, maintain your custom layout
        if ($is_on_sale) : ?>
            <del class="text-xs text-gray-400"><?= wc_price($product->get_regular_price()); ?></del>
            <span class="text-base lg:text-lg font-bold no-underline"><?= wc_price($product->get_sale_price()); ?></span>
        <?php else : ?>
            <span class="text-base lg:text-lg no-underline"><?= wc_price($product->get_regular_price()); ?></span>
        <?php endif;
    } ?>
</div>
