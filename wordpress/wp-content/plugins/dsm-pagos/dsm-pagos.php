<?php
/**
 * Plugin Name: DSM Pagos
 * Description: Infraestructura central de pagos de DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-pagos
 * Requires Plugins: dsm-core, dsm-clientes
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_PAGOS_VERSION',
    '0.1.0'
);

define(
    'DSM_PAGOS_DB_VERSION',
    1
);

define(
    'DSM_PAGOS_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_PAGOS_URL',
    plugin_dir_url(__FILE__)
);

require_once DSM_PAGOS_PATH
    . 'src/Support/Autoloader.php';

use DSM\Pagos\Admin\PaymentProvidersPage;
use DSM\Pagos\Database\Installer;
use DSM\Pagos\Frontend\CheckoutShortcode;
use DSM\Pagos\Frontend\PaymentCheckoutController;
use DSM\Pagos\Integration\PaymentProviderIntegration;
use DSM\Pagos\Support\Autoloader;

Autoloader::register();
PaymentProviderIntegration::register();
PaymentProvidersPage::register();
CheckoutShortcode::register();
PaymentCheckoutController::register();

/*
 * Instalación y migraciones.
 */
register_activation_hook(
    __FILE__,
    [
        Installer::class,
        'activate',
    ]
);

add_action(
    'plugins_loaded',
    [
        Installer::class,
        'migrate',
    ]
);
