<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuSubsanationGenerator
{
    private VerifactuChainRepository $chainRepository;

    private VerifactuRecordRepository $recordRepository;

    private VerifactuHashGenerator $hashGenerator;

    public function __construct(
        ?VerifactuChainRepository $chainRepository = null,
        ?VerifactuRecordRepository $recordRepository = null,
        ?VerifactuHashGenerator $hashGenerator = null
    ) {
        $this->chainRepository =
            $chainRepository
            ?? new VerifactuChainRepository();

        $this->recordRepository =
            $recordRepository
            ?? new VerifactuRecordRepository();

        $this->hashGenerator =
            $hashGenerator
            ?? new VerifactuHashGenerator();
    }

    /**
     * Genera un nuevo RegistroAlta de subsanación.
     *
     * El registro de origen permanece completamente
     * inmutable.
     *
     * $rechazoPrevio:
     *
     * - X: alta anterior rechazada.
     * - N/S: otros supuestos admitidos por AEAT.
     *
     * La decisión fiscal del valor se realiza fuera
     * del generador. Esta clase valida únicamente
     * compatibilidades inequívocas.
     */
    public function generateForRecord(
        int $sourceRecordId,
        VerifactuSubsanationData $correction,
        string $rechazoPrevio
    ): VerifactuRecord {
        global $wpdb;

        if ($sourceRecordId <= 0) {
            throw new RuntimeException(
                'El registro VERI*FACTU de origen no es válido.'
            );
        }

        $source =
            $this->recordRepository
                ->findById(
                    $sourceRecordId
                );

        if ($source === null) {
            throw new RuntimeException(
                sprintf(
                    'No existe el registro VERI*FACTU %d.',
                    $sourceRecordId
                )
            );
        }

        $rechazoPrevio =
            strtoupper(
                trim(
                    $rechazoPrevio
                )
            );

        $this->validateSourceRecord(
            $source,
            $rechazoPrevio
        );

        $settings =
            VerifactuSettings::get();

        $environment =
            trim(
                (string) (
                    $settings['environment']
                    ?? ''
                )
            );

        $systemName =
            trim(
                (string) (
                    $settings['system_name']
                    ?? ''
                )
            );

        $systemId =
            trim(
                (string) (
                    $settings['system_id']
                    ?? ''
                )
            );

        $systemVersion =
            trim(
                (string) (
                    $settings['system_version']
                    ?? ''
                )
            );

        $installationId =
            trim(
                (string) (
                    $settings['installation_id']
                    ?? ''
                )
            );

        if (
            $environment === ''
            || $systemName === ''
            || $systemId === ''
            || $systemVersion === ''
            || $installationId === ''
        ) {
            throw new RuntimeException(
                'La identificación del sistema VERI*FACTU está incompleta.'
            );
        }

        if (
            $environment
            !== $source->getEnvironment()
        ) {
            throw new RuntimeException(
                sprintf(
                    'El registro pertenece al entorno "%s" y la configuración actual utiliza "%s".',
                    $source->getEnvironment(),
                    $environment
                )
            );
        }

        $customerSnapshot =
            $this->readCustomerSnapshot(
                $sourceRecordId
            );

        $invoiceSnapshot =
            $this->readInvoiceSnapshot(
                $sourceRecordId
            );

        /*
         * Partimos siempre del snapshot fiscal del
         * registro que se está corrigiendo.
         */
        $correctedCustomerSnapshot =
            $this->applyCustomerCorrection(
                $customerSnapshot,
                $correction
            );

        $description =
            $correction->getDescription()
            ?? $source->getDescription();

        $taxRate =
            $correction->getTaxRate()
            ?? $source->getTaxRate();

        $taxBase =
            $correction->getTaxBase()
            ?? $source->getTaxBase();

        $taxAmount =
            $correction->getTaxAmount()
            ?? $source->getTaxAmount();

        $totalAmount =
            $correction->getTotalAmount()
            ?? $source->getTotalAmount();

        if ($taxRate === null) {
            throw new RuntimeException(
                'El registro de origen no contiene tipo impositivo.'
            );
        }

        if (
            $taxRate < 0
            || $taxBase < 0
            || $taxAmount < 0
            || $totalAmount <= 0
        ) {
            throw new RuntimeException(
                'Los importes fiscales corregidos no son válidos.'
            );
        }

        $this->assertTotalsConsistency(
            $taxBase,
            $taxAmount,
            $totalAmount
        );

        $generatedDateTime =
            new DateTimeImmutable(
                'now',
                wp_timezone()
            );

        $generatedAt =
            $generatedDateTime
                ->setTimezone(
                    new DateTimeZone(
                        'UTC'
                    )
                )
                ->format(
                    'Y-m-d H:i:s'
                );

        $generatedAtIso =
            $generatedDateTime
                ->format(
                    'Y-m-d\TH:i:sP'
                );

        $transactionStarted =
            false;

        try {
            if (
                $wpdb->query(
                    'START TRANSACTION'
                ) === false
            ) {
                throw new RuntimeException(
                    'No se pudo iniciar la transacción de subsanación VERI*FACTU.'
                );
            }

            $transactionStarted =
                true;

            /*
             * Bloqueamos todas las generaciones de
             * subsanación de esta factura.
             *
             * Así generation_sequence se asigna
             * secuencialmente incluso con concurrencia.
             */
            $lastGeneration =
                $wpdb->get_row(
                    $wpdb->prepare(
                        "
                        SELECT
                            id,
                            generation_sequence
                        FROM {$wpdb->prefix}dsm_verifactu_records
                        WHERE invoice_id = %d
                          AND record_type = 'alta'
                          AND generation_type = 'subsanacion'
                        ORDER BY
                            generation_sequence DESC,
                            id DESC
                        LIMIT 1
                        FOR UPDATE
                        ",
                        $source->getInvoiceId()
                    ),
                    ARRAY_A
                );

            $generationSequence =
                is_array(
                    $lastGeneration
                )
                    ? (
                        (int) $lastGeneration[
                            'generation_sequence'
                        ]
                        + 1
                    )
                    : 1;

            /*
             * Bloqueamos la cadena fiscal.
             */
            $chain =
                $this->chainRepository
                    ->lockOrCreate(
                        $environment,
                        $source->getIssuerTaxId(),
                        $systemId,
                        $installationId
                    );

            $chainId =
                (int) $chain['id'];

            $chainSequence =
                ((int) $chain['last_sequence'])
                + 1;

            $previousRecordId =
                isset(
                    $chain['last_record_id']
                )
                && $chain['last_record_id'] !== null
                    ? (int) $chain['last_record_id']
                    : null;

            $previousHash =
                isset(
                    $chain['last_hash']
                )
                    ? trim(
                        (string) $chain['last_hash']
                    )
                    : '';

            if (
                $previousRecordId === null
                || $previousHash === ''
            ) {
                throw new RuntimeException(
                    'No existe un registro anterior en la cadena VERI*FACTU para generar la subsanación.'
                );
            }

            $this->assertPreviousRecordIntegrity(
                $previousRecordId,
                $previousHash
            );

            $previous =
                $this->recordRepository
                    ->findById(
                        $previousRecordId
                    );

            if ($previous === null) {
                throw new RuntimeException(
                    'No se pudo recuperar el último registro de la cadena VERI*FACTU.'
                );
            }

            /*
             * Una subsanación sigue siendo RegistroAlta.
             * Por ello utiliza exactamente la fórmula
             * oficial de huella de RegistroAlta.
             */
            $hash =
                $this->hashGenerator
                    ->generateRegistrationHash(
                        $source->getIssuerTaxId(),
                        $source->getInvoiceNumber(),
                        $this->formatFiscalDate(
                            $source->getInvoiceDate()
                        ),
                        (string) $source->getInvoiceType(),
                        $taxAmount,
                        $totalAmount,
                        $previousHash,
                        $generatedAtIso
                    );

            $now =
                current_time(
                    'mysql',
                    true
                );

            $systemSnapshot = [
                'system_name' =>
                    $systemName,

                'system_id' =>
                    $systemId,

                'system_version' =>
                    $systemVersion,

                'installation_id' =>
                    $installationId,

                'environment' =>
                    $environment,
            ];

            /*
             * Conservamos información de linaje dentro
             * del snapshot además de source_record_id.
             */
            $correctedInvoiceSnapshot =
                $invoiceSnapshot;

            $correctedInvoiceSnapshot[
                'verifactu_subsanation'
            ] = [
                'source_record_id' =>
                    $source->getId(),

                'source_record_uuid' =>
                    $source->getRecordUuid(),

                'source_generation_type' =>
                    $source->getGenerationType(),

                'source_generation_sequence' =>
                    $source->getGenerationSequence(),

                'source_aeat_status' =>
                    $source->getAeatRecordStatus(),

                'subsanacion' =>
                    'S',

                'rechazo_previo' =>
                    $rechazoPrevio,
            ];

            $recordId =
                $this->recordRepository
                    ->insert(
                        [
                            'record_uuid' =>
                                wp_generate_uuid4(),

                            'chain_id' =>
                                $chainId,

                            'chain_sequence' =>
                                $chainSequence,

                            'invoice_id' =>
                                $source->getInvoiceId(),

                            'record_type' =>
                                'alta',

                            'generation_type' =>
                                'subsanacion',

                            'generation_sequence' =>
                                $generationSequence,

                            'source_record_id' =>
                                $source->getId(),

                            'subsanacion' =>
                                'S',

                            'rechazo_previo' =>
                                $rechazoPrevio,

                            'sin_registro_previo' =>
                                null,

                            'fiscal_status' =>
                                'generated',

                            'environment' =>
                                $environment,

                            /*
                             * La identificación fiscal de
                             * la factura permanece.
                             */
                            'issuer_fiscal_name' =>
                                $source->getIssuerFiscalName(),

                            'issuer_tax_id' =>
                                $source->getIssuerTaxId(),

                            'invoice_number' =>
                                $source->getInvoiceNumber(),

                            'invoice_date' =>
                                $source->getInvoiceDate(),

                            'invoice_type' =>
                                $source->getInvoiceType(),

                            'description' =>
                                $description,

                            'currency' =>
                                $source->getCurrency(),

                            'tax_type' =>
                                $source->getTaxType(),

                            'tax_rate' =>
                                $taxRate,

                            'tax_base' =>
                                number_format(
                                    $taxBase,
                                    2,
                                    '.',
                                    ''
                                ),

                            'tax_amount' =>
                                number_format(
                                    $taxAmount,
                                    2,
                                    '.',
                                    ''
                                ),

                            'total_amount' =>
                                number_format(
                                    $totalAmount,
                                    2,
                                    '.',
                                    ''
                                ),

                            'tax_breakdown_snapshot' =>
                                wp_json_encode(
                                    [
                                        'tax_type' =>
                                            $source->getTaxType(),

                                        'tax_rate' =>
                                            $taxRate,

                                        'tax_base' =>
                                            $taxBase,

                                        'tax_amount' =>
                                            $taxAmount,

                                        'total_amount' =>
                                            $totalAmount,
                                    ],
                                    JSON_UNESCAPED_UNICODE
                                    | JSON_UNESCAPED_SLASHES
                                ),

                            'customer_snapshot' =>
                                wp_json_encode(
                                    $correctedCustomerSnapshot,
                                    JSON_UNESCAPED_UNICODE
                                    | JSON_UNESCAPED_SLASHES
                                ),

                            'invoice_snapshot' =>
                                wp_json_encode(
                                    $correctedInvoiceSnapshot,
                                    JSON_UNESCAPED_UNICODE
                                    | JSON_UNESCAPED_SLASHES
                                ),

                            'system_name' =>
                                $systemName,

                            'system_id' =>
                                $systemId,

                            'system_version' =>
                                $systemVersion,

                            'installation_id' =>
                                $installationId,

                            'system_snapshot' =>
                                wp_json_encode(
                                    $systemSnapshot,
                                    JSON_UNESCAPED_UNICODE
                                    | JSON_UNESCAPED_SLASHES
                                ),

                            /*
                             * Encadenamiento cronológico.
                             *
                             * No confundir con el registro
                             * que estamos subsanando.
                             */
                            'previous_record_id' =>
                                $previousRecordId,

                            'previous_issuer_tax_id' =>
                                $previous->getIssuerTaxId(),

                            'previous_invoice_number' =>
                                $previous->getInvoiceNumber(),

                            'previous_invoice_date' =>
                                $previous->getInvoiceDate(),

                            'previous_hash' =>
                                $previousHash,

                            'hash_algorithm' =>
                                VerifactuHashGenerator::HASH_TYPE,

                            'hash_input' =>
                                $hash['input'],

                            'hash_value' =>
                                $hash['hash'],

                            'generated_at' =>
                                $generatedAt,

                            'generated_at_iso' =>
                                $generatedAtIso,

                            'payload_version' =>
                                null,

                            'payload_xml' =>
                                null,

                            'aeat_record_status' =>
                                null,

                            'aeat_error_code' =>
                                null,

                            'aeat_error_message' =>
                                null,

                            'accepted_at' =>
                                null,

                            'created_at' =>
                                $now,

                            'updated_at' =>
                                $now,
                        ]
                    );

            $this->chainRepository
                ->advance(
                    $chainId,
                    $chainSequence,
                    $recordId,
                    $hash['hash'],
                    $generatedAt,
                    $generatedAtIso
                );

            if (
                $wpdb->query(
                    'COMMIT'
                ) === false
            ) {
                throw new RuntimeException(
                    'No se pudo confirmar la subsanación VERI*FACTU.'
                );
            }

            $transactionStarted =
                false;

            $record =
                $this->recordRepository
                    ->findById(
                        $recordId
                    );

            if ($record === null) {
                throw new RuntimeException(
                    'La subsanación se creó pero no pudo recuperarse.'
                );
            }

            return $record;
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                $wpdb->query(
                    'ROLLBACK'
                );
            }

            throw $exception;
        }
    }

    private function validateSourceRecord(
        VerifactuRecord $source,
        string $rechazoPrevio
    ): void {
        if (
            $source->getRecordType()
            !== 'alta'
        ) {
            throw new RuntimeException(
                'Una subsanación de alta debe partir de un RegistroAlta.'
            );
        }

        if (
            !in_array(
                $rechazoPrevio,
                [
                    'N',
                    'S',
                    'X',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'RechazoPrevio no válido para RegistroAlta: %s',
                    $rechazoPrevio
                )
            );
        }

        $aeatStatus =
            $source->getAeatRecordStatus();

        if (
            !in_array(
                $aeatStatus,
                [
                    'Correcto',
                    'AceptadoConErrores',
                    'Incorrecto',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El RegistroAlta de origen todavía no tiene un resultado fiscal definitivo de AEAT.'
            );
        }

        /*
         * Caso inequívoco:
         *
         * X significa que estamos enviando nuevamente
         * un alta previamente rechazada.
         */
        if (
            $aeatStatus === 'Incorrecto'
            && $rechazoPrevio !== 'X'
        ) {
            throw new RuntimeException(
                'Un RegistroAlta rechazado por AEAT debe subsanarse indicando RechazoPrevio=X.'
            );
        }

        if (
            $aeatStatus !== 'Incorrecto'
            && $rechazoPrevio === 'X'
        ) {
            throw new RuntimeException(
                'RechazoPrevio=X solamente puede utilizarse cuando el RegistroAlta anterior fue rechazado.'
            );
        }

        if (
            trim(
                $source->getIssuerTaxId()
            ) === ''
            || trim(
                $source->getInvoiceNumber()
            ) === ''
            || trim(
                $source->getInvoiceDate()
            ) === ''
            || trim(
                (string) $source->getInvoiceType()
            ) === ''
        ) {
            throw new RuntimeException(
                'La identificación fiscal del RegistroAlta de origen está incompleta.'
            );
        }

        $hash =
            $source->getHashValue();

        if (
            $hash === null
            || !preg_match(
                '/^[A-F0-9]{64}$/',
                $hash
            )
        ) {
            throw new RuntimeException(
                'El RegistroAlta de origen no contiene una huella SHA-256 válida.'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readCustomerSnapshot(
        int $recordId
    ): array {
        return $this->readJsonSnapshot(
            $recordId,
            'customer_snapshot',
            'cliente'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readInvoiceSnapshot(
        int $recordId
    ): array {
        return $this->readJsonSnapshot(
            $recordId,
            'invoice_snapshot',
            'factura'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonSnapshot(
        int $recordId,
        string $column,
        string $label
    ): array {
        global $wpdb;

        if (
            !in_array(
                $column,
                [
                    'customer_snapshot',
                    'invoice_snapshot',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Snapshot VERI*FACTU no permitido.'
            );
        }

        $value =
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT {$column}
                    FROM {$wpdb->prefix}dsm_verifactu_records
                    WHERE id = %d
                    LIMIT 1
                    ",
                    $recordId
                )
            );

        if (
            !is_string(
                $value
            )
            || trim(
                $value
            ) === ''
        ) {
            throw new RuntimeException(
                sprintf(
                    'El RegistroAlta de origen no conserva el snapshot de %s.',
                    $label
                )
            );
        }

        $decoded =
            json_decode(
                $value,
                true
            );

        if (!is_array($decoded)) {
            throw new RuntimeException(
                sprintf(
                    'El snapshot de %s del RegistroAlta de origen no contiene JSON válido.',
                    $label
                )
            );
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $snapshot
     *
     * @return array<string, mixed>
     */
    private function applyCustomerCorrection(
        array $snapshot,
        VerifactuSubsanationData $correction
    ): array {
        $fiscalName =
            $correction->getCustomerFiscalName();

        if ($fiscalName !== null) {
            $snapshot['fiscal_name'] =
                $fiscalName;
        }

        $taxId =
            $correction->getCustomerTaxId();

        if ($taxId !== null) {
            $snapshot['tax_id'] =
                $taxId;
        }

        if (
            trim(
                (string) (
                    $snapshot['fiscal_name']
                    ?? ''
                )
            ) === ''
            || trim(
                (string) (
                    $snapshot['tax_id']
                    ?? ''
                )
            ) === ''
        ) {
            throw new RuntimeException(
                'La subsanación F1 requiere destinatario fiscal identificado.'
            );
        }

        return $snapshot;
    }

    private function assertTotalsConsistency(
        float $taxBase,
        float $taxAmount,
        float $totalAmount
    ): void {
        $expected =
            round(
                $taxBase
                + $taxAmount,
                2
            );

        if (
            abs(
                $expected
                - round(
                    $totalAmount,
                    2
                )
            ) > 0.01
        ) {
            throw new RuntimeException(
                sprintf(
                    'Los importes corregidos no cuadran: base %.2f + cuota %.2f != total %.2f.',
                    $taxBase,
                    $taxAmount,
                    $totalAmount
                )
            );
        }
    }

    private function assertPreviousRecordIntegrity(
        int $previousRecordId,
        string $expectedHash
    ): void {
        $previous =
            $this->recordRepository
                ->findById(
                    $previousRecordId
                );

        if ($previous === null) {
            throw new RuntimeException(
                'La cadena VERI*FACTU apunta a un registro inexistente.'
            );
        }

        $storedHash =
            $previous->getHashValue();

        if (
            $storedHash === null
            || !hash_equals(
                $storedHash,
                $expectedHash
            )
        ) {
            throw new RuntimeException(
                'La huella del último registro VERI*FACTU no coincide con la cadena.'
            );
        }

        $hashInput =
            $previous->getHashInput();

        if ($hashInput === null) {
            throw new RuntimeException(
                'El último registro no conserva la entrada utilizada para generar su huella.'
            );
        }

        $recalculated =
            strtoupper(
                hash(
                    'sha256',
                    $hashInput
                )
            );

        if (
            !hash_equals(
                $storedHash,
                $recalculated
            )
        ) {
            throw new RuntimeException(
                'La huella del último registro VERI*FACTU no supera la comprobación de integridad.'
            );
        }
    }

    private function formatFiscalDate(
        string $invoiceDate
    ): string {
        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                trim(
                    $invoiceDate
                )
            );

        if ($date === false) {
            throw new RuntimeException(
                sprintf(
                    'Fecha de factura VERI*FACTU no válida: %s',
                    $invoiceDate
                )
            );
        }

        return $date->format(
            'd-m-Y'
        );
    }
}
