<?php
/**
 * Plugin Name: DSM Clientes
 * Description: Gestión de clientes, perfiles y autenticación de DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-clientes
 * Requires Plugins: dsm-core, dsm-mail
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_CLIENTES_VERSION',
    '0.1.0'
);

define(
    'DSM_CLIENTES_DB_VERSION',
    6
);

define(
    'DSM_CLIENTES_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_CLIENTES_URL',
    plugin_dir_url(__FILE__)
);

require_once DSM_CLIENTES_PATH
    . 'src/Support/Autoloader.php';

use DSM\Clientes\Admin\CustomerAdminController;
use DSM\Clientes\Admin\CustomerAdminRepository;
use DSM\Clientes\Admin\CustomersPage;
use DSM\Clientes\Database\Installer;
use DSM\Clientes\Frontend\AccountDeletionController;
use DSM\Clientes\Frontend\AccountReactivationController;
use DSM\Clientes\Frontend\AccountShortcode;
use DSM\Clientes\Frontend\AccountStatusController;
use DSM\Clientes\Frontend\AuthController;
use DSM\Clientes\Frontend\EmailVerificationController;
use DSM\Clientes\Frontend\LoginShortcode;
use DSM\Clientes\Frontend\ProfileController;
use DSM\Clientes\Frontend\ProfileShortcode;
use DSM\Clientes\Frontend\RegisterShortcode;
use DSM\Clientes\Frontend\ResendVerificationController;
use DSM\Clientes\Scheduling\CustomerDeletionScheduler;
use DSM\Clientes\Support\Autoloader;

Autoloader::register();

/*
 * Administración.
 */
$customerAdminRepository =
    new CustomerAdminRepository();

$customersPage = new CustomersPage(
    $customerAdminRepository
);

$customersPage->register();

$customerAdminController =
    new CustomerAdminController();

$customerAdminController->register();

/*
 * Controladores públicos.
 */
AccountDeletionController::register();
AccountReactivationController::register();
AccountStatusController::register();
AuthController::register();
EmailVerificationController::register();
ProfileController::register();
ResendVerificationController::register();

/*
 * Shortcodes.
 */
LoginShortcode::register();
RegisterShortcode::register();
AccountShortcode::register();
ProfileShortcode::register();

/*
 * Eliminaciones programadas.
 */
CustomerDeletionScheduler::register();

/*
 * Instalación y migraciones.
 */
register_activation_hook(
    __FILE__,
    [Installer::class, 'activate']
);

register_activation_hook(
    __FILE__,
    [CustomerDeletionScheduler::class, 'activate']
);

register_deactivation_hook(
    __FILE__,
    [CustomerDeletionScheduler::class, 'deactivate']
);

add_action(
    'plugins_loaded',
    [Installer::class, 'migrate']
);