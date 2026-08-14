<?php
/**
 * Plugin Name: DSM Promocionar
 * Description: Gestión de promociones temporales y reutilizables de anuncios en DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-promocionar
 * Requires Plugins: dsm-core, dsm-clientes, dsm-anuncios, dsm-pagos
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_PROMOCIONAR_VERSION',
    '0.1.0'
);

define(
    'DSM_PROMOCIONAR_DB_VERSION',
    4
);

define(
    'DSM_PROMOCIONAR_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_PROMOCIONAR_URL',
    plugin_dir_url(__FILE__)
);

require_once DSM_PROMOCIONAR_PATH
    . 'src/Support/Autoloader.php';

use DSM\Promocionar\Admin\PromotionGrantPage;
use DSM\Promocionar\Admin\PromotionPlansPage;
use DSM\Promocionar\Cron\ExpiredPromotionsCron;
use DSM\Promocionar\Database\Installer;
use DSM\Promocionar\Frontend\CustomerAdvertisementPromotionIntegration;
use DSM\Promocionar\Frontend\CustomerPromotionsShortcode;
use DSM\Promocionar\Frontend\PromoteAdvertisementShortcode;
use DSM\Promocionar\Frontend\PromotionActionController;
use DSM\Promocionar\Frontend\PromotionPurchaseController;
use DSM\Promocionar\Integration\AdvertisementPromotionIntegration;
use DSM\Promocionar\Integration\CustomerAccountPromotionIntegration;
use DSM\Promocionar\Integration\PaymentPromotionIntegration;
use DSM\Promocionar\Support\Autoloader;

/*
 * Registrar primero el autoloader.
 *
 * A partir de este punto ya pueden cargarse automáticamente
 * todas las clases DSM\Promocionar\...
 */
Autoloader::register();

/*
 * Registro de componentes.
 */
PromotionGrantPage::register();
CustomerAdvertisementPromotionIntegration::register();
CustomerPromotionsShortcode::register();
CustomerAccountPromotionIntegration::register();
ExpiredPromotionsCron::register();
PromoteAdvertisementShortcode::register();
PromotionActionController::register();
PromotionPlansPage::register();
PromotionPurchaseController::register();
PaymentPromotionIntegration::register();

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

register_activation_hook(
    __FILE__,
    [
        ExpiredPromotionsCron::class,
        'activate',
    ]
);

register_deactivation_hook(
    __FILE__,
    [
        ExpiredPromotionsCron::class,
        'deactivate',
    ]
);

add_action(
    'plugins_loaded',
    [
        Installer::class,
        'migrate',
    ]
);

add_action(
    'plugins_loaded',
    [
        ExpiredPromotionsCron::class,
        'ensureScheduled',
    ]
);

/*
 * Integración con el ciclo de vida de anuncios.
 *
 * Detiene promociones activas cuando un anuncio
 * queda reservado, cerrado, rechazado o eliminado.
 */
$advertisementPromotionIntegration =
    new AdvertisementPromotionIntegration();

$advertisementPromotionIntegration->register();