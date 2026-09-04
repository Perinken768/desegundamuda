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

    $columnExists =
        static function (
            string $column
        ) use (
            $wpdb,
            $table
        ): bool {
            $result =
                $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SHOW COLUMNS
                        FROM {$table}
                        LIKE %s
                        ",
                        $column
                    )
                );

            return $result !== null;
        };

    $indexExists =
        static function (
            string $index
        ) use (
            $wpdb,
            $table
        ): bool {
            $result =
                $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT INDEX_NAME
                        FROM information_schema.STATISTICS
                        WHERE TABLE_SCHEMA = DATABASE()
                          AND TABLE_NAME = %s
                          AND INDEX_NAME = %s
                        LIMIT 1
                        ",
                        $table,
                        $index
                    )
                );

            return $result !== null;
        };

    /*
     * ==================================================
     * SECUENCIA DE GENERACIÓN
     * ==================================================
     *
     * Permite más de una subsanación/anulación
     * del mismo tipo para una misma factura.
     *
     * Los registros históricos existentes pasan
     * automáticamente a secuencia 1.
     */
    if (
        !$columnExists(
            'generation_sequence'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN generation_sequence
                    int(10) unsigned
                    NOT NULL
                    DEFAULT 1
                AFTER generation_type
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir generation_sequence: '
                . $wpdb->last_error
            );
        }
    }

    /*
     * ==================================================
     * REGISTRO FISCAL DE ORIGEN
     * ==================================================
     *
     * source_record_id indica qué registro fiscal
     * origina una corrección/anulación.
     *
     * NO debe confundirse con previous_record_id,
     * que representa el encadenamiento cronológico.
     */
    if (
        !$columnExists(
            'source_record_id'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN source_record_id
                    bigint(20) unsigned NULL
                AFTER generation_sequence
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir source_record_id: '
                . $wpdb->last_error
            );
        }
    }

    /*
     * Eliminamos el UNIQUE anterior:
     *
     * invoice_id + record_type + generation_type
     *
     * porque impediría generar una segunda
     * subsanación del mismo tipo.
     */
    if (
        $indexExists(
            'verifactu_invoice_record_unique'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                DROP INDEX verifactu_invoice_record_unique
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo eliminar el índice VERI*FACTU anterior: '
                . $wpdb->last_error
            );
        }
    }

    /*
     * Nuevo índice único.
     *
     * Una factura puede tener:
     *
     * alta / normal / 1
     * alta / subsanacion / 1
     * alta / subsanacion / 2
     * anulacion / normal / 1
     * anulacion / subsanacion / 1
     * ...
     */
    if (
        !$indexExists(
            'verifactu_invoice_generation_unique'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD UNIQUE INDEX
                    verifactu_invoice_generation_unique (
                        invoice_id,
                        record_type,
                        generation_type,
                        generation_sequence
                    )
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo crear el nuevo índice único VERI*FACTU: '
                . $wpdb->last_error
            );
        }
    }

    if (
        !$indexExists(
            'source_record_id'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD INDEX source_record_id (
                    source_record_id
                )
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo crear el índice source_record_id: '
                . $wpdb->last_error
            );
        }
    }

    if (
        !$indexExists(
            'generation_lookup'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD INDEX generation_lookup (
                    invoice_id,
                    record_type,
                    generation_type,
                    generation_sequence
                )
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo crear el índice generation_lookup: '
                . $wpdb->last_error
            );
        }
    }
};
