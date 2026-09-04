<?php

declare(strict_types=1);

namespace DSM\Facturacion\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class Autoloader
{
    private const PREFIX =
        'DSM\\Facturacion\\';

    public static function register(): void
    {
        spl_autoload_register(
            [
                self::class,
                'autoload',
            ]
        );
    }

    public static function autoload(
        string $class
    ): void {
        if (
            !str_starts_with(
                $class,
                self::PREFIX
            )
        ) {
            return;
        }

        $relativeClass =
            substr(
                $class,
                strlen(
                    self::PREFIX
                )
            );

        if (
            !is_string(
                $relativeClass
            )
            || $relativeClass === ''
        ) {
            return;
        }

        $relativePath =
            str_replace(
                '\\',
                DIRECTORY_SEPARATOR,
                $relativeClass
            )
            . '.php';

        $file =
            DSM_FACTURACION_PATH
            . 'src/'
            . $relativePath;

        if (is_file($file)) {
            require_once $file;
        }
    }

    private function __construct()
    {
    }
}
