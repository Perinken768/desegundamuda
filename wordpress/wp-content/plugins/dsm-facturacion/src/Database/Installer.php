<?php

declare(strict_types=1);

namespace DSM\Facturacion\Database;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class Installer
{
    private const OPTION_NAME =
        'dsm_facturacion_db_version';

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
                        'La migración %d de DSM Facturación no es ejecutable.',
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
            < DSM_FACTURACION_DB_VERSION
        ) {
            throw new RuntimeException(
                'No se completaron todas las migraciones de DSM Facturación.'
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
                DSM_FACTURACION_PATH
                . 'database/migrations/'
                . '001-create-billing-schema.php',

            2 =>
                DSM_FACTURACION_PATH
                . 'database/migrations/'
                . '002-create-verifactu-schema.php',

            3 =>
                DSM_FACTURACION_PATH
                . 'database/migrations/'
                . '003-add-verifactu-record-idempotency.php',

            4 =>
                DSM_FACTURACION_PATH
                . 'database/migrations/'
                . '004-normalize-verifactu-system-id.php',

            5 =>
                DSM_FACTURACION_PATH
                . 'database/migrations/'
                . '005-add-verifactu-retry-state.php',

            6 =>
                DSM_FACTURACION_PATH
                . 'database/migrations/'
                . '006-add-verifactu-generation-lineage.php',

            7 =>
                DSM_FACTURACION_PATH
                . 'database/migrations/'
                . '007-add-verifactu-correction-markers.php',

            8 =>
                DSM_FACTURACION_PATH
                . 'database/migrations/'
                . '008-add-verifactu-incidence-state.php',

            7 =>
                DSM_FACTURACION_PATH
                . 'database/migrations/'
                . '007-add-verifactu-correction-markers.php',
        ];
    }

    private function __construct()
    {
    }
}
