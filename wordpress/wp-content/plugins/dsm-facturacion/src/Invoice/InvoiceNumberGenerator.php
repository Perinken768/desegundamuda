<?php

declare(strict_types=1);

namespace DSM\Facturacion\Invoice;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class InvoiceNumberGenerator
{
    private string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table =
            $wpdb->prefix
            . 'dsm_invoice_sequences';
    }

    /**
     * Debe ejecutarse dentro de una transacción activa.
     *
     * @return array{
     *     series: string,
     *     fiscal_year: int,
     *     sequence_number: int,
     *     full_number: string
     * }
     */
    public function next(
        string $series,
        int $fiscalYear
    ): array {
        global $wpdb;

        $series =
            strtoupper(
                trim($series)
            );

        if ($series === '') {
            throw new RuntimeException(
                'La serie de facturación está vacía.'
            );
        }

        if (
            $fiscalYear < 2000
            || $fiscalYear > 9999
        ) {
            throw new RuntimeException(
                'El ejercicio fiscal no es válido.'
            );
        }

        /*
         * Creamos la fila si todavía no existe.
         *
         * La clave UNIQUE(series, fiscal_year) evita
         * duplicados incluso con peticiones concurrentes.
         */
        $inserted =
            $wpdb->query(
                $wpdb->prepare(
                    "
                    INSERT IGNORE INTO {$this->table}
                    (
                        series,
                        fiscal_year,
                        last_number,
                        created_at,
                        updated_at
                    )
                    VALUES
                    (
                        %s,
                        %d,
                        0,
                        UTC_TIMESTAMP(),
                        UTC_TIMESTAMP()
                    )
                    ",
                    $series,
                    $fiscalYear
                )
            );

        if ($inserted === false) {
            throw new RuntimeException(
                'No se pudo inicializar la secuencia de facturación: '
                . $wpdb->last_error
            );
        }

        /*
         * Bloqueamos exclusivamente la secuencia que
         * vamos a incrementar.
         */
        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT
                        id,
                        last_number
                    FROM {$this->table}
                    WHERE series = %s
                      AND fiscal_year = %d
                    LIMIT 1
                    FOR UPDATE
                    ",
                    $series,
                    $fiscalYear
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            throw new RuntimeException(
                'No se pudo bloquear la secuencia de facturación.'
            );
        }

        $nextNumber =
            ((int) $row['last_number'])
            + 1;

        $updated =
            $wpdb->update(
                $this->table,
                [
                    'last_number' =>
                        $nextNumber,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        (int) $row['id'],
                ],
                [
                    '%d',
                    '%s',
                ],
                [
                    '%d',
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo actualizar la secuencia de facturación: '
                . $wpdb->last_error
            );
        }

        return [
            'series' =>
                $series,

            'fiscal_year' =>
                $fiscalYear,

            'sequence_number' =>
                $nextNumber,

            'full_number' =>
                sprintf(
                    '%s-%04d-%06d',
                    $series,
                    $fiscalYear,
                    $nextNumber
                ),
        ];
    }
}
