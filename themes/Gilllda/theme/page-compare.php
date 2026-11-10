<?php
/**
 * Template Name: Compare Products
 *
 * Static shell (same for every visitor → page-cache safe). The list comes from localStorage
 * and the data from GET /wp-json/gilllda/v1/compare (javascript/compare.js → glComparePage).
 * Mobile: horizontally swipeable product columns, row labels stick to the start edge.
 */
get_header();
$gl_shop  = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
$gl_cols  = 'grid grid-cols-[repeat(var(--n),minmax(8.75rem,1fr))] lg:grid-cols-[11rem_repeat(var(--n),minmax(11rem,1fr))]';
$gl_chev  = '<svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>';
?>
<section class="container max-lg:px-4 pb-16 pt-6 lg:pt-10" x-data="glComparePage" dir="rtl">

    <header class="mb-6 flex flex-wrap items-end justify-between gap-4" data-reveal>
        <div class="min-w-0">
            <p class="text-xs font-bold text-secondary">کنار هم ببینید، بهتر انتخاب کنید</p>
            <h1 class="mt-1 text-2xl font-black text-primary lg:text-3xl"><?php the_title(); ?></h1>
            <p class="mt-2 text-sm text-gray-500" x-show="n" style="display:none">
                <b class="text-gray-800" x-text="fa(n)"></b> از <?php echo esc_html(gl_fa_num(GL_COMPARE_MAX)); ?> محصول
                <template x-if="n > 1"><span> · <span x-text="diffCount ? fa(diffCount) + ' ویژگی متفاوت' : 'ویژگی‌ها یکسان است'"></span></span></template>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2" x-show="n" style="display:none">
            <button type="button" role="switch" :aria-checked="highlight ? 'true' : 'false'" aria-checked="false" @click="highlight = !highlight" x-show="n > 1"
                    class="flex min-h-11 cursor-pointer items-center gap-2.5 rounded-full border border-gray-200 bg-white px-4 text-sm font-bold text-gray-700 transition hover:border-primary/40">
                <span class="relative h-6 w-10 shrink-0 rounded-full bg-gray-200 transition-colors" :class="highlight ? '!bg-secondary' : ''">
                    <span class="absolute top-1 start-1 size-4 rounded-full bg-white shadow transition-transform duration-300" :class="highlight ? '-translate-x-4' : ''"></span>
                </span>
                نمایش تفاوت‌ها
            </button>
            <a href="<?php echo esc_url($gl_shop); ?>" x-show="n < <?php echo (int) GL_COMPARE_MAX; ?>"
               class="flex min-h-11 items-center gap-1.5 rounded-full border border-gray-200 bg-white px-4 text-sm font-bold text-primary transition hover:border-primary/40">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                افزودن محصول
            </a>
            <button type="button" @click="clearAll()" class="flex min-h-11 cursor-pointer items-center rounded-full px-4 text-sm text-gray-500 transition hover:text-secondary">پاک کردن همه</button>
        </div>
    </header>

    <!-- Loading -->
    <div x-show="loading" class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-busy="true" aria-label="در حال بارگذاری">
        <?php for ($i = 0; $i < 4; $i++) : ?>
            <div class="<?php echo $i > 1 ? 'max-lg:hidden' : ''; ?> rounded-3xl border border-gray-100 p-3">
                <div class="aspect-square animate-pulse rounded-2xl bg-gray-100"></div>
                <div class="mt-3 h-4 w-3/4 animate-pulse rounded-full bg-gray-100"></div>
                <div class="mt-2 h-4 w-1/2 animate-pulse rounded-full bg-gray-100"></div>
                <div class="mt-6 space-y-3"><div class="h-3 animate-pulse rounded-full bg-gray-100"></div><div class="h-3 animate-pulse rounded-full bg-gray-100"></div><div class="h-3 w-2/3 animate-pulse rounded-full bg-gray-100"></div></div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Error -->
    <div x-show="!loading && failed && !n" style="display:none" class="mx-auto flex max-w-md flex-col items-center py-16 text-center">
        <p class="text-gray-600">دریافت اطلاعات محصولات انجام نشد.</p>
        <button type="button" @click="load()" class="mt-4 min-h-11 cursor-pointer rounded-full bg-primary px-6 text-sm font-bold text-white">تلاش دوباره</button>
    </div>

    <!-- Empty -->
    <div x-show="!loading && !failed && !n" style="display:none" class="mx-auto flex max-w-md flex-col items-center py-12 text-center lg:py-20">
        <span class="flex size-24 items-center justify-center rounded-full bg-primary/5 text-primary">
            <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
        </span>
        <h2 class="mt-6 text-xl font-black text-gray-900">هنوز محصولی برای مقایسه انتخاب نکرده‌اید</h2>
        <p class="mt-2 text-sm leading-7 text-gray-500">روی آیکون مقایسه در کارت یا صفحهٔ هر کیف بزنید تا حداکثر <?php echo esc_html(gl_fa_num(GL_COMPARE_MAX)); ?> محصول را کنار هم ببینید؛ قیمت، رنگ‌ها، ابعاد و موجودی.</p>
        <a href="<?php echo esc_url($gl_shop); ?>" class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-full bg-primary px-6 text-sm font-bold text-white transition hover:brightness-125">
            رفتن به فروشگاه <?php echo $gl_chev; // phpcs:ignore ?>
        </a>
    </div>

    <!-- Table -->
    <div x-show="!loading && n" style="display:none">
        <p x-show="n === 1" class="mb-3 rounded-2xl bg-primary/5 px-4 py-3 text-sm text-primary">
            برای مقایسه، دست‌کم یک محصول دیگر اضافه کنید.
            <a href="<?php echo esc_url($gl_shop); ?>" class="font-bold underline underline-offset-4">مشاهدهٔ محصولات</a>
        </p>

        <div class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-[0_8px_30px_-12px_rgba(16,37,66,0.15)]">
            <div x-ref="table" @scroll.passive="syncScroll()" class="snap-x snap-proximity overflow-x-auto overscroll-x-contain [scrollbar-width:thin]">
                <div class="<?php echo esc_attr($gl_cols); ?>" :style="{ '--n': n }" role="table" aria-label="جدول مقایسه محصولات">

                    <!-- Product header row -->
                    <div class="contents" role="row">
                        <div class="sticky start-0 z-[2] hidden flex-col justify-end gap-2 border-e border-gray-100 bg-white p-4 lg:flex" role="columnheader">
                            <span class="text-sm font-bold text-gray-900">مقایسهٔ <span x-text="fa(n)"></span> محصول</span>
                            <span class="text-xs leading-6 text-gray-500">برای پیدا کردن سریع تفاوت‌ها «نمایش تفاوت‌ها» را روشن کنید.</span>
                        </div>
                        <template x-for="p in products" :key="'h' + p.id">
                            <div class="relative flex snap-start flex-col border-e border-gray-100 p-3 last:border-e-0 lg:p-4" role="columnheader">
                                <button type="button" @click="remove(p)" :aria-label="'حذف ' + p.name + ' از مقایسه'" title="حذف"
                                        class="absolute end-1 top-1 z-[1] flex size-11 cursor-pointer items-center justify-center rounded-full text-gray-400 transition hover:bg-secondary/5 hover:text-secondary">
                                    <span class="flex size-8 items-center justify-center rounded-full border border-gray-200 bg-white/90">
                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                                    </span>
                                </button>
                                <a :href="p.url" class="group block aspect-square overflow-hidden rounded-2xl bg-gray-50" tabindex="-1" aria-hidden="true">
                                    <img :src="p.img" :alt="p.name" width="300" height="300" decoding="async" class="size-full object-contain p-2 transition duration-500 group-hover:scale-105">
                                </a>
                                <a :href="p.url" class="mt-3 line-clamp-2 text-sm font-bold leading-6 text-gray-900 transition-colors hover:text-primary lg:text-base" x-text="p.name"></a>
                            </div>
                        </template>
                    </div>
                    <div x-ref="head" class="col-span-full h-0" aria-hidden="true"></div>

                    <!-- Attribute rows -->
                    <template x-for="row in rows" :key="row.key">
                        <div class="contents" role="row">
                            <div class="col-span-full border-t border-gray-100 bg-gray-50/80 py-2 text-xs font-bold text-gray-500 transition-colors lg:sticky lg:start-0 lg:z-[1] lg:col-span-1 lg:border-e lg:bg-white lg:px-4 lg:py-4 lg:text-sm"
                                 :class="highlight && row.diff ? '!bg-amber-50 !text-gray-900' : ''" role="rowheader">
                                <span class="sticky start-0 inline-flex items-center gap-1.5 px-3 lg:px-0">
                                    <span x-text="row.label"></span>
                                    <span x-show="highlight && row.diff" class="size-1.5 rounded-full bg-secondary" aria-label="متفاوت"></span>
                                </span>
                            </div>
                            <template x-for="p in products" :key="row.key + '-' + p.id">
                                <div class="snap-start border-e border-gray-100 px-3 py-3 text-sm leading-7 text-gray-800 transition-opacity duration-300 last:border-e-0 lg:border-t lg:px-4 lg:py-4"
                                     :class="highlight ? (row.diff ? 'bg-amber-50/60' : 'opacity-40') : ''"
                                     role="cell" x-html="row.html(p)"></div>
                            </template>
                        </div>
                    </template>

                    <!-- CTA row -->
                    <div class="contents" role="row">
                        <div class="sticky start-0 hidden border-e border-t border-gray-100 bg-white lg:block"></div>
                        <template x-for="p in products" :key="'c' + p.id">
                            <div class="border-e border-t border-gray-100 p-3 last:border-e-0 lg:p-4" role="cell">
                                <a :href="p.url" class="flex min-h-11 items-center justify-center gap-1 whitespace-nowrap rounded-full bg-primary px-2 text-xs font-bold text-white transition hover:brightness-125 active:scale-[0.98] lg:gap-1.5 lg:px-3 lg:text-sm">
                                    <span x-text="p.stock === 'outofstock' ? 'مشاهدهٔ محصول' : 'مشاهده و خرید'"></span>
                                    <?php echo $gl_chev; // phpcs:ignore ?>
                                </a>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compact sticky product bar -->
    <div x-show="stuck && n > 0" style="display:none" aria-hidden="true"
         x-transition:enter="transition duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-x-0 z-40 border-b border-gray-100 bg-white/95 shadow-sm backdrop-blur" :style="{ top: top + 'px' }">
        <div class="container max-lg:px-4">
            <div x-ref="mini" class="overflow-hidden border-x border-transparent">
                <div class="<?php echo esc_attr($gl_cols); ?>" :style="{ '--n': n }">
                    <div class="hidden items-center px-4 text-xs font-bold text-gray-500 lg:flex">مقایسهٔ <span class="mx-1" x-text="fa(n)"></span> محصول</div>
                    <template x-for="p in products" :key="'m' + p.id">
                        <a :href="p.url" tabindex="-1" class="flex min-w-0 items-center gap-2 px-3 py-2 lg:px-4">
                            <img :src="p.thumb || p.img" alt="" width="40" height="40" class="size-10 shrink-0 rounded-xl bg-gray-50 object-contain">
                            <span class="min-w-0">
                                <span class="block truncate text-xs font-bold text-gray-900" x-text="p.name"></span>
                                <span class="block text-[11px] text-gray-500" x-text="money(p.price) + ' تومان'"></span>
                            </span>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
get_footer();
