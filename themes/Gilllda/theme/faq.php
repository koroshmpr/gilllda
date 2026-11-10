<?php
/* Template Name: faq */
/**
 * FAQ page: hero with live search, category chips, animated accordions, contact CTA, FAQPage schema.
 * Content: ACF `faq_cat` (title, faq_list[question, answer]).
 */
get_header();

$faq_cats = function_exists('get_field') ? (array) get_field('faq_cat') : [];
$faq_cats = array_values(array_filter($faq_cats, function ($c) {
    return !empty($c['faq_list']);
}));
$phone   = function_exists('get_field') ? get_field('phone', 'option') : '';
$contact = get_page_by_path('contact-us');
$total   = array_sum(array_map(function ($c) {
    return count($c['faq_list']);
}, $faq_cats));
$schema  = [];
?>

<div x-data="{ q: '', cat: 'all', noHits: false, norm(s) { return (s || '').replace(/ي/g, 'ی').replace(/ك/g, 'ک').replace(/\s+/g, ' ').trim().toLowerCase(); }, hit(el) { const q = this.norm(this.q); return !q || this.norm(el.textContent).includes(q); } }">

    <!-- Hero -->
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute -top-40 start-1/3 size-[28rem] rounded-full bg-secondary/10 blur-3xl" aria-hidden="true"></div>
        <div class="container relative px-4 pb-8 pt-10 text-center lg:px-0 lg:pb-12 lg:pt-16">
            <p class="mb-3 inline-flex items-center gap-2 text-sm font-bold text-secondary mag-in"><span class="h-0.5 w-6 bg-current"></span>مرکز راهنما<span class="h-0.5 w-6 bg-current"></span></p>
            <h1 class="text-3xl font-black text-gray-900 lg:text-5xl mag-in" style="--d:1"><?php the_title(); ?></h1>
            <p class="mx-auto mt-4 max-w-xl leading-8 text-gray-600 mag-in" style="--d:2">پاسخ <?= esc_html(gl_fa_num($total)); ?> سؤال پرتکرار درباره‌ی خرید، ارسال، بازگشت کالا و نگهداری کیف.</p>
            <label class="relative mx-auto mt-8 block max-w-xl mag-in" style="--d:3">
                <span class="sr-only">جستجو در سؤالات</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="pointer-events-none absolute start-5 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" x-model="q" placeholder="سؤالت را جستجو کن… مثلاً «ارسال» یا «تعویض»"
                       class="min-h-14 w-full rounded-2xl border border-gray-200 bg-white ps-14 pe-5 text-base shadow-lg shadow-primary/5 transition focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10">
            </label>
        </div>
    </section>

    <?php if ($faq_cats) : ?>
        <!-- Category chips -->
        <nav class="container -mx-0 overflow-x-auto px-4 [scrollbar-width:none] lg:px-0" aria-label="دسته‌بندی سؤالات">
            <ul class="mx-auto flex w-max gap-2 pb-2">
                <li><button type="button" @click="cat = 'all'" :class="cat === 'all' ? 'bg-primary text-white' : 'border border-gray-200 text-gray-600 hover:border-primary/40'" class="inline-flex min-h-11 items-center rounded-full px-5 text-sm font-bold transition">همه</button></li>
                <?php foreach ($faq_cats as $i => $c) : ?>
                    <li><button type="button" @click="cat = '<?= (int) $i; ?>'" :class="cat === '<?= (int) $i; ?>' ? 'bg-primary text-white' : 'border border-gray-200 text-gray-600 hover:border-primary/40'" class="inline-flex min-h-11 items-center gap-2 rounded-full px-5 text-sm font-bold transition">
                        <?= esc_html(trim($c['title'])); ?><span class="text-xs font-normal opacity-60"><?= esc_html(gl_fa_num(count($c['faq_list']))); ?></span>
                    </button></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <!-- Questions -->
        <div class="container grid gap-10 px-4 py-10 lg:grid-cols-12 lg:px-0 lg:py-14">
            <div class="flex flex-col gap-12 lg:col-span-8">
                <?php foreach ($faq_cats as $i => $c) : ?>
                    <section x-show="cat === 'all' || cat === '<?= (int) $i; ?>'" x-transition.opacity aria-labelledby="faq-cat-<?= (int) $i; ?>">
                        <h2 id="faq-cat-<?= (int) $i; ?>" class="mb-5 flex items-center gap-3 text-xl font-black text-gray-900 lg:text-2xl">
                            <span class="inline-flex size-9 items-center justify-center rounded-xl bg-secondary text-sm text-white"><?= esc_html(gl_fa_num($i + 1)); ?></span>
                            <?= esc_html(trim($c['title'])); ?>
                        </h2>
                        <div class="grid gap-3">
                            <?php foreach ($c['faq_list'] as $j => $row) :
                                $question = trim(wp_strip_all_tags($row['question'] ?? ''));
                                $answer   = $row['answer'] ?? '';
                                if ($question === '' || trim(wp_strip_all_tags($answer)) === '') {
                                    continue;
                                }
                                $schema[] = ['@type' => 'Question', 'name' => $question, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(wp_strip_all_tags($answer))]]; ?>
                                <details x-show="hit($el)" class="group rounded-3xl border border-gray-100 bg-white shadow-sm transition open:border-secondary/30 open:shadow-lg open:shadow-primary/5" <?= ($i === 0 && $j === 0) ? 'open' : ''; ?>>
                                    <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-start font-bold text-gray-900 [&::-webkit-details-marker]:hidden lg:px-6">
                                        <span><?= esc_html($question); ?></span>
                                        <span class="relative inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-secondary/10 text-secondary transition duration-300 group-open:rotate-45 group-open:bg-secondary group-open:text-white" aria-hidden="true">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M12 5v14M5 12h14"/></svg>
                                        </span>
                                    </summary>
                                    <div class="prose max-w-none px-5 pb-5 leading-8 text-gray-600 prose-p:my-2 prose-a:font-bold prose-a:text-secondary prose-a:no-underline hover:prose-a:underline prose-strong:text-gray-900 prose-li:my-0.5 lg:px-6">
                                        <?= wp_kses_post($answer); ?>
                                    </div>
                                </details>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
                <p x-effect="q; cat; $nextTick(() => { noHits = [...$root.querySelectorAll('details')].every(d => d.offsetParent === null); })" x-show="q && noHits" x-cloak class="rounded-3xl bg-gray-50 p-8 text-center text-gray-500">سؤالی با این عبارت پیدا نشد؛ از پشتیبانی بپرس 👇</p>
            </div>

            <!-- Help card -->
            <aside class="lg:col-span-4">
                <div class="sticky top-24 overflow-hidden rounded-3xl bg-primary p-6 text-white lg:p-8" data-reveal>
                    <div class="pointer-events-none absolute -bottom-16 -start-10 size-52 rounded-full bg-secondary/40 blur-3xl" aria-hidden="true"></div>
                    <p class="relative text-xl font-black">جوابت را پیدا نکردی؟</p>
                    <p class="relative mt-2 text-sm leading-7 text-white/70">همکاران ما در انتخاب مدل، رنگ و پیگیری سفارش کمکت می‌کنند.</p>
                    <div class="relative mt-6 grid gap-2">
                        <?php if ($phone) : ?>
                            <a href="tel:<?= esc_attr(preg_replace('/[^\d+]/', '', (string) $phone)); ?>" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-white px-5 font-bold text-primary transition hover:-translate-y-0.5">
                                تماس: <span dir="ltr"><?= esc_html($phone); ?></span>
                            </a>
                        <?php endif; ?>
                        <?php if ($contact) : ?>
                            <a href="<?= esc_url(get_permalink($contact)); ?>" class="inline-flex min-h-12 items-center justify-center rounded-full border border-white/25 px-5 font-bold transition hover:bg-white/10">ارسال پیام</a>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </div>

        <?php if ($schema) : ?>
            <script type="application/ld+json"><?= wp_json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $schema], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php get_footer();
