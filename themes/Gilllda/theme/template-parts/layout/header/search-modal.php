<?php
/**
 * Header search modal (instant search). Logic: javascript/search.js → glSearch.
 * Opened by the body-level `searchOpen` flag. Markup is identical for everyone (page-cache safe);
 * popular suggestions are server-rendered from a cached transient.
 */
$gl_popular = function_exists('gl_search_popular') ? gl_search_popular() : ['cats' => [], 'colors' => []];
$gl_chip    = 'inline-flex min-h-10 items-center gap-2 rounded-full border border-gray-200 bg-white px-4 text-sm text-gray-700 transition hover:border-primary/40 hover:text-primary';
$gl_label   = 'mb-3 flex items-center justify-between text-xs font-bold text-gray-500';
$gl_row     = 'flex min-h-14 items-center gap-3 rounded-2xl p-2 transition-colors hover:bg-gray-50';

ob_start(); ?>
<div class="flex flex-col gap-6">
    <?php if ($gl_popular['cats']) : ?>
        <div>
            <p class="<?php echo esc_attr($gl_label); ?>">دسته‌بندی‌های پرطرفدار</p>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($gl_popular['cats'] as $c) : ?>
                    <a href="<?php echo esc_url($c['url']); ?>" class="<?php echo esc_attr($gl_chip); ?>"><?php echo esc_html($c['name']); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($gl_popular['colors']) : ?>
        <div>
            <p class="<?php echo esc_attr($gl_label); ?>">جستجو بر اساس رنگ</p>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($gl_popular['colors'] as $c) : ?>
                    <button type="button" @click="pick(<?php echo esc_attr(wp_json_encode($c['name'])); ?>)" class="<?php echo esc_attr($gl_chip); ?> cursor-pointer">
                        <span class="size-4 rounded-full border border-gray-300" style="background:<?php echo esc_attr($c['hex'] ?: '#e5e7eb'); ?>"></span>
                        <?php echo esc_html($c['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
$gl_suggest = ob_get_clean();
?>
<div id="searchModal" x-data="glSearch" x-show="searchOpen" style="display:none"
     x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[80] <?php echo is_admin_bar_showing() ? 'top-[46px] md:top-8' : ''; ?>" role="dialog" aria-modal="true" aria-label="جستجو در گیلدا" dir="rtl"
     @keydown.escape.window="searchOpen && close()">
    <div class="absolute inset-0 bg-primary/30 backdrop-blur-sm max-lg:hidden" @click="close()"
         x-show="searchOpen" x-transition.opacity.duration.200ms></div>

    <div x-show="searchOpen"
         x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
         class="relative flex h-full flex-col bg-white pt-[env(safe-area-inset-top)] lg:mx-auto lg:mt-[8vh] lg:h-auto lg:max-h-[80vh] lg:w-full lg:max-w-[48rem] lg:overflow-hidden lg:rounded-3xl lg:border lg:border-gray-100 lg:pt-0 lg:shadow-2xl">

        <!-- Input -->
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" @submit="submit($event)"
              class="flex shrink-0 items-center gap-2 border-b border-gray-100 p-3 lg:p-4">
            <div class="relative min-w-0 flex-1">
                <label for="gl-search-input" class="sr-only">جستجو</label>
                <svg class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                <input x-ref="input" id="gl-search-input" type="search" name="s" x-model="q" @input="onInput()"
                       @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter="enter($event)"
                       autocomplete="off" autocapitalize="off" spellcheck="false" enterkeyhint="search" maxlength="64"
                       placeholder="جستجوی کیف، رنگ، دسته‌بندی یا مقاله…"
                       role="combobox" aria-autocomplete="list" aria-controls="gl-search-list"
                       :aria-expanded="res && ready ? 'true' : 'false'" aria-expanded="false"
                       :aria-activedescendant="active >= 0 ? 'gls-opt-' + active : ''"
                       class="h-13 w-full rounded-2xl border border-gray-200 bg-gray-50 pe-12 ps-12 text-base text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-primary/40 focus:bg-white focus:ring-4 focus:ring-primary/5 [&::-webkit-search-cancel-button]:hidden">
                <button type="button" x-show="q" style="display:none" @click="clear()" aria-label="پاک کردن عبارت"
                        class="absolute end-1 top-1/2 flex size-11 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-gray-400 hover:text-gray-700">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
            <button type="button" @click="close()" class="flex min-h-11 shrink-0 cursor-pointer items-center gap-1.5 rounded-full px-3 text-sm font-bold text-gray-600 transition hover:bg-gray-100" aria-label="بستن جستجو">
                <span class="lg:hidden">بستن</span>
                <kbd class="hidden rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 font-sans text-[11px] text-gray-500 lg:inline">Esc</kbd>
            </button>
        </form>

        <!-- Body -->
        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 pb-[calc(1.5rem+env(safe-area-inset-bottom))] pt-4 lg:px-5 lg:pb-5">

            <!-- Idle: recent + popular -->
            <div x-show="!ready" class="flex flex-col gap-6">
                <div x-show="recent.length" style="display:none">
                    <p class="<?php echo esc_attr($gl_label); ?>">
                        <span>جستجوهای اخیر</span>
                        <button type="button" @click="clearRecent()" class="min-h-9 cursor-pointer rounded-full px-2 font-normal text-gray-400 hover:text-secondary">پاک کردن</button>
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="s in recent" :key="s">
                            <span class="inline-flex min-h-10 items-center rounded-full border border-gray-200 bg-white text-sm text-gray-700">
                                <button type="button" @click="pick(s)" class="flex min-h-10 cursor-pointer items-center gap-2 ps-4 pe-1">
                                    <svg class="size-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    <span x-text="s"></span>
                                </button>
                                <button type="button" @click="removeRecent(s)" class="flex size-10 cursor-pointer items-center justify-center rounded-full text-gray-300 hover:text-secondary" :aria-label="'حذف ' + s + ' از جستجوهای اخیر'">
                                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                                </button>
                            </span>
                        </template>
                    </div>
                </div>
                <?php echo $gl_suggest; // phpcs:ignore -- escaped above ?>
            </div>

            <!-- Loading skeleton -->
            <div x-show="ready && loading" style="display:none" class="flex flex-col gap-2" aria-hidden="true">
                <?php for ($i = 0; $i < 4; $i++) : ?>
                    <div class="flex items-center gap-3 p-2">
                        <span class="size-14 shrink-0 animate-pulse rounded-xl bg-gray-100"></span>
                        <span class="flex flex-1 flex-col gap-2"><span class="h-3.5 w-2/3 animate-pulse rounded-full bg-gray-100"></span><span class="h-3 w-1/3 animate-pulse rounded-full bg-gray-100"></span></span>
                    </div>
                <?php endfor; ?>
            </div>

            <!-- Error -->
            <p x-show="ready && !loading && failed" style="display:none" class="py-10 text-center text-sm text-gray-500">
                اتصال برقرار نشد. <button type="button" class="cursor-pointer font-bold text-primary underline" @click="onInput()">تلاش دوباره</button>
            </p>

            <!-- Empty -->
            <div x-show="ready && !loading && !failed && empty" style="display:none" class="py-6">
                <div class="mb-8 text-center">
                    <p class="text-base font-bold text-gray-800">نتیجه‌ای برای «<span x-text="term"></span>» پیدا نشد</p>
                    <p class="mt-1 text-sm text-gray-500">املای عبارت را بررسی کنید یا کلمهٔ کوتاه‌تری امتحان کنید.</p>
                </div>
                <?php echo $gl_suggest; // phpcs:ignore -- escaped above ?>
            </div>

            <!-- Results -->
            <div x-show="ready && !loading && !failed && res && !empty" style="display:none" id="gl-search-list" role="listbox" aria-label="نتایج جستجو" class="flex flex-col gap-5">
                <template x-if="res && res.products.length">
                    <section role="group" aria-label="محصولات">
                        <p class="<?php echo esc_attr($gl_label); ?>">محصولات</p>
                        <template x-for="(p, i) in res.products" :key="'p' + p.id">
                            <a :href="p.url" @click.prevent="go(p.url)" :id="'gls-opt-' + idx('products', i)" role="option"
                               :aria-selected="active === idx('products', i) ? 'true' : 'false'" @mouseenter="active = idx('products', i)"
                               class="<?php echo esc_attr($gl_row); ?>" :class="active === idx('products', i) ? '!bg-primary/5' : ''">
                                <span class="size-14 shrink-0 overflow-hidden rounded-xl bg-gray-50">
                                    <img x-show="p.img" :src="p.img" alt="" width="56" height="56" loading="lazy" class="size-full object-contain">
                                </span>
                                <span class="flex min-w-0 flex-1 flex-col gap-1">
                                    <span class="truncate text-sm font-bold text-gray-900" x-html="hl(p.name)"></span>
                                    <span class="flex items-center gap-2 text-xs text-gray-500">
                                        <span x-text="p.cat" class="truncate"></span>
                                        <span class="flex shrink-0 items-center -space-x-1">
                                            <template x-for="c in p.colors" :key="c.n">
                                                <span class="size-3.5 rounded-full border border-white ring-1 ring-gray-200" :style="{ background: c.h || '#e5e7eb' }" :title="c.n"></span>
                                            </template>
                                        </span>
                                    </span>
                                </span>
                                <span class="flex shrink-0 flex-col items-end text-end">
                                    <template x-if="p.in_stock">
                                        <span class="text-sm font-bold text-gray-900"><span x-text="money(p.price)"></span> <span class="text-[10px] font-normal text-gray-500">تومان</span></span>
                                    </template>
                                    <template x-if="p.in_stock && p.regular">
                                        <del class="text-[11px] text-gray-400" x-text="money(p.regular)"></del>
                                    </template>
                                    <template x-if="!p.in_stock">
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-500">ناموجود</span>
                                    </template>
                                </span>
                            </a>
                        </template>
                    </section>
                </template>

                <template x-if="res && res.cats.length">
                    <section role="group" aria-label="دسته‌بندی‌ها">
                        <p class="<?php echo esc_attr($gl_label); ?>">دسته‌بندی‌ها</p>
                        <template x-for="(c, i) in res.cats" :key="'c' + c.url">
                            <a :href="c.url" @click.prevent="go(c.url)" :id="'gls-opt-' + idx('cats', i)" role="option"
                               :aria-selected="active === idx('cats', i) ? 'true' : 'false'" @mouseenter="active = idx('cats', i)"
                               class="<?php echo esc_attr($gl_row); ?> !min-h-12" :class="active === idx('cats', i) ? '!bg-primary/5' : ''">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary">
                                    <?php get_template_part('template-parts/svg/tag', null, ['size' => 16, 'class' => '']); ?>
                                </span>
                                <span class="min-w-0 flex-1 truncate text-sm font-bold text-gray-900" x-html="hl(c.name)"></span>
                                <span class="shrink-0 text-xs text-gray-400"><span x-text="fa(c.count)"></span> محصول</span>
                            </a>
                        </template>
                    </section>
                </template>

                <template x-if="res && res.posts.length">
                    <section role="group" aria-label="مقالات مجله">
                        <p class="<?php echo esc_attr($gl_label); ?>">مجلهٔ گیلدا</p>
                        <template x-for="(a, i) in res.posts" :key="'a' + a.url">
                            <a :href="a.url" @click.prevent="go(a.url)" :id="'gls-opt-' + idx('posts', i)" role="option"
                               :aria-selected="active === idx('posts', i) ? 'true' : 'false'" @mouseenter="active = idx('posts', i)"
                               class="<?php echo esc_attr($gl_row); ?>" :class="active === idx('posts', i) ? '!bg-primary/5' : ''">
                                <span class="size-14 shrink-0 overflow-hidden rounded-xl bg-gray-100">
                                    <img x-show="a.img" :src="a.img" alt="" width="56" height="56" loading="lazy" class="size-full object-cover">
                                </span>
                                <span class="flex min-w-0 flex-1 flex-col gap-1">
                                    <span class="line-clamp-2 text-sm font-bold leading-6 text-gray-900" x-html="hl(a.title)"></span>
                                    <span class="text-xs text-secondary" x-text="a.cat || 'مقاله'"></span>
                                </span>
                            </a>
                        </template>
                    </section>
                </template>

                <a :href="allUrl()" @click.prevent="go(allUrl())" :id="'gls-opt-' + idx('all', 0)" role="option"
                   :aria-selected="active === idx('all', 0) ? 'true' : 'false'" @mouseenter="active = idx('all', 0)"
                   class="flex min-h-12 items-center justify-center gap-2 rounded-full border border-primary/15 bg-primary/5 px-5 text-sm font-bold text-primary transition hover:bg-primary hover:text-white"
                   :class="active === idx('all', 0) ? '!bg-primary !text-white' : ''">
                    مشاهدهٔ همهٔ نتایج «<span class="inline-block max-w-40 truncate align-bottom" x-text="term"></span>»
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>
