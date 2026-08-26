<?php
/**
 * Plugin Name: DSM MFA
 * Description: Autenticación multifactor para DeSegundaMuda.
 * Version: 1.0.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-mfa
 * Requires Plugins: dsm-core, dsm-mail, dsm-clientes
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_MFA_VERSION',
    '1.0.0'
);

define(
    'DSM_MFA_FILE',
    __FILE__
);

define(
    'DSM_MFA_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_MFA_URL',
    plugin_dir_url(__FILE__)
);

spl_autoload_register(
    static function (string $class): void {
        $prefix =
            'DSM\\Mfa\\';

        if (
            strncmp(
                $class,
                $prefix,
                strlen($prefix)
            ) !== 0
        ) {
            return;
        }

        $relativeClass =
            substr(
                $class,
                strlen($prefix)
            );

        $file =
            DSM_MFA_PATH
            . 'src/'
            . str_replace(
                '\\',
                '/',
                $relativeClass
            )
            . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }
);

add_action(
    'plugins_loaded',
    static function (): void {
        DSM\Mfa\Plugin::register();
    }
);
