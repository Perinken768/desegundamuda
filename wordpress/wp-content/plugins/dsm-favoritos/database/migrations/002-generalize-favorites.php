<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $table =
        $wpdb->prefix
        . 'dsm_favorites';

    /*
     * =========================================================
     * NUEVAS COLUMNAS
     * =========================================================
     */

    $columns =
        $wpdb->get_results(
            "SHOW COLUMNS FROM {$table}",
            ARRAY_A
        );

    $columnNames = [];

    foreach ($columns as $column) {
        $columnNames[] =
            (string) (
                $column['Field']
                ?? ''
            );
    }

    if (
        !in_array(
            'item_type',
            $columnNames,
            true
        )
    ) {
        $wpdb->query(
            "
            ALTER TABLE {$table}
            ADD COLUMN item_type varchar(32) NULL
            AFTER customer_id
            "
        );
    }

    if (
        !in_array(
            'item_id',
            $columnNames,
            true
        )
    ) {
        $wpdb->query(
            "
            ALTER TABLE {$table}
            ADD COLUMN item_id bigint(20) unsigned NULL
            AFTER item_type
            "
        );
    }

    /*
     * =========================================================
     * MIGRAR FAVORITOS ACTUALES
     * =========================================================
     */

    $wpdb->query(
        "
        UPDATE {$table}
        SET
            item_type = 'advertisement',
            item_id = advertisement_id
        WHERE
            (
                item_type IS NULL
                OR item_type = ''
            )
            OR item_id IS NULL
            OR item_id = 0
        "
    );

    /*
     * =========================================================
     * VERIFICACIÓN
     * =========================================================
     */

    $invalidRows =
        (int) $wpdb->get_var(
            "
            SELECT COUNT(*)
            FROM {$table}
            WHERE
                item_type IS NULL
                OR item_type = ''
                OR item_id IS NULL
                OR item_id = 0
            "
        );

    if ($invalidRows > 0) {
        throw new RuntimeException(
            'No se pudieron convertir todos los favoritos existentes.'
        );
    }

    /*
     * =========================================================
     * NUEVO ÍNDICE ÚNICO
     * =========================================================
     */

    $indexes =
        $wpdb->get_results(
            "SHOW INDEX FROM {$table}",
            ARRAY_A
        );

    $indexNames = [];

    foreach ($indexes as $index) {
        $indexNames[] =
            (string) (
                $index['Key_name']
                ?? ''
            );
    }

    if (
        in_array(
            'customer_advertisement',
            $indexNames,
            true
        )
    ) {
        $wpdb->query(
            "
            ALTER TABLE {$table}
            DROP INDEX customer_advertisement
            "
        );
    }

    if (
        !in_array(
            'customer_item',
            $indexNames,
            true
        )
    ) {
        $wpdb->query(
            "
            ALTER TABLE {$table}
            ADD UNIQUE KEY customer_item (
                customer_id,
                item_type,
                item_id
            )
            "
        );
    }

    /*
     * Índices auxiliares.
     */
    $indexes =
        $wpdb->get_results(
            "SHOW INDEX FROM {$table}",
            ARRAY_A
        );

    $indexNames = [];

    foreach ($indexes as $index) {
        $indexNames[] =
            (string) (
                $index['Key_name']
                ?? ''
            );
    }

    if (
        !in_array(
            'item_lookup',
            $indexNames,
            true
        )
    ) {
        $wpdb->query(
            "
            ALTER TABLE {$table}
            ADD KEY item_lookup (
                item_type,
                item_id
            )
            "
        );
    }

    /*
     * Ya podemos hacer obligatorias las columnas nuevas.
     *
     * advertisement_id se conserva por compatibilidad durante
     * esta primera fase. Lo retiraremos en una migración futura
     * cuando todo el código haya dejado de depender de él.
     */
    $wpdb->query(
        "
        ALTER TABLE {$table}
        MODIFY item_type varchar(32) NOT NULL,
        MODIFY item_id bigint(20) unsigned NOT NULL
        "
    );
};
