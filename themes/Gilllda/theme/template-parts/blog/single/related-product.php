<?php
$related = get_field('related_product');
if ($related):?>
    <div class="<?= $args['class'] ?? ''; ?>">
        <div class="flex gap-x-2 mb-2 items-center">
            <div class="h-[2px] bg-gradient-to-r from-primary/50 flex-1"></div>
            <div class="text-center font-bold">محصولات مرتبط</div>
            <div class="h-[2px] bg-gradient-to-l from-primary/50 flex-1"></div>
        </div>
        <div class="flex flex-col divide-y-3 gap-1 ">
            <?php
            foreach ($related as $related_product):
                // فراخوانی محصول ووکامرس برای استخراج قیمت
                $product = function_exists('wc_get_product') ? wc_get_product($related_product->ID) : null;
                $price_html = $product ? $product->get_price_html() : '';
                ?>
                <a href="<?= get_the_permalink($related_product->ID); ?>"
                   class="flex group relative border border-black/10 rounded-lg w-full justify-between items-center overflow-hidden">

                    <!-- عکس با همان افکت درخواستی شما -->
                    <img class="object-contain transition-all duration-700 w-full aspect-[4/1] rounded-2 group-hover:object-left"
                         width="200" height="100"
                         loading="lazy"
                         src="<?= get_the_post_thumbnail_url($related_product->ID); ?>"
                         alt="<?= esc_attr($related_product->post_name); ?>"/>

                    <!-- پس‌زمینه تیره که در حالت هاور روشن‌تر می‌شود -->
                    <div class="absolute inset-0 bg-gradient-to-l from-black/40 to-black/40 group-hover:from-black/50 group-hover:to-transparent transition-colors duration-500"></div>

                    <!-- باکسی که کل انیمیشن جابجایی روی آن اعمال می‌شود -->
                    <!-- فرمول جابجایی: از مرکز (right-1/2 translate-x-1/2) به سمت راست (right-5 translate-x-0) -->
                    <div class="absolute top-1/2 -translate-y-1/2 right-1/2 translate-x-1/2 group-hover:right-5 group-hover:translate-x-0 transition-all duration-500 flex flex-col items-center w-max max-w-[90%]">

                        <!-- عنوان محصول -->
                        <p class="text-base text-white font-bold m-0 transition-all duration-500">
                            <?= esc_html($related_product->post_title); ?>
                        </p>

                        <!-- باکس قیمت که به صورت کشویی (Accordion) از زیر متن باز می‌شود -->
                        <?php if ($price_html): ?>
                            <div class="grid grid-rows-[0fr] transition-all group-hover:delay-300 duration-500 group-hover:grid-rows-[1fr] opacity-0 group-hover:opacity-100 group-hover:mt-1 w-full text-right">
                                <div class="overflow-hidden">
                                    <!-- افکت لغزش قیمت از بالا به پایین هنگام ظاهر شدن -->
                                    <span class="text-white/90 font-medium flex items-center transform -translate-y-2 group-hover:translate-y-0 transition-transform duration-500 text-base gap-2 [&>ins]:no-underline [&>ins]:font-bold [&>del]:text-xs [&>del]:opacity-85 ">
                                        <?= $price_html; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </a>
            <?php
            endforeach; ?>
        </div>
    </div>
<?php endif; ?>