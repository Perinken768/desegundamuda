<?php
/**
 * Plugin Name: DSM Core
 * Description: Núcleo compartido de la plataforma DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-core
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('DSM_CORE_VERSION', '0.1.0');
define('DSM_CORE_PATH', plugin_dir_path(__FILE__));
define('DSM_CORE_URL', plugin_dir_url(__FILE__));