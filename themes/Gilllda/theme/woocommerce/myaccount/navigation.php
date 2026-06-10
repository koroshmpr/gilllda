<div x-data="{ isMobileMenuOpen: false }"
     x-effect="document.body.style.overflow = isMobileMenuOpen ? 'hidden' : ''">

    <button @click="isMobileMenuOpen = true"
            class="lg:hidden flex items-center gap-2 p-3 bg-primary  text-white border border-gray-200 absolute top-2 start-4 w-fit justify-center text-sm font-bold rounded-lg">
        <?php
        $args = array(
            'size' => '20',
            'class' => '',
        );
        get_template_part('template-parts/svg/menu-dot', null, $args);
        ?>
    </button>

    <div x-show="isMobileMenuOpen"
         @click="isMobileMenuOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50 z-40 lg:hidden cursor-pointer"
         x-cloak>
    </div>

    <nav :class="isMobileMenuOpen ? '!translate-x-0' : 'translate-x-full'"
         class="woocommerce-MyAccount-navigation translate-x-full fixed top-0 left-0 h-full lg:translate-x-0 bg-white z-50 transform transition-transform duration-300 ease-in-out lg:transform-none lg:transition-none lg:w-auto lg:bg-transparent lg:z-auto lg:sticky lg:top-28 p-0"
         aria-label="<?php esc_html_e( 'Account pages', 'woocommerce' ); ?>">

        <div class="flex justify-between <?= current_user_can('administrator') ? 'pt-16' : ''; ?> items-center p-4 lg:hidden border-b border-gray-200 bg-gray-50">
            <span class="font-bold text-lg"><?php esc_html_e( 'My Account', 'woocommerce' ); ?></span>
            <button @click="isMobileMenuOpen = false" class="text-gray-600 hover:text-black focus:outline-none bg-gray-100 border border-gray-200 p-1">
                <?php
                $args = array(
                    'size' => '20',
                    'class' => '',
                );
                get_template_part('template-parts/svg/close', null, $args);
                ?>
            </button>
        </div>

        <ul class="list-none flex flex-col gap-0.5 p-4 lg:p-0 !m-0 overflow-y-auto h-[calc(100vh-65px)] lg:h-auto lg:overflow-visible">
            <?php
            // Define your icon mapping here
            $icons = array(
                'dashboard'       => 'dashicons-dashboard',
                'orders'          => 'dashicons-cart',
                'downloads'       => 'dashicons-download',
                'edit-address'    => 'dashicons-location',
                'payment-methods' => 'dashicons-id',
                'edit-account'    => 'dashicons-admin-users',
                'customer-logout' => 'dashicons-exit',
            );

            foreach ( wc_get_account_menu_items() as $endpoint => $label ) :
                $active = wc_is_current_account_menu_item( $endpoint );
                // Get the icon for this specific item, or use a default
                $icon_class = isset( $icons[ $endpoint ] ) ? $icons[ $endpoint ] : 'dashicons-admin-generic';
                ?>

                <li class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?> bg-gray-50 hover:bg-gray-100 my-0 border border-gray-200 <?php echo $active ? '!bg-primary' : ''; ?>">
                    <a class="no-underline w-full p-3 px-4 flex items-center gap-3 <?= $active ? '!text-white' : '!text-black/50'; ?>" href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?>>

                        <span class="dashicons <?php echo esc_attr( $icon_class ); ?>"></span>
                        <span class="text-sm <?= $active ? 'font-bold' : 'font-medium'; ?>"><?php echo esc_html( $label ); ?></span>

                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</div>