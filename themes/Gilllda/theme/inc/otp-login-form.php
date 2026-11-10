<?php

// ==========================================
// PLAN A: ورود و ثبت‌نام پیامکی (OTP)
// ==========================================

// ابزارهای کمکی امنیتی
// ------------------------------------------

/**
 * ورود پیامکی فقط وقتی فعال است که کلید و قالب کاوه‌نگار تنظیم شده باشد
 * (ثابت‌های KAVENEGAR_API_KEY / KAVENEGAR_TEMPLATE در wp-config یا فیلدهای تنظیمات سایت).
 * تا آن موقع ورود و ثبت‌نام فقط با ایمیل و رمز عبور انجام می‌شود.
 */
function gl_otp_enabled() {
    static $enabled = null;
    if ($enabled === null) {
        $key  = defined('KAVENEGAR_API_KEY') ? KAVENEGAR_API_KEY : (function_exists('get_field') ? get_field('kavenegar_api_key', 'option') : '');
        $tpl  = defined('KAVENEGAR_TEMPLATE') ? KAVENEGAR_TEMPLATE : (function_exists('get_field') ? get_field('kavenegar_template_name', 'option') : '');
        $enabled = (bool) apply_filters('gl_otp_enabled', $key && $tpl && $key !== 'YOUR_KAVENEGAR_API_KEY' && $tpl !== 'YOUR_TEMPLATE_NAME');
    }
    return $enabled;
}

/** ثبت اکشن‌های AJAX پیامکی فقط در صورت فعال بودن OTP */
add_action('init', function () {
    if (!gl_otp_enabled()) {
        return;
    }
    add_action('wp_ajax_nopriv_kavenegar_send_otp', 'handle_kavenegar_send_otp');
    add_action('wp_ajax_kavenegar_send_otp', 'handle_kavenegar_send_otp');
    add_action('wp_ajax_nopriv_kavenegar_verify_otp', 'handle_kavenegar_verify_otp');
    add_action('wp_ajax_nopriv_kavenegar_register_user', 'handle_kavenegar_register_user');
    add_action('wp_ajax_update_user_phone_with_otp', 'handle_update_user_phone_with_otp');
    add_action('woocommerce_edit_account_form', 'render_otp_phone_updater_in_my_account');
});

/** نام کاربری یکتا از روی ایمیل (برای ثبت‌نام بدون نام کاربری) */
function gl_username_from_email($email) {
    $base = sanitize_user(strtolower(strstr($email, '@', true) ?: 'user'), true);
    $base = $base !== '' ? substr($base, 0, 40) : 'user';
    $name = $base;
    $i = 1;
    while (username_exists($name)) {
        $name = $base . (++$i);
    }
    return $name;
}

/** تبدیل ارقام فارسی/عربی به انگلیسی و حذف فاصله‌ها */
function gl_normalize_digits($value) {
    $value = (string) wp_unslash($value);
    $value = strtr($value, [
        '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
    ]);
    return preg_replace('/[^\d]/', '', $value);
}

/** شماره موبایل نرمال‌شده (09xxxxxxxxx) یا رشته خالی */
function gl_request_phone() {
    $phone = gl_normalize_digits($_POST['phone'] ?? '');
    if (strpos($phone, '98') === 0 && strlen($phone) === 12) {
        $phone = '0' . substr($phone, 2);
    }
    return preg_match('/^09\d{9}$/', $phone) ? $phone : '';
}

function gl_client_ip() {
    return preg_replace('/[^0-9a-fA-F:.]/', '', $_SERVER['REMOTE_ADDR'] ?? '');
}

/**
 * بررسی سخت‌گیرانه کد OTP با محدودیت تعداد تلاش.
 * کد خالی یا منقضی هرگز قبول نمی‌شود؛ بعد از ۵ تلاش اشتباه کد باطل می‌شود.
 */
function gl_check_otp($phone, $otp) {
    $saved = get_transient('otp_' . $phone);
    if (!$phone || !$saved || !preg_match('/^\d{5}$/', $otp)) {
        return false;
    }
    if (hash_equals((string) $saved, (string) $otp)) {
        delete_transient('otp_fail_' . $phone);
        return true;
    }
    $fails = (int) get_transient('otp_fail_' . $phone) + 1;
    if ($fails >= 5) {
        delete_transient('otp_' . $phone);
        delete_transient('otp_fail_' . $phone);
    } else {
        set_transient('otp_fail_' . $phone, $fails, 10 * MINUTE_IN_SECONDS);
    }
    return false;
}

