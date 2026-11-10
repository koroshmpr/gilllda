<?php
/**
 * Global flag used by templates: is WooCommerce active?
 * class_exists() also covers network-activated WooCommerce.
 */
$woo_active = class_exists('WooCommerce');
