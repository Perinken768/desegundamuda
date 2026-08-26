<?php

declare(strict_types=1);

namespace DSM\Publicidad\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register(
            [
                self::class,
                'autoload',
            ]
        );
    }

    private static function autoload(
        string $class
    ): void {
        $prefix =
            'DSM\\Publicidad\\';

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
                strlen(
                    $prefix
                )
            );

        $relativePath =
            str_replace(
                '\\',
                DIRECTORY_SEPARATOR,
                $relativeClass
            )
            . '.php';

        $file =
            DSM_PUBLICIDAD_PATH
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
