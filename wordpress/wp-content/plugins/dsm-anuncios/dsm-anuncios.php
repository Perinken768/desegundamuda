<?php
/**
 * Plugin Name: DSM Anuncios
 * Description: Gestión de anuncios, categorías, imágenes y moderación de DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-anuncios
 * Requires Plugins: dsm-core, dsm-clientes
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_ANUNCIOS_VERSION',
    '0.1.0'
);

define(
    'DSM_ANUNCIOS_DB_VERSION',
    5
);

define(
    'DSM_ANUNCIOS_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_ANUNCIOS_URL',
    plugin_dir_url(__FILE__)
);

require_once DSM_ANUNCIOS_PATH
    . 'src/Support/Autoloader.php';

use DSM\Anuncios\Admin\CategoriesPage;
use DSM\Anuncios\Admin\CategoryAdminController;
use DSM\Anuncios\Category\CategoryRepository;
use DSM\Anuncios\Database\Installer;
use DSM\Anuncios\Support\Autoloader;

Autoloader::register();

/*
 * Administración de categorías.
 */
$categoryRepository =
    new CategoryRepository();

$categoriesPage =
    new CategoriesPage(
        $categoryRepository
    );

$categoriesPage->register();

$categoryAdminController =
    new CategoryAdminController(
        $categoryRepository
    );

$categoryAdminController->register();

/*
 * Instalación y migraciones.
 */
register_activation_hook(
    __FILE__,
    [Installer::class, 'activate']
);

add_action(
    'plugins_loaded',
    [Installer::class, 'migrate']
);