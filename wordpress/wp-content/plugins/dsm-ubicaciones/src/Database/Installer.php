<?php

declare(strict_types=1);

namespace DSM\Ubicaciones\Database;

use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gestiona la instalación y las migraciones
 * de DSM Ubicaciones.
 */
final class Installer
{
    private const OPTION_NAME =
        'dsm_ubicaciones_db_version';

    /**
     * Se ejecuta al activar el plugin.
     */
    public static function activate(): void
    {
        self::migrate();
    }

    /**
     * Ejecuta en orden todas las migraciones pendientes.
     */
    public static function migrate(): void
    {
        $installedVersion =
            max(
                0,
                (int) get_option(
                    self::OPTION_NAME,
                    0
                )
            );

        $migrations =
            self::getMigrations();

        foreach (
            $migrations
            as $version => $migrationFile
        ) {
            if ($version <= $installedVersion) {
                continue;
            }

            self::runMigration(
                $version,
                $migrationFile
            );

            update_option(
                self::OPTION_NAME,
                $version,
                false
            );

            $installedVersion =
                $version;
        }

        if (
            $installedVersion
            < DSM_UBICACIONES_DB_VERSION
        ) {
            throw new RuntimeException(
                sprintf(
                    'DSM Ubicaciones no pudo alcanzar la versión de base de datos %d.',
                    DSM_UBICACIONES_DB_VERSION
                )
            );
        }
    }

    /**
     * Ejecuta una migración individual.
     */
    private static function runMigration(
        int $version,
        string $migrationFile
    ): void {
        if (!is_file($migrationFile)) {
            throw new RuntimeException(
                sprintf(
                    'No se encontró la migración %d de DSM Ubicaciones: %s',
                    $version,
                    $migrationFile
                )
            );
        }

        try {
            $migration =
                require $migrationFile;

            if (!is_callable($migration)) {
                throw new RuntimeException(
                    sprintf(
                        'La migración %d de DSM Ubicaciones no devuelve una función ejecutable.',
                        $version
                    )
                );
            }

            $migration();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    'Error ejecutando la migración %d de DSM Ubicaciones: %s',
                    $version,
                    $exception->getMessage()
                ),
                0,
                $exception
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private static function getMigrations(): array
    {
        return [
            1 =>
                DSM_UBICACIONES_PATH
                . 'database/migrations/'
                . '001-create-countries.php',

            2 =>
                DSM_UBICACIONES_PATH
                . 'database/migrations/'
                . '002-create-areas.php',

            3 =>
                DSM_UBICACIONES_PATH
                . 'database/migrations/'
                . '003-create-municipalities.php',

            4 =>
                DSM_UBICACIONES_PATH
                . 'database/migrations/'
                . '004-seed-spain-and-canary-locations.php',
        ];
    }

    private function __construct()
    {
    }
}