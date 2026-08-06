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
        . 'dsm_municipalities';

    $areasTable =
        $wpdb->prefix
        . 'dsm_location_areas';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned
                NOT NULL
                AUTO_INCREMENT,

            area_id bigint(20) unsigned
                NOT NULL,

            name varchar(150)
                NOT NULL,

            slug varchar(170)
                NOT NULL,

            code varchar(30)
                DEFAULT NULL,

            postal_code varchar(20)
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

            UNIQUE KEY area_slug (
                area_id,
                slug
            ),

            KEY area_id (area_id),

            KEY code (code),

            KEY postal_code (postal_code),

            KEY is_active (is_active),

            KEY sort_order (sort_order)
        ) {$charsetCollate};
    ";

    /*
     * Antes de crear municipios comprobamos que exista
     * la tabla territorial de áreas.
     */
    $existingAreasTable =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $areasTable
            )
        );

    if ($existingAreasTable !== $areasTable) {
        throw new RuntimeException(
            'No existe la tabla de áreas necesaria '
            . 'para crear los municipios.'
        );
    }

    dbDelta($sql);

    /*
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
            'No se pudo crear la tabla de municipios.'
        );
    }
};