<?php
/**
 * Uninstall: remove options, the hits table, cron events and generated files.
 *
 * @package GillldaAI
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

foreach (array('gai_settings', 'gai_state', 'gai_audit', 'gai_policy', 'gai_lock', 'gai_changed', 'gai_version') as $opt) {
    delete_option($opt);
}
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_gai\\_%' OR option_name LIKE '\\_transient\\_timeout\\_gai\\_%'"); // phpcs:ignore

wp_clear_scheduled_hook('gai_rebuild');
wp_clear_scheduled_hook('gai_daily');

$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}gai_hits"); // phpcs:ignore

$up  = wp_upload_dir(null, false);
$dir = trailingslashit($up['basedir']) . 'gilllda-ai';
if (is_dir($dir)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); // phpcs:ignore
    }
    @rmdir($dir); // phpcs:ignore
}

delete_option('rewrite_rules');
