<?php

declare(strict_types=1);

namespace DSM\Clientes\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class Installer
{
    private const DB_VERSION_OPTION =
        'dsm_clientes_db_version';

    /**
     * Se ejecuta al activar el plugin.
     */
    public static function activate(): void
    {
        self::migrate();
    }

    /**
     * Ejecuta todas las migraciones pendientes.
     */
    public static function migrate(): void
    {
        $installedVersion =
            self::getInstalledVersion();

        $targetVersion =
            DSM_CLIENTES_DB_VERSION;

        if ($installedVersion >= $targetVersion) {
            return;
        }

        $migrations = self::getMigrations();

        foreach (
            $migrations as $version => $migrationFile
        ) {
            if ($version <= $installedVersion) {
                continue;
            }

            self::runMigration($migrationFile);

            update_option(
                self::DB_VERSION_OPTION,
                $version,
                false
            );

            $installedVersion = $version;
        }
    }

    /**
     * Devuelve la versión de esquema instalada.
     */
    private static function getInstalledVersion(): int
    {
        $version = get_option(
            self::DB_VERSION_OPTION,
            0
        );

        /*
         * Compatibilidad con la primera versión del plugin.
         *
         * La versión 0.1.0 original creó:
         * - dsm_customers
         * - dsm_customer_profiles
         *
         * Por tanto, equivale a las migraciones 1 y 2.
         */
        if ($version === '0.1.0') {
            update_option(
                self::DB_VERSION_OPTION,
                2,
                false
            );

            return 2;
        }

        return (int) $version;
    }

    /**
     * @return array<int, string>
     */
    private static function getMigrations(): array
    {
        return [
            1 => DSM_CLIENTES_PATH
                . 'database/migrations/'
                . '001-create-customers.php',

            2 => DSM_CLIENTES_PATH
                . 'database/migrations/'
                . '002-create-customer-profiles.php',

            3 => DSM_CLIENTES_PATH
                . 'database/migrations/'
                . '003-create-customer-sessions.php',

            4 => DSM_CLIENTES_PATH
                . 'database/migrations/'
                . '004-create-email-verifications.php',

            5 => DSM_CLIENTES_PATH
                . 'database/migrations/'
                . '005-create-customer-deletion-requests.php',

            6 => DSM_CLIENTES_PATH
                . 'database/migrations/'
                . '006-create-account-reactivations.php',
        ];
    }

    /**
     * Ejecuta una migración individual.
     */
    private static function runMigration(
        string $migrationFile
    ): void {
        if (!is_file($migrationFile)) {
            throw new \RuntimeException(
                sprintf(
                    'No se encontró la migración '
                    . 'DSM Clientes: %s',
                    $migrationFile
                )
            );
        }

        $migration = require $migrationFile;

        if (!is_callable($migration)) {
            throw new \RuntimeException(
                sprintf(
                    'La migración DSM Clientes '
                    . 'no es ejecutable: %s',
                    $migrationFile
                )
            );
        }

        $migration();
    }

    private function __construct()
    {
    }
}