/** پیدا کردن کاربر با شماره؛ اول حساب‌های تأییدشده */
function gl_find_user_by_phone($phone) {
    $verified = get_users([
        'number'     => 1,
        'meta_query' => [
            ['key' => 'billing_phone', 'value' => $phone],
            ['key' => 'is_phone_verified', 'value' => '1'],
        ],
    ]);
    if ($verified) {
        return $verified[0];
    }
    $any = get_users(['meta_key' => 'billing_phone', 'meta_value' => $phone, 'number' => 2]);
    return count($any) === 1 ? $any[0] : null;
}

// ۱. ارسال OTP (Send OTP)
function handle_kavenegar_send_otp() {
    check_ajax_referer('my-nonce', 'security');

    $phone = gl_request_phone();
    if (!$phone) {
        wp_send_json_error(['message' => 'شماره معتبر نیست']);
    }

    // محدودیت آی‌پی: حداکثر ۵ درخواست در ۱۰ دقیقه (قبل از ارسال شمرده می‌شود)
    $ip = gl_client_ip();
    $ip_attempts = (int) get_transient('otp_ip_attempts_' . $ip);
    if ($ip_attempts >= 5) {
        wp_send_json_error(['message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفا ۱۰ دقیقه دیگر دوباره تلاش کنید.']);
    }

    // محدودیت شماره: یک پیامک در دقیقه و حداکثر ۸ پیامک در روز
    if (get_transient('otp_sent_time_' . $phone)) {
        wp_send_json_error(['message' => 'لطفا پس از اتمام زمان تایمر (یک دقیقه) دوباره تلاش کنید.']);
    }
    $daily = (int) get_transient('otp_daily_' . $phone);
    if ($daily >= 8) {
        wp_send_json_error(['message' => 'سقف ارسال کد برای این شماره امروز پر شده است.']);
    }
    set_transient('otp_ip_attempts_' . $ip, $ip_attempts + 1, 10 * MINUTE_IN_SECONDS);
    set_transient('otp_sent_time_' . $phone, true, 60);
    set_transient('otp_daily_' . $phone, $daily + 1, DAY_IN_SECONDS);

    $otp = (string) wp_rand(10000, 99999);
    set_transient('otp_' . $phone, $otp, 3 * MINUTE_IN_SECONDS);
    delete_transient('otp_fail_' . $phone);

    $api_key  = defined('KAVENEGAR_API_KEY') ? KAVENEGAR_API_KEY : (function_exists('get_field') ? get_field('kavenegar_api_key', 'option') : '');
    $template = defined('KAVENEGAR_TEMPLATE') ? KAVENEGAR_TEMPLATE : (function_exists('get_field') ? get_field('kavenegar_template_name', 'option') : '');

    if (empty($api_key) || empty($template) || $api_key === 'YOUR_KAVENEGAR_API_KEY') {
        delete_transient('otp_' . $phone);
        wp_send_json_error(['message' => 'سرویس پیامک تنظیم نشده است. لطفا با رمز عبور وارد شوید.']);
    }

    $url = add_query_arg(
        ['receptor' => $phone, 'token' => $otp, 'template' => rawurlencode($template)],
        'https://api.kavenegar.com/v1/' . rawurlencode($api_key) . '/verify/lookup.json'
    );
    $response = wp_remote_get($url, ['timeout' => 15]);
    $body = is_wp_error($response) ? null : json_decode(wp_remote_retrieve_body($response), true);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200 || (int) ($body['return']['status'] ?? 0) !== 200) {
        delete_transient('otp_' . $phone);
        delete_transient('otp_sent_time_' . $phone);
        wp_send_json_error(['message' => 'ارسال پیامک ناموفق بود. چند لحظه دیگر دوباره تلاش کنید.']);
    }

    wp_send_json_success(['message' => 'کد ارسال شد', 'timer' => 60]);
}

