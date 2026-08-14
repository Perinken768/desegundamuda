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

    $columns = [
        'plan_id' => "
            ADD COLUMN plan_id bigint(20) unsigned NULL
            AFTER customer_id
        ",

        'price_paid' => "
            ADD COLUMN price_paid decimal(10,2) unsigned NULL
            AFTER remaining_seconds
        ",

        'currency' => "
            ADD COLUMN currency char(3) NULL
            AFTER price_paid
        ",
    ];

    foreach ($columns as $columnName => $definition) {
        $exists =
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = %s
                      AND COLUMN_NAME = %s
                    LIMIT 1",
                    $tableName,
                    $columnName
                )
            );

        if ($exists !== null) {
            continue;
        }

        $result =
            $wpdb->query(
                "ALTER TABLE {$tableName}
                {$definition}"
            );

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo añadir %s a los saldos de promoción: %s',
                    $columnName,
                    $wpdb->last_error
                )
            );
        }
    }

    $planIndexExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "SELECT INDEX_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = %s
                  AND INDEX_NAME = %s
                LIMIT 1",
                $tableName,
                'plan_id'
            )
        );

    if ($planIndexExists === null) {
        $result =
            $wpdb->query(
                "ALTER TABLE {$tableName}
                ADD KEY plan_id (plan_id)"
            );

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear el índice plan_id: %s',
                    $wpdb->last_error
                )
            );
        }
    }
};
