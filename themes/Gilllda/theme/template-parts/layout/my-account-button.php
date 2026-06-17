<div
        x-data="{ open: false }"
        @mouseenter="open = true"
        @mouseleave="open = false"
        class="relative inline-block max-lg:contents text-left"
>
    <a
            aria-label="go to my account page"
        <?php if (is_user_logged_in()) : ?>
            href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"
        <?php else : ?>
            href="<?php echo esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>"
        <?php endif; ?>
            class="<?= $args['class'] ?? ''; ?> flex items-center gap-1 text-sm transition-transform duration-200"
    >
        <?php if (is_user_logged_in()) :
            $current_user = wp_get_current_user();
            $first_word = explode(' ', trim($current_user->display_name))[0];
            $first_letter = mb_substr($first_word, 0, 1, 'UTF-8');
            ?>

            <p class="max-lg:hidden px-3 text-sm">
                <?php echo esc_html($first_word); ?>
            </p>

            <p class="lg:hidden flex justify-center items-center leading-auto <?= !$args['active'] ? 'border border-gray-300 pt-2 p-1 text-sm size-8.5 bg-gray-50  absolute top-1/2 start-1/2 translate-x-1/2 -translate-y-1/2  rounded-full' : ''; ?>">
                <?php echo esc_html($first_letter); ?>
            </p>

        <?php else : ?>
            <?php
            $svg_args = array('size' => $args['svgSize'] ?? '', 'class' => $args['svgClass'] ?? '');
            get_template_part('template-parts/svg/person', null, $svg_args);
            ?>
        <?php endif; ?>
    </a>

    <?php if (!wp_is_mobile() && is_user_logged_in()): ?>
        <div
                x-show="open"
                x-cloak
                style="display: none"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                class="absolute right-0 mt-0 translate-x-1/2 w-52 origin-top-right rounded-md bg-white border border-gray-200 focus:outline-none z-[999] overflow-hidden shadow-xl"
        >

            <?php $current_user = wp_get_current_user(); ?>
            <div class="bg-gray-50 p-4 border-b border-gray-100">
                <p class="text-sm font-bold text-start text-gray-800 truncate"><?php echo esc_html($current_user->display_name); ?></p>
            </div>

            <nav>
                <ul class="list-none p-0 m-0 space-y-1">
                    <?php
                    $menu_items = wc_get_account_menu_items();
                    if (isset($menu_items['downloads'])) {
                        unset($menu_items['downloads']);
                    }
                    $svg_icons = array(
                        'dashboard' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
                        'orders' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
                        'edit-address' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                        'payment-methods' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
                        'edit-account' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
                        'customer-logout' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>',
                    );

                    $default_svg = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

                    foreach ($menu_items as $endpoint => $label) :
                        $active = wc_is_current_account_menu_item($endpoint);
                        $current_svg = $svg_icons[$endpoint] ?? $default_svg;
                        ?>
                        <li class="<?php echo wc_get_account_menu_item_classes($endpoint); ?> <?php echo $active ? '!bg-primary/10 border-r-4 !border-primary' : 'border-l-4 border-transparent'; ?>">
                            <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>"
                               class="group flex items-center px-3 py-2 text-xs <?php echo $active ? '!text-primary font-bold' : '!text-gray-600'; ?> hover:bg-gray-50 hover:text-primary transition-all duration-200">
                                <span class="ml-3 <?php echo $active ? '!text-primary' : '!text-gray-400'; ?> group-hover:text-primary transition-colors">
                                    <?php echo $current_svg; ?>
                                </span>
                                <span class="font-medium text-sm"><?php echo esc_html($label); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>