<?php

declare(strict_types=1);

namespace DSM\Multitienda\Database;

use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class Installer
{
    private const OPTION_NAME =
        'dsm_multitienda_db_version';

    public static function activate(): void
    {
        self::migrate();
    }

    public static function migrate(): void
    {
        $installedVersion =
            (int) get_option(
                self::OPTION_NAME,
                0
            );

        $targetVersion =
            (int) DSM_MULTITIENDA_DB_VERSION;

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
        $filePath =
            DSM_MULTITIENDA_PATH
            . 'database/migrations/'
            . sprintf(
                '%03d',
                $version
            )
            . '-'
            . self::getMigrationFileName(
                $version
            )
            . '.php';

        if (!is_file($filePath)) {
            throw new RuntimeException(
                sprintf(
                    'No se encontró la migración %d de DSM Multitienda.',
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
                        'La migración %d no es ejecutable.',
                        $version
                    )
                );
            }

            $migration();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    'Error ejecutando la migración %d: %s',
                    $version,
                    $exception->getMessage()
                ),
                0,
                $exception
            );
        }
    }

    private static function getMigrationFileName(
        int $version
    ): string {
        return match ($version) {
            1 =>
                'create-stores',

            default =>
                throw new RuntimeException(
                    sprintf(
                        'No existe definición para la migración %d.',
                        $version
                    )
                ),
        };
    }

    private function __construct()
    {
    }
}
