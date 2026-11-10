<?php
/**
 * Trust / value strip under the hero.
 */
$items = [
    ['ارسال به سراسر ایران', 'بسته‌بندی شیک، تحویل درِ خانه', '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>'],
    ['ضمانت تعویض ۷ روزه', 'خرید بی‌دغدغه، تعویض آسان', '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>'],
    ['پرداخت امن آنلاین', 'درگاه بانکی معتبر', '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h3"/>'],
    ['مشاوره و پشتیبانی', 'کمک در انتخاب رنگ و مدل', '<path d="M4 5h16v11H8l-4 4z"/><path d="M8 9h8M8 12h5"/>'],
];
?>
<section class="container px-4 py-5 lg:px-0 lg:py-12" aria-label="مزیت‌های خرید از گیلدا">
    <ul class="grid grid-cols-2 gap-2 lg:grid-cols-4 lg:gap-5">
        <?php foreach ($items as $i => $it) : ?>
            <li class="group flex items-center gap-2.5 rounded-2xl border border-gray-100 bg-gradient-to-br from-white to-gray-50 p-2.5 transition duration-500 hover:-translate-y-1 hover:border-secondary/20 hover:shadow-xl hover:shadow-primary/5 lg:gap-3 lg:rounded-3xl lg:p-5" data-reveal style="--d:<?= (int) $i; ?>">
                <span class="inline-flex size-10 shrink-0 lg:size-12 items-center justify-center rounded-2xl bg-secondary/10 text-secondary transition duration-500 group-hover:rotate-[-8deg] group-hover:bg-secondary group-hover:text-white" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $it[2]; // phpcs:ignore -- static svg ?></svg>
                </span>
                <span>
                    <span class="block text-[13px] font-black leading-5 text-gray-900 lg:text-base"><?= esc_html($it[0]); ?></span>
                    <span class="mt-0.5 hidden text-xs leading-5 text-gray-500 lg:block"><?= esc_html($it[1]); ?></span>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
