<?php
/**
 * WooCommerce Cart AJAX Fragments Hooks
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_add_to_cart_fragments', 'gilllda_cart_fragments' );
function gilllda_cart_fragments( $fragments ) {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return $fragments;
    }

    // 1. Update Header Cart Count Badge
    ob_start();
    $cart_count = WC()->cart->get_cart_contents_count() ?? 0;
    ?>
    <span class="header-cart-count absolute top-0 start-2 lg:start-0 lg:translate-x-1/2 lg:-translate-y-1/2 translate-y-1 bg-secondary/80 text-white flex leading-auto justify-center items-center pt-1 p-0.5 rounded-sm text-xs size-4">
         <?= $cart_count; ?>
    </span>
    <?php
    $fragments['span.header-cart-count'] = ob_get_clean();

    // 2. Update Cart Modal Inner Content
    ob_start();
    ?>
    <div class="cart-modal-inner flex flex-col h-full">
        <?php
        $cart_count = WC()->cart->get_cart_contents_count() ?? 0;
        $is_empty = $cart_count == 0;
        ?>
        <div class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
            <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                سبد خرید شما
                <span class="text-xs bg-red-500 text-white px-2 py-0.5 rounded-md shadow-sm"><?= $cart_count; ?></span>
            </h3>
            <button @click="cart = false" class="p-1.5 text-gray-400 bg-gray-50 rounded-lg hover:text-gray-700 hover:bg-gray-100 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-3">
            <?php if ($is_empty) : ?>
                <div class="flex flex-col items-center justify-center h-full text-gray-400 space-y-4">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-sm">
                        <svg class="w-10 h-10 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500">سبد خرید شما خالی است!</p>
                </div>
            <?php else : ?>
                <div class="flex flex-col gap-3">
                    <?php
                    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                        $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);

                        if ($_product && $_product->exists() && $cart_item['quantity'] > 0) {
                            $product_name = $_product->get_name();
                            $thumbnail = $_product->get_image('woocommerce_gallery_thumbnail', ['class' => 'w-full h-full object-cover']);
                            $product_permalink = $_product->is_visible() ? $_product->get_permalink($cart_item) : '#';

                            $item_price_raw = wc_get_price_to_display($_product);
                            $remove_url = wc_get_cart_remove_url($cart_item_key);
                            ?>
                            <div class="flex items-stretch gap-3 p-3 bg-white rounded-2xl border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all group relative">

                                <a href="<?= esc_url($product_permalink); ?>" class="shrink-0 w-24 h-24 relative overflow-hidden rounded-xl bg-gray-50 border border-gray-50">
                                    <?= $thumbnail; ?>
                                </a>

                                <div class="flex flex-col flex-1 justify-between py-0.5">
                                    <div class="flex items-start justify-between gap-2">
                                        <a href="<?= esc_url($product_permalink); ?>"
                                           class="text-sm font-semibold text-gray-800 leading-6 line-clamp-2 hover:text-red-500 transition-colors">
                                            <?= wp_kses_post($product_name); ?>
                                        </a>
                                        <a href="<?= esc_url($remove_url); ?>" aria-label="حذف محصول" class="text-gray-300 hover:text-red-500 hover:bg-red-50 p-1.5 -m-1.5 rounded-lg transition-all shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </a>
                                    </div>

                                    <div class="flex items-end justify-between mt-2">
                                        <div class="flex items-center gap-1.5 bg-gray-50 px-2 py-1 rounded-lg border border-gray-100">
                                            <span class="text-[11px] text-gray-500">تعداد:</span>
                                            <span class="text-xs font-bold text-gray-700"><?= $cart_item['quantity']; ?></span>
                                        </div>

                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[15px] font-bold text-gray-800 tracking-tight">
                                                <?= number_format((float)$item_price_raw); ?>
                                            </span>
                                            <div class="flex flex-col items-center justify-center text-[9px] font-black leading-[0.85] text-gray-500 mt-0.5">
                                                <span>ن</span>
                                                <span class="-mb-[2px]">توما</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$is_empty) :
            $subtotal_raw = WC()->cart->subtotal;
            ?>
            <div class="border-t border-gray-200 p-4 bg-white shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.03)] z-20">
                <div class="flex justify-between items-center mb-4 px-1">
                    <span class="text-sm font-medium text-gray-500">مبلغ قابل پرداخت:</span>

                    <div class="flex items-center gap-1.5">
                        <span class="font-bold text-lg text-gray-900 tracking-tight">
                            <?= number_format((float)$subtotal_raw); ?>
                        </span>
                        <div class="flex flex-col items-center justify-center text-[10px] font-black leading-[0.85] text-gray-500 mt-1">
                            <span>ن</span>
                            <span class="-mb-[2px]">توما</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <a href="<?= esc_url(wc_get_checkout_url()); ?>"
                       class="flex items-center justify-center bg-red-500 hover:bg-red-600 text-white py-3 px-4 rounded-xl font-semibold transition-all shadow-sm shadow-red-200 text-sm">
                        ثبت و پرداخت
                    </a>
                    <a href="<?= esc_url(wc_get_cart_url()); ?>"
                       class="flex items-center justify-center bg-white border-2 border-gray-100 hover:border-gray-200 hover:bg-gray-50 text-gray-700 py-3 px-4 rounded-xl font-semibold transition-all text-sm">
                        مشاهده سبد
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $fragments['div.cart-modal-inner'] = ob_get_clean();

    return $fragments;
}
