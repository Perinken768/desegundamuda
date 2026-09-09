<?php
/**
 * Plugin Name: DSM Ofertas
 * Description: Gestión de ofertas, periodos gratuitos y descuentos comerciales de DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-ofertas
 * Requires Plugins: dsm-core, dsm-clientes, dsm-suscripciones, dsm-pagos
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_OFERTAS_VERSION',
    '0.1.0'
);

define(
    'DSM_OFERTAS_DB_VERSION',
    6
);

define(
    'DSM_OFERTAS_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_OFERTAS_URL',
    plugin_dir_url(__FILE__)
);

require_once DSM_OFERTAS_PATH
    . 'src/Support/Autoloader.php';

use DSM\Ofertas\Admin\OffersPage;
use DSM\Ofertas\Application\ExpireCheckoutIntents;
use DSM\Ofertas\Database\Installer;
use DSM\Ofertas\Integration\OfferRedemptionIntegration;
use DSM\Ofertas\Integration\SubscriptionCheckoutIntegration;
use DSM\Ofertas\Integration\RetentionOfferController;
use DSM\Ofertas\Integration\RetentionOfferPresentationIntegration;
use DSM\Ofertas\Integration\SubscriptionPlanPresentationIntegration;
use DSM\Ofertas\Support\Autoloader;

/*
 * ============================================================
 * AUTOLOAD
 * ============================================================
 */

Autoloader::register();

ExpireCheckoutIntents::register();

SubscriptionCheckoutIntegration::register();
OfferRedemptionIntegration::register();
SubscriptionPlanPresentationIntegration::register();
RetentionOfferPresentationIntegration::register();
RetentionOfferController::register();

/*
 * ============================================================
 * ADMINISTRACIÓN
 * ============================================================
 */

OffersPage::register();

/*
 * ============================================================
 * INSTALACIÓN / MIGRACIONES
 * ============================================================
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

register_deactivation_hook(
    __FILE__,
    [
        ExpireCheckoutIntents::class,
        'deactivate',
    ]
);

