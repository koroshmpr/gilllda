<?php
global $product;

if (!$product) {
    return;
}
$catMode = get_field('catalogue_mode', 'option');
$isArchive = $args['isArchive'] ?? false;
$product_id = $product->get_id();
$product_slug = $product->get_slug(); // ذخیره اسلاگ برای استفاده‌های بعدی

// Use these methods to handle both simple and variable products
$price = $product->get_price();
$regular_price = $product->get_regular_price();
$sale_price = $product->get_sale_price();

// If variable, get the minimum price so the math doesn't break
if ($product->is_type('variable')) {
    $price = $product->get_variation_price('min', true);
    $regular_price = $product->get_variation_regular_price('min', true);
    $sale_price = $product->get_variation_sale_price('min', true);
}

$is_on_sale = $product->is_on_sale();
$stock_status = $product->get_stock_status();

// تعریف آرایه آیکون بیرون از شروط تا همیشه در دسترس باشد
$args_svg = array(
    'size' => '20',
    'class' => 'group-hover/add:delay-100 text-white duration-300 rotate-45 group-hover/add:rotate-0 translate-x-2 opacity-0 transition-all group-hover/add:opacity-100 group-hover/add:translate-x-0'
);
?>

<div
        x-data="{ hover: false, imgLoaded: false }"
        @mouseenter="hover = true"
        @mouseleave="hover = false"
        class="group relative bg-gray-50 border rounded-md border-gray-200 max-lg:p-1 !my-0 transition-all duration-500 hover:border-primary/5 rtl <?= esc_attr($args['class'] ?? ''); ?>"
        dir="rtl"
