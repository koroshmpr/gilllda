<div class="flex md:col-span-3 xl:col-span-1 flex-col gap-y-4 justify-center">
    <?php
    $args = array(
        'logoSize' => 'max-h-15 w-auto',
        'class' => 'xl:justify-start ',
        'logoLink' => 'footer_logo',
    );
    get_template_part('template-parts/global/logo', null, $args);
    ?>
    <p class=" max-xl:text-center text-2xl"><?= get_field('footer_content', 'option') ?? ''; ?></p>
    <?php if (has_nav_menu('menu-2')) : ?>
        <nav aria-label="<?php esc_attr_e('Footer Menu', 'bluebox'); ?>">
            <?php
            wp_nav_menu(
                array(
                    'theme_location' => 'menu-2',
                    'menu_class' => 'max-xl:justify-center flex divide-x-2 divide-white/40 flex-wrap items-center',
                    'depth' => 1,
                    'link_before' => '<span class="px-4 lg:text-xl text-base text-white/60 hover:text-white transition-all leading-tight">',
                    'link_after' => '</span>',
                )
            );
            ?>
        </nav>
    <?php endif;
    get_template_part('template-parts/shop/enamad-list');
    ?>
</div>