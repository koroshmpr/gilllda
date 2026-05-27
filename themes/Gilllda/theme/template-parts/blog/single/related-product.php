<?php
$related = get_field('related_product');
if ($related):?>
    <div class="<?= $args['class'] ?? ''; ?>">
        <div class="flex gap-x-2 mb-2 items-center">
            <div class="h-[2px] bg-gradient-to-r from-primary/50 flex-1"></div>
            <div class="text-center font-bold">محصولات مرتبط</div>
            <div class="h-[2px] bg-gradient-to-l from-primary/50 flex-1"></div>
        </div>
        <div class="flex flex-col divide-y-2 divide-primary/10 rounded-md overflow-hidden">
            <?php
            foreach ($related as $related_product):
                ?>
                <a href="<?= get_the_permalink($related_product->ID); ?>"
                   class="flex group relative w-full justify-between items-center">
                    <img class="object-cover w-full aspect-[4/1] rounded-2" width="200" height="100"
                         src="<?= get_the_post_thumbnail_url($related_product->ID); ?>"
                         alt="link to <?= $related_product->post_name ?>"/>
                    <p class="text-base absolute inset-0 bg-black/40 group-hover:bg-black/60 text-white transition-all font-bold flex justify-center items-center"><?= esc_html($related_product->post_title); ?></p>

                </a>
            <?php
            endforeach; ?>
        </div>
    </div>
<?php endif; ?>