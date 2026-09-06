<?php

declare(strict_types=1);

namespace DSM\Directos\Database;

use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class Installer
{
    private const OPTION_NAME =
        'dsm_directos_db_version';

    public static function activate(): void
    {
        self::migrate();
    }

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

        $targetVersion =
            (int) DSM_DIRECTOS_DB_VERSION;

        if (
            $installedVersion
            >= $targetVersion
        ) {
            return;
        }

        for (
            $version =
                $installedVersion + 1;
            $version <= $targetVersion;
            $version++
        ) {
            self::runMigration(
                $version
            );

            update_option(
                self::OPTION_NAME,
                $version,
                false
            );
        }
    }

    private static function runMigration(
        int $version
    ): void {
        $migrationName =
            match ($version) {
                1 =>
                    'create-live-system',

                2 =>
                    'single-live-per-customer',

                default =>
                    throw new RuntimeException(
                        sprintf(
                            'No existe definición para la migración %d de DSM Directos.',
                            $version
                        )
                    ),
            };

        $filePath =
            DSM_DIRECTOS_PATH
            . 'database/migrations/'
            . sprintf(
                '%03d',
                $version
            )
            . '-'
            . $migrationName
            . '.php';

        if (!is_file($filePath)) {
            throw new RuntimeException(
                sprintf(
                    'No se encontró la migración %d de DSM Directos.',
                    $version
                )
            );
        }

        try {
            $migration =
                require $filePath;

            if (!is_callable($migration)) {
                throw new RuntimeException(
                    sprintf(
                        'La migración %d de DSM Directos no es ejecutable.',
                        $version
                    )
                );
            }

            $migration();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    'Error ejecutando la migración %d de DSM Directos: %s',
                    $version,
                    $exception->getMessage()
                ),
                0,
                $exception
            );
        }
    }

    private function __construct()
    {
    }
}
