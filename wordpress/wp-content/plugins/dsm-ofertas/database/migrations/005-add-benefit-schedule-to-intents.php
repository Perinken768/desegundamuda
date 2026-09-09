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
        'commercial_context' =>
            "
            ALTER TABLE {$table}
            ADD COLUMN commercial_context
                varchar(40)
                NOT NULL
                DEFAULT 'new_subscription'
            AFTER plan_id
            ",

        'benefit_starts_at' =>
            "
            ALTER TABLE {$table}
            ADD COLUMN benefit_starts_at
                datetime
                DEFAULT NULL
            AFTER currency
            ",

        'benefit_ends_at' =>
            "
            ALTER TABLE {$table}
            ADD COLUMN benefit_ends_at
                datetime
                DEFAULT NULL
            AFTER benefit_starts_at
            ",
    ];

    foreach (
        $columns
        as $column => $sql
    ) {
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
                    'No se pudo añadir %s a las intenciones de ofertas: %s',
                    $column,
                    $wpdb->last_error
                )
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
                'context_status'
            )
        );

    if ($indexExists === null) {
        if (
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD KEY context_status (
                    commercial_context,
                    status
                )
                "
            ) === false
        ) {
            throw new RuntimeException(
                'No se pudo crear el índice context_status: '
                . $wpdb->last_error
            );
        }
    }
};
