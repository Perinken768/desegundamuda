<?php

declare(strict_types=1);

namespace DSM\Catalogo\Database;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class Installer
{
    private const OPTION_NAME =
        'dsm_catalogo_db_version';

    public static function activate(): void
    {
        self::migrate();
    }

    public static function migrate(): void
    {
        $installedVersion = (int) get_option(
            self::OPTION_NAME,
            0
        );

        $migrations = self::getMigrations();

        foreach (
            $migrations
            as $version => $migrationFile
        ) {
            if ($version <= $installedVersion) {
                continue;
            }

            if (!is_file($migrationFile)) {
                throw new RuntimeException(
                    sprintf(
                        'No se encontró la migración %d: %s',
                        $version,
                        $migrationFile
                    )
                );
            }

            $migration = require $migrationFile;

            if (!is_callable($migration)) {
                throw new RuntimeException(
                    sprintf(
                        'La migración %d no es ejecutable.',
                        $version
                    )
                );
            }

            $migration();

            update_option(
                self::OPTION_NAME,
                $version,
                false
            );

            $installedVersion = $version;
        }

        if (
            $installedVersion
            < DSM_CATALOGO_DB_VERSION
        ) {
            throw new RuntimeException(
                'No se completaron todas las migraciones de DSM Catálogo.'
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private static function getMigrations(): array
    {
        return [
            1 => DSM_CATALOGO_PATH
                . 'database/migrations/'
                . '001-create-products.php',

            2 => DSM_CATALOGO_PATH
                . 'database/migrations/'
                . '002-create-product-variants.php',

            3 => DSM_CATALOGO_PATH
                . 'database/migrations/'
                . '003-create-stock-movements.php',

            4 => DSM_CATALOGO_PATH
                . 'database/migrations/'
                . '004-create-product-reservations.php',

            5 => DSM_CATALOGO_PATH
                . 'database/migrations/'
                . '005-create-brands.php',

            6 => DSM_CATALOGO_PATH
                . 'database/migrations/'
                . '006-add-expired-at-to-reservations.php',
        ];
    }

    private function __construct()
    {
    }
}