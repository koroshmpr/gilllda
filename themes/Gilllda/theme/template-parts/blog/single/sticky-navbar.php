<?php
$buttonCLass = 'flex-1 flex items-center justify-center py-1.5 gap-x-2';
$textCLass = 'text-gray-700 text-xs';
$args = array(
    'size' => 20,
);
?>
<button @click="stickyMenu = !stickyMenu" aria-label="show more detail"
        class="w-10 h-10 lg:hidden fixed left-4 bottom-28 bg-gray-700 hover:bg-gray-900 cursor-pointer justify-center border border-white/20 transition-all duration-700 items-center flex text-white rounded-sm z-[5]">
        <span class="absolute transition-all duration-500" :class="stickyMenu ? 'opacity-0 rotate-90' : ''">
            <?php get_template_part('template-parts/svg/menu-dot', null, $args); ?>
        </span>
        <span class="absolute transition-all duration-500 opacity-0" :class="stickyMenu ? 'opacity-100' : 'opacity-0 -rotate-90'">
            <?php get_template_part('template-parts/svg/close', null, $args); ?>
        </span>
</button>
<?php
//nav bar single
?>
<div :class="stickyMenu ? 'bottom-14' : '-bottom-14'"
     class="lg:hidden duration-500 transition-all fixed shadow-sm  divide-x divide-gray-200 inset-x-0 bg-white z-40 py-2 flex items-center rtl">
    <button @click="share = true"
            aria-label="open share links" class="<?= $buttonCLass; ?> cursor-pointer">
        <?php get_template_part('template-parts/svg/share', null, ['size' => '15']); ?>
        <span class="<?= $textCLass; ?>">اشتراک گذاری</span>
    </button>
    <button @click="toc = true"
            aria-label="open toc modal" class="<?= $buttonCLass; ?> cursor-pointer">
        <?php get_template_part('template-parts/svg/list', null, $args); ?>
        <span class="<?= $textCLass; ?>">فهرست مطالب </span>
    </button>
    <button @click.prevent="document.getElementById('comments').scrollIntoView({ behavior: 'smooth' })"
            aria-label="comment count and scroll to comment section" class="<?= $buttonCLass; ?> cursor-pointer">
        <?php get_template_part('template-parts/svg/message', null, $args); ?>
       <span class="flex items-center gap-1">
            <span class="text-xs"><?= get_comments_number(); ?></span>
            <span class="<?= $textCLass; ?>">نظر</span>
       </span>
    </button>
</div>
<?php
//toc modal
?>
<div
        @keydown.escape.window="toc = false" id="mobileMenu"
        :class="toc ? '!z-50 !opacity-100' : ''"
        class="fixed inset-0 flex justify-center z-[-1] opacity-0 bg-black/10 items-end lg:hidden backdrop-blur-sm transition-all duration-300"
        @click.self="toc = false"
>
    <div class="bg-gray-100 text-black w-full py-4 px-6 h-[40vh] rounded-t-2xl flex flex-col gap-y-3 translate-y-full transition-all duration-300"
         :class="toc ? 'delay-200 !translate-y-0' : 'translate-y-full'">
        <div class="flex justify-between items-center">
            <button @click="toc = false"
                    class="p-2 transition-all border bg-white border-gray-200 rounded-lg aspect-square z-[5]">
                <?php get_template_part('template-parts/svg/close', null, $args); ?>
            </button>
            <p>فهرست مطالب</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 h-full overflow-y-scroll p-5">
            <?= do_shortcode('[TOC levels="2,3"]'); ?>
        </div>
    </div>
</div>
<div :class="share ? 'bottom-14' : '-bottom-14'"
     class="lg:hidden duration-500 transition-all border-t border-gray-100 fixed shadow-sm inset-x-0 bg-white z-40 py-2 px-4 flex justify-between items-center rtl">
    <?php
    $args = array(
        'class' => 'lg:hidden text-black !mx-0',
        'linkClass' => 'bg-gray-700 p-2 text-white rounded-sm hover:bg-black/10 transition-all'
    );
    get_template_part('template-parts/blog/single/share-button', null, $args);
    ?>
    <button @click="share = false"
            class=" px-3 py-1 bg-gray-700 text-sm hover:bg-gray-900 cursor-pointer justify-center border border-white/20 transition-all duration-700 items-center flex text-white rounded-sm z-[5]">
        انصراف
    </button>
</div>