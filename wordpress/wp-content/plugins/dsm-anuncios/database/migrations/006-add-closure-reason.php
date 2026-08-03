<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Añade el motivo de cierre a los anuncios.
 *
 * Valores previstos:
 *
 * - sold: vendido por el cliente;
 * - withdrawn: retirado voluntariamente;
 * - moderated: cerrado por administración;
 * - expired: cerrado automáticamente por caducidad.
 *
 * La columna se mantiene nullable porque los anuncios abiertos
 * no tienen motivo de cierre.
 */
return static function (): void {
    global $wpdb;

    $tableName =
        $wpdb->prefix
        . 'dsm_ads';

    $columnExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = %s
                  AND COLUMN_NAME = 'closure_reason'
                LIMIT 1
                ",
                $tableName
            )
        );

    if (
        is_string($columnExists)
        && $columnExists === 'closure_reason'
    ) {
        return;
    }

    $result =
        $wpdb->query(
            "
            ALTER TABLE {$tableName}
            ADD closure_reason varchar(40) NULL
            AFTER closed_at
            "
        );

    if ($result === false) {
        throw new RuntimeException(
            sprintf(
                'No se pudo añadir closure_reason a %s: %s',
                $tableName,
                $wpdb->last_error
            )
        );
    }

    /*
     * Añadimos un índice simple porque será habitual consultar
     * anuncios vendidos, retirados o cerrados por moderación.
     */
    $indexExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT INDEX_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = %s
                  AND INDEX_NAME = 'closure_reason'
                LIMIT 1
                ",
                $tableName
            )
        );

    if (
        !is_string($indexExists)
        || $indexExists !== 'closure_reason'
    ) {
        $indexResult =
            $wpdb->query(
                "
                ALTER TABLE {$tableName}
                ADD KEY closure_reason (closure_reason)
                "
            );

        if ($indexResult === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear el índice closure_reason en %s: %s',
                    $tableName,
                    $wpdb->last_error
                )
            );
        }
    }
};