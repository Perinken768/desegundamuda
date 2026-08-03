<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $tableName =
        $wpdb->prefix
        . 'dsm_product_reservations';

    $columnExists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = %s
              AND COLUMN_NAME = %s
            LIMIT 1",
            $tableName,
            'expired_at'
        )
    );

    if ($columnExists !== null) {
        return;
    }

    $result = $wpdb->query(
        "ALTER TABLE {$tableName}
        ADD COLUMN expired_at datetime NULL
        AFTER cancelled_at"
    );

    if ($result === false) {
        throw new RuntimeException(
            sprintf(
                'No se pudo añadir expired_at a la tabla de reservas: %s',
                $wpdb->last_error
            )
        );
    }

    $indexExists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = %s
              AND INDEX_NAME = %s
            LIMIT 1",
            $tableName,
            'expired_at'
        )
    );

    if ($indexExists === null) {
        $indexResult = $wpdb->query(
            "ALTER TABLE {$tableName}
            ADD KEY expired_at (expired_at)"
        );

        if ($indexResult === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear el índice expired_at: %s',
                    $wpdb->last_error
                )
            );
        }
    }
};