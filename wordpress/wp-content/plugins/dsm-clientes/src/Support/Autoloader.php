<?php

declare(strict_types=1);

namespace DSM\Clientes\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class Autoloader
{
    private const PREFIX = 'DSM\\Clientes\\';

    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    private static function autoload(string $class): void
    {
        if (!str_starts_with($class, self::PREFIX)) {
            return;
        }

        $relativeClass = substr($class, strlen(self::PREFIX));

        $relativePath = str_replace(
            '\\',
            DIRECTORY_SEPARATOR,
            $relativeClass
        );

        $file = DSM_CLIENTES_PATH
            . 'src/'
            . $relativePath
            . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }

    private function __construct()
    {
    }
}