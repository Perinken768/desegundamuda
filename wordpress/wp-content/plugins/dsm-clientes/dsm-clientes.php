<?php
/**
 * Plugin Name: DSM Clientes
 * Description: Gestión de clientes, perfiles y autenticación de DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-clientes
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('DSM_CLIENTES_VERSION', '0.1.0');
define('DSM_CLIENTES_DB_VERSION', '0.1.0');

define('DSM_CLIENTES_PATH', plugin_dir_path(__FILE__));
define('DSM_CLIENTES_URL', plugin_dir_url(__FILE__));

require_once DSM_CLIENTES_PATH . 'src/Database/Installer.php';

use DSM\Clientes\Database\Installer;

register_activation_hook(
    __FILE__,
    [Installer::class, 'activate']
);

add_action(
    'plugins_loaded',
    [Installer::class, 'migrate']
);