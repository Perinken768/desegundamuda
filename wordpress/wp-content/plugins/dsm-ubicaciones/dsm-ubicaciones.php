<?php
/**
 * Plugin Name: DSM Ubicaciones
 * Description: Gestión centralizada de países, áreas territoriales y municipios de DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-ubicaciones
 * Requires Plugins: dsm-core
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_UBICACIONES_VERSION',
    '0.1.0'
);

define(
    'DSM_UBICACIONES_DB_VERSION',
    4
);

define(
    'DSM_UBICACIONES_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_UBICACIONES_URL',
    plugin_dir_url(__FILE__)
);

require_once DSM_UBICACIONES_PATH
    . 'src/Support/Autoloader.php';

use DSM\Ubicaciones\Admin\LocationAdminController;
use DSM\Ubicaciones\Admin\LocationsPage;
use DSM\Ubicaciones\Area\AreaRepository;
use DSM\Ubicaciones\Country\CountryRepository;
use DSM\Ubicaciones\Database\Installer;
use DSM\Ubicaciones\Integration\LocationIntegration;
use DSM\Ubicaciones\Municipality\MunicipalityRepository;
use DSM\Ubicaciones\Support\Autoloader;

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
 * Repositorios compartidos.
 */
$countryRepository =
    new CountryRepository();

$areaRepository =
    new AreaRepository();

$municipalityRepository =
    new MunicipalityRepository();

/*
 * Integración pública con otros plugins DSM.
 */
$locationIntegration =
    new LocationIntegration(
        $countryRepository,
        $areaRepository,
        $municipalityRepository
    );

$locationIntegration->register();

/*
 * Administración de ubicaciones.
 */
$locationsPage =
    new LocationsPage(
        $countryRepository,
        $areaRepository,
        $municipalityRepository
    );

$locationsPage->register();

$locationAdminController =
    new LocationAdminController(
        $countryRepository,
        $areaRepository,
        $municipalityRepository
    );

$locationAdminController->register();