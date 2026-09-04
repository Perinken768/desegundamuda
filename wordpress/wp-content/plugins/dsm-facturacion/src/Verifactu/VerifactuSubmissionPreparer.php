<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuSubmissionPreparer
{
    private string $recordsTable;

    private VerifactuSubmissionRepository $submissionRepository;

    private VerifactuSoapRequestBuilder $soapBuilder;

    public function __construct()
    {
        global $wpdb;

        $this->recordsTable =
            $wpdb->prefix
            . 'dsm_verifactu_records';

        $this->submissionRepository =
            new VerifactuSubmissionRepository();

        $this->soapBuilder =
            new VerifactuSoapRequestBuilder();
    }

    /**
     * Prepara el primer intento normal.
     *
     * @return array<string, mixed>
     */
    public function prepare(
        int $recordId
    ): array {
        /*
         * El tipo de remisión se decide en el momento
         * de preparar la submission.
         *
         * Si el registro se está remitiendo durante una
         * situación global de incidencia, la envoltura
         * SOAP se congela desde el principio con:
         *
         * RemisionVoluntaria
         *   Incidencia = S
         *
         * El payload fiscal del registro NO cambia.
         */
        $incidenceState =
            VerifactuSettings::getIncidenceState();

        if (
            (bool) (
                $incidenceState[
                    'active'
                ]
                ?? false
            )
        ) {
            $reason =
                trim(
                    (string) (
                        $incidenceState[
                            'reason'
                        ]
                        ?? ''
                    )
                );

            if ($reason === '') {
                throw new RuntimeException(
                    'VERI*FACTU está en modo incidencia pero no existe motivo registrado.'
                );
            }

            return $this->prepareSubmission(
                $recordId,
                'incidence',
                $reason
            );
        }

        return $this->prepareSubmission(
            $recordId,
            'normal',
            null
        );
    }

    /**
     * Prepara una nueva remisión de incidencia.
     *
     * No modifica el payload fiscal del registro.
     * Únicamente reconstruye la envoltura SOAP con:
     *
     * RemisionVoluntaria
     *   Incidencia = S
     *
     * @return array<string, mixed>
     */
    public function prepareIncidence(
        int $recordId,
        string $reason
    ): array {
        $reason =
            trim(
                $reason
            );

        if ($reason === '') {
            throw new RuntimeException(
                'Debe indicar el motivo interno de la incidencia VERI*FACTU.'
            );
        }

        if (
            function_exists(
                'mb_substr'
            )
        ) {
            $reason =
                mb_substr(
                    $reason,
                    0,
                    500
                );
        } else {
            $reason =
                substr(
                    $reason,
                    0,
                    500
                );
        }

        return $this->prepareSubmission(
            $recordId,
            'incidence',
            $reason
        );
    }

    /**
     * Prepara un nuevo intento a partir de una
     * remisión anterior marcada como reintentable.
     *
     * El SOAP NO se reconstruye.
     *
     * Por tanto:
     *
     * - retry de normal -> sigue normal;
     * - retry de incidencia -> sigue incidencia;
     * - incidence_reason se conserva.
     *
     * @return array<string, mixed>
     */
    public function retry(
        int $submissionId
    ): array {
        return $this->submissionRepository
            ->createRetryFrom(
                $submissionId
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareSubmission(
        int $recordId,
        string $submissionType,
        ?string $incidenceReason
    ): array {
        global $wpdb;

        if ($recordId <= 0) {
            throw new RuntimeException(
                'El identificador del registro VERI*FACTU no es válido.'
            );
        }

        if (
            !in_array(
                $submissionType,
                [
                    'normal',
                    'incidence',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'Tipo de remisión VERI*FACTU no soportado: %s',
                    $submissionType
                )
            );
        }

        /*
         * Nunca permitimos dos remisiones activas
         * simultáneamente para el mismo registro.
         */
        $pending =
            $this->submissionRepository
                ->findPendingForRecord(
                    $recordId
                );

        if ($pending !== null) {
            throw new RuntimeException(
                sprintf(
                    'El registro VERI*FACTU %d ya tiene una remisión pendiente.',
                    $recordId
                )
            );
        }

        $record =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT
                        id,
                        environment,
                        fiscal_status,
                        payload_version,
                        payload_xml
                    FROM {$this->recordsTable}
                    WHERE id = %d
                    LIMIT 1
                    ",
                    $recordId
                ),
                ARRAY_A
            );

        if (!is_array($record)) {
            throw new RuntimeException(
                sprintf(
                    'No existe el registro VERI*FACTU %d.',
                    $recordId
                )
            );
        }

        $environment =
            trim(
                (string) (
                    $record[
                        'environment'
                    ]
                    ?? ''
                )
            );

        if (
            !in_array(
                $environment,
                [
                    VerifactuSettings::ENVIRONMENT_TEST,
                    VerifactuSettings::ENVIRONMENT_PRODUCTION,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El entorno del registro VERI*FACTU no es válido.'
            );
        }

        if (
            trim(
                (string) (
                    $record[
                        'payload_xml'
                    ]
                    ?? ''
                )
            ) === ''
        ) {
            throw new RuntimeException(
                sprintf(
                    'El registro VERI*FACTU %d no tiene payload fiscal congelado.',
                    $recordId
                )
            );
        }

        if (
            trim(
                (string) (
                    $record[
                        'payload_version'
                    ]
                    ?? ''
                )
            ) !== VerifactuXmlGenerator::VERSION
        ) {
            throw new RuntimeException(
                sprintf(
                    'El registro VERI*FACTU %d tiene una versión de payload no soportada.',
                    $recordId
                )
            );
        }

        /*
         * Para normal mantenemos la regla histórica:
         * si ya hubo una remisión, prepare() no crea
         * otra silenciosamente.
         */
        if ($submissionType === 'normal') {
            $latest =
                $this->submissionRepository
                    ->findLatestForRecordByType(
                        $recordId,
                        'normal'
                    );

            if ($latest !== null) {
                throw new RuntimeException(
                    sprintf(
                        'El registro VERI*FACTU %d ya tiene una remisión normal previa. Utilice retry() si procede.',
                        $recordId
                    )
                );
            }
        }

        /*
         * Una incidencia es una nueva remisión explícita.
         *
         * Si ya existe una incidencia pendiente,
         * findPendingForRecord() la habrá bloqueado.
         *
         * Una incidencia anterior ya finalizada no impide
         * registrar una nueva incidencia futura.
         */
        $settings =
            VerifactuSettings::get();

        $certificateType =
            trim(
                (string) (
                    $settings[
                        'certificate_type'
                    ]
                    ?? VerifactuSettings::CERTIFICATE_STANDARD
                )
            );

        $endpoint =
            $this->resolveEndpoint(
                $environment,
                $certificateType
            );

        $requestXml =
            $this->soapBuilder
                ->build(
                    [
                        $recordId,
                    ],
                    $submissionType
                        === 'incidence'
                );

        if (
            trim(
                $requestXml
            ) === ''
        ) {
            throw new RuntimeException(
                'No se pudo construir la solicitud SOAP VERI*FACTU.'
            );
        }

        /*
         * attempt_number es global por record_id.
         *
         * Esto respeta UNIQUE(record_id, attempt_number)
         * aunque alternemos:
         *
         * normal -> retry -> incidencia -> retry...
         */
        $attemptNumber =
            $this->submissionRepository
                ->nextAttemptNumber(
                    $recordId
                );

        $now =
            gmdate(
                'Y-m-d H:i:s'
            );

        $submissionId =
            $this->submissionRepository
                ->insert(
                    [
                        'submission_uuid' =>
                            wp_generate_uuid4(),

                        'record_id' =>
                            $recordId,

                        'environment' =>
                            $environment,

                        'submission_type' =>
                            $submissionType,

                        'incidence_reason' =>
                            $submissionType
                                === 'incidence'
                                    ? $incidenceReason
                                    : null,

                        'attempt_number' =>
                            $attemptNumber,

                        'endpoint' =>
                            $endpoint,

                        'request_xml' =>
                            $requestXml,

                        'response_xml' =>
                            null,

                        'http_status' =>
                            null,

                        'transport_status' =>
                            'pending',

                        'sending_started_at' =>
                            null,

                        'retryable' =>
                            0,

                        'retry_reason' =>
                            null,

                        'next_retry_at' =>
                            null,

                        'aeat_submission_status' =>
                            null,

                        'aeat_record_status' =>
                            null,

                        'aeat_error_code' =>
                            null,

                        'aeat_error_message' =>
                            null,

                        'aeat_duplicate_status' =>
                            null,

                        'aeat_csv' =>
                            null,

                        'aeat_presenter_tax_id' =>
                            null,

                        'aeat_presentation_timestamp' =>
                            null,

                        'aeat_wait_seconds' =>
                            null,

                        'sent_at' =>
                            null,

                        'responded_at' =>
                            null,

                        'duration_ms' =>
                            null,

                        'created_at' =>
                            $now,

                        'updated_at' =>
                            $now,
                    ]
                );

        $submission =
            $this->submissionRepository
                ->findById(
                    $submissionId
                );

        if ($submission === null) {
            throw new RuntimeException(
                'La remisión VERI*FACTU se creó pero no pudo recuperarse.'
            );
        }

        return $submission;
    }

    private function resolveEndpoint(
        string $environment,
        string $certificateType
    ): string {
        if (
            $certificateType
            !== VerifactuSettings::CERTIFICATE_STANDARD
            && $certificateType
                !== VerifactuSettings::CERTIFICATE_SEAL
        ) {
            throw new RuntimeException(
                'El tipo de certificado VERI*FACTU no es válido.'
            );
        }

        if (
            $environment
            === VerifactuSettings::ENVIRONMENT_TEST
        ) {
            if (
                $certificateType
                === VerifactuSettings::CERTIFICATE_SEAL
            ) {
                return
                    'https://prewww10.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP';
            }

            return
                'https://prewww1.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP';
        }

        if (
            $certificateType
            === VerifactuSettings::CERTIFICATE_SEAL
        ) {
            return
                'https://www10.agenciatributaria.gob.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP';
        }

        return
            'https://www1.agenciatributaria.gob.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP';
    }
}
