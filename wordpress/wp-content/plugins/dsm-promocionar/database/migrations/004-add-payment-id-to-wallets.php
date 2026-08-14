<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $tableName =
        $wpdb->prefix
        . 'dsm_promotion_wallets';

    $columnExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "SHOW COLUMNS
                FROM {$tableName}
                LIKE %s",
                'payment_id'
            )
        );

    if ($columnExists === null) {
        $result =
            $wpdb->query(
                "ALTER TABLE {$tableName}
                ADD COLUMN payment_id
                    BIGINT(20) UNSIGNED NULL
                    AFTER plan_id"
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo añadir payment_id a los saldos de promoción: '
                . $wpdb->last_error
            );
        }
    }

    $indexExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = %s
                  AND INDEX_NAME = %s
                LIMIT 1",
                $tableName,
                'payment_id_unique'
            )
        );

    if ($indexExists === null) {
        $result =
            $wpdb->query(
                "ALTER TABLE {$tableName}
                ADD UNIQUE KEY payment_id_unique (payment_id)"
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear el índice único de payment_id: '
                . $wpdb->last_error
            );
        }
    }
};
