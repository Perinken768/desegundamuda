<?php
/**
 * Plugin Name: DSM WhatsApp
 * Description: Infraestructura común de integración con WhatsApp para DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-whatsapp
 * Requires Plugins: dsm-core, dsm-clientes
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_WHATSAPP_VERSION',
    '0.1.0'
);

define(
    'DSM_WHATSAPP_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_WHATSAPP_URL',
    plugin_dir_url(__FILE__)
);

require_once DSM_WHATSAPP_PATH
    . 'src/Support/Autoloader.php';

use DSM\Whatsapp\Support\Autoloader;

Autoloader::register();
