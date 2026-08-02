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
        throw new RuntimeException(
            'No existe la tabla de anuncios.'
        );
    }

    $brandColumn = $wpdb->get_var(
        "SHOW COLUMNS
        FROM {$tableName}
        LIKE 'brand'"
    );

    if ($brandColumn === null) {
        $result = $wpdb->query(
            "ALTER TABLE {$tableName}
            ADD COLUMN brand varchar(120) NULL
            AFTER description"
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo añadir el campo de marca.'
            );
        }
    }

    $purchaseDateColumn = $wpdb->get_var(
        "SHOW COLUMNS
        FROM {$tableName}
        LIKE 'purchase_date'"
    );

    if ($purchaseDateColumn === null) {
        $result = $wpdb->query(
            "ALTER TABLE {$tableName}
            ADD COLUMN purchase_date date NULL
            AFTER original_price"
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo añadir la fecha de compra.'
            );
        }
    }

    $brandIndex = $wpdb->get_var(
        "SHOW INDEX
        FROM {$tableName}
        WHERE Key_name = 'brand'"
    );

    if ($brandIndex === null) {
        $result = $wpdb->query(
            "ALTER TABLE {$tableName}
            ADD INDEX brand (brand)"
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear el índice de marca.'
            );
        }
    }
};