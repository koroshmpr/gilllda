<?php
global $product;
$thumbnail_id = get_post_thumbnail_id();
$gallery_ids = $product->get_gallery_image_ids();

// 1. All slides for the MODAL (Featured + Gallery + Portfolio)
$product_images = array_merge([$thumbnail_id], $gallery_ids);
$product_count = count($product_images);

$portfolio_args = [
    'post_type' => 'portfolio',
    'meta_query' => [['key' => 'related_product', 'value' => get_the_id(), 'compare' => 'like']]
];
$portfolio_query = new WP_Query($portfolio_args);
$portfolio_images = [];
while ($portfolio_query->have_posts()) {
    $portfolio_query->the_post();
    if (has_post_thumbnail()) $portfolio_images[] = get_post_thumbnail_id();
}
wp_reset_postdata();

$all_slides = array_merge($product_images, $portfolio_images);

// 2. Grid items (excluding the featured image which is shown large)
$grid_images = array_merge($gallery_ids, $portfolio_images);
$total_grid_items = count($grid_images);
?>

<div x-data="{
        modalOpen: false,
        activeTab: 'product',
        portfolioStart: <?= $product_count; ?>,
        openAt(index) {
            this.modalOpen = true;

            this.$nextTick(() => {
                // 1. Initialize if it doesn't exist yet
                if (!window.modalSwiperInstance && typeof initModalGallery === 'function') {
                    initModalGallery();
                }

                // 2. Safely trigger the slide change
                setTimeout(() => {
                    // The '?.' (optional chaining) prevents the crash if Swiper isn't ready
                    window.modalSwiperInstance?.slideTo(index, 0);

                    // Safely update the thumbnail slider if it exists
                    window.modalSwiperInstance?.thumbs?.swiper?.slideTo(index, 0);
                }, 100); // Increased to 100ms to ensure the DOM is fully painted
            });
        }
    }"
     @slide-changed.window="activeTab = $event.detail.index >= $event.detail.portfolioStart ? 'customer' : 'product'"
     class="lg:col-span-5 xl:col-span-4 max-lg:sticky duration-500 transition-all top-12 flex flex-col max-lg:p-2 cursor-pointer"
     :class="scrolled ? 'max-md:scale-95 max-md:blur-[1px] max-md:grayscale-25': ''"
     dir="rtl">

    <div>
        <img
                @click="openAt(0)"
                fetchpriority="high"
                src="<?= get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>"
                alt="<?= the_title_attribute(['echo' => false]); ?>"
                class="object-contain w-full duration-500 aspect-video lg:aspect-square mb-1 overflow-hidden transition-all rounded-sm">

        <div class="grid grid-cols-4 gap-1">
            <?php
            foreach (array_slice($grid_images, 0, 4) as $index => $image_id) :
                // Slide index in all_slides is index + 1 (because featured image is 0)
                $slide_index = $index + 1;
                $is_fourth_item = ($index === 3);
                $has_more = ($total_grid_items > 4);
                ?>
                <div class="relative aspect-square overflow-hidden rounded-sm" @click="openAt(<?= $slide_index; ?>)">
                    <img class="object-contain w-full h-full"
                         fetchpriority="high"
                         src="<?= wp_get_attachment_image_url($image_id, 'medium') ?>"
                         alt="<?= get_the_title(); ?>">

                    <?php if ($is_fourth_item && $has_more) : ?>
                        <div class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center text-white backdrop-blur-[2px]">
                            <span class="text-lg font-black">+<?= $total_grid_items - 3 ?></span>
                            <span class="text-[9px]">بیشتر</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="modalOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-10" x-cloak>
            <div @click="modalOpen = false" class="absolute inset-0 bg-black/30 backdrop-blur-md"></div>
            <div x-show="modalOpen" x-transition
                 class="relative bg-white w-full max-w-[700px] rounded-xl flex flex-col h-[65vh] md:h-[85vh]"
                 @click.stop>

                <div class="border-b rounded-t-xl pt-5 flex justify-between border-gray-200 items-center bg-white z-10">
                    <div class="p-2 absolute lg:start-2 bottom-0 lg:top-0 max-lg:translate-y-full">
                        <button @click="modalOpen = false"
                                class="size-8 cursor-pointer flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded-lg">
                            <?php get_template_part('template-parts/svg/close', null, ['size' => '17']); ?>
                        </button>
                    </div>
                    <div class="flex gap-4 mt-auto mx-auto">
                        <?php
                        $btnCLass = 'pb-2 border-b-2 font-bold group/btn flex  cursor-pointer text-sm transition-all px-1';
                        $textClass = 'group-hover/btn:-translate-y-0.5 transition-all';
                        ?>
                        <button @click="activeTab = 'product'; window.modalSwiperInstance?.slideTo(0)"
                                :class="activeTab === 'product' ? 'text-primary border-primary' : 'text-gray-400 border-transparent'"
                                class="<?= $btnCLass; ?>">
                            <span :class="activeTab === 'product' ? '' : '<?= $textClass; ?>'">تصاویر محصول</span>
                        </button>

                        <?php if (!empty($portfolio_images)): ?>
                            <button @click="activeTab = 'customer'; window.modalSwiperInstance?.slideTo(portfolioStart)"
                                    :class="activeTab === 'customer' ? 'text-primary border-primary' : 'text-gray-400 border-transparent'"
                                    class="<?= $btnCLass; ?>">
                                <span :class="activeTab === 'customer' ? '' : '<?= $textClass; ?>'">عکس‌های خریداران</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex-1 overflow-hidden p-4 flex flex-col">
                    <div class="swiper main-modal-slider w-full flex-1" data-portfolio-start="<?= $product_count; ?>">
                        <div class="swiper-wrapper">
                            <?php foreach ($all_slides as $id) : ?>
                                <div class="swiper-slide flex items-center justify-center bg-gray-50/50">
                                    <img width="400" height="400"
                                         alt="<?= the_title(); ?>-image-<?= $id; ?>"
                                         x-bind:src="modalOpen ? '<?= wp_get_attachment_image_url($id, 'full'); ?>' : null"
                                         loading="lazy"
                                         class="size-full max-h-full max-w-full object-contain rounded-md transition-opacity duration-300">
                                    <div class="swiper-lazy-preloader swiper-lazy-preloader-black"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="swiper thumb-modal-slider w-full h-20 mt-6 px-10">
                        <div class="swiper-wrapper">
                            <?php foreach ($all_slides as $id) : ?>
                                <div class="swiper-slide cursor-pointer hover:scale-105 opacity-40 transition-all duration-300 [.swiper-slide-thumb-active&]:opacity-100 scale-95 [.swiper-slide-thumb-active&]:scale-100 border border-transparent [.swiper-slide-thumb-active&]:!border-primary !w-16 !h-16 bg-gray-100">
                                    <img width="62" height="62"
                                         alt="<?= the_title(); ?>-customers-<?= $id; ?>"
                                         x-bind:src="modalOpen ? '<?= wp_get_attachment_image_url($id, 'thumbnail'); ?>' : null"
                                         loading="lazy"
                                         class="w-full h-full object-cover">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </template>
</div>
