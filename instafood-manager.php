<?php

/**
 * Plugin Name: InstaFood Manager
 * Description: Import, export, and manage InstaFood categories, products, variations, and images.
 * Version: 1.1.0
 * Author: Talal Almrka
 * Requires at least: 5.8
 * Requires PHP: 8.2
 */

if (!defined('ABSPATH')) {
    exit;
}

define('IFM_DEV_MODE', true);
define('IFM_PLUGIN_FILE', __FILE__);
define('IFM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IFM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$ifm_plugin_data = get_plugin_data(IFM_PLUGIN_FILE, false, false);

define('IFM_PLUGIN_VER', $ifm_plugin_data['Version']);
define('IFM_PLUGIN_TITLE', $ifm_plugin_data['Name']);
define('IFM_PAGE_SLUG', 'instafood-manager');

define('IFM_ITEM_POST_TYPE', 'appetit_item');
define('IFM_ITEM_TAXONOMY', 'appetit_items_category');
define('IFM_ITEM_META_KEY', 'appetit_item_meta');

$autoload = IFM_PLUGIN_DIR . 'vendor/autoload.php';

if (!file_exists($autoload)) {
    return;
}

require_once $autoload;

use Ifm\Ifm;

Ifm::boot();
