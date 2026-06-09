<?php
$enamad = get_field('enamad', 'options');
$show = $enamad['show'] ?? false;
$List = $enamad['list'] ?? [];

if ($show && !empty($List)) :
    ?>
    <div class="flex items-center max-xl:justify-center gap-x-3">
        <?php foreach ($List as $index => $item) : ?>
                <?= $item['item'] ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
