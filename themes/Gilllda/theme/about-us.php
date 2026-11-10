<?php
/* Template Name: About Us */
/**
 * About page. The editor content is split at its <h2> headings: the intro becomes the hero
 * lead and every H2 section becomes a numbered card, so long copy stays readable.
 */
get_header();

$owners  = function_exists('get_field') ? get_field('owners') : null;
$content = apply_filters('the_content', get_the_content());

// intro (before first H2) + [title, html] sections
$parts    = preg_split('/(<h2[^>]*>.*?<\/h2>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
$intro    = '';
$sections = [];
foreach ($parts as $part) {
    if (preg_match('/^<h2[^>]*>(.*?)<\/h2>$/is', trim($part), $m)) {
        $sections[] = ['title' => wp_strip_all_tags($m[1]), 'html' => ''];
    } elseif ($sections) {
        $sections[count($sections) - 1]['html'] .= $part;
    } else {
        $intro .= $part;
    }
}
// No intro paragraph? Use the first section as the hero lead.
$lead_title = '';
if (!trim(wp_strip_all_tags($intro)) && $sections) {
    $first      = array_shift($sections);
    $lead_title = $first['title'];
    $intro      = $first['html'];
}
$shop    = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
$contact = get_page_by_path('contact-us');
$facts   = [
    ['ارسال به سراسر ایران', '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>'],
    ['۷ روز ضمانت تعویض', '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>'],
    ['پرداخت امن آنلاین', '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h3"/>'],
    ['مشاوره قبل از خرید', '<path d="M4 5h16v11H8l-4 4z"/><path d="M8 9h8M8 12h5"/>'],
];
?>

<!-- ============ HERO ============ -->
<section class="relative overflow-hidden">
    <div class="pointer-events-none absolute -top-40 end-1/4 size-[30rem] rounded-full bg-secondary/10 blur-3xl" aria-hidden="true"></div>
    <div class="container relative grid items-center gap-10 px-4 py-10 lg:grid-cols-12 lg:gap-16 lg:px-0 lg:py-16">
        <header class="lg:col-span-7">
            <p class="mb-3 flex items-center gap-2 text-sm font-bold text-secondary mag-in" style="--d:0"><span class="h-0.5 w-8 bg-current"></span>داستان ما</p>
            <h1 class="text-3xl font-black leading-tight text-gray-900 lg:text-5xl mag-in" style="--d:1"><?php the_title(); ?></h1>
            <?php if ($lead_title) : ?>
                <p class="mt-5 text-lg font-black text-secondary lg:text-xl mag-in" style="--d:2"><?= esc_html($lead_title); ?></p>
            <?php endif; ?>
            <?php if (trim(wp_strip_all_tags($intro))) : ?>
                <div class="prose prose-lg mt-6 max-w-none leading-9 text-gray-600 prose-p:text-justify prose-strong:text-gray-900 prose-a:text-secondary mag-in" style="--d:2"><?= $intro; // phpcs:ignore -- filtered content ?></div>
            <?php endif; ?>
            <ul class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4 mag-in" style="--d:3">
                <?php foreach ($facts as $f) : ?>
                    <li class="flex flex-col items-center gap-2 rounded-2xl border border-gray-100 bg-white p-3 text-center shadow-sm">
                        <span class="inline-flex size-10 items-center justify-center rounded-xl bg-secondary/10 text-secondary" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $f[1]; // phpcs:ignore ?></svg></span>
                        <span class="text-xs font-bold text-gray-800"><?= esc_html($f[0]); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </header>
        <?php if (has_post_thumbnail()) : ?>
            <figure class="relative lg:col-span-5 mag-cover">
                <div class="absolute inset-0 -z-10 translate-x-4 translate-y-4 rounded-[2rem] bg-primary/10" aria-hidden="true"></div>
                <?php the_post_thumbnail('large', ['class' => 'aspect-[4/5] w-full rounded-[2rem] object-cover shadow-2xl shadow-primary/10', 'fetchpriority' => 'high', 'loading' => 'eager', 'decoding' => 'async', 'sizes' => '(min-width: 1024px) 40vw, 100vw', 'alt' => esc_attr(get_the_title())]); ?>
            </figure>
        <?php endif; ?>
    </div>
</section>

<!-- ============ SECTIONS ============ -->
<?php if ($sections) : ?>
    <section class="bg-gradient-to-b from-gray-50 to-white py-14 lg:py-20" aria-label="<?= esc_attr(get_the_title()); ?>">
        <div class="container grid gap-5 px-4 md:grid-cols-2 lg:px-0 lg:gap-6">
            <?php foreach ($sections as $i => $sec) :
                $wide = count(preg_split('/\s+/u', trim(wp_strip_all_tags($sec['html'])), -1, PREG_SPLIT_NO_EMPTY)) > 140; ?>
                <article class="group relative overflow-hidden rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition duration-500 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/5 lg:p-8 <?= ($wide || ($i === count($sections) - 1 && count($sections) % 2 === 1)) ? 'md:col-span-2' : ''; ?>" data-reveal style="--d:<?= (int) ($i % 2); ?>">
                    <span class="pointer-events-none absolute -end-6 -top-8 text-[7rem] font-black leading-none text-primary/[0.04] transition group-hover:text-secondary/[0.07]" aria-hidden="true"><?= esc_html(gl_fa_num(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT))); ?></span>
                    <h2 class="relative mb-4 flex items-center gap-3 text-xl font-black text-gray-900 lg:text-2xl">
                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-secondary text-sm text-white"><?= esc_html(gl_fa_num($i + 1)); ?></span>
                        <?= esc_html($sec['title']); ?>
                    </h2>
                    <div class="prose relative max-w-none leading-8 text-gray-600 prose-p:text-justify prose-strong:text-gray-900 prose-a:font-bold prose-a:text-secondary prose-a:no-underline hover:prose-a:underline prose-li:my-1 prose-li:marker:text-secondary <?= $wide ? 'lg:columns-2 lg:gap-10 [&>*]:break-inside-avoid' : ''; ?>">
                        <?= $sec['html']; // phpcs:ignore -- filtered content ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- ============ TEAM ============ -->
