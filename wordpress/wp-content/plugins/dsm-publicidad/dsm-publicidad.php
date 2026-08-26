<?php
/**
 * Plugin Name: DSM Publicidad
 * Description: Gestión de espacios publicitarios y banners de DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-publicidad
 * Requires Plugins: dsm-core, dsm-ubicaciones, dsm-suscripciones
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_PUBLICIDAD_VERSION',
    '0.1.0'
);

define(
    'DSM_PUBLICIDAD_DB_VERSION',
    4
);

define(
    'DSM_PUBLICIDAD_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_PUBLICIDAD_URL',
    plugin_dir_url(__FILE__)
);

require_once DSM_PUBLICIDAD_PATH
    . 'src/Support/Autoloader.php';

use DSM\Publicidad\Admin\AdvertisingAdminController;
use DSM\Publicidad\Database\Installer;
use DSM\Publicidad\Frontend\CustomerAdvertisingController;
use DSM\Publicidad\Frontend\CustomerAdvertisingShortcode;
use DSM\Publicidad\Integration\CustomerAccountIntegration;
use DSM\Publicidad\Integration\HomeThemeIntegration;
use DSM\Publicidad\Support\Autoloader;

Autoloader::register();

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

/*
 * Administración.
 */
AdvertisingAdminController::register();

/*
 * Frontend de cliente.
 */
CustomerAdvertisingShortcode::register();

CustomerAdvertisingController::register();

/*
 * Integración con Mi cuenta.
 */
CustomerAccountIntegration::register();

/*
 * Integración con el tema.
 */
HomeThemeIntegration::register();
