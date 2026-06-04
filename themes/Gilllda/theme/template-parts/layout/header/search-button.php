<?php
global $woo_active;
?>
<button
        class="w-10 h-10 left-4 lg:hidden fixed <?= $woo_active && ( is_singular('product') || is_shop() || is_product_category() ) || is_page_template('category.php') ? 'bottom-30' : 'bottom-16 '; ?> lg:bottom-4 bg-gray-700 hover:bg-gray-900 cursor-pointer justify-center border border-white/20 transition-all duration-700 items-center flex text-white rounded-sm z-[5] <?= $args['class'] ?? ''; ?> "
        @click="searchOpen = true" aria-label="open search modal">
    <?php
    $args = array(
        'size' => 16,
        'class' => $args['svgClass'] ?? ''
    );
    get_template_part('template-parts/svg/search', null, $args);
    ?>
</button>