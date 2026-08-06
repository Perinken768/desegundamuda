<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    $tableName =
        $wpdb->prefix
        . 'dsm_countries';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned
                NOT NULL
                AUTO_INCREMENT,

            name varchar(150)
                NOT NULL,

            slug varchar(170)
                NOT NULL,

            iso_code varchar(3)
                NOT NULL,

            phone_prefix varchar(10)
                DEFAULT NULL,

            is_active tinyint(1) unsigned
                NOT NULL
                DEFAULT 1,

            sort_order int(10) unsigned
                NOT NULL
                DEFAULT 0,

            created_at datetime
                NOT NULL,

            updated_at datetime
                NOT NULL,

            PRIMARY KEY  (id),

            UNIQUE KEY slug (slug),

            UNIQUE KEY iso_code (iso_code),

            KEY is_active (is_active),

            KEY sort_order (sort_order)
        ) {$charsetCollate};
    ";

    dbDelta($sql);

    /*
     * Comprobación final.
     *
     * dbDelta() no lanza excepciones, así que verificamos
     * expresamente que la tabla exista.
     */
    $existingTable =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $tableName
            )
        );

    if ($existingTable !== $tableName) {
        throw new RuntimeException(
            'No se pudo crear la tabla de países.'
        );
    }
};