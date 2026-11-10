<?php
$bannerSection = function_exists('get_field') ? get_field('banners') : null;
$title = $bannerSection['title'] ?? '';
$content = $bannerSection['content'] ?? '';
if ($bannerSection) : ?>
    <section class="container px-3 lg:my-5">
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
        $banners = $bannerSection['banners'] ?? [];
        if ($banners) :?>
            <div class="grid lg:grid-cols-2 gap-3">
                <?php foreach ($banners as $i => $banner) : ?>
                    <a <?= !empty($banner['link']['target']) ? 'target="' . esc_attr($banner['link']['target']) . '" rel="noopener"' : ''; ?> href="<?= esc_url($banner['link']['url'] ?? '#'); ?>"
                       data-reveal style="--d:<?= (int) $i; ?>" class="<?= $i === 0 ? 'lg:col-span-2' : ''; ?> relative group rounded-sm h-60 border border-primary/10 overflow-hidden block shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-500 ease-out">

                        <div class="absolute inset-0 -translate-x-[150%] skew-x-[-25deg] group-hover:translate-x-[150%] bg-gradient-to-r from-transparent via-white/30 to-transparent transition-transform duration-700 z-10 pointer-events-none"></div>

                        <?php
                        $img_id = (int) ($banner['image']['ID'] ?? $banner['image']['id'] ?? 0);
                        $img_class = 'absolute inset-0 size-full group-hover:scale-110 transition-transform duration-700 ease-out object-cover';
                        $img_alt = ($banner['image']['alt'] ?? '') ?: ($banner['title'] ?? 'کیف زنانه گیلدا');
                        if ($img_id) {
                            echo wp_get_attachment_image($img_id, $i === 0 ? '1536x1536' : 'large', false, [
                                'class'    => $img_class,
                                'loading'  => 'lazy',
                                'decoding' => 'async',
                                'sizes'    => $i === 0 ? '(min-width: 1024px) 1200px, 100vw' : '(min-width: 1024px) 600px, 100vw',
                                'alt'      => esc_attr(wp_strip_all_tags($img_alt)),
                            ]);
                        } elseif (!empty($banner['image']['url'])) { ?>
                            <img class="<?= esc_attr($img_class); ?>" loading="lazy" decoding="async" src="<?= esc_url($banner['image']['url']); ?>" alt="<?= esc_attr(wp_strip_all_tags($img_alt)); ?>">
                        <?php } ?>

                        <div class="absolute inset-0 bg-gradient-to-tl from-black via-transparent group-hover:opacity-100 transition-opacity duration-500"></div>

                        <div class="absolute inset-0 text-white p-5 flex flex-col justify-end z-20">
                            <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-500 ease-out">
                                <p class="font-bold text-2xl">
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