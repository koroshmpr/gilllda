<?php
/**
 * Search results: products (theme card, matched via gl_search_product_ids — same normalised
 * matching as the instant search) + magazine articles (main query, paginated).
 * Recent searches are client-side (localStorage) so the page stays cacheable.
 *
 * @package bluebox
 */
get_header();

global $wp_query;
$gl_q        = trim((string) get_search_query(false));
$gl_paged    = max(1, (int) get_query_var('paged'));
$gl_has_q    = '' !== $gl_q;
$gl_pids     = $gl_has_q && 1 === $gl_paged && function_exists('gl_search_product_ids') ? gl_search_product_ids($gl_q, 48) : [];
$gl_cats     = $gl_has_q && 1 === $gl_paged && function_exists('gl_search_cats') ? gl_search_cats($gl_q, 8) : [];
$gl_n_prod   = count($gl_pids);
$gl_n_posts  = $gl_has_q ? (int) $wp_query->found_posts : 0;
$gl_has_any  = $gl_n_prod || ($gl_has_q && have_posts());
$gl_tab      = $gl_paged > 1 ? 'posts' : 'all';
$gl_popular  = function_exists('gl_search_popular') ? gl_search_popular() : ['cats' => [], 'colors' => []];
$gl_chip     = 'inline-flex min-h-11 items-center gap-2 rounded-full border border-gray-200 bg-white px-4 text-sm text-gray-700 transition hover:border-primary/40 hover:text-primary';
$gl_tab_cls  = 'flex min-h-11 shrink-0 cursor-pointer items-center gap-2 rounded-full px-5 text-sm font-bold transition';
?>
<div x-data="glSearchPage(<?php echo esc_attr(wp_json_encode($gl_q)); ?>, '<?php echo esc_attr($gl_tab); ?>')" dir="rtl">

    <header class="bg-gradient-to-b from-primary/5 to-transparent pb-4 pt-8 lg:pt-12">
        <div class="container max-w-content max-lg:px-4">
            <p class="text-xs font-bold text-secondary" data-reveal>نتایج جستجو</p>
            <h1 class="mt-1 text-2xl font-black text-primary lg:text-4xl" data-reveal style="--d:1">
                <?php echo $gl_has_q ? '«' . esc_html($gl_q) . '»' : 'جستجو در گیلدا'; ?>
            </h1>
            <?php if ($gl_has_q) : ?>
                <p class="mt-2 text-sm text-gray-500" data-reveal style="--d:2">
                    <?php
                    $gl_parts = [];
                    if (1 === $gl_paged) {
                        $gl_parts[] = gl_fa_num($gl_n_prod) . ' محصول';
                    }
                    $gl_parts[] = gl_fa_num($gl_n_posts) . ' مقاله';
                    echo esc_html(implode(' · ', $gl_parts));
                    ?>
                </p>
            <?php endif; ?>

            <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="mt-5 flex max-w-2xl gap-2" data-reveal style="--d:3">
                <label for="gl-search-page-input" class="sr-only">جستجو</label>
                <div class="relative min-w-0 flex-1">
                    <svg class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                    <input id="gl-search-page-input" type="search" name="s" value="<?php echo esc_attr($gl_q); ?>" maxlength="64" required minlength="2"
                           placeholder="جستجوی کیف، رنگ یا مقاله…" enterkeyhint="search"
                           class="h-13 w-full rounded-2xl border border-gray-200 bg-white pe-4 ps-12 text-base outline-none transition focus:border-primary/40 focus:ring-4 focus:ring-primary/5">
                </div>
                <button type="submit" class="min-h-13 shrink-0 cursor-pointer rounded-full bg-primary px-6 text-sm font-bold text-white transition hover:brightness-125">جستجو</button>
            </form>

            <nav x-show="recent.length" style="display:none" aria-label="جستجوهای اخیر" class="mt-4 flex items-center gap-2 overflow-x-auto pb-1 [scrollbar-width:none]">
                <span class="shrink-0 text-xs text-gray-400">اخیر:</span>
                <template x-for="s in recent" :key="s">
                    <a :href="url(s)" class="inline-flex min-h-9 shrink-0 items-center rounded-full bg-gray-100 px-3 text-xs text-gray-600 transition hover:bg-gray-200" x-text="s"></a>
                </template>
            </nav>
        </div>
    </header>

    <div class="container max-w-content max-lg:px-4 pb-12 lg:pb-16">
        <?php if ($gl_has_any) : ?>

            <?php if ($gl_n_prod && $gl_n_posts) : ?>
                <div class="-mx-4 mb-6 flex gap-2 overflow-x-auto bg-white/90 px-4 py-3 backdrop-blur lg:sticky lg:top-16 lg:z-20 lg:mx-0 lg:px-0" role="tablist" aria-label="نوع نتایج">
                    <?php
                    $gl_tabs = ['all' => ['همه', $gl_n_prod + $gl_n_posts], 'products' => ['محصولات', $gl_n_prod], 'posts' => ['مقالات', $gl_n_posts]];
                    foreach ($gl_tabs as $gl_key => $gl_t) :
                        if (1 !== $gl_paged && 'posts' !== $gl_key) {
                            continue;
                        } ?>
                        <button type="button" role="tab" @click="tab = '<?php echo esc_attr($gl_key); ?>'"
                                :aria-selected="tab === '<?php echo esc_attr($gl_key); ?>' ? 'true' : 'false'"
                                class="<?php echo esc_attr($gl_tab_cls); ?>"
                                :class="tab === '<?php echo esc_attr($gl_key); ?>' ? 'bg-primary text-white' : 'border border-gray-200 bg-white text-gray-600 hover:border-primary/40'">
                            <?php echo esc_html($gl_t[0]); ?>
                            <span class="rounded-full px-2 text-xs" :class="tab === '<?php echo esc_attr($gl_key); ?>' ? 'bg-white/20' : 'bg-gray-100'"><?php echo esc_html(gl_fa_num($gl_t[1])); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($gl_cats) : ?>
                <div class="mb-6 flex flex-wrap items-center gap-2" x-show="tab !== 'posts'">
                    <span class="text-xs text-gray-500">دسته‌بندی‌های مرتبط:</span>
                    <?php foreach ($gl_cats as $gl_c) : ?>
                        <a href="<?php echo esc_url($gl_c['url']); ?>" class="<?php echo esc_attr($gl_chip); ?>">
                            <?php echo esc_html($gl_c['name']); ?>
                            <span class="text-xs text-gray-400"><?php echo esc_html(gl_fa_num($gl_c['count'])); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($gl_n_prod) : ?>
                <section x-show="tab !== 'posts'" class="mb-12" aria-labelledby="gl-sr-products">
                    <h2 id="gl-sr-products" class="mb-4 text-lg font-black text-gray-900">محصولات</h2>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <?php
                        $gl_pq = new WP_Query([
                            'post_type'           => 'product',
                            'post__in'            => $gl_pids,
                            'orderby'             => 'post__in',
                            'posts_per_page'      => $gl_n_prod,
                            'no_found_rows'       => true,
                            'ignore_sticky_posts' => true,
                        ]);
                        while ($gl_pq->have_posts()) :
                            $gl_pq->the_post();
                            $GLOBALS['product'] = wc_get_product(get_the_ID());
                            get_template_part('template-parts/product/card/product-card', null, [
                                'isArchive' => true,
                                'eager'     => $gl_pq->current_post < 2,
                            ]);
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (have_posts()) : ?>
                <section x-show="tab !== 'products'" aria-labelledby="gl-sr-posts">
                    <h2 id="gl-sr-posts" class="mb-6 text-lg font-black text-gray-900">مقالات مجلهٔ گیلدا</h2>
                    <div class="grid gap-x-6 gap-y-12 md:grid-cols-2 lg:grid-cols-3">
                        <?php while (have_posts()) : the_post();
                            get_template_part('template-parts/blog/mag-card', null, ['d' => $wp_query->current_post % 3, 'eager' => !$gl_n_prod && $wp_query->current_post < 3]);
                        endwhile; ?>
                    </div>
                    <?php get_template_part('template-parts/global/pagination'); ?>
                </section>
            <?php endif; ?>

        <?php else : ?>
            <div class="mx-auto flex max-w-xl flex-col items-center py-10 text-center lg:py-16">
                <span class="flex size-24 items-center justify-center rounded-full bg-primary/5 text-primary">
                    <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5M8.5 11h5"/></svg>
                </span>
                <?php if ($gl_has_q) : ?>
                    <h2 class="mt-6 text-xl font-black text-gray-900">برای «<?php echo esc_html($gl_q); ?>» نتیجه‌ای پیدا نشد</h2>
                    <p class="mt-2 text-sm leading-7 text-gray-500">املای عبارت را بررسی کنید، کلمهٔ کوتاه‌تر یا نام یک رنگ (مثلاً «مشکی») را امتحان کنید.</p>
                <?php else : ?>
                    <h2 class="mt-6 text-xl font-black text-gray-900">دنبال چه می‌گردید؟</h2>
                    <p class="mt-2 text-sm leading-7 text-gray-500">نام کیف، رنگ، دسته‌بندی یا موضوع مقاله را بنویسید.</p>
                <?php endif; ?>

                <?php if ($gl_popular['cats'] || $gl_popular['colors']) : ?>
                    <div class="mt-8 flex flex-wrap justify-center gap-2">
                        <?php foreach ($gl_popular['cats'] as $gl_c) : ?>
                            <a href="<?php echo esc_url($gl_c['url']); ?>" class="<?php echo esc_attr($gl_chip); ?>"><?php echo esc_html($gl_c['name']); ?></a>
                        <?php endforeach; ?>
                        <?php foreach ($gl_popular['colors'] as $gl_c) : ?>
                            <a href="<?php echo esc_url(add_query_arg('s', rawurlencode($gl_c['name']), home_url('/'))); ?>" class="<?php echo esc_attr($gl_chip); ?>">
                                <span class="size-4 rounded-full border border-gray-300" style="background:<?php echo esc_attr($gl_c['hex'] ?: '#e5e7eb'); ?>"></span>
                                <?php echo esc_html($gl_c['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>"
                   class="mt-8 inline-flex min-h-11 items-center gap-2 rounded-full bg-primary px-6 text-sm font-bold text-white transition hover:brightness-125">
                    مشاهدهٔ همهٔ محصولات
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
get_footer();
