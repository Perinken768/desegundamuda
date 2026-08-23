<?php

declare(strict_types=1);

namespace DSM\Multitienda\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class Autoloader
{
    private const PREFIX =
        'DSM\\Multitienda\\';

    public static function register(): void
    {
        spl_autoload_register(
            [
                self::class,
                'load',
            ]
        );
    }

    public static function load(
        string $className
    ): void {
        if (
            !str_starts_with(
                $className,
                self::PREFIX
            )
        ) {
            return;
        }

        $relativeClass =
            substr(
                $className,
                strlen(
                    self::PREFIX
                )
            );

        if ($relativeClass === false) {
            return;
        }

        $relativePath =
            str_replace(
                '\\',
                DIRECTORY_SEPARATOR,
                $relativeClass
            )
            . '.php';

        $filePath =
            DSM_MULTITIENDA_PATH
            . 'src/'
            . $relativePath;

        if (is_file($filePath)) {
            require_once $filePath;
        }
    }

    private function __construct()
    {
    }
}
