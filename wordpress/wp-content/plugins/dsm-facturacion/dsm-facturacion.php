<?php
/**
 * Plugin Name: DSM Facturación
 * Description: Facturación de los servicios cobrados por DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-facturacion
 * Requires Plugins: dsm-core, dsm-clientes, dsm-pagos
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_FACTURACION_VERSION',
    '0.1.0'
);

define(
    'DSM_FACTURACION_DB_VERSION',
    8
);

define(
    'DSM_FACTURACION_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_FACTURACION_URL',
    plugin_dir_url(__FILE__)
);

$composerAutoload =
    DSM_FACTURACION_PATH
    . 'vendor/autoload.php';

if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

require_once DSM_FACTURACION_PATH
    . 'src/Support/Autoloader.php';

use DSM\Facturacion\Admin\BillingSettingsPage;
use DSM\Facturacion\Admin\VerifactuSettingsPage;
use DSM\Facturacion\Database\Installer;
use DSM\Facturacion\Frontend\BillingProfileController;
use DSM\Facturacion\Frontend\BillingProfileShortcode;
use DSM\Facturacion\Frontend\InvoiceDownloadController;
use DSM\Facturacion\Frontend\InvoicesShortcode;
use DSM\Facturacion\Integration\CustomerAccountIntegration;
use DSM\Facturacion\Integration\PaymentInvoiceIntegration;
use DSM\Facturacion\Integration\VerifactuInvoiceIntegration;
use DSM\Facturacion\Support\Autoloader;
use DSM\Facturacion\Verifactu\VerifactuCronRunner;

Autoloader::register();

/*
 * Administración.
 */
BillingSettingsPage::register();
VerifactuSettingsPage::register();

/*
 * Frontend.
 */
BillingProfileShortcode::register();
BillingProfileController::register();

InvoicesShortcode::register();
InvoiceDownloadController::register();

/*
 * Integraciones.
 */
CustomerAccountIntegration::register();
PaymentInvoiceIntegration::register();
VerifactuInvoiceIntegration::register();

/*
 * VERI*FACTU.
 */
VerifactuCronRunner::register();

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
