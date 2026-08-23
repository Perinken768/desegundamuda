<?php

declare(strict_types=1);

namespace DSM\Whatsapp\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class Autoloader
{
    private const PREFIX =
        'DSM\\Whatsapp\\';

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

        $file =
            DSM_WHATSAPP_PATH
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

    private function __construct()
    {
    }
}
