<?php
/**
 * Plugin Name: DSM Legal
 * Description: Gestión de documentos legales para DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-legal
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define(
    'DSM_LEGAL_VERSION',
    '0.1.0'
);

define(
    'DSM_LEGAL_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'DSM_LEGAL_URL',
    plugin_dir_url(__FILE__)
);

spl_autoload_register(
    static function (
        string $class
    ): void {
        $prefix =
            'DSM\\Legal\\';

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
            DSM_LEGAL_PATH
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
        DSM\Legal\Plugin::register();
    }
);