// ۲. بررسی OTP و وضعیت کاربر
function handle_kavenegar_verify_otp() {
    check_ajax_referer('my-nonce', 'security');

    $phone = gl_request_phone();
    $otp = gl_normalize_digits($_POST['otp'] ?? '');

    if (!gl_check_otp($phone, $otp)) {
        wp_send_json_error(['message' => 'کد تایید اشتباه است یا منقضی شده.']);
    }

    $user = gl_find_user_by_phone($phone);

    if ($user) {
        // کاربر وجود دارد -> لاگین
        $user_id = $user->ID;

        // **جدید: چون کاربر با OTP وارد شده، شماره‌اش قطعا تایید شده است**
        update_user_meta($user_id, 'is_phone_verified', '1');

        clean_user_cache($user_id);
        wp_clear_auth_cookie();
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        delete_transient('otp_' . $phone);
        wp_send_json_success(['action' => 'logged_in']);
    } else {
        // کد تا تکمیل فرم ثبت‌نام معتبر می‌ماند؛ ثبت‌نام همین مجوز را می‌خواهد
        set_transient('otp_ok_' . $phone, 1, 10 * MINUTE_IN_SECONDS);
        set_transient('otp_' . $phone, $otp, 10 * MINUTE_IN_SECONDS);
        wp_send_json_success(['action' => 'needs_registration']);
    }
}

// ۳. تکمیل ثبت‌نام کاربر جدید با پیامک
function handle_kavenegar_register_user() {
    check_ajax_referer('my-nonce', 'security');

    $phone = gl_request_phone();
    $otp = gl_normalize_digits($_POST['otp'] ?? '');

    if (!$phone || !get_transient('otp_ok_' . $phone) || !gl_check_otp($phone, $otp)) {
        wp_send_json_error(['message' => 'نشست شما منقضی شده است. لطفا دوباره تلاش کنید.']);
    }

    if (get_users(['meta_key' => 'billing_phone', 'meta_value' => $phone, 'number' => 1, 'fields' => 'ID'])) {
        wp_send_json_error(['message' => 'این شماره موبایل قبلاً در سیستم ثبت شده است.']);
    }

    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $username = sanitize_user(wp_unslash($_POST['username'] ?? ''), true);
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    if ($username === '') {
        $username = 'u' . $phone;
    }

    if (username_exists($username) || email_exists($email)) {
        wp_send_json_error(['message' => 'نام کاربری یا ایمیل از قبل وجود دارد.']);
    }

    $random_password = wp_generate_password(12, false);
    $user_id = wp_create_user($username, $random_password, $email);

    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => $user_id->get_error_message()]);
    }

    $role = class_exists('WooCommerce') ? 'customer' : 'subscriber';
    wp_update_user(['ID' => $user_id, 'display_name' => $name, 'first_name' => $name, 'role' => $role]);

    update_user_meta($user_id, 'billing_phone', $phone);
    update_user_meta($user_id, 'shipping_phone', $phone);
    update_user_meta($user_id, 'billing_first_name', $name);
    update_user_meta($user_id, 'shipping_first_name', $name);
    update_user_meta($user_id, 'billing_email', $email);

    // **جدید: ثبت وضعیت تایید شده**
    update_user_meta($user_id, 'is_phone_verified', '1');

    clean_user_cache($user_id);
    wp_clear_auth_cookie();
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);

    delete_transient('otp_' . $phone);
    delete_transient('otp_ok_' . $phone);
    wp_send_json_success(['action' => 'registered_and_logged_in']);
}

// ==========================================
// PLAN B: ورود و ثبت‌نام کلاسیک (با رمز عبور)
// ==========================================

// ۴. ورود با ایمیل/نام کاربری
add_action('wp_ajax_nopriv_classic_login', 'handle_classic_login');
function handle_classic_login() {
    check_ajax_referer('my-nonce', 'security');

    $username = sanitize_text_field(wp_unslash($_POST['username'] ?? ''));
    $password = (string) wp_unslash($_POST['password'] ?? '');

    // محدودیت حدس رمز: ۸ تلاش ناموفق در ۱۵ دقیقه برای هر آی‌پی یا نام کاربری
    $keys = ['login_fail_ip_' . md5(gl_client_ip()), 'login_fail_u_' . md5(strtolower($username))];
    foreach ($keys as $key) {
        if ((int) get_transient($key) >= 8) {
            wp_send_json_error(['message' => 'تلاش‌های ناموفق زیاد بود. لطفا ۱۵ دقیقه دیگر دوباره تلاش کنید یا با کد پیامکی وارد شوید.']);
        }
    }

    $creds = ['user_login' => $username, 'user_password' => $password, 'remember' => true];
    $user = wp_signon($creds, is_ssl());

    if (is_wp_error($user)) {
        foreach ($keys as $key) {
            set_transient($key, (int) get_transient($key) + 1, 15 * MINUTE_IN_SECONDS);
        }
        wp_send_json_error(['message' => 'نام کاربری یا رمز عبور اشتباه است.']);
    }
    foreach ($keys as $key) {
        delete_transient($key);
    }
    wp_send_json_success(['action' => 'logged_in']);
}

