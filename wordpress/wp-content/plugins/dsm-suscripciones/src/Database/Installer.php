<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Database;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class Installer
{
    private const OPTION_NAME =
        'dsm_suscripciones_db_version';

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
            < DSM_SUSCRIPCIONES_DB_VERSION
        ) {
            throw new RuntimeException(
                'No se completaron todas las migraciones '
                . 'de DSM Suscripciones.'
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
                DSM_SUSCRIPCIONES_PATH
                . 'database/migrations/'
                . '001-create-subscription-plans.php',

            2 =>
                DSM_SUSCRIPCIONES_PATH
                . 'database/migrations/'
                . '002-create-subscriptions.php',

            3 =>
                DSM_SUSCRIPCIONES_PATH
                . 'database/migrations/'
                . '003-add-recurring-subscriptions.php',
        ];
    }

    private function __construct()
    {
    }
}
