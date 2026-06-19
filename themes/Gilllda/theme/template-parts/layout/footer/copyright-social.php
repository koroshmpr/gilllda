<div class="container flex max-lg:flex-col-reverse gap-y-3 justify-between max-lg:pt-8 lg:pb-10 items-center">
        <span class="ltr text-end">© 2025 - <?= date('Y'); ?> طراحی شده توسط <a class="px-1 !underline hover:text-blue-400 transition-all"
                                                                                target="_blank" href="https://kmpr.ir/">وبکو</a></span>
    <div class="py-[0.5px] bg-gradient-to-r from-white/20 to-white/5 flex-1 max-lg:hidden ms-1 border-white/20"></div>
    <nav aria-label="social network links" class="flex items-center justify-between lg:justify-end">
        <div class="py-[0.5px] bg-gradient-to-r from-white/20 to-white/5 flex w-12 lg:hidden"></div>
        <?php get_template_part('template-parts/global/social-links', null , ['class' => 'border-white/10 text-white hover:bg-white/5']); ?>
        <div class="py-[0.5px] bg-gradient-to-l from-white/20 to-white/5 flex-1 w-12 lg:hidden"></div>
    </nav>
</div>