// ۵. ثبت نام با رمز عبور
add_action('wp_ajax_nopriv_classic_register', 'handle_classic_register');
function handle_classic_register() {
    check_ajax_referer('my-nonce', 'security');

    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $username = sanitize_user(wp_unslash($_POST['username'] ?? ''), true);
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $password = (string) wp_unslash($_POST['password'] ?? '');
    $phone = gl_request_phone();

    if ($name === '') {
        wp_send_json_error(['message' => 'لطفا نام و نام خانوادگی را وارد کنید.']);
    }
    if (!is_email($email)) {
        wp_send_json_error(['message' => 'ایمیل وارد شده معتبر نیست.']);
    }
    if (strlen($password) < 8) {
        wp_send_json_error(['message' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.']);
    }
    if (!$phone) {
        wp_send_json_error(['message' => 'شماره موبایل نامعتبر است.']);
    }

    $existing_users_with_phone = get_users(['meta_key' => 'billing_phone', 'meta_value' => $phone, 'number' => 1]);
    if (!empty($existing_users_with_phone)) {
        wp_send_json_error(['message' => 'این شماره موبایل قبلاً در سیستم ثبت شده است.']);
    }

    if (email_exists($email)) {
        wp_send_json_error(['message' => 'با این ایمیل قبلاً حساب ساخته شده است. وارد شوید یا رمز را بازیابی کنید.']);
    }
    if ($username === '') {
        $username = gl_username_from_email($email);
    } elseif (username_exists($username)) {
        wp_send_json_error(['message' => 'این نام کاربری قبلاً گرفته شده است.']);
    }

    // wc_create_new_customer: نقش customer، ایمیل خوش‌آمد ووکامرس و هوک‌های پلاگین‌ها
    $user_id = function_exists('wc_create_new_customer')
        ? wc_create_new_customer($email, $username, $password, ['first_name' => $name, 'display_name' => $name])
        : wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => wp_strip_all_tags($user_id->get_error_message())]);
    }

    wp_update_user(['ID' => $user_id, 'display_name' => $name, 'first_name' => $name]);

    update_user_meta($user_id, 'billing_phone', $phone);
    update_user_meta($user_id, 'shipping_phone', $phone);
    update_user_meta($user_id, 'billing_first_name', $name);
    update_user_meta($user_id, 'shipping_first_name', $name);
    update_user_meta($user_id, 'billing_email', $email);

    // **جدید: ثبت وضعیت تایید نشده چون فقط تایپ شده است**
    update_user_meta($user_id, 'is_phone_verified', '0');

    clean_user_cache($user_id);
    wp_clear_auth_cookie();
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);

    wp_send_json_success(['action' => 'registered_and_logged_in']);
}

// ==========================================
// تنظیمات پنل مدیریت وردپرس
// ==========================================

add_filter('manage_users_columns', 'add_phone_column_to_users_table');
function add_phone_column_to_users_table($columns) {
    $columns['billing_phone'] = 'شماره موبایل';
    return $columns;
}

add_filter('manage_users_custom_column', 'show_phone_data_in_users_table', 10, 3);
function show_phone_data_in_users_table($val, $column_name, $user_id) {
    if ($column_name === 'billing_phone') {
        $phone = get_user_meta($user_id, 'billing_phone', true);
        $is_verified = get_user_meta($user_id, 'is_phone_verified', true);

        if (!$phone) return 'ندارد';

        // نمایش وضعیت تایید در لیست کاربران ادمین
        $badge = $is_verified === '1'
            ? '<span style="color:green; font-weight:bold; font-size:11px;">(تایید شده)</span>'
            : '<span style="color:red; font-weight:bold; font-size:11px;">(تایید نشده)</span>';

        return esc_html($phone) . ' ' . $badge;
    }
    return $val;
}

// ==========================================
// حساب کاربری ووکامرس (آپدیت امن شماره با OTP)
// ==========================================

