<?php

declare(strict_types=1);

namespace DSM\Ofertas\Database;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class Installer
{
    private const OPTION_NAME =
        'dsm_ofertas_db_version';

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
                        'No se encontró la migración %d de DSM Ofertas: %s',
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
                        'La migración %d de DSM Ofertas no es ejecutable.',
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
            < DSM_OFERTAS_DB_VERSION
        ) {
            throw new RuntimeException(
                'No se completaron todas las migraciones '
                . 'de DSM Ofertas.'
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
                DSM_OFERTAS_PATH
                . 'database/migrations/'
                . '001-create-offers.php',

            2 =>
                DSM_OFERTAS_PATH
                . 'database/migrations/'
                . '002-add-rule-status.php',

            3 =>
                DSM_OFERTAS_PATH
                . 'database/migrations/'
                . '003-version-offer-rules.php',

            4 =>
                DSM_OFERTAS_PATH
                . 'database/migrations/'
                . '004-create-checkout-intents.php',

            5 =>
                DSM_OFERTAS_PATH
                . 'database/migrations/'
                . '005-add-benefit-schedule-to-intents.php',

            6 =>
                DSM_OFERTAS_PATH
                . 'database/migrations/'
                . '006-add-intent-expiration.php',
        ];
    }

    private function __construct()
    {
    }
}
