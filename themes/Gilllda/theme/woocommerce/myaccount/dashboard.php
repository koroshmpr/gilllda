<?php
/**
 * My Account Dashboard
 * Overridden to show a custom Tailwind/Alpine dashboard for Admins.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$current_user = wp_get_current_user();
$is_admin = in_array( 'administrator', (array) $current_user->roles );

if ( $is_admin ) {

    // --- ۱. جمع‌آوری داده‌های مدیر ---

    // دریافت تعداد کل سفارشات تکمیل شده
    $order_count = wc_orders_count( 'completed' );

    // دریافت تعداد کل محصولات منتشر شده
    $product_count = wp_count_posts('product')->publish;

    // محاسبه درآمد (همین ماه) - سازگار با HPOS
    $first_day_of_month = date('Y-m-01 00:00:00');
    $recent_completed_orders = wc_get_orders( array(
        'status'       => array( 'wc-completed' ),
        'date_created' => '>=' . $first_day_of_month,
        'limit'        => -1,
        'return'       => 'ids',
    ) );

    $monthly_sales = 0;
    foreach( $recent_completed_orders as $order_id ) {
        $order = wc_get_order( $order_id );
        $monthly_sales += $order->get_total();
    }

    // دریافت ۵ سفارش اخیر (با هر وضعیتی)
    $latest_orders = wc_get_orders( array(
        'limit'   => 5,
        'orderby' => 'date',
        'order'   => 'DESC',
    ) );

    // --- ۲. خروجی داشبورد با TAILWIND و ALPINE ---
    ?>
    <div class="w-full bg-gray-50/50 rounded-2xl font-sans" dir="rtl" x-data="{ showWelcome: true }">

        <!-- هدر و دکمه Alpine.js -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">نمای کلی مدیریت</h2>
                <p class="text-sm text-gray-500">نگاهی سریع به وضعیت و عملکرد فروشگاه شما.</p>
            </div>
            <button @click="showWelcome = !showWelcome" class="text-sm px-4 py-2 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 transition-colors text-gray-700 font-medium">
                بستن/نمایش پیام خوش‌آمدگویی
            </button>
        </div>

        <!-- پیام خوش‌آمدگویی قابل بستن با Alpine.js -->
        <div x-show="showWelcome" x-transition class="mb-8 p-4 bg-blue-50/80 border border-blue-100 rounded-xl flex items-start gap-3 text-blue-800">
            <svg class="w-5 h-5 text-blue-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <strong class="block font-semibold">خوش برگشتید، <?php echo esc_html( $current_user->display_name ); ?>!</strong>
                <span class="text-sm opacity-90">در اینجا خلاصه‌ای از فعالیت فروشگاه شما در ماه جاری آورده شده است.</span>
            </div>
            <button @click="showWelcome = false" class="mr-auto text-blue-500 hover:text-blue-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- شبکه آمارها (Stats Grid) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

            <!-- کارت درآمد -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-5">
                <div class="p-4 bg-green-50 text-green-600 rounded-xl">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 font-medium mb-1">درآمد (ماه جاری)</p>
                    <h3 class="text-2xl font-bold text-gray-900" dir="ltr"><?php echo wc_price( $monthly_sales ); ?></h3>
                </div>
            </div>

            <!-- کارت سفارشات -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-5">
                <div class="p-4 bg-blue-50 text-blue-600 rounded-xl">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 font-medium mb-1">کل سفارشات تکمیل شده</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo esc_html( $order_count ); ?></h3>
                </div>
            </div>

            <!-- کارت محصولات -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-5">
                <div class="p-4 bg-purple-50 text-purple-600 rounded-xl">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 font-medium mb-1">محصولات فعال</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo esc_html( $product_count ); ?></h3>
                </div>
            </div>
        </div>

        <!-- جدول سفارشات اخیر -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-900">سفارشات اخیر</h3>
                <a href="<?php echo admin_url('edit.php?post_type=shop_order'); ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium">&larr; مشاهده همه</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                    <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-semibold">سفارش</th>
                        <th class="px-6 py-4 font-semibold">تاریخ</th>
                        <th class="px-6 py-4 font-semibold">وضعیت</th>
                        <th class="px-6 py-4 font-semibold text-left">مجموع</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if ( ! empty( $latest_orders ) ) : ?>
                        <?php foreach ( $latest_orders as $order ) :
                            // تعیین رنگ‌های Tailwind بر اساس وضعیت سفارش
                            $status = $order->get_status();
                            $bg_color = 'bg-gray-100';
                            $text_color = 'text-gray-800';

                            if ( $status === 'completed' ) {
                                $bg_color = 'bg-green-100'; $text_color = 'text-green-800';
                            } elseif ( in_array($status, ['processing', 'pending']) ) {
                                $bg_color = 'bg-yellow-100'; $text_color = 'text-yellow-800';
                            } elseif ( in_array($status, ['cancelled', 'failed']) ) {
                                $bg_color = 'bg-red-100'; $text_color = 'text-red-800';
                            }
                            ?>
                            <tr class="hover:bg-gray-50/80 transition-colors">
                                <td class="px-6 py-4 font-medium text-blue-600">
                                    <a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>" dir="ltr" class="inline-block">
                                        #<?php echo esc_html( $order->get_id() ); ?>
                                    </a>
                                    <div class="text-xs text-gray-500 mt-0.5 font-normal"><?php echo esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></div>
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    <?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
                                </td>
                                <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo esc_attr($bg_color . ' ' . $text_color); ?>">
                                            <?php echo esc_html( wc_get_order_status_name( $status ) ); ?>
                                        </span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 text-left" dir="ltr">
                                    <?php echo $order->get_formatted_order_total(); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">هنوز سفارشی ثبت نشده است.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    <?php

} else {

    // --- ۳. داشبورد استاندارد ووکامرس برای کاربران عادی ---

    $allowed_html = array(
        'a' => array(
            'href' => array(),
        ),
    );

    echo '<p>';
    printf(
    /* translators: 1: user display name 2: logout url */
        wp_kses( __( 'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'woocommerce' ), $allowed_html ),
        '<strong>' . esc_html( $current_user->display_name ) . '</strong>',
        esc_url( wc_logout_url() )
    );
    echo '</p>';

    echo '<p>';
    printf(
        wp_kses(
        /* translators: 1: Orders URL 2: Address URL 3: Account URL. */
            __( 'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">shipping and billing addresses</a>, and <a href="%3$s">edit your password and account details</a>.', 'woocommerce' ),
            $allowed_html
        ),
        esc_url( wc_get_endpoint_url( 'orders' ) ),
        esc_url( wc_get_endpoint_url( 'edit-address' ) ),
        esc_url( wc_get_endpoint_url( 'edit-account' ) )
    );
    echo '</p>';

    /**
     * My Account dashboard.
     *
     * @since 2.6.0
     */
    do_action( 'woocommerce_account_dashboard' );

    /**
     * Deprecated woocommerce_before_my_account action.
     *
     * @deprecated 2.6.0
     */
    do_action( 'woocommerce_before_my_account' );

    /**
     * Deprecated woocommerce_after_my_account action.
     *
     * @deprecated 2.6.0
     */
    do_action( 'woocommerce_after_my_account' );
}
?>