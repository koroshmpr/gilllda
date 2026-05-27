<?php
add_shortcode('out_stock_product', 'out_stock_product_list_dropdown');
function out_stock_product_list_dropdown()
{
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'meta_query' => array(
            array(
                'key' => '_stock_status',
                'value' => 'instock',
                'compare' => '!=',
            )
        ),
    );
    $query = new WP_query($args);
    if ($query->have_posts()) :
        $products = $query->posts; ?>
        <label class="pb-2 block" for="product_list">محصولات ناموجود:</label>
        <select id="product_list" class="bg-white w-full rounded-md p-2 mb-3 text-black">
            <option selected value="">انتخاب محصول...</option>
            <?php foreach ($products as $product)  :?>
                <option value="<?= $product->post_title; ?>">
                    <?= $product->post_title; ?>
                </option>
            <?php endforeach; ?>
        </select>

    <?php
    else :
        echo '<p class="absolute inset-0 rounded-lg bg-primary/80 border border-white/15 select-none flex flex-col max-lg:gap-2 items-center justify-center backdrop-blur-3xl">
                <span class="text-sm opacity-50">همه محصولات موجود هستند </span>
                <span class="text-xl lg:text-2xl font-bold">محصولی برای نمایش وجود ندارد!</span>
            </p>';
    endif;
}?>
