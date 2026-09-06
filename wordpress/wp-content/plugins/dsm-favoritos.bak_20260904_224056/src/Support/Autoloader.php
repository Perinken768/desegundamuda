<?php

declare(strict_types=1);

namespace DSM\Favoritos\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class Autoloader
{
    private const NAMESPACE_PREFIX =
        'DSM\\Favoritos\\';

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
        string $className
    ): void {
        if (
            !str_starts_with(
                $className,
                self::NAMESPACE_PREFIX
            )
        ) {
            return;
        }

        $relativeClass =
            substr(
                $className,
                strlen(
                    self::NAMESPACE_PREFIX
                )
            );

        if (
            !is_string($relativeClass)
            || $relativeClass === ''
        ) {
            return;
        }

        $relativePath =
            str_replace(
                '\\',
                DIRECTORY_SEPARATOR,
                $relativeClass
            );

        $file =
            DSM_FAVORITOS_PATH
            . 'src/'
            . $relativePath
            . '.php';

        if (!is_file($file)) {
            return;
        }

        require_once $file;
    }

    private function __construct()
    {
    }
}