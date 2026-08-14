<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $tableName =
        $wpdb->prefix
        . 'dsm_products';

    $columnExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = %s
                  AND COLUMN_NAME = %s
                LIMIT 1",
                $tableName,
                'category_id'
            )
        );

    if ($columnExists === null) {
        $result =
            $wpdb->query(
                "ALTER TABLE {$tableName}
                ADD COLUMN category_id bigint(20) unsigned NULL
                AFTER store_id"
            );

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo añadir category_id a la tabla de productos: %s',
                    $wpdb->last_error
                )
            );
        }
    }

    $indexExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "SELECT INDEX_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = %s
                  AND INDEX_NAME = %s
                LIMIT 1",
                $tableName,
                'category_id'
            )
        );

    if ($indexExists === null) {
        $indexResult =
            $wpdb->query(
                "ALTER TABLE {$tableName}
                ADD KEY category_id (category_id)"
            );

        if ($indexResult === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear el índice category_id: %s',
                    $wpdb->last_error
                )
            );
        }
    }
};
