<?php
/**
 * Plugin Name: DSM Directos
 * Description: Gestión de ventas en directo mediante anuncios y productos de tienda en DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-directos
 * Requires Plugins: dsm-core, dsm-clientes, dsm-suscripciones, dsm-anuncios
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_DIRECTOS_VERSION',
    '0.1.0'
);

define(
    'DSM_DIRECTOS_DB_VERSION',
    2
);

define(
    'DSM_DIRECTOS_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_DIRECTOS_URL',
    plugin_dir_url(__FILE__)
);

require_once DSM_DIRECTOS_PATH
    . 'src/Support/Autoloader.php';

use DSM\Directos\Admin\DirectSettingsPage;
use DSM\Directos\Database\Installer;
use DSM\Directos\Frontend\CustomerDirectsShortcode;
use DSM\Directos\Frontend\DirectFormController;
use DSM\Directos\Frontend\DirectItemsController;
use DSM\Directos\Frontend\DirectSelectionController;
use DSM\Directos\Frontend\DirectLifecycleController;
use DSM\Directos\Frontend\DirectReservationController;
use DSM\Directos\Frontend\PublicDirectShortcode;
use DSM\Directos\Frontend\PublicDirectsShortcode;
use DSM\Directos\Integration\DirectSelectionIntegration;
use DSM\Directos\Support\Autoloader;

/*
 * ============================================================
 * AUTOLOAD
 * ============================================================
 */

Autoloader::register();

/*
 * ============================================================
 * ADMINISTRACIÓN
 * ============================================================
 */

DirectSettingsPage::register();

/*
 * ============================================================
 * FRONTEND
 * ============================================================
 */

CustomerDirectsShortcode::register();
DirectFormController::register();
DirectItemsController::register();
DirectSelectionController::register();
DirectLifecycleController::register();
DirectReservationController::register();
PublicDirectShortcode::register();
PublicDirectsShortcode::register();
DirectSelectionIntegration::register();

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