function handle_update_user_phone_with_otp() {
    check_ajax_referer('my-nonce', 'security');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'لطفا ابتدا وارد حساب خود شوید.']);
    }

    $phone = gl_request_phone();
    $otp = gl_normalize_digits($_POST['otp'] ?? '');

    if (!gl_check_otp($phone, $otp)) {
        wp_send_json_error(['message' => 'کد تایید اشتباه است یا منقضی شده.']);
    }

    $current_user_id = get_current_user_id();
    $existing_users = get_users([
        'meta_key' => 'billing_phone',
        'meta_value' => $phone,
        'exclude' => [$current_user_id]
    ]);

    if (!empty($existing_users)) {
        wp_send_json_error(['message' => 'این شماره توسط کاربر دیگری ثبت شده است.']);
    }

    update_user_meta($current_user_id, 'billing_phone', $phone);
    update_user_meta($current_user_id, 'shipping_phone', $phone);

    // **جدید: ثبت شماره به عنوان تایید شده پس از ویرایش موفق**
    update_user_meta($current_user_id, 'is_phone_verified', '1');
    delete_transient('otp_' . $phone);

    wp_send_json_success(['message' => 'شماره موبایل با موفقیت بروزرسانی و تایید شد.']);
}

function render_otp_phone_updater_in_my_account() {
    $user_id = get_current_user_id();
    $current_phone = get_user_meta($user_id, 'billing_phone', true);
    $is_verified = get_user_meta($user_id, 'is_phone_verified', true);

    $display_phone = $current_phone ? $current_phone : 'ثبت نشده';

    // ساخت بج گرافیکی برای کاربر
    $status_html = '';
    if ($current_phone) {
        if ($is_verified === '1') {
            $status_html = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 mr-2">تایید شده</span>';
            $btn_text = 'تغییر شماره';
        } else {
            $status_html = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 mr-2">تایید نشده</span>';
            $btn_text = 'تایید یا تغییر شماره';
        }
    } else {
        $btn_text = 'ثبت شماره موبایل';
    }
    ?>
    <div x-data="myAccountPhoneUpdater()" class="w-full mt-8 mb-8 p-6 bg-gray-50 border border-gray-200 rounded-xl">
        <h3 class="text-lg font-bold text-gray-800 mb-4">شماره موبایل حساب کاربری</h3>

        <div x-show="!isEditing" class="flex items-center justify-between">
            <div>
                <span class="text-sm text-gray-500 block mb-1">شماره فعلی شما:</span>
                <div class="flex items-center">
                    <span class="font-bold text-lg dir-ltr block text-left" x-text="currentPhoneDisplay"><?php echo esc_html($display_phone); ?></span>
                    <?php echo $status_html; ?>
                </div>
            </div>
            <button type="button" @click="isEditing = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                <?php echo esc_html($btn_text); ?>
            </button>
        </div>

        <div x-show="isEditing" style="display: none;" class="mt-4 border-t border-gray-200 pt-4">
            <template x-if="message">
                <div :class="isError ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'" class="p-3 rounded-lg text-sm mb-4 text-center" x-text="message"></div>
            </template>

            <div x-show="step === 1">
                <label class="block text-gray-700 text-sm font-bold mb-2">شماره موبایل جدید جهت تایید</label>
                <div class="flex gap-2">
                    <input type="tel" x-model="newPhone" placeholder="09123456789" class="w-full px-4 py-2 rounded-lg border border-gray-300 text-left dir-ltr focus:ring-2 focus:ring-blue-500">
                    <button type="button" @click="sendUpdateOtp()" :disabled="isLoading" class="whitespace-nowrap px-4 py-2 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition disabled:opacity-50">
                        <span x-show="!isLoading">ارسال کد</span>
                        <span x-show="isLoading">کمی صبر...</span>
                    </button>
                </div>
                <button type="button" @click="cancelEdit()" class="mt-3 text-sm text-gray-500 hover:text-gray-800">انصراف</button>
            </div>

            <div x-show="step === 2" style="display: none;">
                <label class="block text-gray-700 text-sm font-bold mb-2">کد تایید پیامک شده</label>
                <input type="text" x-model="otp" autocomplete="one-time-code" inputmode="numeric" placeholder="- - - - -" maxlength="5" class="w-full px-4 py-3 rounded-lg border border-gray-300 text-center tracking-widest text-xl mb-3 focus:ring-2 focus:ring-blue-500">

                <div class="flex gap-2">
                    <button type="button" @click="verifyUpdateOtp()" :disabled="isLoading" class="w-full px-4 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition disabled:opacity-50">
                        <span x-show="!isLoading">تایید و ذخیره</span>
                        <span x-show="isLoading">در حال بررسی...</span>
                    </button>
                    <button type="button" @click="step = 1" class="w-1/3 px-4 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">
                        اصلاح شماره
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('myAccountPhoneUpdater', () => ({
                isEditing: false,
                step: 1,
                newPhone: '',
                otp: '',
                isLoading: false,
                message: '',
                isError: false,
                currentPhoneDisplay: '<?php echo esc_js($display_phone); ?>',
                ajaxUrl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',

                cancelEdit() {
                    this.isEditing = false;
                    this.step = 1;
                    this.newPhone = '';
                    this.message = '';
                },

                async sendUpdateOtp() {
                    if (!this.newPhone.match(/^09\d{9}$/)) {
                        this.isError = true;
                        this.message = 'شماره موبایل نامعتبر است.';
                        return;
                    }
                    this.isLoading = true;
                    this.message = '';

                    let formData = new FormData();
                    formData.append('action', 'kavenegar_send_otp');
                    formData.append('phone', this.newPhone);
                    if (typeof jsData !== 'undefined' && jsData.nonce) {
                        formData.append('security', jsData.nonce);
                    }

                    try {
                        let response = await fetch(this.ajaxUrl, { method: 'POST', body: formData });
                        let result = await response.json();
                        this.isLoading = false;

                        if (result.success) {
                            this.step = 2;
                            this.isError = false;
                            this.message = 'کد تایید ارسال شد.';
                        } else {
                            this.isError = true;
                            this.message = result.data.message || 'خطا در ارسال پیامک';
                        }
                    } catch (e) {
                        this.isLoading = false;
                        this.isError = true;
                        this.message = 'خطای شبکه';
                    }
                },

                async verifyUpdateOtp() {
                    if (this.otp.length < 5) return;
                    this.isLoading = true;
                    this.message = '';

                    let formData = new FormData();
                    formData.append('action', 'update_user_phone_with_otp');
                    formData.append('phone', this.newPhone);
                    formData.append('otp', this.otp);
                    if (typeof jsData !== 'undefined' && jsData.nonce) {
                        formData.append('security', jsData.nonce);
                    }

                    try {
                        let response = await fetch(this.ajaxUrl, { method: 'POST', body: formData });
                        let result = await response.json();
                        this.isLoading = false;

                        if (result.success) {
                            this.isError = false;
                            this.message = result.data.message;
                            // رفرش صفحه برای آپدیت شدن ویژوال وضعیت به "تایید شده"
                            setTimeout(() => { window.location.reload(); }, 1500);
                        } else {
                            this.isError = true;
                            this.message = result.data.message || 'کد اشتباه است.';
                        }
                    } catch (e) {
                        this.isLoading = false;
                        this.isError = true;
                        this.message = 'خطای شبکه';
                    }
                }
            }))
        })
    </script>
    <?php
}
// ==========================================
// قوانین ریدایرکت برای صفحه ورود اختصاصی
// ==========================================

