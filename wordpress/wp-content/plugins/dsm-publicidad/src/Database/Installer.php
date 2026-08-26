<?php

declare(strict_types=1);

namespace DSM\Publicidad\Database;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class Installer
{
    private const OPTION_NAME =
        'dsm_publicidad_db_version';

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

        foreach (
            self::getMigrations()
            as $version => $migrationFile
        ) {
            if ($version <= $installedVersion) {
                continue;
            }

            if (!is_file($migrationFile)) {
                throw new RuntimeException(
                    sprintf(
                        'No se encontró la migración %d.',
                        $version
                    )
                );
            }

            $migration =
                require $migrationFile;

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

            $installedVersion =
                $version;
        }

        if (
            $installedVersion
            < DSM_PUBLICIDAD_DB_VERSION
        ) {
            throw new RuntimeException(
                'No se completaron todas las migraciones de DSM Publicidad.'
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
                DSM_PUBLICIDAD_PATH
                . 'database/migrations/'
                . '001-create-advertisements.php',

            2 =>
                DSM_PUBLICIDAD_PATH
                . 'database/migrations/'
                . '002-add-customer-id.php',

            3 =>
                DSM_PUBLICIDAD_PATH
                . 'database/migrations/'
                . '003-repair-customer-id.php',

            4 =>
                DSM_PUBLICIDAD_PATH
                . 'database/migrations/'
                . '004-one-banner-per-customer.php',
        ];
    }

    private function __construct()
    {
    }
}
