<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sustituye la denominación específica island_id por area_id.
 *
 * Los valores existentes se conservan. Los identificadores que
 * antes representaban islas pasan a representar áreas territoriales
 * administradas por DSM Ubicaciones.
 */
return static function (): void {
    global $wpdb;

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    $tableName =
        $wpdb->prefix
        . 'dsm_customer_profiles';

    $existingTable =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $tableName
            )
        );

    if ($existingTable !== $tableName) {
        throw new RuntimeException(
            'No existe la tabla de perfiles de clientes.'
        );
    }

    $hasIslandId =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SHOW COLUMNS
                FROM {$tableName}
                LIKE %s
                ",
                'island_id'
            )
        );

    $hasAreaId =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SHOW COLUMNS
                FROM {$tableName}
                LIKE %s
                ",
                'area_id'
            )
        );

    /*
     * Caso normal:
     *
     * island_id existe y area_id todavía no.
     * Se renombra conservando todos los valores.
     */
    if (
        $hasIslandId === 'island_id'
        && $hasAreaId !== 'area_id'
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$tableName}
                CHANGE island_id area_id
                    bigint(20) unsigned
                    DEFAULT NULL
                "
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo renombrar island_id como area_id.'
            );
        }
    }

    /*
     * Caso defensivo:
     *
     * Si por una ejecución parcial existieran ambas columnas,
     * trasladamos los valores que aún falten en area_id y
     * eliminamos la columna antigua.
     */
    if (
        $hasIslandId === 'island_id'
        && $hasAreaId === 'area_id'
    ) {
        $copyResult =
            $wpdb->query(
                "
                UPDATE {$tableName}
                SET area_id = island_id
                WHERE area_id IS NULL
                  AND island_id IS NOT NULL
                "
            );

        if ($copyResult === false) {
            throw new RuntimeException(
                'No se pudieron trasladar los valores de island_id.'
            );
        }

        $dropResult =
            $wpdb->query(
                "
                ALTER TABLE {$tableName}
                DROP COLUMN island_id
                "
            );

        if ($dropResult === false) {
            throw new RuntimeException(
                'No se pudo eliminar la columna island_id antigua.'
            );
        }
    }

    /*
     * Verificación final.
     */
    $finalAreaColumn =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SHOW COLUMNS
                FROM {$tableName}
                LIKE %s
                ",
                'area_id'
            )
        );

    if ($finalAreaColumn !== 'area_id') {
        throw new RuntimeException(
            'La columna area_id no quedó disponible en los perfiles.'
        );
    }

    /*
     * dbDelta permite asegurar que el índice exista después
     * del cambio de nombre.
     */
    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned
                NOT NULL
                AUTO_INCREMENT,

            customer_id bigint(20) unsigned
                NOT NULL,

            display_name varchar(150)
                NOT NULL,

            phone varchar(30)
                DEFAULT NULL,

            allow_phone_calls tinyint(1) unsigned
                NOT NULL
                DEFAULT 0,

            whatsapp_phone varchar(30)
                DEFAULT NULL,

            allow_whatsapp tinyint(1) unsigned
                NOT NULL
                DEFAULT 0,

            avatar_attachment_id bigint(20) unsigned
                DEFAULT NULL,

            bio text
                DEFAULT NULL,

            area_id bigint(20) unsigned
                DEFAULT NULL,

            municipality_id bigint(20) unsigned
                DEFAULT NULL,

            created_at datetime
                NOT NULL,

            updated_at datetime
                NOT NULL,

            PRIMARY KEY  (id),

            UNIQUE KEY customer_id (customer_id),

            KEY area_id (area_id),

            KEY municipality_id (municipality_id)
        ) {$charsetCollate};
    ";

    dbDelta($sql);

    $finalIslandColumn =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SHOW COLUMNS
                FROM {$tableName}
                LIKE %s
                ",
                'island_id'
            )
        );

    if ($finalIslandColumn === 'island_id') {
        throw new RuntimeException(
            'La columna island_id antigua continúa existiendo.'
        );
    }
};