<?php
include_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Check if the Digits plugin is active
$active_plugins = get_option('active_plugins');
$woo_active = in_array('woocommerce/woocommerce.php', $active_plugins);
