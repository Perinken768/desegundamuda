<?php
/**
 * Plugin Name: DSM Anuncios
 * Description: Gestión de anuncios particulares, categorías, imágenes, moderación y marketplace público de DeSegundaMuda.
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
    6
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

use DSM\Anuncios\Admin\AdvertisementAdminController;
use DSM\Anuncios\Admin\AdvertisementAdminRepository;
use DSM\Anuncios\Admin\AdvertisementsPage;
use DSM\Anuncios\Admin\CategoriesPage;
use DSM\Anuncios\Admin\CategoryAdminController;
use DSM\Anuncios\Category\CategoryRepository;
use DSM\Anuncios\Database\Installer;
use DSM\Anuncios\Frontend\AdvertisementController;
use DSM\Anuncios\Frontend\AdvertisementDetailShortcode;
use DSM\Anuncios\Frontend\AdvertisementListShortcode;
use DSM\Anuncios\Frontend\AdvertisementSearchRepository;
use DSM\Anuncios\Support\Autoloader;

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
 * Administración de anuncios.
 */
$advertisementAdminRepository =
    new AdvertisementAdminRepository();

$advertisementsPage =
    new AdvertisementsPage(
        $advertisementAdminRepository
    );

$advertisementsPage->register();

$advertisementAdminController =
    new AdvertisementAdminController();

$advertisementAdminController->register();

/*
 * Administración de categorías.
 *
 * AdvertisementsPage crea el menú principal.
 * CategoriesPage añade el submenú Categorías.
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
 * Repositorio compartido por el marketplace público
 * y las fichas individuales.
 */
$advertisementSearchRepository =
    new AdvertisementSearchRepository();

/*
 * Marketplace público.
 *
 * Shortcode:
 *
 * [dsm_advertisements]
 */
$advertisementListShortcode =
    new AdvertisementListShortcode(
        $advertisementSearchRepository
    );

$advertisementListShortcode->register();

/*
 * Ficha pública individual.
 *
 * Shortcode:
 *
 * [dsm_advertisement_detail]
 */
$advertisementDetailShortcode =
    new AdvertisementDetailShortcode(
        $advertisementSearchRepository
    );

$advertisementDetailShortcode->register();

/*
 * URLs públicas de anuncios:
 *
 * /anuncio/{slug}/
 */
$advertisementController =
    new AdvertisementController(
        $advertisementSearchRepository
    );

$advertisementController->register();