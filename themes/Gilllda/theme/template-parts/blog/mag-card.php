<?php
/**
 * Magazine article card.
 * @var array $args ['class' => string, 'eager' => bool, 'size' => 'lg'|'md', 'd' => int (reveal delay index)]
 */
$pid    = get_the_ID();
$eager  = !empty($args['eager']);
$large  = ($args['size'] ?? 'md') === 'lg';
$cat    = get_the_category()[0] ?? null;
$series = function_exists('gl_series_info') ? gl_series_info($pid) : null;
?>
<article class="group relative flex flex-col <?= esc_attr($args['class'] ?? ''); ?>" data-reveal style="--d:<?= (int) ($args['d'] ?? 0); ?>">
    <a href="<?php the_permalink(); ?>" class="relative block overflow-hidden rounded-3xl bg-gray-100 <?= $large ? 'aspect-[16/10]' : 'aspect-[4/3]'; ?>" tabindex="-1" aria-hidden="true">
        <?php if (has_post_thumbnail()) {
            the_post_thumbnail($large ? 'large' : 'medium_large', [
                'class'         => 'size-full object-cover transition duration-700 ease-out group-hover:scale-105',
                'loading'       => $eager ? 'eager' : 'lazy',
                'fetchpriority' => $eager ? 'high' : 'auto',
                'decoding'      => 'async',
                'sizes'         => $large ? '(min-width: 1024px) 60vw, 100vw' : '(min-width: 1024px) 30vw, (min-width: 768px) 50vw, 100vw',
                'alt'           => '',
            ]);
        } ?>
        <span class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent opacity-0 transition duration-500 group-hover:opacity-100"></span>
        <?php if ($series && $series['total'] > 1) : ?>
            <span class="absolute start-3 top-3 rounded-full bg-white/90 px-3 py-1 text-[11px] font-bold text-primary backdrop-blur">
                پرونده · قسمت <?= esc_html(gl_fa_num($series['number'])); ?>
            </span>
        <?php endif; ?>
    </a>
    <div class="flex flex-1 flex-col pt-4">
        <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
            <?php if ($cat) : ?>
                <a href="<?= esc_url(get_category_link($cat)); ?>" class="relative z-[1] font-bold text-secondary hover:underline"><?= esc_html($cat->name); ?></a>
                <span aria-hidden="true">·</span>
            <?php endif; ?>
            <span><?= esc_html(gl_fa_num(gl_reading_minutes($pid))); ?> دقیقه مطالعه</span>
        </div>
        <h3 class="font-black leading-snug text-gray-900 <?= $large ? 'text-2xl lg:text-3xl' : 'text-lg'; ?>">
            <a href="<?php the_permalink(); ?>" class="decoration-secondary/60 decoration-2 underline-offset-[6px] transition-colors duration-300 group-hover:text-secondary group-hover:underline"><?php the_title(); ?></a>
        </h3>
        <p class="mt-2 line-clamp-2 text-sm leading-7 text-gray-600"><?= esc_html(wp_trim_words(get_the_excerpt(), $large ? 34 : 22)); ?></p>
        <time class="mt-auto pt-3 text-xs text-gray-400" datetime="<?= esc_attr(get_the_date('c')); ?>"><?= esc_html(function_exists('shamsi_date') ? shamsi_date('j F Y', get_the_time('U')) : get_the_date()); ?></time>
    </div>
</article>
