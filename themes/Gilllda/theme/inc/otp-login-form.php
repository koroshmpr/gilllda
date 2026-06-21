<?php

// ==========================================
// PLAN A: ورود و ثبت‌نام پیامکی (OTP)
// ==========================================

// ۱. ارسال OTP (Send OTP)
add_action('wp_ajax_nopriv_kavenegar_send_otp', 'handle_kavenegar_send_otp');
add_action('wp_ajax_kavenegar_send_otp', 'handle_kavenegar_send_otp');
function handle_kavenegar_send_otp() {
    check_ajax_referer('my-nonce', 'security');

    $phone = sanitize_text_field($_POST['phone'] ?? '');
    if (!preg_match('/^09\d{9}$/', $phone)) {
        wp_send_json_error(['message' => 'شماره معتبر نیست']);
    }

    // 1. IP Rate Limiting (Max 5 attempts per 10 minutes)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ip_attempts = get_transient('otp_ip_attempts_' . $ip) ?: 0;
    if ($ip_attempts >= 5) {
        wp_send_json_error(['message' => 'تعداد درخواست‌های شما از این آی‌پی بیش از حد مجاز است. لطفا ۱۰ دقیقه دیگر دوباره تلاش کنید.']);
    }

    // 2. Phone Number Rate Limiting (Max 1 attempt per 60 seconds)
    $last_sent = get_transient('otp_sent_time_' . $phone);
    if ($last_sent) {
        wp_send_json_error(['message' => 'لطفا پس از اتمام زمان تایمر (یک دقیقه) دوباره تلاش کنید.']);
    }

    $otp = wp_rand(10000, 99999);
    set_transient('otp_' . $phone, $otp, 3 * MINUTE_IN_SECONDS);

    // Get Kavenegar credentials securely from constants or ACF Option page
    $api_key = defined('KAVENEGAR_API_KEY') ? KAVENEGAR_API_KEY : get_field('kavenegar_api_key', 'option');
    $template = defined('KAVENEGAR_TEMPLATE') ? KAVENEGAR_TEMPLATE : get_field('kavenegar_template_name', 'option');

    if (empty($api_key) || $api_key === 'YOUR_KAVENEGAR_API_KEY') {
        $api_key = 'YOUR_KAVENEGAR_API_KEY'; // fallback placeholder
    }
    if (empty($template) || $template === 'YOUR_TEMPLATE_NAME') {
        $template = 'YOUR_TEMPLATE_NAME'; // fallback placeholder
    }

    $url = "https://api.kavenegar.com/v1/{$api_key}/verify/lookup.json?receptor={$phone}&token={$otp}&template={$template}";

    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'خطا در ارتباط با کاوه‌نگار']);
    }

    // Set rate-limiting transients
    set_transient('otp_sent_time_' . $phone, true, 60);
    set_transient('otp_ip_attempts_' . $ip, $ip_attempts + 1, 10 * MINUTE_IN_SECONDS);

    wp_send_json_success(['message' => 'کد ارسال شد', 'timer' => 60]);
}

// ۲. بررسی OTP و وضعیت کاربر
add_action('wp_ajax_nopriv_kavenegar_verify_otp', 'handle_kavenegar_verify_otp');
function handle_kavenegar_verify_otp() {
    check_ajax_referer('my-nonce', 'security');

    $phone = sanitize_text_field($_POST['phone']);
    $otp = sanitize_text_field($_POST['otp']);

    $saved_otp = get_transient('otp_' . $phone);
    if ($saved_otp != $otp) {
        wp_send_json_error(['message' => 'کد تایید اشتباه است یا منقضی شده.']);
    }

    $users = get_users([
        'meta_key' => 'billing_phone',
        'meta_value' => $phone,
        'number' => 1
    ]);

    if (!empty($users)) {
        // کاربر وجود دارد -> لاگین
        $user_id = $users[0]->ID;

        // **جدید: چون کاربر با OTP وارد شده، شماره‌اش قطعا تایید شده است**
        update_user_meta($user_id, 'is_phone_verified', '1');

        clean_user_cache($user_id);
        wp_clear_auth_cookie();
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        delete_transient('otp_' . $phone);
        wp_send_json_success(['action' => 'logged_in']);
    } else {
        wp_send_json_success(['action' => 'needs_registration']);
    }
}

