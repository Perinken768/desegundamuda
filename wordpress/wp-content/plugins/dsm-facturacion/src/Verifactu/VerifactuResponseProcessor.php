<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use DateTimeImmutable;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuResponseProcessor
{
    private VerifactuSubmissionRepository $submissionRepository;

    private VerifactuRecordRepository $recordRepository;

    private VerifactuResponseParser $parser;

    public function __construct()
    {
        $this->submissionRepository =
            new VerifactuSubmissionRepository();

        $this->recordRepository =
            new VerifactuRecordRepository();

        $this->parser =
            new VerifactuResponseParser();
    }

    /**
     * Analiza y correlaciona una respuesta AEAT.
     *
     * NO modifica ninguna tabla.
     *
     * @return array<string, mixed>
     */
    public function preview(
        int $submissionId,
        string $responseXml
    ): array {
        if ($submissionId <= 0) {
            throw new RuntimeException(
                'El identificador de la remisión VERI*FACTU no es válido.'
            );
        }

        $submission =
            $this->submissionRepository
                ->findById(
                    $submissionId
                );

        if ($submission === null) {
            throw new RuntimeException(
                sprintf(
                    'No existe la remisión VERI*FACTU %d.',
                    $submissionId
                )
            );
        }

        $recordId =
            (int) (
                $submission[
                    'record_id'
                ]
                ?? 0
            );

        if ($recordId <= 0) {
            throw new RuntimeException(
                'La remisión VERI*FACTU no tiene un registro asociado válido.'
            );
        }

        $record =
            $this->recordRepository
                ->findById(
                    $recordId
                );

        if ($record === null) {
            throw new RuntimeException(
                sprintf(
                    'No existe el registro VERI*FACTU %d asociado a la remisión.',
                    $recordId
                )
            );
        }

        $submissionEnvironment =
            trim(
                (string) (
                    $submission[
                        'environment'
                    ]
                    ?? ''
                )
            );

        if (
            $submissionEnvironment
            !== $record->getEnvironment()
        ) {
            throw new RuntimeException(
                'El entorno de la remisión no coincide con el entorno del registro VERI*FACTU.'
            );
        }

        $parsed =
            $this->parser
                ->parse(
                    $responseXml
                );

        if (
            (bool) (
                $parsed[
                    'is_fault'
                ]
                ?? false
            )
        ) {
            return [
                'submission_id' =>
                    $submissionId,

                'record_id' =>
                    $recordId,

                'is_fault' =>
                    true,

                'fault_code' =>
                    $parsed[
                        'fault_code'
                    ]
                    ?? null,

                'fault_message' =>
                    $parsed[
                        'fault_message'
                    ]
                    ?? null,

                'submission_status' =>
                    null,

                'record_status' =>
                    null,

                'expected_issuer_tax_id' =>
                    $record->getIssuerTaxId(),

                'response_issuer_tax_id' =>
                    null,

                'issuer_match' =>
                    false,

                'expected_invoice_number' =>
                    $record->getInvoiceNumber(),

                'response_invoice_number' =>
                    null,

                'invoice_number_match' =>
                    false,

                'expected_invoice_date' =>
                    $record->getInvoiceDate(),

                'response_invoice_date' =>
                    null,

                'invoice_date_match' =>
                    false,

                'expected_operation' =>
                    $this->expectedOperation(
                        $record
                    ),

                'response_operation' =>
                    null,

                'operation_match' =>
                    false,

                'matched' =>
                    false,

                'safe_to_persist' =>
                    false,

                'reason' =>
                    'La respuesta contiene un SOAP Fault y no una RespuestaLinea fiscal.',
            ];
        }

        $lines =
            $parsed[
                'records'
            ]
            ?? [];

        if (!is_array($lines)) {
            $lines = [];
        }

        if (count($lines) !== 1) {
            throw new RuntimeException(
                sprintf(
                    'La remisión %d contiene un único registro, pero AEAT ha devuelto %d líneas de respuesta.',
                    $submissionId,
                    count($lines)
                )
            );
        }

        $line =
            $lines[0];

        if (!is_array($line)) {
            throw new RuntimeException(
                'La línea de respuesta AEAT no tiene un formato válido.'
            );
        }

        $expectedIssuer =
            $this->normalizeTaxId(
                $record->getIssuerTaxId()
            );

        $responseIssuer =
            $this->normalizeTaxId(
                (string) (
                    $line[
                        'issuer_tax_id'
                    ]
                    ?? ''
                )
            );

        $expectedNumber =
            trim(
                $record->getInvoiceNumber()
            );

        $responseNumber =
            trim(
                (string) (
                    $line[
                        'invoice_number'
                    ]
                    ?? ''
                )
            );

        $expectedDate =
            $this->normalizeRecordDate(
                $record->getInvoiceDate()
            );

        $responseDate =
            $this->normalizeResponseDate(
                (string) (
                    $line[
                        'invoice_date'
                    ]
                    ?? ''
                )
            );

        $expectedOperation =
            $this->expectedOperation(
                $record
            );

        $responseOperation =
            trim(
                (string) (
                    $line[
                        'operation'
                    ]
                    ?? ''
                )
            );

        $issuerMatch =
            $expectedIssuer !== ''
            && $responseIssuer !== ''
            && hash_equals(
                $expectedIssuer,
                $responseIssuer
            );

        $numberMatch =
            $expectedNumber !== ''
            && $responseNumber !== ''
            && hash_equals(
                $expectedNumber,
                $responseNumber
            );

        $dateMatch =
            $expectedDate !== ''
            && $responseDate !== ''
            && hash_equals(
                $expectedDate,
                $responseDate
            );

        $operationMatch =
            $expectedOperation !== ''
            && $responseOperation !== ''
            && hash_equals(
                $expectedOperation,
                $responseOperation
            );

        $matched =
            $issuerMatch
            && $numberMatch
            && $dateMatch
            && $operationMatch;

        return [
            'submission_id' =>
                $submissionId,

            'record_id' =>
                $recordId,

            'is_fault' =>
                false,

            'fault_code' =>
                null,

            'fault_message' =>
                null,

            'submission_status' =>
                $parsed[
                    'submission_status'
                ]
                ?? null,

            'record_status' =>
                $line[
                    'status'
                ]
                ?? null,

            'record_error_code' =>
                $line[
                    'error_code'
                ]
                ?? null,

            'record_error_message' =>
                $line[
                    'error_message'
                ]
                ?? null,

            'csv' =>
                $parsed[
                    'csv'
                ]
                ?? null,

            'presentation_tax_id' =>
                $parsed[
                    'presentation_tax_id'
                ]
                ?? null,

            'presentation_timestamp' =>
                $parsed[
                    'presentation_timestamp'
                ]
                ?? null,

            'wait_seconds' =>
                $parsed[
                    'wait_seconds'
                ]
                ?? null,

            'expected_issuer_tax_id' =>
                $expectedIssuer,

            'response_issuer_tax_id' =>
                $responseIssuer,

            'issuer_match' =>
                $issuerMatch,

            'expected_invoice_number' =>
                $expectedNumber,

            'response_invoice_number' =>
                $responseNumber,

            'invoice_number_match' =>
                $numberMatch,

            'expected_invoice_date' =>
                $expectedDate,

            'response_invoice_date' =>
                $responseDate,

            'invoice_date_match' =>
                $dateMatch,

            'expected_operation' =>
                $expectedOperation,

            'response_operation' =>
                $responseOperation,

            'operation_match' =>
                $operationMatch,

            'duplicate' =>
                $line[
                    'duplicate'
                ]
                ?? null,

            'matched' =>
                $matched,

            'safe_to_persist' =>
                $matched,

            'reason' =>
                $matched
                    ? 'La respuesta AEAT corresponde exactamente al registro de la remisión.'
                    : 'La identidad fiscal de la respuesta AEAT no coincide completamente con el registro de la remisión.',
        ];
    }

    /**
     * Persiste el resultado fiscal de AEAT en una única transacción.
     *
     * $rollbackForTest=true:
     * ejecuta las escrituras y fuerza ROLLBACK.
     *
     * @return array<string, mixed>
     */
    public function persist(
        int $submissionId,
        string $responseXml,
        bool $rollbackForTest = false
    ): array {
        global $wpdb;

        $result =
            $this->preview(
                $submissionId,
                $responseXml
            );

        if (
            !(
                $result[
                    'safe_to_persist'
                ]
                ?? false
            )
        ) {
            throw new RuntimeException(
                'La respuesta AEAT no puede persistirse porque no coincide exactamente con la remisión.'
            );
        }

        $submissionStatus =
            trim(
                (string) (
                    $result[
                        'submission_status'
                    ]
                    ?? ''
                )
            );

        $recordStatus =
            trim(
                (string) (
                    $result[
                        'record_status'
                    ]
                    ?? ''
                )
            );

        if (
            $submissionStatus === ''
            || $recordStatus === ''
        ) {
            throw new RuntimeException(
                'La respuesta AEAT no contiene los estados fiscales necesarios.'
            );
        }

        $duplicate =
            $result[
                'duplicate'
            ]
            ?? null;

        $duplicateStatus =
            is_array($duplicate)
                ? (
                    isset(
                        $duplicate[
                            'status'
                        ]
                    )
                        ? (string) $duplicate[
                            'status'
                        ]
                        : null
                )
                : null;

        /*
         * accepted_at solo se establece cuando
         * EstadoRegistro = Correcto.
         */
        $acceptedAt =
            $recordStatus === 'Correcto'
                ? gmdate(
                    'Y-m-d H:i:s'
                )
                : null;

        /*
         * persist() puede ejecutarse dentro de una
         * transacción controlada por una capa superior.
         *
         * Nunca debe hacer COMMIT o ROLLBACK sobre una
         * transacción que pertenece al llamador.
         */
        $alreadyInTransaction =
            (int) $wpdb->get_var(
                'SELECT @@in_transaction'
            ) === 1;

        $savepoint =
            'dsm_verifactu_response';

        if ($alreadyInTransaction) {
            $started =
                $wpdb->query(
                    'SAVEPOINT '
                    . $savepoint
                );
        } else {
            $started =
                $wpdb->query(
                    'START TRANSACTION'
                );
        }

        if ($started === false) {
            throw new RuntimeException(
                'No se pudo iniciar el ámbito transaccional de la respuesta VERI*FACTU: '
                . $wpdb->last_error
            );
        }

        try {
            $this->submissionRepository
                ->updateAeatResult(
                    $submissionId,
                    $submissionStatus,
                    $recordStatus,
                    isset(
                        $result[
                            'record_error_code'
                        ]
                    )
                        ? (string) $result[
                            'record_error_code'
                        ]
                        : null,
                    isset(
                        $result[
                            'record_error_message'
                        ]
                    )
                        ? (string) $result[
                            'record_error_message'
                        ]
                        : null,
                    $duplicateStatus,
                    isset(
                        $result[
                            'csv'
                        ]
                    )
                        ? (string) $result[
                            'csv'
                        ]
                        : null,
                    isset(
                        $result[
                            'presentation_tax_id'
                        ]
                    )
                        ? (string) $result[
                            'presentation_tax_id'
                        ]
                        : null,
                    isset(
                        $result[
                            'presentation_timestamp'
                        ]
                    )
                        ? (string) $result[
                            'presentation_timestamp'
                        ]
                        : null,
                    isset(
                        $result[
                            'wait_seconds'
                        ]
                    )
                        ? (int) $result[
                            'wait_seconds'
                        ]
                        : null
                );

            $this->recordRepository
                ->updateAeatResult(
                    (int) $result[
                        'record_id'
                    ],
                    $recordStatus,
                    isset(
                        $result[
                            'record_error_code'
                        ]
                    )
                        ? (string) $result[
                            'record_error_code'
                        ]
                        : null,
                    isset(
                        $result[
                            'record_error_message'
                        ]
                    )
                        ? (string) $result[
                            'record_error_message'
                        ]
                        : null,
                    $acceptedAt
                );

            /*
             * Leemos los valores dentro de la misma
             * transacción para verificar qué se ha escrito.
             */
            $submission =
                $this->submissionRepository
                    ->findById(
                        $submissionId
                    );

            $record =
                $this->recordRepository
                    ->findById(
                        (int) $result[
                            'record_id'
                        ]
                    );

            if (
                $submission === null
                || $record === null
            ) {
                throw new RuntimeException(
                    'No se pudo verificar la persistencia fiscal antes del COMMIT.'
                );
            }

            $result[
                'persisted_submission_status'
            ] =
                $submission[
                    'aeat_submission_status'
                ]
                ?? null;

            $result[
                'persisted_submission_record_status'
            ] =
                $submission[
                    'aeat_record_status'
                ]
                ?? null;

            $result[
                'persisted_csv'
            ] =
                $submission[
                    'aeat_csv'
                ]
                ?? null;

            $result[
                'persisted_presenter_tax_id'
            ] =
                $submission[
                    'aeat_presenter_tax_id'
                ]
                ?? null;

            $result[
                'persisted_presentation_timestamp'
            ] =
                $submission[
                    'aeat_presentation_timestamp'
                ]
                ?? null;

            $result[
                'persisted_wait_seconds'
            ] =
                isset(
                    $submission[
                        'aeat_wait_seconds'
                    ]
                )
                    ? (int) $submission[
                        'aeat_wait_seconds'
                    ]
                    : null;

            $result[
                'persisted_record_status'
            ] =
                $record->getAeatRecordStatus();

            $result[
                'accepted_at'
            ] =
                $acceptedAt;

            if ($rollbackForTest) {
                if ($alreadyInTransaction) {
                    $wpdb->query(
                        'ROLLBACK TO SAVEPOINT '
                        . $savepoint
                    );

                    $wpdb->query(
                        'RELEASE SAVEPOINT '
                        . $savepoint
                    );
                } else {
                    $wpdb->query(
                        'ROLLBACK'
                    );
                }

                $result[
                    'transaction'
                ] =
                    'rolled_back';

                return $result;
            }

            if ($alreadyInTransaction) {
                $committed =
                    $wpdb->query(
                        'RELEASE SAVEPOINT '
                        . $savepoint
                    );
            } else {
                $committed =
                    $wpdb->query(
                        'COMMIT'
                    );
            }

            if ($committed === false) {
                throw new RuntimeException(
                    'No se pudo confirmar la transacción fiscal VERI*FACTU.'
                );
            }

            $result[
                'transaction'
            ] =
                'committed';

            return $result;
        } catch (Throwable $e) {
            if ($alreadyInTransaction) {
                $wpdb->query(
                    'ROLLBACK TO SAVEPOINT '
                    . $savepoint
                );

                $wpdb->query(
                    'RELEASE SAVEPOINT '
                    . $savepoint
                );
            } else {
                $wpdb->query(
                    'ROLLBACK'
                );
            }

            throw $e;
        }
    }

    private function expectedOperation(
        VerifactuRecord $record
    ): string {
        return match (
            strtolower(
                trim(
                    $record->getRecordType()
                )
            )
        ) {
            'alta' =>
                'Alta',

            'anulacion',
            'anulación' =>
                'Anulacion',

            default =>
                '',
        };
    }

    private function normalizeTaxId(
        string $taxId
    ): string {
        return strtoupper(
            preg_replace(
                '/[\s\-.]+/',
                '',
                trim(
                    $taxId
                )
            )
            ?? ''
        );
    }

    private function normalizeRecordDate(
        string $date
    ): string {
        $date =
            trim(
                $date
            );

        if ($date === '') {
            return '';
        }

        $parsed =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $date
            );

        if (
            $parsed === false
            || $parsed->format(
                'Y-m-d'
            ) !== $date
        ) {
            throw new RuntimeException(
                sprintf(
                    'La fecha almacenada del registro VERI*FACTU no es válida: %s',
                    $date
                )
            );
        }

        return $parsed->format(
            'Y-m-d'
        );
    }

    private function normalizeResponseDate(
        string $date
    ): string {
        $date =
            trim(
                $date
            );

        if ($date === '') {
            return '';
        }

        $parsed =
            DateTimeImmutable::createFromFormat(
                '!d-m-Y',
                $date
            );

        if (
            $parsed === false
            || $parsed->format(
                'd-m-Y'
            ) !== $date
        ) {
            throw new RuntimeException(
                sprintf(
                    'La fecha devuelta por AEAT no es válida: %s',
                    $date
                )
            );
        }

        return $parsed->format(
            'Y-m-d'
        );
    }
}
