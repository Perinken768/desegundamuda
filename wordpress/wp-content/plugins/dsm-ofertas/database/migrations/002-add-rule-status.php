<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $table =
        $wpdb->prefix
        . 'dsm_offer_plan_rules';

    $columnExists =
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
                'is_active'
            )
        );

    if ($columnExists === null) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN is_active
                    tinyint(1) unsigned
                    NOT NULL
                    DEFAULT 1
                AFTER applies_to
                "
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo añadir is_active a las reglas de ofertas: '
                . $wpdb->last_error
            );
        }
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
                'offer_active'
            )
        );

    if ($indexExists === null) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD KEY offer_active (
                    offer_id,
                    is_active
                )
                "
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear el índice offer_active: '
                . $wpdb->last_error
            );
        }
    }
};
