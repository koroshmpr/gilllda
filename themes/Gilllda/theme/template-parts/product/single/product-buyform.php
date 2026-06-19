<?php
global $product;
if (!$product) {
    return;
}

$available = $product->is_in_stock();

if ($available) :
    $is_variable = $product->is_type('variable');
    if ($is_variable) :
        $variations_json = wp_json_encode($product->get_available_variations());
    endif;
    ?>

    <form class="variations_form cart"
          action="<?php echo esc_url($product->get_permalink()); ?>"
          method="post"
          enctype="multipart/form-data"
          data-product_id="<?php echo absint($product->get_id()); ?>"
        <?php if ($is_variable) : ?>
            data-product_variations='<?php echo esc_attr($variations_json); ?>'
        <?php endif; ?>
    >

        <?php wp_nonce_field('woocommerce-add-to-cart', 'woocommerce-add-to-cart'); ?>

        <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>"/>
        <input type="hidden" name="variation_id" class="variation_id" value="0"/>

        <?php
        if ($is_variable) :
            $attributes = $product->get_attributes();

            foreach ($attributes as $attribute_name => $attribute) :

                if (!$attribute->get_variation()) {
                    continue;
                }

                $taxonomy = $attribute->get_name(); // e.g. 'pa_color'
                $label = wc_attribute_label($taxonomy);
                ?>

                <input type="hidden" name="attribute_<?php echo esc_attr($taxonomy); ?>" value=""/>

                <div class="w-full relative z-1 flex gap-3 bg-white border border-primary/30 rounded-lg overflow-hidden">
                    <span class="bg-primary text-white py-3 px-4 flex items-center select-none text-sm">
                        <?php echo esc_html($label); ?>
                    </span>
                    <fieldset class="flex p-1.5 flex-wrap items-center gap-3" aria-label="<?php echo esc_attr($label); ?>">
                        <legend class="sr-only"><?php echo esc_html($label); ?></legend>

                        <?php if ($attribute->is_taxonomy()) :

                            $terms = wc_get_product_terms($product->get_id(), $taxonomy, array('fields' => 'all'));

                            foreach ($terms as $term) :
                                $term_slug = $term->slug;
                                $input_id = 'var-' . esc_attr($taxonomy . '-' . $term_slug);

                                // Get ACF color picker field for the term (taxonomy_termID format)
                                $acf_color = get_field('color', $term->taxonomy . '_' . $term->term_id);
                                ?>

                                <label for="<?php echo $input_id; ?>"
                                       class="relative flex flex-1 w-full cursor-pointer hover:border-primary/30 items-center  justify-center rounded-full ring-0 focus:outline-none has-checked:ring-2 has-checked:ring-primary has-checked:bg-primary has-checked:text-white has-checked:ring-offset-2">

                                    <input class="variation-input sr-only"
                                           type="radio"
                                           id="<?php echo $input_id; ?>"
                                           name="attribute_<?php echo esc_attr($taxonomy); ?>"
                                           value="<?php echo esc_attr($term_slug); ?>"
                                           data-attribute_name="attribute_<?php echo esc_attr($taxonomy); ?>"
                                           required/>

                                    <?php if ($acf_color) : ?>
                                        <span class="sr-only"><?php echo esc_html($term->name); ?></span>
                                        <span
                                                class="color-swatch rounded-full size-6 border border-gray-300"
                                                title="<?php echo esc_attr($term->name); ?>"
                                                style="background-color: <?php echo esc_attr($acf_color); ?>;">
                                        </span>
                                    <?php else : ?>
                                        <span class="text-sm py-1 px-3 border rounded-full border-primary/10"><?php echo esc_html($term->name); ?></span>
                                    <?php endif; ?>

                                </label>
                            <?php endforeach;

                        endif; ?>
                    </fieldset>
                </div>

            <?php endforeach; ?>
            <div class="woocommerce-variation-price pb-3 pt-5 rounded-b-lg -mt-2 bg-gray-700 text-white text-center" style="display:none;">
                <?php echo $product->get_price_html(); ?>
            </div>
        <?php endif; ?>



        <?php if ($product->is_in_stock() && !$product->get_sold_individually()) : ?>
            <div class="flex items-center justify-between mt-2 bg-primary/5 border border-primary/20 rounded-t-lg p-2 pb-4">
                <label for="quantity-input" class="text-sm text-gray-800 ps-4">تعداد:</label>
                <div class="flex items-center border gap-1 p-0.5 border-gray-300 bg-white rounded-md">
                    <button type="button"
                            aria-label="decrease the quantity"
                            class="p-1 cursor-pointer border border-primary/10 bg-primary/5 rounded-md hover:bg-primary hover:text-white transition-all"
                            onclick="this.nextElementSibling.stepDown()">
                        <?php
                        $svg_args = array('size' => '20');
                        get_template_part('template-parts/svg/dash', null, $svg_args);
                        ?>
                    </button>
                    <input type="number" name="quantity" value="1" id="quantity-input" min="1" step="1"
                           class="w-12 text-center border-none focus:ring-0 text-sm bg-transparent"/>
                    <button type="button"
                            aria-label="increase the quantity"
                            class="p-1 cursor-pointer border border-primary/10 bg-primary/5 rounded-md hover:bg-primary hover:text-white transition-all"
                            onclick="this.previousElementSibling.stepUp()">
                        <?php get_template_part('template-parts/svg/plus', null, $svg_args); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <button type="submit"
                class="w-full bg-primary p-6 lg:py-4 duration-300 ease-out transition-all group/add cursor-pointer flex gap-x-2 justify-center hover:brightness-110 text-center rounded-lg text-white <?php echo ($product->is_in_stock() && ! $product->is_sold_individually()) ? '-mt-2' : 'mt-2'; ?> shadow-sm">
            <?php get_template_part('template-parts/svg/shop', null, [
                'size' => '20',
                'class' => 'group-hover/add:delay-100 rotate-45 group-hover/add:rotate-0 text-white duration-300 translate-x-2 opacity-0 transition-all group-hover/add:opacity-100 group-hover/add:translate-x-0'
            ]); ?>
            <span class="group-hover/add:-translate-x-0 font-bold text-sm transition-all duration-300 translate-x-3">
                <?php echo $product->is_purchasable() ? esc_html__('افزودن به سبد خرید', 'woocommerce') : esc_html($product->add_to_cart_text()); ?>
            </span>
        </button>
    </form>
<?php else : ?>
    <a href="<?php echo esc_url(home_url('/make-available/?' . $product->get_id())); ?>"
       aria-label="link to available product page"
       class="w-full bg-primary rounded-xl p-6 lg:py-5 lg:mt-4 group flex relative flex-col hover:brightness-125 justify-center text-center text-white">
        <span class="text-xs absolute bg-secondary group-hover:px-3 group-hover:translate-y-0.5 transition-all rounded-sm px-2 py-1 -top-3 start-1/2 translate-x-1/2">ناموجود</span>
        <span class="text-sm font-bold">درخواست موجود کردن!</span>
    </a>
<?php endif; ?>