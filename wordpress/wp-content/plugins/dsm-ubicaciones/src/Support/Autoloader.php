<?php

declare(strict_types=1);

namespace DSM\Ubicaciones\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cargador automático de clases de DSM Ubicaciones.
 *
 * Convierte clases del espacio:
 *
 * DSM\Ubicaciones\
 *
 * en archivos situados dentro de:
 *
 * src/
 */
final class Autoloader
{
    private const NAMESPACE_PREFIX =
        'DSM\\Ubicaciones\\';

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

        /*
         * Protege frente a nombres de clase manipulados.
         */
        if (
            str_contains(
                $relativeClass,
                '..'
            )
        ) {
            return;
        }

        $relativePath =
            str_replace(
                '\\',
                DIRECTORY_SEPARATOR,
                $relativeClass
            );

        $filePath =
            DSM_UBICACIONES_PATH
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