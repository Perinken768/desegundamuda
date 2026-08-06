<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sustituye la denominación específica island_id por area_id
 * en la tabla de anuncios.
 *
 * Los valores existentes se conservan.
 *
 * En Canarias, esos identificadores seguirán apuntando a áreas
 * de tipo island. En el futuro también podrán representar
 * provincias, comarcas u otras divisiones territoriales
 * gestionadas por DSM Ubicaciones.
 */
return static function (): void {
    global $wpdb;

    $tableName =
        $wpdb->prefix
        . 'dsm_ads';

    $existingTable =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $tableName
            )
        );

    if ($existingTable !== $tableName) {
        throw new RuntimeException(
            'No existe la tabla de anuncios.'
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
     * Se renombra la columna conservando sus valores.
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
                'No se pudo renombrar island_id como area_id en los anuncios.'
            );
        }
    }

    /*
     * Caso defensivo:
     *
     * Si una ejecución parcial hubiera dejado ambas columnas,
     * trasladamos los valores que falten y eliminamos island_id.
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
                'No se pudieron trasladar los valores territoriales de los anuncios.'
            );
        }

        /*
         * Eliminamos primero el índice antiguo si continúa
         * existiendo con el nombre island_id.
         */
        $oldIndexExists =
            (int) $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(*)
                    FROM information_schema.statistics
                    WHERE table_schema = DATABASE()
                      AND table_name = %s
                      AND index_name = %s
                    ",
                    $tableName,
                    'island_id'
                )
            ) > 0;

        if ($oldIndexExists) {
            $dropIndexResult =
                $wpdb->query(
                    "
                    ALTER TABLE {$tableName}
                    DROP INDEX island_id
                    "
                );

            if ($dropIndexResult === false) {
                throw new RuntimeException(
                    'No se pudo eliminar el índice island_id antiguo.'
                );
            }
        }

        $dropColumnResult =
            $wpdb->query(
                "
                ALTER TABLE {$tableName}
                DROP COLUMN island_id
                "
            );

        if ($dropColumnResult === false) {
            throw new RuntimeException(
                'No se pudo eliminar la columna island_id antigua.'
            );
        }
    }

    /*
     * Verificamos que area_id exista después del cambio.
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
            'La columna area_id no quedó disponible en los anuncios.'
        );
    }

    /*
     * Aseguramos que exista un índice llamado area_id.
     *
     * Al renombrar una columna, MariaDB puede conservar el nombre
     * antiguo del índice aunque este ya apunte a area_id.
     */
    $areaIndexExists =
        (int) $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COUNT(*)
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = %s
                  AND index_name = %s
                ",
                $tableName,
                'area_id'
            )
        ) > 0;

    $oldIndexExists =
        (int) $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COUNT(*)
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = %s
                  AND index_name = %s
                ",
                $tableName,
                'island_id'
            )
        ) > 0;

    if (
        !$areaIndexExists
        && $oldIndexExists
    ) {
        $renameIndexResult =
            $wpdb->query(
                "
                ALTER TABLE {$tableName}
                RENAME INDEX island_id TO area_id
                "
            );

        if ($renameIndexResult === false) {
            throw new RuntimeException(
                'No se pudo renombrar el índice territorial de los anuncios.'
            );
        }

        $areaIndexExists =
            true;
    }

    if (!$areaIndexExists) {
        $addIndexResult =
            $wpdb->query(
                "
                ALTER TABLE {$tableName}
                ADD KEY area_id (area_id)
                "
            );

        if ($addIndexResult === false) {
            throw new RuntimeException(
                'No se pudo crear el índice area_id de los anuncios.'
            );
        }
    }

    /*
     * Verificación final: island_id ya no debe existir.
     */
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
            'La columna island_id antigua continúa existiendo en los anuncios.'
        );
    }
};