// ۳. تکمیل ثبت‌نام کاربر جدید با پیامک
add_action('wp_ajax_nopriv_kavenegar_register_user', 'handle_kavenegar_register_user');
function handle_kavenegar_register_user() {
    check_ajax_referer('my-nonce', 'security');

    $phone = sanitize_text_field($_POST['phone']);
    $otp = sanitize_text_field($_POST['otp']);

    $saved_otp = get_transient('otp_' . $phone);
    if ($saved_otp != $otp) {
        wp_send_json_error(['message' => 'نشست شما منقضی شده است. لطفا دوباره تلاش کنید.']);
    }

    $name = sanitize_text_field($_POST['name']);
    $username = sanitize_user($_POST['username']);
    $email = sanitize_email($_POST['email']);

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
    wp_send_json_success(['action' => 'registered_and_logged_in']);
}

// ==========================================
// PLAN B: ورود و ثبت‌نام کلاسیک (با رمز عبور)
// ==========================================

// ۴. ورود با ایمیل/نام کاربری
add_action('wp_ajax_nopriv_classic_login', 'handle_classic_login');
function handle_classic_login() {
    check_ajax_referer('my-nonce', 'security');

    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];

    $creds = ['user_login' => $username, 'user_password' => $password, 'remember' => true];
    $user = wp_signon($creds, false);

    if (is_wp_error($user)) {
        wp_send_json_error(['message' => 'نام کاربری یا رمز عبور اشتباه است.']);
    }
    wp_send_json_success(['action' => 'logged_in']);
}

// ۵. ثبت نام با رمز عبور
add_action('wp_ajax_nopriv_classic_register', 'handle_classic_register');
function handle_classic_register() {
    check_ajax_referer('my-nonce', 'security');

    $name = sanitize_text_field($_POST['name']);
    $username = sanitize_user($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $phone = sanitize_text_field($_POST['phone']);

    if (!preg_match('/^09\d{9}$/', $phone)) {
        wp_send_json_error(['message' => 'شماره موبایل نامعتبر است.']);
    }

    $existing_users_with_phone = get_users(['meta_key' => 'billing_phone', 'meta_value' => $phone, 'number' => 1]);
    if (!empty($existing_users_with_phone)) {
        wp_send_json_error(['message' => 'این شماره موبایل قبلاً در سیستم ثبت شده است.']);
    }

    if (username_exists($username) || email_exists($email)) {
        wp_send_json_error(['message' => 'نام کاربری یا ایمیل از قبل وجود دارد.']);
    }

    $user_id = wp_create_user($username, $password, $email);
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

        return $phone . ' ' . $badge;
    }
    return $val;
}

// ==========================================
// حساب کاربری ووکامرس (آپدیت امن شماره با OTP)
// ==========================================

add_action('wp_ajax_update_user_phone_with_otp', 'handle_update_user_phone_with_otp');
function handle_update_user_phone_with_otp() {
    check_ajax_referer('my-nonce', 'security');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'لطفا ابتدا وارد حساب خود شوید.']);
    }

    $phone = sanitize_text_field($_POST['phone']);
    $otp = sanitize_text_field($_POST['otp']);

    $saved_otp = get_transient('otp_' . $phone);
    if ($saved_otp != $otp) {
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

add_action('woocommerce_edit_account_form', 'render_otp_phone_updater_in_my_account');
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
                <?php echo $btn_text; ?>
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
                <input type="text" x-model="otp" autocomplete="one-time-code" inputmode="numeric" placeholder="- - - -" class="w-full px-4 py-3 rounded-lg border border-gray-300 text-center tracking-widest text-xl mb-3 focus:ring-2 focus:ring-blue-500">

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
                ajaxUrl: '/wp-admin/admin-ajax.php',

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
                    if (this.otp.length < 4) return;
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
        if (is_account_page() && !is_user_logged_in()) {
            wp_redirect(home_url('/login/'));
            exit;
        }

        // ۲. اگر کاربر لاگین کرده است و می‌خواهد صفحه لاگین را ببیند -> انتقال به حساب کاربری
        // نکته: اگر نامک (Slug) برگه ورود شما چیزی غیر از "login" است، کلمه زیر را تغییر دهید
        if (is_page('login') && is_user_logged_in()) {
            wp_redirect(get_permalink(get_option('woocommerce_myaccount_page_id')));
            exit;
        }

    }
}