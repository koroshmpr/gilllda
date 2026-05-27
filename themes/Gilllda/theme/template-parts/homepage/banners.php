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
            <div class="grid lg:grid-cols-2 gap-3">
                <?php foreach ($banners as $i => $banner) : ?>
                    <a target="<?= $banner['link']['target'] ?? ''; ?>" href="<?= $banner['link']['url'] ?? ''; ?>" class="<?= $i === 0 ? 'lg:col-span-2' : ''; ?> relative group rounded-md h-60 border border-primary/10 overflow-hidden">
                        <img class="size-full group-hover:scale-105 transition-all duration-300 object-cover" src="<?= $banner['image']['url'] ?? ''; ?>" alt="<?= $banner['image']['title'] ?? 'banner-'. $i; ?>">
                        <div class=" absolute inset-0 text-white p-5 group-hover:pb-7 ease-linear bg-gradient-to-tl from-primary to-90% transition-all to-transparent via-transparent justify-end flex flex-col gap-1">
                            <p class="font-bold text-xl group-hover:text-2xl ease-linear transition-all"><?= $banner['title'] ?? '' ?></p>
                            <p class="opacity-75 group-hover:ps-2 transition-all delay-100 ease-linear text-xs"><?= $banner['subtitle'] ?? '' ?></p>
                        </div>
                    </a>

                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>