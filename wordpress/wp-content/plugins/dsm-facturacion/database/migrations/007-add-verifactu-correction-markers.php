<?php

declare(strict_types=1);


if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $table =
        $wpdb->prefix
        . 'dsm_verifactu_records';

    /*
     * ======================================================
     * Subsanacion
     * ======================================================
     */
    $subsanacionExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SHOW COLUMNS
                FROM {$table}
                LIKE %s
                ",
                'subsanacion'
            )
        );

    if ($subsanacionExists === null) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN subsanacion CHAR(1) NULL
                AFTER source_record_id
                "
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo añadir subsanacion a '
                . $table
                . ': '
                . $wpdb->last_error
            );
        }
    }

    /*
     * ======================================================
     * Rechazo previo
     * ======================================================
     */
    $rechazoPrevioExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SHOW COLUMNS
                FROM {$table}
                LIKE %s
                ",
                'rechazo_previo'
            )
        );

    if ($rechazoPrevioExists === null) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN rechazo_previo CHAR(1) NULL
                AFTER subsanacion
                "
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo añadir rechazo_previo a '
                . $table
                . ': '
                . $wpdb->last_error
            );
        }
    }

    /*
     * ======================================================
     * Sin registro previo
     * ======================================================
     */
    $sinRegistroPrevioExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SHOW COLUMNS
                FROM {$table}
                LIKE %s
                ",
                'sin_registro_previo'
            )
        );

    if ($sinRegistroPrevioExists === null) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN sin_registro_previo CHAR(1) NULL
                AFTER rechazo_previo
                "
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo añadir sin_registro_previo a '
                . $table
                . ': '
                . $wpdb->last_error
            );
        }
    }

    /*
     * ======================================================
     * Índice redundante
     * ======================================================
     *
     * Ya existe:
     *
     * verifactu_invoice_generation_unique
     * (
     *   invoice_id,
     *   record_type,
     *   generation_type,
     *   generation_sequence
     * )
     *
     * Por tanto generation_lookup sobre las mismas
     * columnas no aporta nada.
     */
    $generationLookupExists =
        $wpdb->get_var(
            "
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND INDEX_NAME = 'generation_lookup'
            LIMIT 1
            "
        );

    if ($generationLookupExists !== null) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                DROP INDEX generation_lookup
                "
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo eliminar el índice generation_lookup de '
                . $table
                . ': '
                . $wpdb->last_error
            );
        }
    }
};
