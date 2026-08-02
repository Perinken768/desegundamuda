<?php

declare(strict_types=1);

namespace DSM\Anuncios\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class Autoloader
{
    private const NAMESPACE_PREFIX =
        'DSM\\Anuncios\\';

    public static function register(): void
    {
        spl_autoload_register(
            [self::class, 'autoload']
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

        $relativeClass = substr(
            $className,
            strlen(self::NAMESPACE_PREFIX)
        );

        if (
            $relativeClass === false
            || $relativeClass === ''
        ) {
            return;
        }

        $relativePath = str_replace(
            '\\',
            DIRECTORY_SEPARATOR,
            $relativeClass
        );

        $filePath =
            DSM_ANUNCIOS_PATH
            . 'src/'
            . $relativePath
            . '.php';

        if (!is_file($filePath)) {
            return;
        }

        require_once $filePath;
    }

    private function __construct()
    {
    }
}