<?php
/**
 * Plugin Name: DSM Multitienda
 * Description: Gestión de tiendas profesionales, catálogo, inventario y reservas de DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-multitienda
 * Requires Plugins: dsm-core, dsm-clientes, dsm-catalogo, dsm-suscripciones, dsm-mail, dsm-whatsapp
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_MULTITIENDA_VERSION',
    '0.1.0'
);

define(
    'DSM_MULTITIENDA_DB_VERSION',
    1
);

define(
    'DSM_MULTITIENDA_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_MULTITIENDA_URL',
    plugin_dir_url(__FILE__)
);

require_once DSM_MULTITIENDA_PATH
    . 'src/Support/Autoloader.php';

use DSM\Multitienda\Database\Installer;
use DSM\Multitienda\Frontend\MyStoreShortcode;
use DSM\Multitienda\Integration\CustomerAccountIntegration;
use DSM\Multitienda\Frontend\PublicStoreController;
use DSM\Multitienda\Frontend\PublicReservationController;
use DSM\Multitienda\Frontend\StoreProductFormController;
use DSM\Multitienda\Frontend\StoreProductImageController;
use DSM\Multitienda\Frontend\StoreProductsController;
use DSM\Multitienda\Frontend\StoreProductVariantController;
use DSM\Multitienda\Frontend\StoreProfileController;
use DSM\Multitienda\Frontend\StoreReservationController;
use DSM\Multitienda\Frontend\StoreStatusController;
use DSM\Multitienda\Frontend\StoreStockController;
use DSM\Multitienda\Integration\HomeThemeIntegration;
use DSM\Multitienda\Support\Autoloader;

/*
 * ============================================================
 * AUTOLOAD
 * ============================================================
 */

Autoloader::register();

/*
 * ============================================================
 * PANEL PRIVADO / ERP
 * ============================================================
 */

MyStoreShortcode::register();

/*
 * Integración con Mi cuenta.
 */
CustomerAccountIntegration::register();

PublicReservationController::register();

StoreProfileController::register();

StoreStatusController::register();

StoreProductsController::register();

StoreProductFormController::register();

StoreProductImageController::register();

StoreProductVariantController::register();

StoreReservationController::register();

StoreStockController::register();

/*
 * ============================================================
 * INTEGRACIÓN CON EL TEMA
 * ============================================================
 */

HomeThemeIntegration::register();

/*
 * ============================================================
 * ESCAPARATE PÚBLICO
 * ============================================================
 */

PublicStoreController::register();

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