>
    <?php
    $args_compare = [
        'id' => get_the_ID(),
        'class' => 'mr-auto rounded-full lg:opacity-0 bg-secondary/30 group-hover:opacity-100 lg:translate-y-2 group-hover:translate-y-0 !absolute z-1 block top-1.5 end-1.5 p-3 bg-black/5 shadow-sm backdrop-blur-sm border-white text-white hover:bg-gray-800 hover:text-white',
        'textClass' => 'hidden'
    ];
    get_template_part('template-parts/product/compare-button', null, $args_compare);
    ?>
    <a href="<?php the_permalink(); ?>" aria-label="add to cart the <?= esc_attr($product_slug); ?>"
       class="relative lg:aspect-[4/5] overflow-hidden flex <?= $isArchive ? '' : 'flex-col'; ?> lg:flex-col  rounded-sm bg-gray-50">

        <?php if ($is_on_sale && $stock_status === 'instock') :
            $percentage = round((((float)$regular_price - (float)$sale_price) / (float)$regular_price) * 100); ?>
            <span class="absolute top-2 right-2 z-[2] bg-primary text-white text-[10px] font-bold px-2.5 py-1 rounded-lg">
                <?= $percentage; ?>% تخفیف
            </span>
        <?php endif; ?>
        <?php if ($stock_status === 'outofstock') : ?>
            <span class="absolute top-2 start-2 z-[2] bg-red-600/90 text-white text-sm font-bold px-2.5 py-1 text-center rounded-lg">
                ناموجود
            </span>
        <?php endif; ?>

        <!-- Image Wrapper -->
        <div class="block relative size-full <?= $isArchive ? 'aspect-square max-lg:w-1/3' : ' aspect-square'; ?> lg:aspect-[3/4] overflow-hidden">

            <!-- SKELETON LOADER -->
            <div x-show="!imgLoaded"
                 x-transition.opacity.duration.500ms
                 class="absolute inset-0 z-[1] bg-gray-200 animate-pulse flex items-center justify-center">
            </div>

            <?php
            $image_id = $product->get_image_id();
            $image_url = wp_get_attachment_image_url($image_id, 'large');
            $gallery_ids = $product->get_gallery_image_ids();
            $hover_image_url = !empty($gallery_ids) ? wp_get_attachment_image_url($gallery_ids[0], 'large') : '';
            ?>

            <!-- MAIN IMAGE -->
            <img width="200" height="250"
                 x-ref="mainImg"
                 x-init="if ($refs.mainImg.complete) imgLoaded = true"
                 @load="imgLoaded = true"
                 loading="lazy"
                 src="<?= esc_url($image_url); ?>"
                 alt="<?= esc_attr($product_slug); ?>"
                 class="object-contain w-full !h-full !my-0 transition-all duration-700 transform group-hover:scale-110"
                 :class="hover ? '<?= $hover_image_url ? 'opacity-0' : ''; ?> scale-105' : 'opacity-100'"
            >

            <!-- HOVER IMAGE -->
            <?php if ($hover_image_url) : ?>
                <img width="200" height="250"
                     loading="lazy"
                     alt="<?= esc_attr($product_slug . '-hover'); ?>"
                     src="<?= esc_url($hover_image_url); ?>"
                     class="absolute inset-0 object-contain w-full !my-0 !h-full transition-all duration-700 transform-all opacity-0"
                     :class="hover ? 'opacity-100 scale-110 duration-500' : 'opacity-0'"
                >
            <?php endif; ?>

            <!-- DESKTOP ADD TO CART BUTTON -->
            <?php if (!$catMode && $stock_status === 'instock' && !wp_is_mobile()): ?>
                <button aria-label="add to cart the <?= esc_attr($product_slug); ?>"
                        @click.stop.prevent="window.location.href = '<?= $product->is_type('variable') ? get_the_permalink() : esc_url(add_query_arg('add-to-cart', $product_id)); ?>'"
                        class="absolute bottom-3 group/add overflow-hidden inset-x-3 duration-300 shadow-sm translate-y-12 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 z-20 flex items-center cursor-pointer justify-center gap-2 w-11/12 py-3 bg-primary text-white rounded-md font-bold hover:bg-secondary hover:text-white transition-all"
                >
                    <?php get_template_part('template-parts/svg/shop', null, $args_svg); ?>
                    <span class="group-hover/add:-translate-x-0 text-sm transition-all duration-300 translate-x-3">افزودن به سبد</span>
                </button>
            <?php endif; ?>
        </div>

        <!-- MOBILE ADD TO CART BUTTON -->
        <?php if (!$catMode && $stock_status === 'instock' && wp_is_mobile() && $isArchive ): ?>
            <button aria-label="add to cart the <?= esc_attr($product_slug); ?>"
                    @click.stop.prevent="window.location.href = '<?= $product->is_type('variable') ? get_the_permalink() : esc_url(add_query_arg('add-to-cart', $product_id)); ?>'"
                    class="absolute bottom-2 lg:hidden group/add overflow-hidden end-2 duration-300 shadow-sm z-1 flex items-center cursor-pointer justify-center gap-2 w-fit p-3 rounded-lg bg-primary text-white transition-all"
            >
                <?php get_template_part('template-parts/svg/shop', null, $args_svg); ?>
                <span class="group-hover/add:-translate-x-0 text-xs transition-all duration-300 translate-x-3">افزودن به سبد</span>
            </button>
        <?php endif; ?>

        <div class="flex min-h-20 lg:min-h-14 p-2 lg:px-4 max-lg:flex-col max-lg:gap-2 items-center justify-between">

            <!-- Title & Colors Wrapper -->
            <div class="flex flex-col gap-1 items-center lg:items-start w-full">
                 <span class="text-md font-bold text-gray-800 line-clamp-1 w-full text-right">
                    <?php the_title(); ?>
                 </span>

                <?php
                // Display Product Colors
                $colors = wc_get_product_terms($product_id, 'pa_color', array('fields' => 'all'));
                if (!empty($colors) && !is_wp_error($colors)) : ?>
                    <div class="flex items-center gap-1 mt-0.5">
                        <?php foreach ($colors as $color) :
                            $acf_color = get_field('color', $color->taxonomy . '_' . $color->term_id);
                            if ($acf_color) : ?>
                                <span class="rounded-full size-3.5 border border-gray-300 shadow-sm block"
                                      title="<?php echo esc_attr($color->name); ?>"
                                      style="background-color: <?php echo esc_attr($acf_color); ?>;">
                                 </span>
                            <?php endif;
                        endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Price -->
            <?php if ($stock_status === 'instock'):
                $reg_price_val = !empty($regular_price) ? (float)$regular_price : 0;
                $sale_price_val = !empty($sale_price) ? (float)$sale_price : 0;
                $price_val = !empty($price) ? (float)$price : 0;
                ?>
                <div class="flex flex-col w-full text-left items-end">
                    <?php if ($is_on_sale && $reg_price_val > 0) : ?>
                        <del class="text-xs text-gray-700 font-medium"><?= number_format($reg_price_val); ?>
                            <span class="text-[10px] text-gray-600 font-bold">تومان</span>
                        </del>
                        <div class="flex items-baseline gap-1">
                            <span class="text-base lg:text-lg font-black text-gray-900"><?= number_format($sale_price_val); ?></span>
                            <span class="text-[10px] text-gray-600 font-bold">تومان</span>
                        </div>
                    <?php elseif ($price_val > 0) : ?>
                        <div class="flex items-baseline gap-1">
                            <span class="text-lg font-black text-gray-900"><?= number_format($price_val); ?></span>
                            <span class="text-[10px] text-gray-500 font-bold">تومان</span>
                        </div>
                    <?php else : ?>
                        <span class="text-xs text-gray-500">تماس بگیرید</span>
                    <?php endif; ?>
                </div>
            <?php else :
                echo '-';
            endif; ?>
        </div>
    </a>
</div>