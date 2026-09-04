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

final class VerifactuCancellationSubsanationGenerator
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
     * Genera una nueva anulación de subsanación
     * partiendo de un RegistroAnulacion rechazado.
     *
     * El registro de origen permanece inmutable.
     */
    public function generateForRecord(
        int $sourceRecordId,
        VerifactuCancellationSubsanationData $correction
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

        $this->validateSourceRecord(
            $source
        );

        /*
         * DSM tiene un único obligado tributario
         * emisor.
         *
         * No permitimos cambiar aquí el NIF porque
         * implicaría cambiar también la identidad
         * de la cadena fiscal.
         */
        $correctedIssuerTaxId =
            $correction->getIssuerTaxId();

        if (
            $correctedIssuerTaxId !== null
            && $correctedIssuerTaxId
                !== $source->getIssuerTaxId()
        ) {
            throw new RuntimeException(
                'DSM no permite cambiar el NIF del emisor mediante una subsanación de anulación.'
            );
        }

        $issuerTaxId =
            $source->getIssuerTaxId();

        $invoiceNumber =
            $correction->getInvoiceNumber()
            ?? $source->getInvoiceNumber();

        $invoiceDate =
            $correction->getInvoiceDate()
            ?? $source->getInvoiceDate();

        if (
            trim(
                $invoiceNumber
            ) === ''
        ) {
            throw new RuntimeException(
                'El número de factura corregido está vacío.'
            );
        }

        if (
            trim(
                $invoiceDate
            ) === ''
        ) {
            throw new RuntimeException(
                'La fecha de factura corregida está vacía.'
            );
        }

        /*
         * Fuerza validación estricta YYYY-MM-DD.
         */
        $this->formatFiscalDate(
            $invoiceDate
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
                    'No se pudo iniciar la transacción de subsanación de anulación VERI*FACTU.'
                );
            }

            $transactionStarted =
                true;

            /*
             * Obtenemos y bloqueamos la última
             * generación de subsanación existente
             * para esta factura.
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
                          AND record_type = 'anulacion'
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
             * Bloqueamos la cadena fiscal global
             * correspondiente a DSM.
             */
            $chain =
                $this->chainRepository
                    ->lockOrCreate(
                        $environment,
                        $issuerTaxId,
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
                    ? (int) $chain[
                        'last_record_id'
                    ]
                    : null;

            $previousHash =
                isset(
                    $chain['last_hash']
                )
                    ? trim(
                        (string) $chain[
                            'last_hash'
                        ]
                    )
                    : '';

            if (
                $previousRecordId === null
                || $previousHash === ''
            ) {
                throw new RuntimeException(
                    'No existe un registro anterior en la cadena VERI*FACTU para generar la subsanación de anulación.'
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
             * Sigue siendo un RegistroAnulacion,
             * por lo que utiliza la fórmula de huella
             * de anulación.
             */
            $hash =
                $this->hashGenerator
                    ->generateCancellationHash(
                        $issuerTaxId,
                        $invoiceNumber,
                        $this->formatFiscalDate(
                            $invoiceDate
                        ),
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
             * Snapshot específico de esta corrección.
             *
             * Permite reconstruir después por qué
             * existe este registro sin modificar el
             * registro rechazado.
             */
            $sourceSnapshot = [
                'source_record_id' =>
                    $source->getId(),

                'source_record_uuid' =>
                    $source->getRecordUuid(),

                'source_record_type' =>
                    $source->getRecordType(),

                'source_generation_type' =>
                    $source->getGenerationType(),

                'source_generation_sequence' =>
                    $source->getGenerationSequence(),

                'source_aeat_status' =>
                    $source->getAeatRecordStatus(),

                'source_issuer_tax_id' =>
                    $source->getIssuerTaxId(),

                'source_invoice_number' =>
                    $source->getInvoiceNumber(),

                'source_invoice_date' =>
                    $source->getInvoiceDate(),

                'source_hash' =>
                    $source->getHashValue(),

                'corrected_issuer_tax_id' =>
                    $issuerTaxId,

                'corrected_invoice_number' =>
                    $invoiceNumber,

                'corrected_invoice_date' =>
                    $invoiceDate,

                'rechazo_previo' =>
                    'S',

                'sin_registro_previo' =>
                    null,
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
                                'anulacion',

                            'generation_type' =>
                                'subsanacion',

                            'generation_sequence' =>
                                $generationSequence,

                            /*
                             * Linaje fiscal:
                             * registro rechazado que
                             * estamos corrigiendo.
                             */
                            'source_record_id' =>
                                $source->getId(),

                            /*
                             * RegistroAnulacion no usa
                             * el campo Subsanacion.
                             */
                            'subsanacion' =>
                                null,

                            /*
                             * La anulación anterior fue
                             * rechazada por AEAT.
                             */
                            'rechazo_previo' =>
                                'S',

                            /*
                             * No estamos anulando una
                             * factura sin registro previo:
                             * estamos corrigiendo una
                             * anulación rechazada.
                             */
                            'sin_registro_previo' =>
                                null,

                            'fiscal_status' =>
                                'generated',

                            'environment' =>
                                $environment,

                            'issuer_fiscal_name' =>
                                $source->getIssuerFiscalName(),

                            'issuer_tax_id' =>
                                $issuerTaxId,

                            'invoice_number' =>
                                $invoiceNumber,

                            'invoice_date' =>
                                $invoiceDate,

                            /*
                             * RegistroAnulacion no utiliza
                             * TipoFactura ni desglose en XML.
                             */
                            'invoice_type' =>
                                null,

                            'description' =>
                                'Subsanación de anulación de '
                                . $invoiceNumber,

                            /*
                             * Conservamos estos datos como
                             * snapshot auxiliar interno.
                             */
                            'currency' =>
                                $source->getCurrency(),

                            'tax_type' =>
                                $source->getTaxType(),

                            'tax_rate' =>
                                $source->getTaxRate(),

                            'tax_base' =>
                                number_format(
                                    $source->getTaxBase(),
                                    2,
                                    '.',
                                    ''
                                ),

                            'tax_amount' =>
                                number_format(
                                    $source->getTaxAmount(),
                                    2,
                                    '.',
                                    ''
                                ),

                            'total_amount' =>
                                number_format(
                                    $source->getTotalAmount(),
                                    2,
                                    '.',
                                    ''
                                ),

                            'tax_breakdown_snapshot' =>
                                null,

                            'customer_snapshot' =>
                                null,

                            'invoice_snapshot' =>
                                wp_json_encode(
                                    $sourceSnapshot,
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
                             * No tiene por qué coincidir
                             * con source_record_id.
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
                    'No se pudo confirmar la subsanación de anulación VERI*FACTU.'
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
                    'La subsanación de anulación se creó pero no pudo recuperarse.'
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
        VerifactuRecord $source
    ): void {
        if (
            $source->getRecordType()
            !== 'anulacion'
        ) {
            throw new RuntimeException(
                'Una subsanación de anulación debe partir de un RegistroAnulacion.'
            );
        }

        /*
         * Este flujo concreto sirve para corregir
         * una anulación rechazada.
         */
        if (
            $source->getAeatRecordStatus()
            !== 'Incorrecto'
        ) {
            throw new RuntimeException(
                'Solo puede subsanarse por este flujo un RegistroAnulacion rechazado por AEAT.'
            );
        }

        if (
            trim(
                $source->getIssuerTaxId()
            ) === ''
        ) {
            throw new RuntimeException(
                'El RegistroAnulacion de origen no contiene NIF del emisor.'
            );
        }

        if (
            trim(
                $source->getInvoiceNumber()
            ) === ''
        ) {
            throw new RuntimeException(
                'El RegistroAnulacion de origen no contiene número de factura.'
            );
        }

        if (
            trim(
                $source->getInvoiceDate()
            ) === ''
        ) {
            throw new RuntimeException(
                'El RegistroAnulacion de origen no contiene fecha de factura.'
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
                'El RegistroAnulacion de origen no contiene una huella SHA-256 válida.'
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

    /**
     * Convierte YYYY-MM-DD a DD-MM-YYYY
     * para la huella oficial de anulación.
     */
    private function formatFiscalDate(
        string $invoiceDate
    ): string {
        $invoiceDate =
            trim(
                $invoiceDate
            );

        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $invoiceDate
            );

        if (
            $date === false
            || $date->format(
                'Y-m-d'
            ) !== $invoiceDate
        ) {
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
