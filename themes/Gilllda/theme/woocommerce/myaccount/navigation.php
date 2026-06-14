<div x-data="{ isMobileMenuOpen: false }"
     x-effect="document.body.style.overflow = isMobileMenuOpen ? 'hidden' : ''">

    <div x-show="isMobileMenuOpen"
         @click="isMobileMenuOpen = false"
         x-transition.opacity.duration.300ms
         class="fixed inset-0 bg-black/40 z-40 lg:hidden transition-all"
         style="display: none;"></div>

    <nav :class="isMobileMenuOpen ? 'w-[65vw] max-w-[280px] z-40' : 'max-lg:!w-12'"
         class="lg:w-[30%] float-right max-lg:fixed top-0 <?= current_user_can('administrator') ? 'max-lg:pt-24' : 'max-lg:pt-12'; ?> right-0 h-full bg-white border-l border-gray-100 transition-all duration-300 ease-out flex flex-col overflow-hidden lg:shadow-none lg:border-none lg:bg-transparent lg:static lg:h-auto lg:p-0"
         aria-label="<?php esc_html_e( 'Account pages', 'woocommerce' ); ?>">

        <div :class="isMobileMenuOpen ? 'p-1' : 'pt-1'" class="flex items-center gap-3 border-b transition-all duration-300 border-gray-200 bg-gray-50 lg:hidden relative shrink-0">

            <button @click="isMobileMenuOpen = !isMobileMenuOpen"
                    :class="isMobileMenuOpen ? 'rounded-lg border' : 'border-t'"
                    class="size-11 shrink-0 bg-white border-gray-200 flex items-center justify-center text-gray-600 transition-colors">
                <svg x-show="!isMobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                <svg x-show="isMobileMenuOpen" style="display:none;" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>

            <span :class="isMobileMenuOpen ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-4'"
                  class="font-black text-gray-800 text-sm whitespace-nowrap transition-all duration-300">
                <?php esc_html_e( 'My Account', 'woocommerce' ); ?>
            </span>
        </div>

        <ul class="list-none flex flex-col lg:gap-2 lg:p-0 m-0 overflow-y-auto overflow-x-hidden flex-1 lg:overflow-visible">
            <?php
            // دریافت لیست آیتم‌های ووکامرس
            $menu_items = wc_get_account_menu_items();

            // ۱. 🔴 حذف کردن تب "دانلودها"
            if (isset($menu_items['downloads'])) {
                unset($menu_items['downloads']);
            }

            // ۲. 🔴 آرایه SVG های اختصاصی برای هر بخش (جایگزین Dashicons)
            $svg_icons = array(
                'dashboard'       => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
                'orders'          => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
                'edit-address'    => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                'payment-methods' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
                'edit-account'    => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
                'customer-logout' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>',
            );

            // آیکون پیش‌فرض در صورتی که افزونه‌ای تب جدیدی اضافه کند
            $default_svg = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

            foreach ( $menu_items as $endpoint => $label ) :
                $active = wc_is_current_account_menu_item( $endpoint );
                $current_svg = $svg_icons[$endpoint] ?? $default_svg;
                ?>

                <li class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?> bg-gray-50 hover:bg-gray-100 max-lg:border-b lg:border border-gray-200 lg:rounded-xl transition-all <?php echo $active ? '!bg-primary !border-primary' : ''; ?>">

                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"
                       class="flex items-center h-10 px-1 lg:px-4 gap-3 no-underline <?php echo $active ? '!text-white' : '!text-gray-600'; ?>"
                        <?php echo $active ? 'aria-current="page"' : ''; ?>>

                        <div class="w-9 flex justify-center shrink-0">
                            <?php echo $current_svg; ?>
                        </div>

                        <span :class="isMobileMenuOpen ? 'opacity-100' : 'opacity-0 lg:opacity-100'"
                              class="text-sm whitespace-nowrap transition-all duration-300 <?php echo $active ? 'font-bold' : 'font-medium'; ?>">
                            <?php echo esc_html( $label ); ?>
                        </span>

                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</div>