<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $tableName =
        $wpdb->prefix
        . 'dsm_ads';

    $tableExists = $wpdb->get_var(
        $wpdb->prepare(
            'SHOW TABLES LIKE %s',
            $tableName
        )
    );

    if ($tableExists !== $tableName) {
        throw new \RuntimeException(
            'No existe la tabla de anuncios.'
        );
    }

    /*
     * Existencias físicas totales.
     *
     * NULL significa que el anuncio no utiliza control de stock,
     * como ocurre con los anuncios de clientes particulares.
     */
    $stockQuantityColumn = $wpdb->get_var(
        "SHOW COLUMNS
        FROM {$tableName}
        LIKE 'stock_quantity'"
    );

    if ($stockQuantityColumn === null) {
        $result = $wpdb->query(
            "ALTER TABLE {$tableName}
            ADD COLUMN stock_quantity
                int(10) unsigned NULL
            AFTER purchase_date"
        );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir el stock total del anuncio.'
            );
        }
    }

    /*
     * Cantidad comprometida en reservas activas.
     */
    $stockReservedColumn = $wpdb->get_var(
        "SHOW COLUMNS
        FROM {$tableName}
        LIKE 'stock_reserved'"
    );

    if ($stockReservedColumn === null) {
        $result = $wpdb->query(
            "ALTER TABLE {$tableName}
            ADD COLUMN stock_reserved
                int(10) unsigned NOT NULL
                DEFAULT 0
            AFTER stock_quantity"
        );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir el stock reservado.'
            );
        }
    }

    /*
     * Estado previo al agotamiento.
     *
     * Permitirá devolver el anuncio a su estado anterior al reponer
     * existencias, normalmente active.
     */
    $previousStatusColumn = $wpdb->get_var(
        "SHOW COLUMNS
        FROM {$tableName}
        LIKE 'status_before_out_of_stock'"
    );

    if ($previousStatusColumn === null) {
        $result = $wpdb->query(
            "ALTER TABLE {$tableName}
            ADD COLUMN status_before_out_of_stock
                varchar(30) NULL
            AFTER stock_reserved"
        );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir el estado anterior al agotamiento.'
            );
        }
    }

    /*
     * Fecha del último movimiento o ajuste de existencias.
     */
    $stockUpdatedAtColumn = $wpdb->get_var(
        "SHOW COLUMNS
        FROM {$tableName}
        LIKE 'stock_updated_at'"
    );

    if ($stockUpdatedAtColumn === null) {
        $result = $wpdb->query(
            "ALTER TABLE {$tableName}
            ADD COLUMN stock_updated_at
                datetime NULL
            AFTER status_before_out_of_stock"
        );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir la fecha de actualización del stock.'
            );
        }
    }

    /*
     * Evita enviar varias notificaciones durante el mismo periodo
     * de agotamiento.
     *
     * Se limpiará cuando vuelva a existir stock disponible.
     */
    $notifiedAtColumn = $wpdb->get_var(
        "SHOW COLUMNS
        FROM {$tableName}
        LIKE 'out_of_stock_notified_at'"
    );

    if ($notifiedAtColumn === null) {
        $result = $wpdb->query(
            "ALTER TABLE {$tableName}
            ADD COLUMN out_of_stock_notified_at
                datetime NULL
            AFTER stock_updated_at"
        );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir el control de notificación de agotamiento.'
            );
        }
    }

    /*
     * Índice para localizar rápidamente anuncios de tienda
     * con existencias disponibles o agotadas.
     */
    $stockIndex = $wpdb->get_var(
        "SHOW INDEX
        FROM {$tableName}
        WHERE Key_name = 'store_stock'"
    );

    if ($stockIndex === null) {
        $result = $wpdb->query(
            "ALTER TABLE {$tableName}
            ADD INDEX store_stock (
                store_id,
                stock_quantity,
                stock_reserved
            )"
        );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo crear el índice de stock.'
            );
        }
    }

    /*
     * Garantizamos que ningún registro existente tenga
     * stock reservado negativo o inconsistente.
     */
    $normalized = $wpdb->query(
        "UPDATE {$tableName}
        SET stock_reserved = 0
        WHERE stock_reserved IS NULL"
    );

    if ($normalized === false) {
        throw new \RuntimeException(
            'No se pudo normalizar el stock reservado.'
        );
    }
};