<?php if ($owners) : ?>
    <section class="container px-4 py-12 lg:px-0" aria-label="تیم گیلدا">
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <?php foreach ($owners as $i => $owner) : ?>
                <div class="flex items-center gap-5 rounded-3xl border border-gray-100 bg-white p-4 shadow-sm transition hover:shadow-md" data-reveal style="--d:<?= (int) $i; ?>">
                    <img class="size-20 rounded-full border-2 border-primary/10 object-cover" src="<?= esc_url($owner['image']['sizes']['thumbnail'] ?? $owner['image']['url'] ?? ''); ?>" alt="<?= esc_attr($owner['name'] ?? ''); ?>" width="80" height="80" loading="lazy">
                    <div class="flex flex-col">
                        <p class="text-lg font-bold text-gray-900"><?= esc_html($owner['name'] ?? ''); ?></p>
                        <span class="text-sm font-medium text-gray-500"><?= esc_html($owner['position'] ?? ''); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- ============ CTA ============ -->
<section class="container px-4 pb-14 lg:px-0 lg:pb-20">
    <div class="relative overflow-hidden rounded-[2rem] bg-primary px-6 py-12 text-center text-white lg:px-16 lg:py-16" data-reveal>
        <div class="pointer-events-none absolute -bottom-24 start-1/4 size-80 rounded-full bg-secondary/40 blur-3xl" aria-hidden="true"></div>
        <h2 class="relative text-2xl font-black lg:text-4xl">کیف بعدی‌ات را با خیال راحت انتخاب کن</h2>
        <p class="relative mx-auto mt-3 max-w-xl leading-8 text-white/75">ارسال به سراسر ایران، ۷ روز ضمانت تعویض و مشاوره‌ی رایگان قبل از خرید.</p>
        <div class="relative mt-8 flex flex-wrap justify-center gap-3">
            <a href="<?= esc_url($shop); ?>" class="inline-flex min-h-12 items-center rounded-full bg-white px-8 font-bold text-primary transition hover:scale-105">مشاهده فروشگاه</a>
            <?php if ($contact) : ?>
                <a href="<?= esc_url(get_permalink($contact)); ?>" class="inline-flex min-h-12 items-center rounded-full border border-white/30 px-8 font-bold text-white transition hover:bg-white/10">ارتباط با ما</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// Portfolio slider (unchanged behaviour)
$portfolioSection = function_exists('get_field') ? get_field('portfolio') : null;
$portfolios = $portfolioSection['portfolios'] ?? [];
if ($portfolios) : ?>
    <section class="overflow-hidden py-8">
        <div class="swiper post-slider container relative mx-auto mb-1 px-0 ltr lg:overflow-visible"
             data-index="portfolios" data-perfix="portfolio" data-space="20" data-loop="1" data-speed="4000"
             data-perpage="4.2" data-mobile="1.3" data-autoplay="1" data-scroll="0" data-free="1">
            <div class="swiper-wrapper swiper-container group/slider min-h-[40vh] !ease-linear">
                <?php foreach ($portfolios as $portfolio) :
                    if (!$portfolio) {
                        continue;
                    }
                    $pid     = $portfolio->ID;
                    $related = function_exists('get_field') ? get_field('related_product', $pid) : null;
                    $title   = get_the_title($pid); ?>
                    <div class="swiper-slide group relative h-[45vh] cursor-pointer overflow-hidden rounded-xl shadow-sm lg:h-[50vh]">
                        <?= get_the_post_thumbnail($pid, 'large', ['loading' => 'lazy', 'decoding' => 'async', 'alt' => esc_attr($title), 'class' => 'h-full w-full object-cover transition-transform duration-1000 group-hover:scale-110']); ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/90 via-gray-900/40 to-transparent opacity-80 transition-opacity duration-500 group-hover:opacity-100"></div>
                        <div class="absolute inset-x-0 bottom-0 z-10 flex flex-col items-end justify-end p-6 text-right">
                            <p class="mb-3 translate-y-2 text-xl font-semibold text-white transition-transform duration-500 group-hover:translate-y-0"><?= esc_html($title); ?></p>
                            <?php if ($related && !empty($related[0])) : ?>
                                <div class="flex w-full items-center justify-between opacity-0 transition-opacity delay-100 duration-500 group-hover:opacity-100">
                                    <p class="rounded bg-white/90 px-2 py-1 text-xs font-medium text-gray-800 backdrop-blur-sm"><?= esc_html($related[0]->post_title); ?></p>
                                    <a href="<?= esc_url(get_permalink($related[0]->ID)); ?>" class="flex items-center gap-2 text-sm text-white transition-colors hover:text-primary"><span>سفارش</span><?php get_template_part('template-parts/svg/arrow-right', null, ['size' => '16', 'class' => 'fill-current']); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif;

get_footer();
