<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuRecordRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table =
            $wpdb->prefix
            . 'dsm_verifactu_records';
    }

    public function findById(
        int $recordId
    ): ?VerifactuRecord {
        global $wpdb;

        if ($recordId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->table}
                    WHERE id = %d
                    LIMIT 1
                    ",
                    $recordId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate(
            $row
        );
    }

    public function findRegistrationByInvoiceId(
        int $invoiceId
    ): ?VerifactuRecord {
        global $wpdb;

        if ($invoiceId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->table}
                    WHERE invoice_id = %d
                      AND record_type = 'alta'
                      AND generation_type = 'normal'
                      AND generation_sequence = 1
                    ORDER BY id ASC
                    LIMIT 1
                    ",
                    $invoiceId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate(
            $row
        );
    }

    public function findLatestGeneration(
        int $invoiceId,
        string $recordType,
        string $generationType
    ): ?VerifactuRecord {
        global $wpdb;

        if ($invoiceId <= 0) {
            return null;
        }

        $recordType =
            trim(
                $recordType
            );

        $generationType =
            trim(
                $generationType
            );

        if (
            $recordType === ''
            || $generationType === ''
        ) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->table}
                    WHERE invoice_id = %d
                      AND record_type = %s
                      AND generation_type = %s
                    ORDER BY
                        generation_sequence DESC,
                        id DESC
                    LIMIT 1
                    ",
                    $invoiceId,
                    $recordType,
                    $generationType
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate(
            $row
        );
    }

    /**
     * @return array<int, VerifactuRecord>
     */
    public function findRecordsBySourceRecordId(
        int $sourceRecordId
    ): array {
        global $wpdb;

        if ($sourceRecordId <= 0) {
            return [];
        }

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->table}
                    WHERE source_record_id = %d
                    ORDER BY
                        chain_sequence ASC,
                        id ASC
                    ",
                    $sourceRecordId
                ),
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $records = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $records[] =
                $this->hydrate(
                    $row
                );
        }

        return $records;
    }

    /**
     * Devuelve registros fiscales generados que todavía
     * no tienen ninguna remisión asociada.
     *
     * Esta consulta sirve para recuperar la ventana:
     *
     * registro VERI*FACTU creado
     *          ↓
     * fallo antes de crear submission
     *
     * No recuperamos registros que ya tengan historial
     * de remisiones, independientemente de su estado.
     *
     * @return array<int, VerifactuRecord>
     */
    public function findOrphanedGeneratedRecords(
        int $limit = 50
    ): array {
        global $wpdb;

        $limit =
            max(
                1,
                min(
                    500,
                    $limit
                )
            );

        $submissionsTable =
            $wpdb->prefix
            . 'dsm_verifactu_submissions';

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT r.*
                    FROM {$this->table} r
                    WHERE r.fiscal_status = 'generated'
                      AND NOT EXISTS (
                            SELECT 1
                            FROM {$submissionsTable} s
                            WHERE s.record_id = r.id
                      )
                    ORDER BY
                        r.chain_sequence ASC,
                        r.id ASC
                    LIMIT %d
                    ",
                    $limit
                ),
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $records = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $records[] =
                $this->hydrate(
                    $row
                );
        }

        return $records;
    }
    /**
     * Devuelve el payload fiscal almacenado.
     *
     * @return array{
     *     version: string,
     *     xml: string
     * }|null
     */
    public function findPayload(
        int $recordId
    ): ?array {
        global $wpdb;

        if ($recordId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT
                        payload_version,
                        payload_xml
                    FROM {$this->table}
                    WHERE id = %d
                    LIMIT 1
                    ",
                    $recordId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        $xml =
            trim(
                (string) (
                    $row['payload_xml']
                    ?? ''
                )
            );

        if ($xml === '') {
            return null;
        }

        return [
            'version' =>
                trim(
                    (string) (
                        $row['payload_version']
                        ?? ''
                    )
                ),

            'xml' =>
                (string) $row['payload_xml'],
        ];
    }

    public function persistPayload(
        int $recordId,
        string $version,
        string $xml
    ): string {
        global $wpdb;

        if ($recordId <= 0) {
            throw new RuntimeException(
                'El identificador del registro VERI*FACTU no es válido.'
            );
        }

        $version =
            trim(
                $version
            );

        if ($version === '') {
            throw new RuntimeException(
                'La versión del payload VERI*FACTU está vacía.'
            );
        }

        if (trim($xml) === '') {
            throw new RuntimeException(
                'El payload XML VERI*FACTU está vacío.'
            );
        }

        $existing =
            $this->findPayload(
                $recordId
            );

        if ($existing !== null) {
            $this->assertSamePayload(
                $recordId,
                $version,
                $xml,
                $existing
            );

            return $existing['xml'];
        }

        $updated =
            $wpdb->query(
                $wpdb->prepare(
                    "
                    UPDATE {$this->table}
                    SET
                        payload_version = %s,
                        payload_xml = %s,
                        updated_at = %s
                    WHERE id = %d
                      AND (
                            payload_xml IS NULL
                            OR payload_xml = ''
                      )
                    ",
                    $version,
                    $xml,
                    gmdate(
                        'Y-m-d H:i:s'
                    ),
                    $recordId
                )
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo persistir el payload VERI*FACTU: '
                . $wpdb->last_error
            );
        }

        if ($updated === 1) {
            return $xml;
        }

        $existing =
            $this->findPayload(
                $recordId
            );

        if ($existing === null) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo persistir el payload del registro VERI*FACTU %d.',
                    $recordId
                )
            );
        }

        $this->assertSamePayload(
            $recordId,
            $version,
            $xml,
            $existing
        );

        return $existing['xml'];
    }

    /**
     * Persiste el resultado fiscal devuelto por AEAT.
     *
     * Debe ejecutarse dentro de la transacción controlada
     * por VerifactuResponseProcessor.
     */
    public function updateAeatResult(
        int $recordId,
        string $status,
        ?string $errorCode,
        ?string $errorMessage,
        ?string $acceptedAt
    ): void {
        global $wpdb;

        if ($recordId <= 0) {
            throw new RuntimeException(
                'El identificador del registro VERI*FACTU no es válido.'
            );
        }

        if (
            !in_array(
                $status,
                [
                    'Correcto',
                    'AceptadoConErrores',
                    'Incorrecto',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'EstadoRegistro AEAT no válido: %s',
                    $status
                )
            );
        }

        $updated =
            $wpdb->update(
                $this->table,
                [
                    'aeat_record_status' =>
                        $status,

                    'aeat_error_code' =>
                        self::nullable(
                            $errorCode
                        ),

                    'aeat_error_message' =>
                        self::nullable(
                            $errorMessage
                        ),

                    'accepted_at' =>
                        $acceptedAt,

                    'updated_at' =>
                        gmdate(
                            'Y-m-d H:i:s'
                        ),
                ],
                [
                    'id' =>
                        $recordId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo actualizar el resultado AEAT del registro VERI*FACTU: '
                . $wpdb->last_error
            );
        }

        if ($updated !== 1) {
            throw new RuntimeException(
                sprintf(
                    'No se actualizó el resultado AEAT del registro VERI*FACTU %d.',
                    $recordId
                )
            );
        }
    }

    /**
     * @param array{
     *     version: string,
     *     xml: string
     * } $existing
     */
    private function assertSamePayload(
        int $recordId,
        string $version,
        string $xml,
        array $existing
    ): void {
        if (
            $existing['version']
            !== $version
        ) {
            throw new RuntimeException(
                sprintf(
                    'El registro VERI*FACTU %d ya contiene un payload de versión distinta.',
                    $recordId
                )
            );
        }

        $existingHash =
            hash(
                'sha256',
                $existing['xml']
            );

        $newHash =
            hash(
                'sha256',
                $xml
            );

        if (
            !hash_equals(
                $existingHash,
                $newHash
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'El registro VERI*FACTU %d ya contiene un payload XML diferente. No puede sobrescribirse.',
                    $recordId
                )
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(
        array $data
    ): int {
        global $wpdb;

        $inserted =
            $wpdb->insert(
                $this->table,
                $data
            );

        if ($inserted === false) {
            throw new RuntimeException(
                'No se pudo guardar el registro VERI*FACTU: '
                . $wpdb->last_error
            );
        }

        $recordId =
            (int) $wpdb->insert_id;

        if ($recordId <= 0) {
            throw new RuntimeException(
                'No se obtuvo el identificador del registro VERI*FACTU.'
            );
        }

        return $recordId;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row
    ): VerifactuRecord {
        return new VerifactuRecord(
            id:
                (int) $row['id'],

            recordUuid:
                (string) $row['record_uuid'],

            chainId:
                (int) $row['chain_id'],

            chainSequence:
                (int) $row['chain_sequence'],

            invoiceId:
                (int) $row['invoice_id'],

            recordType:
                (string) $row['record_type'],

            generationType:
                (string) $row['generation_type'],

            generationSequence:
                isset(
                    $row[
                        'generation_sequence'
                    ]
                )
                    ? (int) $row[
                        'generation_sequence'
                    ]
                    : 1,

            sourceRecordId:
                isset(
                    $row[
                        'source_record_id'
                    ]
                )
                && $row[
                    'source_record_id'
                ] !== null
                    ? (int) $row[
                        'source_record_id'
                    ]
                    : null,

            subsanacion:
                self::nullable(
                    $row[
                        'subsanacion'
                    ]
                    ?? null
                ),

            rechazoPrevio:
                self::nullable(
                    $row[
                        'rechazo_previo'
                    ]
                    ?? null
                ),

            sinRegistroPrevio:
                self::nullable(
                    $row[
                        'sin_registro_previo'
                    ]
                    ?? null
                ),

            fiscalStatus:
                (string) $row['fiscal_status'],

            environment:
                (string) $row['environment'],

            issuerFiscalName:
                (string) $row['issuer_fiscal_name'],

            issuerTaxId:
                (string) $row['issuer_tax_id'],

            invoiceNumber:
                (string) $row['invoice_number'],

            invoiceDate:
                (string) $row['invoice_date'],

            invoiceType:
                self::nullable(
                    $row['invoice_type']
                    ?? null
                ),

            description:
                self::nullable(
                    $row['description']
                    ?? null
                ),

            currency:
                (string) $row['currency'],

            taxType:
                self::nullable(
                    $row['tax_type']
                    ?? null
                ),

            taxRate:
                isset(
                    $row['tax_rate']
                )
                && $row['tax_rate'] !== null
                    ? (float) $row[
                        'tax_rate'
                    ]
                    : null,

            taxBase:
                (float) $row[
                    'tax_base'
                ],

            taxAmount:
                (float) $row[
                    'tax_amount'
                ],

            totalAmount:
                (float) $row[
                    'total_amount'
                ],

            previousRecordId:
                isset(
                    $row[
                        'previous_record_id'
                    ]
                )
                && $row[
                    'previous_record_id'
                ] !== null
                    ? (int) $row[
                        'previous_record_id'
                    ]
                    : null,

            previousHash:
                self::nullable(
                    $row[
                        'previous_hash'
                    ]
                    ?? null
                ),

            hashAlgorithm:
                (string) $row[
                    'hash_algorithm'
                ],

            hashInput:
                self::nullable(
                    $row[
                        'hash_input'
                    ]
                    ?? null
                ),

            hashValue:
                self::nullable(
                    $row[
                        'hash_value'
                    ]
                    ?? null
                ),

            generatedAt:
                (string) $row[
                    'generated_at'
                ],

            generatedAtIso:
                (string) $row[
                    'generated_at_iso'
                ],

            aeatRecordStatus:
                self::nullable(
                    $row[
                        'aeat_record_status'
                    ]
                    ?? null
                ),

            createdAt:
                (string) $row[
                    'created_at'
                ],

            updatedAt:
                (string) $row[
                    'updated_at'
                ]
        );
    }

    private static function nullable(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        return $value !== ''
            ? $value
            : null;
    }
}
