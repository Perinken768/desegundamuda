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
     * Antes de crear la restricción comprobamos que
     * los datos existentes sean coherentes.
     *
     * Una factura no puede tener dos registros con
     * la misma combinación:
     *
     * invoice_id + record_type + generation_type
     */
    $duplicate =
        $wpdb->get_row(
            "
            SELECT
                invoice_id,
                record_type,
                generation_type,
                COUNT(*) AS total
            FROM {$table}
            GROUP BY
                invoice_id,
                record_type,
                generation_type
            HAVING COUNT(*) > 1
            LIMIT 1
            ",
            ARRAY_A
        );

    if (is_array($duplicate)) {
        throw new \RuntimeException(
            sprintf(
                'No se puede aplicar la migración VERI*FACTU 003: '
                . 'la factura %d tiene %d registros duplicados '
                . 'para %s/%s.',
                (int) $duplicate['invoice_id'],
                (int) $duplicate['total'],
                (string) $duplicate['record_type'],
                (string) $duplicate['generation_type']
            )
        );
    }

    /*
     * Comprobamos si el índice ya existe.
     *
     * Esto permite que la migración sea segura
     * incluso si hubiese quedado parcialmente
     * aplicada anteriormente.
     */
    $indexName =
        'verifactu_invoice_record_unique';

    $existingIndex =
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
                $indexName
            )
        );

    if ($existingIndex !== null) {
        return;
    }

    /*
     * Blindaje de idempotencia a nivel MySQL.
     *
     * Permite, por ejemplo:
     *
     * factura 1 + alta + normal
     * factura 1 + anulacion + normal
     * factura 1 + alta + subsanacion
     *
     * Pero nunca dos veces la misma combinación.
     */
    $result =
        $wpdb->query(
            "
            ALTER TABLE {$table}
            ADD UNIQUE KEY {$indexName}
            (
                invoice_id,
                record_type,
                generation_type
            )
            "
        );

    if ($result === false) {
        throw new \RuntimeException(
            'No se pudo crear la restricción de idempotencia '
            . 'VERI*FACTU: '
            . $wpdb->last_error
        );
    }
};
