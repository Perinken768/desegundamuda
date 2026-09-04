<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuChainRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table =
            $wpdb->prefix
            . 'dsm_verifactu_chains';
    }

    /**
     * Debe llamarse dentro de una transacción.
     *
     * @return array<string, mixed>
     */
    public function lockOrCreate(
        string $environment,
        string $issuerTaxId,
        string $systemId,
        string $installationId
    ): array {
        global $wpdb;

        $environment =
            trim($environment);

        $issuerTaxId =
            trim($issuerTaxId);

        $systemId =
            trim($systemId);

        $installationId =
            trim($installationId);

        if (
            $environment === ''
            || $issuerTaxId === ''
            || $systemId === ''
            || $installationId === ''
        ) {
            throw new RuntimeException(
                'La identidad de la cadena VERI*FACTU está incompleta.'
            );
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->table}
                    WHERE environment = %s
                      AND issuer_tax_id = %s
                      AND system_id = %s
                      AND installation_id = %s
                    LIMIT 1
                    FOR UPDATE
                    ",
                    $environment,
                    $issuerTaxId,
                    $systemId,
                    $installationId
                ),
                ARRAY_A
            );

        if (is_array($row)) {
            return $row;
        }

        $now =
            current_time(
                'mysql',
                true
            );

        $inserted =
            $wpdb->insert(
                $this->table,
                [
                    'environment' =>
                        $environment,

                    'issuer_tax_id' =>
                        $issuerTaxId,

                    'system_id' =>
                        $systemId,

                    'installation_id' =>
                        $installationId,

                    'last_sequence' =>
                        0,

                    'last_record_id' =>
                        null,

                    'last_hash' =>
                        null,

                    'last_generated_at' =>
                        null,

                    'last_generated_at_iso' =>
                        null,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ]
            );

        if ($inserted === false) {
            /*
             * Puede existir una carrera entre dos procesos
             * intentando crear la misma cadena por primera vez.
             *
             * Si otro proceso consiguió crearla, volvemos
             * a bloquearla.
             */
            $row =
                $wpdb->get_row(
                    $wpdb->prepare(
                        "
                        SELECT *
                        FROM {$this->table}
                        WHERE environment = %s
                          AND issuer_tax_id = %s
                          AND system_id = %s
                          AND installation_id = %s
                        LIMIT 1
                        FOR UPDATE
                        ",
                        $environment,
                        $issuerTaxId,
                        $systemId,
                        $installationId
                    ),
                    ARRAY_A
                );

            if (is_array($row)) {
                return $row;
            }

            throw new RuntimeException(
                'No se pudo crear la cadena VERI*FACTU: '
                . $wpdb->last_error
            );
        }

        $chainId =
            (int) $wpdb->insert_id;

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->table}
                    WHERE id = %d
                    LIMIT 1
                    FOR UPDATE
                    ",
                    $chainId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            throw new RuntimeException(
                'La cadena VERI*FACTU se creó pero no pudo bloquearse.'
            );
        }

        return $row;
    }

    public function advance(
        int $chainId,
        int $sequence,
        int $recordId,
        string $hash,
        string $generatedAt,
        string $generatedAtIso
    ): void {
        global $wpdb;

        $updated =
            $wpdb->update(
                $this->table,
                [
                    'last_sequence' =>
                        $sequence,

                    'last_record_id' =>
                        $recordId,

                    'last_hash' =>
                        $hash,

                    'last_generated_at' =>
                        $generatedAt,

                    'last_generated_at_iso' =>
                        $generatedAtIso,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $chainId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo avanzar la cadena VERI*FACTU: '
                . $wpdb->last_error
            );
        }
    }
}