add_action('template_redirect', 'custom_login_redirect_rules');
function custom_login_redirect_rules() {

    // مطمئن می‌شویم که ووکامرس فعال است تا خطایی رخ ندهد
    if (class_exists('WooCommerce')) {

        // ۱. اگر کاربر لاگین نکرده است و می‌خواهد صفحه حساب کاربری را ببیند -> انتقال به صفحه لاگین شما
        // صفحه «فراموشی رمز» و لینک بازنشانی ایمیل باید برای مهمان باز بماند
        if (is_account_page() && !is_user_logged_in() && !is_wc_endpoint_url('lost-password') && !isset($_GET['show-reset-form'])) {
            wp_safe_redirect(add_query_arg('redirect_to', rawurlencode(wc_get_page_permalink('myaccount')), home_url('/login/')));
            exit;
        }

        // ۲. اگر کاربر لاگین کرده است و می‌خواهد صفحه لاگین را ببیند -> انتقال به حساب کاربری
        // نکته: اگر نامک (Slug) برگه ورود شما چیزی غیر از "login" است، کلمه زیر را تغییر دهید
        if (is_page('login') && is_user_logged_in()) {
            $to = isset($_GET['redirect_to']) ? wp_validate_redirect(wp_unslash($_GET['redirect_to']), '') : '';
            wp_safe_redirect($to ?: wc_get_page_permalink('myaccount'));
            exit;
        }

    }
}