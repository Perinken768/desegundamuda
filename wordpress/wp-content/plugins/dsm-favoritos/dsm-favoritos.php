<?php
/**
 * Plugin Name: DSM Favoritos
 * Description: Gestión de anuncios favoritos de clientes en DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-favoritos
 * Requires Plugins: dsm-core, dsm-clientes, dsm-anuncios
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_FAVORITOS_VERSION',
    '0.1.0'
);

define(
    'DSM_FAVORITOS_DB_VERSION',
    1
);

define(
    'DSM_FAVORITOS_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_FAVORITOS_URL',
    plugin_dir_url(__FILE__)
);

require_once DSM_FAVORITOS_PATH
    . 'src/Support/Autoloader.php';

use DSM\Favoritos\Cleanup\FavoriteCleanup;
use DSM\Favoritos\Database\Installer;
use DSM\Favoritos\Favorite\FavoriteRepository;
use DSM\Favoritos\Frontend\CustomerFavoritesShortcode;
use DSM\Favoritos\Frontend\FavoriteController;
use DSM\Favoritos\Frontend\FavoriteIntegration;
use DSM\Favoritos\Support\Autoloader;

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
 * Frontend.
 */
add_action(
    'init',
    static function (): void {
        $favoriteRepository =
            new FavoriteRepository();

        $favoriteController =
            new FavoriteController();

        $favoriteIntegration =
            new FavoriteIntegration(
                $favoriteRepository
            );

        $favoriteCleanup =
            new FavoriteCleanup(
                $favoriteRepository
            );

        $favoriteController->register();
        $favoriteIntegration->register();
        $favoriteCleanup->register();

        CustomerFavoritesShortcode::register();
    }
);