<?php
/**
 * Plugin Name: DSM Cookies
 * Description: Gestión de consentimiento de cookies para DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-cookies
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_COOKIES_VERSION',
    '0.1.0'
);

define(
    'DSM_COOKIES_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_COOKIES_URL',
    plugin_dir_url(__FILE__)
);

spl_autoload_register(
    static function (
        string $class
    ): void {
        $prefix =
            'DSM\\Cookies\\';

        if (
            !str_starts_with(
                $class,
                $prefix
            )
        ) {
            return;
        }

        $relativeClass =
            substr(
                $class,
                strlen($prefix)
            );

        if ($relativeClass === false) {
            return;
        }

        $file =
            DSM_COOKIES_PATH
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
        DSM\Cookies\Plugin::register();
    }
);
