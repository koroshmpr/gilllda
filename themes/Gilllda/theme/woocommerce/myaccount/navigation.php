<nav class="woocommerce-MyAccount-navigation lg:sticky top-28 p-0" aria-label="<?php esc_html_e( 'Account pages', 'woocommerce' ); ?>">
    <ul class="list-none max-lg:flex overflow-x-scroll gap-0.5 p-0 !m-0">
        <?php
        // 1. Define your icon mapping here
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
            // 2. Get the icon for this specific item, or use a default
            $icon_class = isset( $icons[ $endpoint ] ) ? $icons[ $endpoint ] : 'dashicons-admin-generic';
            ?>

            <li class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?> bg-gray-50 flex flex-col text-nowrap hover:bg-gray-100 items-center my-0 text-center border border-gray-200 <?php echo $active ? '!bg-primary' : ''; ?>">
                <a class="no-underline w-full p-3 px-5 flex justify-center items-center gap-1 <?= $active ? '!text-white' : '!text-black/50'; ?>" href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?>>

                    <span class="dashicons <?php echo esc_attr( $icon_class ); ?> mb-1"></span>

                    <span class="text-xs <?= $active ? 'font-bold' : 'font-medium'; ?>"><?php echo esc_html( $label ); ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
