<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $tableName =
        $wpdb->prefix
        . 'dsm_advertising_banners';

    /*
     * Añadimos customer_id únicamente si todavía
     * no existe.
     */
    $customerColumn =
        $wpdb->get_var(
            "
            SHOW COLUMNS
            FROM {$tableName}
            LIKE 'customer_id'
            "
        );

    if ($customerColumn === null) {
        $wpdb->query(
            "
            ALTER TABLE {$tableName}
            ADD COLUMN customer_id
                bigint(20) unsigned NULL
                AFTER id
            "
        );
    }

    /*
     * Índice simple por cliente.
     */
    $customerIndex =
        $wpdb->get_var(
            "
            SHOW INDEX
            FROM {$tableName}
            WHERE Key_name = 'customer_id'
            "
        );

    if ($customerIndex === null) {
        $wpdb->query(
            "
            ALTER TABLE {$tableName}
            ADD INDEX customer_id (customer_id)
            "
        );
    }

    /*
     * Cliente + estado.
     */
    $customerStatusIndex =
        $wpdb->get_var(
            "
            SHOW INDEX
            FROM {$tableName}
            WHERE Key_name = 'customer_status'
            "
        );

    if ($customerStatusIndex === null) {
        $wpdb->query(
            "
            ALTER TABLE {$tableName}
            ADD INDEX customer_status (
                customer_id,
                status
            )
            "
        );
    }

    /*
     * Cliente + isla.
     */
    $customerAreaIndex =
        $wpdb->get_var(
            "
            SHOW INDEX
            FROM {$tableName}
            WHERE Key_name = 'customer_area'
            "
        );

    if ($customerAreaIndex === null) {
        $wpdb->query(
            "
            ALTER TABLE {$tableName}
            ADD INDEX customer_area (
                customer_id,
                area_id
            )
            "
        );
    }
};
