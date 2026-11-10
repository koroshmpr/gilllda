<?php
/**
 * Series («پرونده») timeline.
 * @var array $args ['series' => gl_series_info(), 'current' => post ID, 'flat' => bool]
 */
$series  = $args['series'] ?? null;
$current = (int) ($args['current'] ?? 0);
if (!$series) {
    return;
}
$flat = !empty($args['flat']);
?>
<div class="<?= $flat ? '' : 'flex min-h-0 shrink flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white'; ?>">
    <div class="<?= $flat ? 'mb-3' : 'border-b border-gray-100 p-5 pb-4'; ?>">
        <p class="text-xs font-bold text-secondary">پرونده</p>
        <p class="mt-1 font-black text-gray-900"><?= esc_html($series['name']); ?></p>
        <div class="mt-3 flex gap-1" aria-hidden="true">
            <?php foreach ($series['parts'] as $i => $pid) : ?>
                <span class="h-1.5 flex-1 rounded-full <?= $i < $series['number'] ? 'bg-secondary' : 'bg-gray-200'; ?>"></span>
            <?php endforeach; ?>
        </div>
    </div>
    <ol class="<?= $flat ? '' : 'min-h-0 overflow-y-auto overscroll-contain p-3'; ?> relative grid gap-1">
        <?php foreach ($series['parts'] as $i => $pid) :
            $is = $pid === $current; ?>
            <li>
                <a href="<?= esc_url(get_permalink($pid)); ?>" <?= $is ? 'aria-current="page"' : ''; ?>
                   class="group flex items-start gap-3 rounded-2xl p-2.5 transition <?= $is ? 'bg-secondary/[0.07]' : 'hover:bg-gray-50'; ?>">
                    <span class="mt-0.5 inline-flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-black <?= $is ? 'bg-secondary text-white' : ($i < $series['index'] ? 'bg-secondary/15 text-secondary' : 'bg-gray-100 text-gray-500'); ?>">
                        <?= esc_html(gl_fa_num($i + 1)); ?>
                    </span>
                    <span class="text-sm leading-6 <?= $is ? 'font-bold text-gray-900' : 'text-gray-600 group-hover:text-gray-900'; ?>"><?= esc_html(get_the_title($pid)); ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ol>
</div>
