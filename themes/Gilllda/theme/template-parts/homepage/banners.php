<?php
$bannerSection = get_field('banners');
$title = $bannerSection['title'];
$content = $bannerSection['content'];
if ($bannerSection) : ?>
    <section class="container px-3 my-5">
        <?php if (!empty($title) || !empty($content)) : ?>
            <div class="flex flex-col items-center gap-5 mb-5">
                <?php if (!empty($title)) : ?>
                    <h2 class="font-bold text-3xl border-b-2 border-secondary pb-1 px-3">
                        <?= $title; ?>
                    </h2>
                <?php endif;
                if (!empty($content)) :?>
                    <article class="opacity-70 text-sm lg:text-base text-center max-w-content">
                        <?= $content ?>
                    </article>
                <?php endif; ?>
            </div>
        <?php endif;
        $banners = $bannerSection['banners'];
        if ($banners) :?>
            <div class="grid lg:grid-cols-2 gap-4">
                <?php foreach ($banners as $i => $banner) : ?>
                    <a target="<?= $banner['link']['target'] ?? ''; ?>" href="<?= $banner['link']['url'] ?? ''; ?>"
                       class="<?= $i === 0 ? 'lg:col-span-2' : ''; ?> relative group rounded-xl h-60 border border-primary/10 overflow-hidden block shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-500 ease-out">

                        <div class="absolute inset-0 -translate-x-[150%] skew-x-[-25deg] group-hover:translate-x-[150%] bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-1000 z-10 pointer-events-none"></div>

                        <img class="absolute inset-0 size-full group-hover:scale-110 transition-transform duration-700 ease-out object-cover"
                             src="<?= $banner['image']['url'] ?? ''; ?>" alt="<?= $banner['image']['title'] ?? 'banner-'. $i; ?>">

                        <div class="absolute inset-0 bg-gradient-to-t from-primary/90 via-primary/30 to-transparent opacity-80 group-hover:opacity-100 transition-opacity duration-500"></div>

                        <div class="absolute inset-0 text-white p-6 flex flex-col justify-end z-20">
                            <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-500 ease-out">
                                <p class="font-bold text-2xl drop-shadow-md">
                                    <?= $banner['title'] ?? '' ?>
                                </p>

                                <div class="overflow-hidden mt-1">
                                    <p class="opacity-0 group-hover:opacity-90 transform translate-y-full group-hover:translate-y-0 transition-all duration-500 delay-75 ease-out text-sm">
                                        <?= $banner['subtitle'] ?? '' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>