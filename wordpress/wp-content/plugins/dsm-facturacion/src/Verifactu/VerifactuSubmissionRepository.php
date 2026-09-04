<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuSubmissionRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table =
            $wpdb->prefix
            . 'dsm_verifactu_submissions';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(
        int $submissionId
    ): ?array {
        global $wpdb;

        if ($submissionId <= 0) {
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
                    $submissionId
                ),
                ARRAY_A
            );

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLatestForRecord(
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
                    SELECT *
                    FROM {$this->table}
                    WHERE record_id = %d
                    ORDER BY attempt_number DESC
                    LIMIT 1
                    ",
                    $recordId
                ),
                ARRAY_A
            );

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * Devuelve la última remisión de un registro
     * para un tipo concreto.
     *
     * @return array<string, mixed>|null
     */
    public function findLatestForRecordByType(
        int $recordId,
        string $submissionType
    ): ?array {
        global $wpdb;

        if ($recordId <= 0) {
            return null;
        }

        $submissionType =
            $this->normalizeSubmissionType(
                $submissionType
            );

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->table}
                    WHERE record_id = %d
                      AND submission_type = %s
                    ORDER BY attempt_number DESC
                    LIMIT 1
                    ",
                    $recordId,
                    $submissionType
                ),
                ARRAY_A
            );

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPendingForRecord(
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
                    SELECT *
                    FROM {$this->table}
                    WHERE record_id = %d
                      AND transport_status = 'pending'
                    ORDER BY attempt_number DESC
                    LIMIT 1
                    ",
                    $recordId
                ),
                ARRAY_A
            );

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * Devuelve remisiones pendientes preparadas
     * para su procesamiento.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findPending(
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

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->table}
                    WHERE transport_status = 'pending'
                    ORDER BY created_at ASC, id ASC
                    LIMIT %d
                    ",
                    $limit
                ),
                ARRAY_A
            );

        return is_array($rows)
            ? $rows
            : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findDueRetries(
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

        $now =
            gmdate(
                'Y-m-d H:i:s'
            );

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->table}
                    WHERE retryable = 1
                      AND next_retry_at IS NOT NULL
                      AND next_retry_at <= %s
                    ORDER BY next_retry_at ASC, id ASC
                    LIMIT %d
                    ",
                    $now,
                    $limit
                ),
                ARRAY_A
            );

        return is_array($rows)
            ? $rows
            : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findStaleSending(
        int $olderThanSeconds = 300,
        int $limit = 50
    ): array {
        global $wpdb;

        $olderThanSeconds =
            max(
                60,
                $olderThanSeconds
            );

        $limit =
            max(
                1,
                min(
                    500,
                    $limit
                )
            );

        $cutoff =
            gmdate(
                'Y-m-d H:i:s',
                time() - $olderThanSeconds
            );

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->table}
                    WHERE transport_status = 'sending'
                      AND sending_started_at IS NOT NULL
                      AND sending_started_at <= %s
                    ORDER BY sending_started_at ASC, id ASC
                    LIMIT %d
                    ",
                    $cutoff,
                    $limit
                ),
                ARRAY_A
            );

        return is_array($rows)
            ? $rows
            : [];
    }

    public function nextAttemptNumber(
        int $recordId
    ): int {
        global $wpdb;

        if ($recordId <= 0) {
            throw new RuntimeException(
                'El identificador del registro VERI*FACTU no es válido.'
            );
        }

        $lastAttempt =
            (int) $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COALESCE(
                        MAX(attempt_number),
                        0
                    )
                    FROM {$this->table}
                    WHERE record_id = %d
                    ",
                    $recordId
                )
            );

        return $lastAttempt + 1;
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
                'No se pudo guardar la remisión VERI*FACTU: '
                . $wpdb->last_error
            );
        }

        $submissionId =
            (int) $wpdb->insert_id;

        if ($submissionId <= 0) {
            throw new RuntimeException(
                'No se obtuvo el identificador de la remisión VERI*FACTU.'
            );
        }

        return $submissionId;
    }

    /**
     * Reclama atómicamente una remisión pendiente.
     *
     * pending -> sending
     */
    public function claimForSending(
        int $submissionId
    ): bool {
        global $wpdb;

        if ($submissionId <= 0) {
            return false;
        }

        $now =
            gmdate(
                'Y-m-d H:i:s'
            );

        $updated =
            $wpdb->query(
                $wpdb->prepare(
                    "
                    UPDATE {$this->table}
                    SET
                        transport_status = 'sending',
                        sending_started_at = %s,
                        retryable = 0,
                        retry_reason = NULL,
                        next_retry_at = NULL,
                        updated_at = %s
                    WHERE id = %d
                      AND transport_status = 'pending'
                    ",
                    $now,
                    $now,
                    $submissionId
                )
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo reclamar la remisión VERI*FACTU: '
                . $wpdb->last_error
            );
        }

        return $updated === 1;
    }

    /**
     * Respuesta HTTP/SOAP fiscalmente procesable.
     *
     * sending -> sent
     */
    public function markSent(
        int $submissionId,
        int $httpStatus,
        string $responseXml,
        int $durationMs
    ): void {
        global $wpdb;

        if ($submissionId <= 0) {
            throw new RuntimeException(
                'La remisión VERI*FACTU no es válida.'
            );
        }

        $now =
            gmdate(
                'Y-m-d H:i:s'
            );

        $updated =
            $wpdb->query(
                $wpdb->prepare(
                    "
                    UPDATE {$this->table}
                    SET
                        transport_status = 'sent',
                        http_status = %d,
                        response_xml = %s,
                        duration_ms = %d,
                        sent_at = %s,
                        responded_at = %s,
                        retryable = 0,
                        retry_reason = NULL,
                        next_retry_at = NULL,
                        updated_at = %s
                    WHERE id = %d
                      AND transport_status = 'sending'
                    ",
                    $httpStatus,
                    $responseXml,
                    max(
                        0,
                        $durationMs
                    ),
                    $now,
                    $now,
                    $now,
                    $submissionId
                )
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo guardar la respuesta de transporte VERI*FACTU: '
                . $wpdb->last_error
            );
        }

        if ($updated !== 1) {
            throw new RuntimeException(
                'La remisión VERI*FACTU no estaba en estado sending al guardar la respuesta.'
            );
        }
    }

    /**
     * Hubo respuesta HTTP, pero no puede pasar al
     * procesador fiscal.
     *
     * Ejemplos:
     *
     * - HTTP 503
     * - HTTP 400
     * - SOAP Fault
     * - HTTP 200 con cuerpo vacío
     * - XML no procesable
     *
     * sending -> response_error
     */
    public function markResponseError(
        int $submissionId,
        int $httpStatus,
        string $responseXml,
        int $durationMs,
        bool $retryable,
        string $reason,
        string $message,
        int $retryAfterSeconds = 60
    ): void {
        global $wpdb;

        if ($submissionId <= 0) {
            throw new RuntimeException(
                'La remisión VERI*FACTU no es válida.'
            );
        }

        $nowTimestamp =
            time();

        $now =
            gmdate(
                'Y-m-d H:i:s',
                $nowTimestamp
            );

        $retryAfterSeconds =
            max(
                60,
                $retryAfterSeconds
            );

        $nextRetryAt =
            $retryable
                ? gmdate(
                    'Y-m-d H:i:s',
                    $nowTimestamp
                    + $retryAfterSeconds
                )
                : null;

        $reason =
            trim(
                $reason
            );

        if ($reason === '') {
            $reason =
                'response_error';
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
                    100
                );
        } else {
            $reason =
                substr(
                    $reason,
                    0,
                    100
                );
        }

        $message =
            trim(
                $message
            );

        if (
            function_exists(
                'mb_substr'
            )
        ) {
            $message =
                mb_substr(
                    $message,
                    0,
                    65535
                );
        } else {
            $message =
                substr(
                    $message,
                    0,
                    65535
                );
        }

        $updated =
            $wpdb->update(
                $this->table,
                [
                    'transport_status' =>
                        'response_error',

                    'http_status' =>
                        $httpStatus,

                    'response_xml' =>
                        $responseXml,

                    'duration_ms' =>
                        max(
                            0,
                            $durationMs
                        ),

                    /*
                     * La petición llegó al punto de obtener
                     * respuesta HTTP. Conservamos ambas
                     * marcas temporales como evidencia.
                     */
                    'sent_at' =>
                        $now,

                    'responded_at' =>
                        $now,

                    'retryable' =>
                        $retryable
                            ? 1
                            : 0,

                    'retry_reason' =>
                        $reason,

                    'next_retry_at' =>
                        $nextRetryAt,

                    'aeat_error_message' =>
                        $message !== ''
                            ? $message
                            : null,

                    'updated_at' =>
                        $now,
                ],
                [
                    'id' =>
                        $submissionId,

                    'transport_status' =>
                        'sending',
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo guardar el error de respuesta VERI*FACTU: '
                . $wpdb->last_error
            );
        }

        if ($updated !== 1) {
            throw new RuntimeException(
                'La remisión VERI*FACTU no estaba en estado sending al guardar el error de respuesta.'
            );
        }
    }

    /**
     * Fallo antes de obtener una respuesta HTTP
     * utilizable.
     *
     * sending -> transport_error
     */
    public function markTransportError(
        int $submissionId,
        string $message,
        ?int $durationMs = null,
        int $retryAfterSeconds = 60
    ): void {
        global $wpdb;

        if ($submissionId <= 0) {
            throw new RuntimeException(
                'La remisión VERI*FACTU no es válida.'
            );
        }

        $nowTimestamp =
            time();

        $now =
            gmdate(
                'Y-m-d H:i:s',
                $nowTimestamp
            );

        $retryAfterSeconds =
            max(
                60,
                $retryAfterSeconds
            );

        $nextRetryAt =
            gmdate(
                'Y-m-d H:i:s',
                $nowTimestamp
                + $retryAfterSeconds
            );

        $durationValue =
            $durationMs !== null
                ? max(
                    0,
                    $durationMs
                )
                : null;

        $message =
            trim(
                $message
            );

        if (
            function_exists(
                'mb_substr'
            )
        ) {
            $message =
                mb_substr(
                    $message,
                    0,
                    65535
                );
        } else {
            $message =
                substr(
                    $message,
                    0,
                    65535
                );
        }

        $updated =
            $wpdb->update(
                $this->table,
                [
                    'transport_status' =>
                        'transport_error',

                    'aeat_error_message' =>
                        $message,

                    'duration_ms' =>
                        $durationValue,

                    'retryable' =>
                        1,

                    'retry_reason' =>
                        'transport_error',

                    'next_retry_at' =>
                        $nextRetryAt,

                    'updated_at' =>
                        $now,
                ],
                [
                    'id' =>
                        $submissionId,

                    'transport_status' =>
                        'sending',
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo registrar el error de transporte VERI*FACTU: '
                . $wpdb->last_error
            );
        }

        if ($updated !== 1) {
            throw new RuntimeException(
                'La remisión VERI*FACTU no estaba en estado sending al registrar el error.'
            );
        }
    }

    /**
     * Convierte una remisión sending abandonada
     * en una remisión recuperable.
     */
    public function markStaleSendingRetryable(
        int $submissionId,
        int $retryAfterSeconds = 60
    ): bool {
        global $wpdb;

        if ($submissionId <= 0) {
            return false;
        }

        $nowTimestamp =
            time();

        $now =
            gmdate(
                'Y-m-d H:i:s',
                $nowTimestamp
            );

        $nextRetryAt =
            gmdate(
                'Y-m-d H:i:s',
                $nowTimestamp
                + max(
                    60,
                    $retryAfterSeconds
                )
            );

        $updated =
            $wpdb->query(
                $wpdb->prepare(
                    "
                    UPDATE {$this->table}
                    SET
                        transport_status = 'transport_error',
                        retryable = 1,
                        retry_reason = 'stale_sending',
                        next_retry_at = %s,
                        updated_at = %s
                    WHERE id = %d
                      AND transport_status = 'sending'
                    ",
                    $nextRetryAt,
                    $now,
                    $submissionId
                )
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo recuperar la remisión VERI*FACTU abandonada: '
                . $wpdb->last_error
            );
        }

        return $updated === 1;
    }

    /**
     * Crea un nuevo intento reutilizando EXACTAMENTE
     * el SOAP y endpoint del intento anterior.
     *
     * @return array<string, mixed>
     */
    public function createRetryFrom(
        int $submissionId
    ): array {
        global $wpdb;

        if ($submissionId <= 0) {
            throw new RuntimeException(
                'La remisión VERI*FACTU no es válida.'
            );
        }

        /*
         * createRetryFrom() puede ser invocado dentro de una
         * transacción exterior.
         *
         * Nunca debemos ejecutar COMMIT sobre una transacción
         * que pertenece al llamador.
         */
        $alreadyInTransaction =
            (int) $wpdb->get_var(
                'SELECT @@in_transaction'
            ) === 1;

        $savepoint =
            'dsm_verifactu_retry';

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
                'No se pudo iniciar el ámbito transaccional del retry VERI*FACTU: '
                . $wpdb->last_error
            );
        }

        try {
            $previous =
                $wpdb->get_row(
                    $wpdb->prepare(
                        "
                        SELECT *
                        FROM {$this->table}
                        WHERE id = %d
                        LIMIT 1
                        FOR UPDATE
                        ",
                        $submissionId
                    ),
                    ARRAY_A
                );

            if (!is_array($previous)) {
                throw new RuntimeException(
                    sprintf(
                        'No existe la remisión VERI*FACTU %d.',
                        $submissionId
                    )
                );
            }

            if (
                (int) (
                    $previous[
                        'retryable'
                    ]
                    ?? 0
                ) !== 1
            ) {
                throw new RuntimeException(
                    'La remisión VERI*FACTU no está marcada como reintentable.'
                );
            }

            $nextRetryAt =
                trim(
                    (string) (
                        $previous[
                            'next_retry_at'
                        ]
                        ?? ''
                    )
                );

            if (
                $nextRetryAt !== ''
                && strtotime(
                    $nextRetryAt . ' UTC'
                ) > time()
            ) {
                throw new RuntimeException(
                    sprintf(
                        'La remisión no puede reintentarse todavía. Próximo intento: %s UTC.',
                        $nextRetryAt
                    )
                );
            }

            $recordId =
                (int) (
                    $previous[
                        'record_id'
                    ]
                    ?? 0
                );

            if ($recordId <= 0) {
                throw new RuntimeException(
                    'La remisión anterior no tiene un registro VERI*FACTU válido.'
                );
            }

            $activeCount =
                (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT COUNT(*)
                        FROM {$this->table}
                        WHERE record_id = %d
                          AND transport_status IN (
                              'pending',
                              'sending'
                          )
                        ",
                        $recordId
                    )
                );

            if ($activeCount > 0) {
                throw new RuntimeException(
                    'Ya existe una remisión pendiente o en proceso para este registro VERI*FACTU.'
                );
            }

            $requestXml =
                (string) (
                    $previous[
                        'request_xml'
                    ]
                    ?? ''
                );

            if (
                trim(
                    $requestXml
                ) === ''
            ) {
                throw new RuntimeException(
                    'El intento anterior no contiene SOAP congelado.'
                );
            }

            $endpoint =
                trim(
                    (string) (
                        $previous[
                            'endpoint'
                        ]
                        ?? ''
                    )
                );

            if ($endpoint === '') {
                throw new RuntimeException(
                    'El intento anterior no contiene endpoint congelado.'
                );
            }

            $attemptNumber =
                (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT COALESCE(
                            MAX(attempt_number),
                            0
                        ) + 1
                        FROM {$this->table}
                        WHERE record_id = %d
                        ",
                        $recordId
                    )
                );

            $now =
                gmdate(
                    'Y-m-d H:i:s'
                );

            $inserted =
                $wpdb->insert(
                    $this->table,
                    [
                        'submission_uuid' =>
                            wp_generate_uuid4(),

                        'record_id' =>
                            $recordId,

                        'environment' =>
                            (string) $previous[
                                'environment'
                            ],

                        /*
                         * Un retry es un nuevo intento de
                         * EXACTAMENTE la misma remisión.
                         *
                         * Por tanto conserva también su
                         * naturaleza normal/incidencia y
                         * la razón interna de incidencia.
                         */
                        'submission_type' =>
                            $this->normalizeSubmissionType(
                                (string) (
                                    $previous[
                                        'submission_type'
                                    ]
                                    ?? 'normal'
                                )
                            ),

                        'incidence_reason' =>
                            self::nullable(
                                $previous[
                                    'incidence_reason'
                                ]
                                ?? null
                            ),

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

            if ($inserted === false) {
                throw new RuntimeException(
                    'No se pudo crear el nuevo intento VERI*FACTU: '
                    . $wpdb->last_error
                );
            }

            $newId =
                (int) $wpdb->insert_id;

            if ($newId <= 0) {
                throw new RuntimeException(
                    'No se obtuvo el ID del nuevo intento VERI*FACTU.'
                );
            }

            $wpdb->query(
                $wpdb->prepare(
                    "
                    UPDATE {$this->table}
                    SET
                        retryable = 0,
                        updated_at = %s
                    WHERE id = %d
                    ",
                    $now,
                    $submissionId
                )
            );

            $newSubmission =
                $this->findById(
                    $newId
                );

            if ($newSubmission === null) {
                throw new RuntimeException(
                    'El nuevo intento VERI*FACTU se creó pero no pudo recuperarse.'
                );
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
                    'No se pudo confirmar la creación del nuevo intento VERI*FACTU.'
                );
            }

            return $newSubmission;
        } catch (\Throwable $e) {
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

    public function updateAeatResult(
        int $submissionId,
        string $submissionStatus,
        string $recordStatus,
        ?string $errorCode,
        ?string $errorMessage,
        ?string $duplicateStatus,
        ?string $csv,
        ?string $presenterTaxId = null,
        ?string $presentationTimestamp = null,
        ?int $waitSeconds = null
    ): void {
        global $wpdb;

        if ($submissionId <= 0) {
            throw new RuntimeException(
                'La remisión VERI*FACTU no es válida.'
            );
        }

        if (
            !in_array(
                $submissionStatus,
                [
                    'Correcto',
                    'ParcialmenteCorrecto',
                    'Incorrecto',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'EstadoEnvio AEAT no válido: %s',
                    $submissionStatus
                )
            );
        }

        if (
            !in_array(
                $recordStatus,
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
                    $recordStatus
                )
            );
        }

        $updated =
            $wpdb->update(
                $this->table,
                [
                    'aeat_submission_status' =>
                        $submissionStatus,

                    'aeat_record_status' =>
                        $recordStatus,

                    'aeat_error_code' =>
                        self::nullable(
                            $errorCode
                        ),

                    'aeat_error_message' =>
                        self::nullable(
                            $errorMessage
                        ),

                    'aeat_duplicate_status' =>
                        self::nullable(
                            $duplicateStatus
                        ),

                    'aeat_csv' =>
                        self::nullable(
                            $csv
                        ),

                    'aeat_presenter_tax_id' =>
                        self::nullable(
                            $presenterTaxId
                        ),

                    'aeat_presentation_timestamp' =>
                        self::nullable(
                            $presentationTimestamp
                        ),

                    'aeat_wait_seconds' =>
                        $waitSeconds !== null
                            ? max(
                                0,
                                $waitSeconds
                            )
                            : null,

                    'retryable' =>
                        0,

                    'retry_reason' =>
                        null,

                    'next_retry_at' =>
                        null,

                    'updated_at' =>
                        gmdate(
                            'Y-m-d H:i:s'
                        ),
                ],
                [
                    'id' =>
                        $submissionId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo guardar el resultado AEAT de la remisión: '
                . $wpdb->last_error
            );
        }

        if ($updated !== 1) {
            throw new RuntimeException(
                sprintf(
                    'No se actualizó el resultado AEAT de la remisión %d.',
                    $submissionId
                )
            );
        }
    }

    /**
     * Devuelve el estado actual de la espera solicitada
     * por AEAT mediante TiempoEsperaEnvio.
     *
     * La espera se calcula a partir de responded_at,
     * porque representa el momento en que DSM recibió
     * la respuesta que contenía TiempoEsperaEnvio.
     *
     * Se toma la respuesta más reciente que tenga un
     * TiempoEsperaEnvio positivo.
     *
     * @return array{
     *     blocked: bool,
     *     submission_id: int|null,
     *     responded_at: string|null,
     *     wait_seconds: int,
     *     wait_until: string|null,
     *     remaining_seconds: int
     * }
     */
    public function getAeatTransportWaitState(): array
    {
        global $wpdb;

        $row =
            $wpdb->get_row(
                "
                SELECT
                    id,
                    responded_at,
                    aeat_wait_seconds
                FROM {$this->table}
                WHERE responded_at IS NOT NULL
                  AND aeat_wait_seconds IS NOT NULL
                  AND aeat_wait_seconds > 0
                ORDER BY responded_at DESC, id DESC
                LIMIT 1
                ",
                ARRAY_A
            );

        if (!is_array($row)) {
            return [
                'blocked' =>
                    false,

                'submission_id' =>
                    null,

                'responded_at' =>
                    null,

                'wait_seconds' =>
                    0,

                'wait_until' =>
                    null,

                'remaining_seconds' =>
                    0,
            ];
        }

        $submissionId =
            (int) (
                $row['id']
                ?? 0
            );

        $respondedAt =
            trim(
                (string) (
                    $row['responded_at']
                    ?? ''
                )
            );

        $waitSeconds =
            max(
                0,
                (int) (
                    $row['aeat_wait_seconds']
                    ?? 0
                )
            );

        if (
            $submissionId <= 0
            || $respondedAt === ''
            || $waitSeconds <= 0
        ) {
            return [
                'blocked' =>
                    false,

                'submission_id' =>
                    null,

                'responded_at' =>
                    null,

                'wait_seconds' =>
                    0,

                'wait_until' =>
                    null,

                'remaining_seconds' =>
                    0,
            ];
        }

        $respondedTimestamp =
            strtotime(
                $respondedAt
                . ' UTC'
            );

        if ($respondedTimestamp === false) {
            throw new RuntimeException(
                sprintf(
                    'responded_at no es válido en la remisión VERI*FACTU %d.',
                    $submissionId
                )
            );
        }

        $waitUntilTimestamp =
            $respondedTimestamp
            + $waitSeconds;

        $nowTimestamp =
            time();

        $remainingSeconds =
            max(
                0,
                $waitUntilTimestamp
                - $nowTimestamp
            );

        return [
            'blocked' =>
                $remainingSeconds > 0,

            'submission_id' =>
                $submissionId,

            'responded_at' =>
                $respondedAt,

            'wait_seconds' =>
                $waitSeconds,

            'wait_until' =>
                gmdate(
                    'Y-m-d H:i:s',
                    $waitUntilTimestamp
                ),

            'remaining_seconds' =>
                $remainingSeconds,
        ];
    }

    private function normalizeSubmissionType(
        string $submissionType
    ): string {
        $submissionType =
            strtolower(
                trim(
                    $submissionType
                )
            );

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
                    'Tipo de remisión VERI*FACTU no válido: %s',
                    $submissionType
                )
            );
        }

        return $submissionType;
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
