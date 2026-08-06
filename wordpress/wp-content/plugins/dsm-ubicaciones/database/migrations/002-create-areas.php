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
        . 'dsm_location_areas';

    $countriesTable =
        $wpdb->prefix
        . 'dsm_countries';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned
                NOT NULL
                AUTO_INCREMENT,

            country_id bigint(20) unsigned
                NOT NULL,

            parent_id bigint(20) unsigned
                DEFAULT NULL,

            name varchar(150)
                NOT NULL,

            slug varchar(170)
                NOT NULL,

            area_type varchar(40)
                NOT NULL
                DEFAULT 'other',

            code varchar(30)
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

            UNIQUE KEY country_slug (
                country_id,
                slug
            ),

            KEY country_id (country_id),

            KEY parent_id (parent_id),

            KEY area_type (area_type),

            KEY code (code),

            KEY is_active (is_active),

            KEY sort_order (sort_order)
        ) {$charsetCollate};
    ";

    dbDelta($sql);

    /*
     * Comprobamos que la tabla de países exista antes
     * de considerar completada esta migración.
     */
    $existingCountriesTable =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $countriesTable
            )
        );

    if ($existingCountriesTable !== $countriesTable) {
        throw new RuntimeException(
            'No existe la tabla de países necesaria '
            . 'para crear las áreas territoriales.'
        );
    }

    /*
     * dbDelta() no lanza excepciones. Verificamos que
     * la tabla se haya creado realmente.
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
            'No se pudo crear la tabla de áreas territoriales.'
        );
    }
};