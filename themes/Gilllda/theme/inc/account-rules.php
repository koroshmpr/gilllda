<?php
/**
 * قوانین حساب کاربری و تسویه‌حساب
 *
 * - خرید مهمان همیشه بسته است: هر مشتری هنگام خرید حساب می‌سازد یا وارد می‌شود.
 * - موبایل در صورت‌حساب اجباری است (برای ارسال مرسوله و پیگیری سفارش).
 * این قوانین در کد قفل شده‌اند تا با تغییر تصادفی تنظیمات ووکامرس باز نشوند.
 *
 * @package bluebox
 */

if (!defined('ABSPATH')) {
    exit;
}

// ثبت‌نام هنگام خرید: فعال و اجباری
add_filter('woocommerce_checkout_registration_enabled', '__return_true');
add_filter('woocommerce_checkout_registration_required', '__return_true');
add_filter('pre_option_woocommerce_enable_guest_checkout', function () {
    return 'no';
});
add_filter('pre_option_woocommerce_enable_checkout_login_reminder', function () {
    return 'yes';
});

// موبایل اجباری و با کیبورد عددی
add_filter('woocommerce_billing_fields', function ($fields) {
    if (isset($fields['billing_phone'])) {
        $fields['billing_phone']['required'] = true;
        $fields['billing_phone']['label'] = 'شماره موبایل';
        $fields['billing_phone']['placeholder'] = '09123456789';
        $fields['billing_phone']['custom_attributes'] = array_merge(
            $fields['billing_phone']['custom_attributes'] ?? [],
            ['inputmode' => 'numeric', 'dir' => 'ltr', 'autocomplete' => 'tel']
        );
    }
    return $fields;
}, 20);

// اعتبارسنجی موبایل ایران (ارقام فارسی هم قبول می‌شود و ذخیره به‌صورت 09xxxxxxxxx)
add_action('woocommerce_after_checkout_validation', function ($data, $errors) {
    if (empty($data['billing_phone'])) {
        return;
    }
    $phone = function_exists('gl_normalize_digits') ? gl_normalize_digits($data['billing_phone']) : preg_replace('/\D/', '', $data['billing_phone']);
    if (strpos($phone, '98') === 0 && strlen($phone) === 12) {
        $phone = '0' . substr($phone, 2);
    }
    if (!preg_match('/^09\d{9}$/', $phone)) {
        $errors->add('validation', 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.');
    }
}, 10, 2);

add_filter('woocommerce_process_checkout_field_billing_phone', function ($value) {
    $phone = function_exists('gl_normalize_digits') ? gl_normalize_digits($value) : $value;
    if (strpos($phone, '98') === 0 && strlen($phone) === 12) {
        $phone = '0' . substr($phone, 2);
    }
    return $phone;
});

// مهمانی که مستقیم به /checkout می‌آید: پیام واضح بالای فرم
add_action('woocommerce_before_checkout_form', function () {
    if (!is_user_logged_in()) {
        wc_print_notice('برای ثبت سفارش، حساب کاربری شما همین‌جا با ایمیل و رمز عبور ساخته می‌شود. اگر قبلاً ثبت‌نام کرده‌اید، از لینک بالا وارد شوید.', 'notice');
    }
}, 5);
