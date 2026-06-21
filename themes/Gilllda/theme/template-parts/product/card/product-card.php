<?php
global $product;

if (!$product) {
    return;
}
$catMode = get_field('catalogue_mode', 'option');
$isArchive = $args['isArchive'] ?? false;
$product_id = $product->get_id();
$product_slug = $product->get_slug();

// Base Pricing
$price = $product->get_price();
$regular_price = $product->get_regular_price();
$sale_price = $product->get_sale_price();

// Variations Logic for Alpine.js
$available_variations = [];
if ($product->is_type('variable')) {
    $price = $product->get_variation_price('min', true);
    $regular_price = $product->get_variation_regular_price('min', true);
    $sale_price = $product->get_variation_sale_price('min', true);

    // Fetch variations to pass to Alpine
    $variations = $product->get_available_variations();
    foreach ($variations as $var) {
        $available_variations[] = [
            'id' => $var['variation_id'],
            'price' => number_format((float)$var['display_price']),
            'regular_price' => number_format((float)$var['display_regular_price']),
            'is_on_sale' => $var['display_price'] < $var['display_regular_price'],
            'percentage' => $var['display_regular_price'] > 0 ? round(((($var['display_regular_price'] - $var['display_price']) / $var['display_regular_price']) * 100)) : 0,
            'image' => $var['image']['url'] ?? '',
            'attributes' => $var['attributes'] // e.g., attribute_pa_color
        ];
    }
}

$is_on_sale = $product->is_on_sale();
$stock_status = $product->get_stock_status();

// Variables for initial display
$reg_price_val = !empty($regular_price) ? (float)$regular_price : 0;
$sale_price_val = !empty($sale_price) ? (float)$sale_price : 0;
$price_val = !empty($price) ? (float)$price : 0;
$percentage = ($reg_price_val > 0) ? round((($reg_price_val - $sale_price_val) / $reg_price_val) * 100) : 0;

$args_svg = array(
    'size' => '20',
    'class' => 'group-hover/add:delay-100 text-white duration-300 rotate-45 group-hover/add:rotate-0 translate-x-2 opacity-0 transition-all group-hover/add:opacity-100 group-hover/add:translate-x-0'
);

// FIX: Optimized image sizes
$image_id = $product->get_image_id();
$image_url = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');
$gallery_ids = $product->get_gallery_image_ids();
$hover_image_url = !empty($gallery_ids) ? wp_get_attachment_image_url($gallery_ids[0], 'woocommerce_thumbnail') : '';

// FIX: Dynamic loading attributes based on loop position
$is_eager = $args['eager'] ?? false;
$loading_attr = $is_eager ? 'eager' : 'lazy';
$fetch_priority = $is_eager ? 'fetchpriority="high"' : '';
?>

<div
        x-data="{
        hover: false,
        imgLoaded: false,
        variations: <?= htmlspecialchars(json_encode($available_variations), ENT_QUOTES, 'UTF-8'); ?>,
        selectedVar: null,
        mainImageUrl: '<?= esc_url($image_url); ?>',
        currentPrice: '<?= number_format($sale_price_val > 0 ? $sale_price_val : $price_val); ?>',
        currentRegularPrice: '<?= number_format($reg_price_val); ?>',
        isOnSale: <?= $is_on_sale ? 'true' : 'false'; ?>,
        currentDiscount: '<?= $percentage; ?>',
        addToCartUrl: '<?= $product->is_type('variable') ? get_the_permalink() : esc_url(add_query_arg('add-to-cart', $product_id)); ?>',

        selectColor(termSlug) {
            if (this.variations.length === 0) return;

            // Find the variation matching the selected color
            let match = this.variations.find(v => v.attributes['attribute_pa_color'] === termSlug);
            if (match) {
                this.selectedVar = match.id;
                this.currentPrice = match.price;
                this.currentRegularPrice = match.regular_price;
                this.isOnSale = match.is_on_sale;
                this.currentDiscount = match.percentage;
                this.addToCartUrl = '?add-to-cart=' + match.id;
                if(match.image) {
                    this.mainImageUrl = match.image;
                }
            }
        }
    }"
        @mouseenter="hover = true"
        @mouseleave="hover = false"
        class="group relative bg-white border rounded-lg border-gray-100 max-lg:border-gray-200 !my-0 transition-all duration-500 hover:shadow-lg rtl <?= esc_attr($args['class'] ?? ''); ?>"
        dir="rtl"
