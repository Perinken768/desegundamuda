<?php

declare(strict_types=1);


if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $subscriptionsTable =
        $wpdb->prefix
        . 'dsm_subscriptions';

    $subscriptionPaymentsTable =
        $wpdb->prefix
        . 'dsm_subscription_payments';

    /*
     * ========================================================
     * COMPROBACIÓN TABLA PRINCIPAL
     * ========================================================
     */

    $subscriptionsTableExists =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $subscriptionsTable
            )
        );

    if (
        $subscriptionsTableExists
        !== $subscriptionsTable
    ) {
        throw new RuntimeException(
            'No se encontró la tabla de suscripciones.'
        );
    }

    /*
     * ========================================================
     * NUEVAS COLUMNAS DE SUSCRIPCIONES
     * ========================================================
     *
     * No utilizamos dbDelta() para modificar esta tabla.
     *
     * En este entorno dbDelta() no está interpretando
     * correctamente la definición completa de una tabla
     * existente y genera warnings internos de WordPress.
     *
     * Añadimos las columnas de forma explícita e idempotente.
     */

    $columns = [
        'cancel_requested_at' => "
            ALTER TABLE {$subscriptionsTable}
            ADD COLUMN cancel_requested_at datetime NULL
            AFTER cancelled_at
        ",

        'provider' => "
            ALTER TABLE {$subscriptionsTable}
            ADD COLUMN provider varchar(30) NULL
            AFTER auto_renew
        ",

        'provider_subscription_id' => "
            ALTER TABLE {$subscriptionsTable}
            ADD COLUMN provider_subscription_id varchar(190) NULL
            AFTER provider
        ",
    ];

    foreach (
        $columns
        as $columnName => $sql
    ) {
        $columnExists =
            $wpdb->get_var(
                $wpdb->prepare(
                    "SHOW COLUMNS
                    FROM {$subscriptionsTable}
                    LIKE %s",
                    $columnName
                )
            );

        if ($columnExists === null) {
            $result =
                $wpdb->query(
                    $sql
                );

            if ($result === false) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo crear la columna %s: %s',
                        $columnName,
                        $wpdb->last_error
                    )
                );
            }
        }
    }

    /*
     * ========================================================
     * ÍNDICES DE SUSCRIPCIONES
     * ========================================================
     */

    $indexes = [
        'provider_subscription' => "
            ALTER TABLE {$subscriptionsTable}
            ADD KEY provider_subscription (
                provider,
                provider_subscription_id
            )
        ",

        'cancel_requested_at' => "
            ALTER TABLE {$subscriptionsTable}
            ADD KEY cancel_requested_at (
                cancel_requested_at
            )
        ",
    ];

    foreach (
        $indexes
        as $indexName => $sql
    ) {
        $indexExists =
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT INDEX_NAME
                    FROM information_schema.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = %s
                    AND INDEX_NAME = %s
                    LIMIT 1",
                    $subscriptionsTable,
                    $indexName
                )
            );

        if ($indexExists === null) {
            $result =
                $wpdb->query(
                    $sql
                );

            if ($result === false) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo crear el índice %s: %s',
                        $indexName,
                        $wpdb->last_error
                    )
                );
            }
        }
    }

    /*
     * ========================================================
     * TABLA HISTÓRICO DE COBROS
     * ========================================================
     *
     * Puede existir ya si la primera ejecución de esta
     * migración consiguió crearla antes de fallar.
     *
     * Por eso usamos CREATE TABLE IF NOT EXISTS.
     */

    $charsetCollate =
        $wpdb->get_charset_collate();

    $createPaymentsTableSql = "
        CREATE TABLE IF NOT EXISTS {$subscriptionPaymentsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            subscription_id bigint(20) unsigned NOT NULL,

            payment_id bigint(20) unsigned DEFAULT NULL,

            provider varchar(30) DEFAULT NULL,

            provider_invoice_reference varchar(190) DEFAULT NULL,

            period_start datetime DEFAULT NULL,

            period_end datetime DEFAULT NULL,

            amount decimal(10,2) unsigned DEFAULT NULL,

            currency char(3) DEFAULT NULL,

            created_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY payment_id_unique (
                payment_id
            ),

            UNIQUE KEY provider_invoice_unique (
                provider,
                provider_invoice_reference
            ),

            KEY subscription_id (
                subscription_id
            ),

            KEY subscription_period (
                subscription_id,
                period_start,
                period_end
            )
        ) {$charsetCollate}
    ";

    $result =
        $wpdb->query(
            $createPaymentsTableSql
        );

    if ($result === false) {
        throw new RuntimeException(
            sprintf(
                'No se pudo crear la tabla de pagos de suscripciones: %s',
                $wpdb->last_error
            )
        );
    }

    /*
     * ========================================================
     * VERIFICACIONES FINALES
     * ========================================================
     */

    $requiredColumns = [
        'cancel_requested_at',
        'provider',
        'provider_subscription_id',
    ];

    foreach (
        $requiredColumns
        as $columnName
    ) {
        $columnExists =
            $wpdb->get_var(
                $wpdb->prepare(
                    "SHOW COLUMNS
                    FROM {$subscriptionsTable}
                    LIKE %s",
                    $columnName
                )
            );

        if ($columnExists !== $columnName) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo verificar la columna %s.',
                    $columnName
                )
            );
        }
    }

    $paymentsTableExists =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $subscriptionPaymentsTable
            )
        );

    if (
        $paymentsTableExists
        !== $subscriptionPaymentsTable
    ) {
        throw new RuntimeException(
            'No se pudo verificar la tabla de pagos de suscripciones.'
        );
    }
};
