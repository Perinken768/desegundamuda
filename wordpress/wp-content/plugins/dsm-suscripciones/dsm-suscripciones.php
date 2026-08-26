<?php
/**
 * Plugin Name: DSM Suscripciones
 * Description: Gestión central de planes, prestaciones y suscripciones de DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-suscripciones
 * Requires Plugins: dsm-core, dsm-clientes, dsm-pagos
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_SUSCRIPCIONES_VERSION',
    '0.1.0'
);

define(
    'DSM_SUSCRIPCIONES_DB_VERSION',
    2
);

define(
    'DSM_SUSCRIPCIONES_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_SUSCRIPCIONES_URL',
    plugin_dir_url(__FILE__)
);

require_once DSM_SUSCRIPCIONES_PATH
    . 'src/Support/Autoloader.php';

use DSM\Suscripciones\Admin\SubscriptionGrantPage;
use DSM\Suscripciones\Admin\SubscriptionsPage;
use DSM\Suscripciones\Database\Installer;
use DSM\Suscripciones\Frontend\SubscriptionPlansShortcode;
use DSM\Suscripciones\Frontend\SubscriptionPurchaseController;
use DSM\Suscripciones\Integration\PaymentSubscriptionIntegration;
use DSM\Suscripciones\Support\Autoloader;
use DSM\Suscripciones\Integration\CustomerEntitlementIntegration;

/*
 * Autoload.
 */
Autoloader::register();

/*
 * Contratos públicos de derechos de suscripción.
 */
CustomerEntitlementIntegration::register();

/*
 * Administración.
 */
SubscriptionsPage::register();
SubscriptionGrantPage::register();

/*
 * Frontend.
 */
SubscriptionPlansShortcode::register();
SubscriptionPurchaseController::register();

/*
 * Integraciones.
 */
PaymentSubscriptionIntegration::register();

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