>
    <?php
    $args_compare = [
        'id' => get_the_ID(),
        'class' => 'mr-auto rounded-full lg:opacity-0 group-hover:opacity-100 lg:translate-y-2 group-hover:translate-y-0 !absolute z-10 block top-2 end-2 p-2 lg:p-2.5 backdrop-blur-sm !border-gray-300 text-primary hover:bg-gray-800 hover:text-white transition-all',
        'textClass' => 'hidden'
    ];
    get_template_part('template-parts/product/compare-button', null, $args_compare);
    ?>

    <a :href="addToCartUrl" aria-label="View <?= esc_attr($product_slug); ?>"
       class="relative overflow-hidden flex <?= $isArchive ? 'max-lg:flex-row' : ''; ?> flex-col rounded-t-lg bg-gray-50/50">

        <?php if ($stock_status === 'outofstock') : ?>
            <span class="absolute top-2 start-2 z-[2] bg-red-600/90 text-white text-sm font-bold px-2.5 py-1 text-center rounded-lg">
                ناموجود
            </span>
        <?php endif; ?>

        <div class="block bg-white relative <?= $isArchive ? 'max-lg:w-1/3' : ''; ?> size-full aspect-square overflow-hidden p-4">
            <div x-show="!imgLoaded" x-transition.opacity.duration.500ms
                 class="absolute inset-0 z-[1] bg-gray-200 animate-pulse flex items-center justify-center"></div>

            <img width="300" height="300"
                 x-ref="mainImg"
                 x-init="if ($refs.mainImg.complete) imgLoaded = true"
                 @load="imgLoaded = true"
                 loading="<?= esc_attr($loading_attr); ?>"
                <?= $fetch_priority; ?>
                 decoding="<?= $is_eager ? 'sync' : 'async'; ?>"
                 src="<?= esc_url($image_url); ?>"
                 :src="mainImageUrl"
                 alt="<?= esc_attr($product_slug); ?>"
                 class="object-contain size-full !my-0 transition-all duration-700 transform group-hover:scale-105"
                 :class="hover ? '<?= $hover_image_url ? 'opacity-0' : ''; ?> scale-105' : 'opacity-100'"
            >

            <?php if ($hover_image_url) : ?>
                <img width="300" height="300"
                     loading="<?= esc_attr($loading_attr); ?>"
                     decoding="<?= $is_eager ? 'sync' : 'async'; ?>"
                     alt="<?= esc_attr($product_slug . '-hover'); ?>"
                     src="<?= esc_url($hover_image_url); ?>"
                     class="absolute inset-0 size-full aspect-square object-contain transition-all duration-700 opacity-0"
                     :class="hover ? 'opacity-100 scale-105 duration-500' : 'opacity-0'">
            <?php endif; ?>

            <?php if (!$catMode && $stock_status === 'instock' && !wp_is_mobile()): ?>
                <button aria-label="add to cart"
                        @click.stop.prevent="window.location.href = addToCartUrl"
                        class="absolute bottom-0 group/add overflow-hidden inset-x-4 duration-300 shadow-md translate-y-full opacity-0 group-hover:translate-y-0 group-hover:opacity-100 z-20 flex items-center justify-center gap-2 py-3 bg-secondary text-white cursor-pointer rounded-lg font-bold hover:brightness-125 transition-all"
                >
                    <?php get_template_part('template-parts/svg/shop', null, $args_svg); ?>
                    <span class="text-sm translate-x-4 group-hover/add:translate-x-0 transition-all duration-300">افزودن به سبد</span>
                </button>
            <?php endif; ?>
        </div>

        <div class="flex relative <?= $isArchive ? 'max-lg:w-2/3' : 'items-center max-lg:gap-1'; ?> max-lg:flex-col justify-between p-3 lg:px-4 gap-3">
            <div class="flex flex-col <?= $isArchive ? '' : 'max-lg:flex-row max-lg:justify-between w-full'; ?> gap-1">

                <p class="text-sm lg:text-base font-semibold text-gray-800 line-clamp-1 w-full text-right leading-relaxed">
                    <?php the_title(); ?>
                </p>

                <?php
                $colors = wc_get_product_terms($product_id, 'pa_color', array('fields' => 'all'));
                if (!empty($colors) && !is_wp_error($colors)) : ?>
                    <div class="flex items-center gap-1" @click.stop.prevent>
                        <?php foreach ($colors as $color) :
                            $acf_color = get_field('color', $color->taxonomy . '_' . $color->term_id);
                            if ($acf_color) : ?>
                                <button
                                        @click="selectColor('<?= esc_js($color->slug); ?>')"
                                        class="rounded-sm size-6 cursor-pointer border border-gray-300 shadow-sm transition-all focus:outline-none hover:scale-110"
                                        :class="selectedVar && variations.find(v => v.id === selectedVar)?.attributes['attribute_pa_color'] === '<?= esc_js($color->slug); ?>' ? 'ring-2 ring-offset-1 ring-gray-400' : ''"
                                        title="<?php echo esc_attr($color->name); ?>"
                                        style="background-color: <?php echo esc_attr($acf_color); ?>;"
                                        aria-label="Select <?= esc_attr($color->name); ?>">
                                </button>
                            <?php endif;
                        endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="h-4"></div>
                <?php endif; ?>
            </div>

            <div class="flex justify-between items-start  <?= $isArchive ? 'max-lg:items-center' : ' flex-wrap'; ?> mt-1 min-h-[50px]">
                <?php if ($stock_status === 'instock'): ?>
                    <div class="flex flex-col items-end gap-0.5 <?= $isArchive ? '' : 'w-full max-lg:items-center'; ?>">
                        <div class="flex items-center gap-1.5">
                            <span class="text-lg lg:text-[19px] font-bold text-gray-800 tracking-tight"
                                  x-text="currentPrice"></span>

                            <div class="flex flex-col items-center justify-center text-[9px] font-black leading-[0.85] text-gray-600 mt-1">
                                <span>ن</span>
                                <span class="-mb-[2px]">توما</span>
                            </div>
                        </div>
                        <div class="h-4 pe-1 flex gap-1 items-center">
                            <template x-if="isOnSale && currentDiscount > 0">
                                <div class="bg-secondary text-white text-[12px] font-bold px-2 py-0.5 rounded-md inline-flex items-center justify-center">
                                    <span x-text="currentDiscount"></span>٪
                                </div>
                            </template>
                            <template x-if="isOnSale && currentDiscount > 0">
                                <del class="text-[12px] text-gray-500 font-medium decoration-red-600"
                                     x-text="currentRegularPrice"></del>
                            </template>
                        </div>
                    </div>
                    <?php if (!$catMode && wp_is_mobile()): ?>
                        <button aria-label="add to cart"
                                @click.stop.prevent="window.location.href = addToCartUrl"
                                class="lg:hidden <?= $isArchive ? 'w-fit py-2' : 'w-full mt-3 py-3'; ?> group/add overflow-hidden duration-300 shadow-sm z-1 flex items-center cursor-pointer justify-center gap-2 px-1 rounded-lg bg-primary text-white transition-all"
                        >
                            <?php get_template_part('template-parts/svg/shop', null, $args_svg); ?>
                            <span class="group-hover/add:-translate-x-0 text-xs transition-all text-nowrap duration-300 translate-x-3">افزودن به سبد</span>
                        </button>
                    <?php endif; ?>
                <?php else : ?>
                    <span class="text-sm text-gray-500 w-full text-left">ناموجود</span>
                <?php endif; ?>
            </div>
        </div>
    </a>
</div>