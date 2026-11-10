<?php
/**
 * Plugin Name:       Gilllda AI Visibility
 * Plugin URI:        https://gilllda.ir/
 * Description:       دیده‌شدن فروشگاه گیلدا در هوش مصنوعی: ‎/llms.txt‎، ‎/llms-full.txt‎، نسخه Markdown هر صفحه، سیاست خزنده‌های AI در robots.txt، اسکیمای تکمیلی هماهنگ با Rank Math و گزارش بازدید ربات‌ها و ارجاع از دستیارهای هوش مصنوعی.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Gilllda
 * License:           GPL-2.0-or-later
 * Text Domain:       gilllda-ai
 * Domain Path:       /languages
 *
 * @package GillldaAI
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GAI_VERSION', '1.0.0');
define('GAI_FILE', __FILE__);
define('GAI_DIR', plugin_dir_path(__FILE__));
define('GAI_URL', plugin_dir_url(__FILE__));

require_once GAI_DIR . 'includes/class-gai-core.php';
require_once GAI_DIR . 'includes/class-gai-router.php';
require_once GAI_DIR . 'includes/class-gai-tracker.php';
require_once GAI_DIR . 'includes/class-gai-robots.php';
require_once GAI_DIR . 'includes/class-gai-schema.php';

register_activation_hook(__FILE__, array('GAI_Core', 'activate'));
register_deactivation_hook(__FILE__, array('GAI_Core', 'deactivate'));

add_action('plugins_loaded', function () {
    load_plugin_textdomain('gilllda-ai', false, dirname(plugin_basename(GAI_FILE)) . '/languages');
    GAI_Core::boot();
    GAI_Router::boot();
    GAI_Tracker::boot();
    GAI_Robots::boot();
    GAI_Schema::boot();
    if (is_admin()) {
        require_once GAI_DIR . 'includes/class-gai-admin.php';
        GAI_Admin::boot();
    }
}, 5);

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=gilllda-ai')) . '">' . esc_html__('داشبورد', 'gilllda-ai') . '</a>');
    return $links;
});
