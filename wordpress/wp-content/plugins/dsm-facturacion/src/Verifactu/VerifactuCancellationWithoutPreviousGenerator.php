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

final class VerifactuCancellationWithoutPreviousGenerator
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
     * Genera un RegistroAnulacion con
     * SinRegistroPrevio=S.
     *
     * Este flujo se utiliza cuando el RegistroAlta
     * que queremos anular NO consta registrado en AEAT.
     *
     * En DSM, inicialmente solo permitimos el caso
     * seguro y comprobable:
     *
     * RegistroAlta -> AEAT = Incorrecto.
     */
    public function generateForRecord(
        int $sourceRecordId
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
         * Esta operación solo puede existir una vez
         * inicialmente para la factura.
         */
        $existing =
            $this->recordRepository
                ->findLatestGeneration(
                    $source->getInvoiceId(),
                    'anulacion',
                    'sin_registro_previo'
                );

        if ($existing !== null) {
            return $existing;
        }

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
                    'No se pudo iniciar la transacción de anulación sin registro previo.'
                );
            }

            $transactionStarted =
                true;

            /*
             * Idempotencia dentro de la transacción.
             */
            $existingId =
                $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT id
                        FROM {$wpdb->prefix}dsm_verifactu_records
                        WHERE invoice_id = %d
                          AND record_type = 'anulacion'
                          AND generation_type = 'sin_registro_previo'
                          AND generation_sequence = 1
                        ORDER BY id ASC
                        LIMIT 1
                        FOR UPDATE
                        ",
                        $source->getInvoiceId()
                    )
                );

            if ($existingId !== null) {
                if (
                    $wpdb->query(
                        'COMMIT'
                    ) === false
                ) {
                    throw new RuntimeException(
                        'No se pudo cerrar la comprobación de idempotencia.'
                    );
                }

                $transactionStarted =
                    false;

                $record =
                    $this->recordRepository
                        ->findById(
                            (int) $existingId
                        );

                if ($record === null) {
                    throw new RuntimeException(
                        'No se pudo recuperar la anulación sin registro previo existente.'
                    );
                }

                return $record;
            }

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

            /*
             * El Alta rechazado existe localmente en
             * nuestra cadena SIF, por tanto la anulación
             * sigue encadenándose normalmente.
             */
            if (
                $previousRecordId === null
                || $previousHash === ''
            ) {
                throw new RuntimeException(
                    'No existe un registro anterior en la cadena VERI*FACTU.'
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

            $hash =
                $this->hashGenerator
                    ->generateCancellationHash(
                        $source->getIssuerTaxId(),
                        $source->getInvoiceNumber(),
                        $this->formatFiscalDate(
                            $source->getInvoiceDate()
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

                'invoice_id' =>
                    $source->getInvoiceId(),

                'issuer_tax_id' =>
                    $source->getIssuerTaxId(),

                'invoice_number' =>
                    $source->getInvoiceNumber(),

                'invoice_date' =>
                    $source->getInvoiceDate(),

                'source_hash' =>
                    $source->getHashValue(),

                'sin_registro_previo' =>
                    'S',
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
                                'sin_registro_previo',

                            'generation_sequence' =>
                                1,

                            /*
                             * RegistroAlta rechazado que
                             * queremos dejar anulado.
                             */
                            'source_record_id' =>
                                $source->getId(),

                            'subsanacion' =>
                                null,

                            /*
                             * AEAT permite omitirlo o N.
                             * DSM lo omite.
                             */
                            'rechazo_previo' =>
                                null,

                            'sin_registro_previo' =>
                                'S',

                            'fiscal_status' =>
                                'generated',

                            'environment' =>
                                $environment,

                            'issuer_fiscal_name' =>
                                $source->getIssuerFiscalName(),

                            'issuer_tax_id' =>
                                $source->getIssuerTaxId(),

                            'invoice_number' =>
                                $source->getInvoiceNumber(),

                            'invoice_date' =>
                                $source->getInvoiceDate(),

                            'invoice_type' =>
                                null,

                            'description' =>
                                'Anulación sin registro previo de '
                                . $source->getInvoiceNumber(),

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
                             * Puede ser el Alta rechazado
                             * u otro registro posterior.
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
                    'No se pudo confirmar la anulación sin registro previo VERI*FACTU.'
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
                    'La anulación sin registro previo se creó pero no pudo recuperarse.'
                );
            }

            return $record;
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                $wpdb->query(
                    'ROLLBACK'
                );
            }

            /*
             * Recuperación ante carrera concurrente.
             */
            $existing =
                $this->recordRepository
                    ->findLatestGeneration(
                        $source->getInvoiceId(),
                        'anulacion',
                        'sin_registro_previo'
                    );

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    private function validateSourceRecord(
        VerifactuRecord $source
    ): void {
        if (
            $source->getRecordType()
            !== 'alta'
        ) {
            throw new RuntimeException(
                'Una anulación sin registro previo debe partir de un RegistroAlta.'
            );
        }

        /*
         * En este flujo DSM exige certeza de que el
         * Alta NO fue registrado por AEAT.
         *
         * Incorrecto = rechazado.
         */
        if (
            $source->getAeatRecordStatus()
            !== 'Incorrecto'
        ) {
            throw new RuntimeException(
                'SinRegistroPrevio=S solo puede utilizarse aquí cuando el RegistroAlta fue rechazado por AEAT.'
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
