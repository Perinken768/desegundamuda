<?php

declare(strict_types=1);

namespace DSM\Promocionar\Database;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class Installer
{
    private const OPTION_NAME =
        'dsm_promocionar_db_version';

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

        $migrations =
            self::getMigrations();

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
            < DSM_PROMOCIONAR_DB_VERSION
        ) {
            throw new RuntimeException(
                'No se completaron todas las migraciones '
                . 'de DSM Promocionar.'
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
                DSM_PROMOCIONAR_PATH
                . 'database/migrations/'
                . '001-create-promotion-wallets.php',

            2 =>
                DSM_PROMOCIONAR_PATH
                . 'database/migrations/'
                . '002-create-promotion-plans.php',

            3 =>
                DSM_PROMOCIONAR_PATH
                . 'database/migrations/'
                . '003-add-commercial-snapshot-to-wallets.php',

            4 =>
                DSM_PROMOCIONAR_PATH
                . 'database/migrations/'
                . '004-add-payment-id-to-wallets.php',
        ];
    }

    private function __construct()
    {
    }
}