<?php

/**
 * Plugin Name: InstaFood Manager
 * Description: Import, export, and manage InstaFood categories, products, variations, and images.
 * Version: 1.0.0
 * Author: Talal Almrka
 * Requires at least: 5.8
 * Requires PHP: 8.1
 */


if (!defined('ABSPATH')) {
    exit;
}

define('IFM_DEV_MODE', true);
define('IFM_PLUGIN_TITLE', 'Instafood Manager');
define('IFM_PLUGIN_VER', '1.0.0');
define('IFM_PAGE_SLUG', 'instafood-manager');

define('IFM_ITEM_POST_TYPE', 'appetit_item');
define('IFM_ITEM_TAXONOMY', 'appetit_items_category');
define('IFM_ITEM_META_KEY', 'appetit_item_meta');

$autoload = __DIR__ . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    return;
}

require_once $autoload;

use Ifm\Ifm;

Ifm::boot();
