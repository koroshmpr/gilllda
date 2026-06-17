<?php

$share_url = rawurlencode(get_permalink());
$share_title = rawurlencode(get_the_title());
$svgSize = '16';
?>
<div class="relative mt-auto w-8 h-8 flex justify-center items-center" x-data="{ shareOpen: false, copySuccess: false }">

    <button title="اشتراک‌گذاری" @click.prevent="shareOpen = !shareOpen" @click.outside="shareOpen = false"
            class="hover:bg-gray-100 w-full aspect-square justify-center rounded-lg gap-1.5 flex items-center text-gray-500 hover:text-gray-700 text-sm cursor-pointer transition-colors">
        <?php get_template_part('template-parts/svg/share', null, ['size' => $svgSize]); ?>
        <span class="max-lg:hidden sr-only">اشتراک‌گذاری</span>
    </button>

    <div x-show="shareOpen"
         style="display: none;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="absolute top-full left-0 lg:left-auto lg:right-0 mt-2 w-48 bg-white border border-gray-100 shadow-xl rounded-xl p-2 z-[60] flex flex-col gap-1">

        <a target="_blank" href="https://t.me/share/url?url=<?= $share_url ?>&text=<?= $share_title ?>"
           class="flex items-center gap-2 px-3 py-2 hover:bg-blue-50 text-gray-600 hover:text-blue-500 rounded-lg transition-colors text-xs font-bold">
            <?php get_template_part('template-parts/svg/socials/telegram', null, ['size' => $svgSize]); ?>
            تلگرام
        </a>

        <a target="_blank" href="https://api.whatsapp.com/send?text=<?= $share_title ?> - <?= $share_url ?>"
           class="flex items-center gap-2 px-3 py-2 hover:bg-green-50 text-gray-600 hover:text-green-500 rounded-lg transition-colors text-xs font-bold">
            <?php get_template_part('template-parts/svg/socials/whatsapp', null, ['size' => $svgSize]); ?>
            واتس‌اپ
        </a>

        <a target="_blank" href="https://twitter.com/intent/tweet?text=<?= $share_title ?>&url=<?= $share_url ?>"
           class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100 text-gray-600 hover:text-black rounded-lg transition-colors text-xs font-bold">
            <?php get_template_part('template-parts/svg/socials/twitter', null, ['size' => $svgSize]); ?>
            ایکس (توییتر)
        </a>

        <button
            @click="navigator.clipboard.writeText('<?= get_permalink() ?>'); copySuccess = true; setTimeout(() => copySuccess = false, 2000)"
            class="flex w-full items-center gap-2 cursor-pointer px-3 py-2 hover:bg-gray-100 text-gray-600 rounded-lg transition-colors text-xs font-bold border-t border-gray-100 mt-1">
            <span x-show="!copySuccess">
                <?php get_template_part('template-parts/svg/copy', null, ['size' => '15']); ?>
            </span>
            <span class="text-green-500" x-show="copySuccess">
                <?php get_template_part('template-parts/svg/check', null, ['size' => $svgSize]); ?>
            </span>
            <span x-text="copySuccess ? 'کپی شد!' : 'کپی لینک'"></span>
        </button>

    </div>
</div>