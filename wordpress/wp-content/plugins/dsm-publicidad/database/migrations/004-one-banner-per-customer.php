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
     * Nos negamos a crear el índice si ya existen
     * duplicados reales. Nunca eliminamos datos
     * automáticamente.
     */
    $duplicate =
        $wpdb->get_row(
            "
            SELECT
                customer_id,
                COUNT(*) AS total
            FROM {$tableName}
            WHERE customer_id IS NOT NULL
            GROUP BY customer_id
            HAVING COUNT(*) > 1
            LIMIT 1
            ",
            ARRAY_A
        );

    if (is_array($duplicate)) {
        throw new RuntimeException(
            'No se puede limitar a una publicidad por cliente porque existen clientes con más de una publicidad.'
        );
    }

    $existing =
        $wpdb->get_var(
            "
            SHOW INDEX
            FROM {$tableName}
            WHERE Key_name = 'unique_customer'
            "
        );

    if ($existing !== null) {
        return;
    }

    $result =
        $wpdb->query(
            "
            ALTER TABLE {$tableName}
            ADD UNIQUE INDEX unique_customer (
                customer_id
            )
            "
        );

    if ($result === false) {
        throw new RuntimeException(
            'No se pudo crear la restricción de una publicidad por cliente.'
        );
    }
};
