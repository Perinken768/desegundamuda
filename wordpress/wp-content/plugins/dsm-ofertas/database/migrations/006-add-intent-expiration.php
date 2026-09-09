<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $table =
        $wpdb->prefix
        . 'dsm_offer_checkout_intents';

    $columns = [
        'expires_at' =>
            "
            ALTER TABLE {$table}
            ADD COLUMN expires_at
                datetime
                DEFAULT NULL
            AFTER status
            ",

        'expired_at' =>
            "
            ALTER TABLE {$table}
            ADD COLUMN expired_at
                datetime
                DEFAULT NULL
            AFTER expires_at
            ",
    ];

    foreach ($columns as $column => $sql) {
        $exists =
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COLUMN_NAME
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = %s
                      AND COLUMN_NAME = %s
                    LIMIT 1
                    ",
                    $table,
                    $column
                )
            );

        if ($exists !== null) {
            continue;
        }

        if ($wpdb->query($sql) === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo añadir %s a las intenciones: %s',
                    $column,
                    $wpdb->last_error
                )
            );
        }
    }

    /*
     * Backfill:
     * las intenciones antiguas caducan 24 h
     * después de su creación.
     */
    if (
        $wpdb->query(
            "
            UPDATE {$table}
            SET expires_at =
                DATE_ADD(
                    created_at,
                    INTERVAL 24 HOUR
                )
            WHERE expires_at IS NULL
            "
        ) === false
    ) {
        throw new RuntimeException(
            'No se pudo calcular la caducidad de '
            . 'las intenciones existentes: '
            . $wpdb->last_error
        );
    }

    $indexExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT INDEX_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = %s
                  AND INDEX_NAME = %s
                LIMIT 1
                ",
                $table,
                'status_expires'
            )
        );

    if ($indexExists === null) {
        if (
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD KEY status_expires (
                    status,
                    expires_at
                )
                "
            ) === false
        ) {
            throw new RuntimeException(
                'No se pudo crear el índice status_expires: '
                . $wpdb->last_error
            );
        }
    }
};
