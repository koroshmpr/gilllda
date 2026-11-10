<?php
/**
 * Magazine masthead + category chips.
 * @var array $args ['title' => string, 'desc' => string, 'current' => term_id]
 */
$current = (int) ($args['current'] ?? 0);
$blog    = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
$cats    = get_categories(['hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC']);
?>
<header class="relative overflow-hidden">
    <div class="pointer-events-none absolute -top-32 end-0 size-[28rem] rounded-full bg-secondary/10 blur-3xl" aria-hidden="true"></div>
    <div class="container relative px-4 pb-6 pt-8 lg:px-0 lg:pt-12">
        <p class="mb-3 flex items-center gap-2 text-sm font-bold text-secondary mag-in" style="--d:0"><span class="h-0.5 w-8 bg-current"></span>مجله گیلدا</p>
        <h1 class="text-3xl font-black text-gray-900 lg:text-5xl mag-in" style="--d:1"><?= esc_html($args['title'] ?? ''); ?></h1>
        <?php if (!empty($args['desc'])) : ?>
            <p class="mt-4 max-w-2xl leading-8 text-gray-600 mag-in" style="--d:2"><?= esc_html($args['desc']); ?></p>
        <?php endif; ?>
        <?php if ($cats) : ?>
            <nav class="-mx-4 mt-7 overflow-x-auto px-4 [scrollbar-width:none] lg:mx-0 lg:px-0 mag-in" style="--d:3" aria-label="دسته‌بندی‌های مجله">
                <ul class="flex w-max gap-2">
                    <li><a href="<?= esc_url($blog); ?>" class="inline-flex min-h-10 items-center rounded-full px-4 text-sm font-bold transition <?= !$current && is_home() ? 'bg-primary text-white' : 'border border-gray-200 text-gray-600 hover:border-primary/40 hover:text-primary'; ?>">همه مقالات</a></li>
                    <?php foreach ($cats as $c) : ?>
                        <li><a href="<?= esc_url(get_category_link($c)); ?>" <?= $current === $c->term_id ? 'aria-current="page"' : ''; ?> class="inline-flex min-h-10 items-center gap-2 rounded-full px-4 text-sm font-bold transition <?= $current === $c->term_id ? 'bg-primary text-white' : 'border border-gray-200 text-gray-600 hover:border-primary/40 hover:text-primary'; ?>">
                            <?= esc_html($c->name); ?><span class="text-xs font-normal opacity-60"><?= esc_html(gl_fa_num($c->count)); ?></span>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</header>
