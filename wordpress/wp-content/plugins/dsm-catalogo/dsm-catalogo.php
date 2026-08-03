<?php
/**
 * Plugin Name: DSM Catálogo
 * Description: Gestión de productos, variantes, marcas y datos comerciales de DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-catalogo
 * Requires Plugins: dsm-core, dsm-clientes
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_CATALOGO_VERSION',
    '0.1.0'
);

define(
    'DSM_CATALOGO_DB_VERSION',
    6
);

define(
    'DSM_CATALOGO_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_CATALOGO_URL',
    plugin_dir_url(__FILE__)
);

require_once DSM_CATALOGO_PATH
    . 'src/Support/Autoloader.php';

use DSM\Catalogo\Admin\BrandAdminController;
use DSM\Catalogo\Admin\CatalogPage;
use DSM\Catalogo\Admin\ProductAdminController;
use DSM\Catalogo\Admin\VariantAdminController;
use DSM\Catalogo\Database\Installer;
use DSM\Catalogo\Support\Autoloader;

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
 * Dashboard y menú principal.
 *
 * Debe registrarse antes que los submenús.
 */
CatalogPage::register();

/*
 * Administración de productos.
 */
ProductAdminController::register();

/*
 * Administración de variantes.
 */
VariantAdminController::register();

/*
 * Administración de marcas.
 */
BrandAdminController::register();