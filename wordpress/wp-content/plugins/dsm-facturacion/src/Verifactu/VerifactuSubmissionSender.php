<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuSubmissionSender
{
    private VerifactuSubmissionRepository $repository;

    private VerifactuHttpTransport $transport;

    private VerifactuResponseProcessor $responseProcessor;

    private VerifactuTransportResponseClassifier $responseClassifier;

    public function __construct()
    {
        $this->repository =
            new VerifactuSubmissionRepository();

        $this->transport =
            new VerifactuHttpTransport();

        $this->responseProcessor =
            new VerifactuResponseProcessor();

        $this->responseClassifier =
            new VerifactuTransportResponseClassifier();
    }

    /**
     * Comprobación completa sin transmitir nada.
     *
     * @return array<string, mixed>
     */
    public function preflight(
        int $submissionId
    ): array {
        $submission =
            $this->repository
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

        $transportStatus =
            trim(
                (string) (
                    $submission[
                        'transport_status'
                    ]
                    ?? ''
                )
            );

        if ($transportStatus !== 'pending') {
            throw new RuntimeException(
                sprintf(
                    'La remisión VERI*FACTU %d no está pendiente. Estado actual: %s.',
                    $submissionId,
                    $transportStatus !== ''
                        ? $transportStatus
                        : '(vacío)'
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

        $settings =
            VerifactuSettings::get();

        $currentEnvironment =
            trim(
                (string) (
                    $settings[
                        'environment'
                    ]
                    ?? ''
                )
            );

        if (
            $submissionEnvironment
            !== $currentEnvironment
        ) {
            throw new RuntimeException(
                sprintf(
                    'La remisión está congelada para el entorno "%s", pero la configuración actual está en "%s".',
                    $submissionEnvironment,
                    $currentEnvironment
                )
            );
        }

        $submissionEndpoint =
            trim(
                (string) (
                    $submission[
                        'endpoint'
                    ]
                    ?? ''
                )
            );

        $expectedEndpoint =
            VerifactuSettings::getEndpoint(
                $settings
            );

        if (
            $submissionEndpoint === ''
            || !hash_equals(
                $expectedEndpoint,
                $submissionEndpoint
            )
        ) {
            throw new RuntimeException(
                'El endpoint congelado de la remisión no coincide con el endpoint VERI*FACTU actual.'
            );
        }

        $requestXml =
            (string) (
                $submission[
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
                'La remisión VERI*FACTU no contiene solicitud SOAP.'
            );
        }

        $document =
            new \DOMDocument();

        $previousLibxmlState =
            libxml_use_internal_errors(
                true
            );

        libxml_clear_errors();

        try {
            $xmlOk =
                $document->loadXML(
                    $requestXml
                );

            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors(
                $previousLibxmlState
            );
        }

        if (!$xmlOk) {
            throw new RuntimeException(
                'La solicitud SOAP congelada no contiene XML válido.'
            );
        }

        $transportInfo =
            $this->transport
                ->inspect();

        $transportReady =
            false;

        $transportError =
            '';

        try {
            $this->transport
                ->assertReady();

            $transportReady =
                true;
        } catch (Throwable $e) {
            $transportError =
                $e->getMessage();
        }

        return [
            'submission_id' =>
                (int) $submission[
                    'id'
                ],

            'record_id' =>
                (int) $submission[
                    'record_id'
                ],

            'attempt_number' =>
                (int) $submission[
                    'attempt_number'
                ],

            'transport_status' =>
                $transportStatus,

            'submission_environment' =>
                $submissionEnvironment,

            'current_environment' =>
                $currentEnvironment,

            'environment_match' =>
                true,

            'endpoint' =>
                $submissionEndpoint,

            'endpoint_match' =>
                true,

            'request_bytes' =>
                strlen(
                    $requestXml
                ),

            'request_sha256' =>
                hash(
                    'sha256',
                    $requestXml
                ),

            'soap_valid' =>
                true,

            'verifactu_enabled' =>
                (bool) (
                    $transportInfo[
                        'enabled'
                    ]
                    ?? false
                ),

            'certificate_status' =>
                (string) (
                    $transportInfo[
                        'certificate_status'
                    ]
                    ?? ''
                ),

            'certificate_format' =>
                (string) (
                    $transportInfo[
                        'certificate_format'
                    ]
                    ?? ''
                ),

            'curl_available' =>
                (bool) (
                    $transportInfo[
                        'curl_available'
                    ]
                    ?? false
                ),

            'transport_ready' =>
                $transportReady,

            'transport_error' =>
                $transportError,
        ];
    }

    /**
     * Procesa fiscalmente una respuesta que YA ha sido
     * recibida y almacenada por el transporte.
     *
     * No realiza ninguna conexión HTTP.
     *
     * @return array<string, mixed>
     */
    public function processStoredResponse(
        int $submissionId
    ): array {
        $submission =
            $this->repository
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

        $transportStatus =
            trim(
                (string) (
                    $submission[
                        'transport_status'
                    ]
                    ?? ''
                )
            );

        if ($transportStatus !== 'sent') {
            throw new RuntimeException(
                sprintf(
                    'La remisión %d no puede procesarse fiscalmente porque su estado de transporte es "%s".',
                    $submissionId,
                    $transportStatus !== ''
                        ? $transportStatus
                        : '(vacío)'
                )
            );
        }

        $responseXml =
            (string) (
                $submission[
                    'response_xml'
                ]
                ?? ''
            );

        if (
            trim(
                $responseXml
            ) === ''
        ) {
            throw new RuntimeException(
                sprintf(
                    'La remisión %d está marcada como enviada pero no contiene respuesta SOAP.',
                    $submissionId
                )
            );
        }

        $preview =
            $this->responseProcessor
                ->preview(
                    $submissionId,
                    $responseXml
                );

        if (
            (bool) (
                $preview[
                    'is_fault'
                ]
                ?? false
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'AEAT devolvió SOAP Fault%s%s.',
                    !empty(
                        $preview[
                            'fault_code'
                        ]
                    )
                        ? ' '
                        . (string) $preview[
                            'fault_code'
                        ]
                        : '',
                    !empty(
                        $preview[
                            'fault_message'
                        ]
                    )
                        ? ': '
                        . (string) $preview[
                            'fault_message'
                        ]
                        : ''
                )
            );
        }

        if (
            !(
                $preview[
                    'safe_to_persist'
                ]
                ?? false
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'La respuesta AEAT de la remisión %d no coincide exactamente con el registro fiscal asociado.',
                    $submissionId
                )
            );
        }

        $result =
            $this->responseProcessor
                ->persist(
                    $submissionId,
                    $responseXml,
                    false
                );

        /*
         * Una respuesta fiscal válida y persistida
         * demuestra que el canal con AEAT vuelve a
         * estar operativo.
         *
         * Lo hacemos aquí, y no únicamente en send(),
         * para cubrir también respuestas almacenadas
         * que se procesen posteriormente.
         */
        if (
            VerifactuSettings::isIncidenceActive()
        ) {
            VerifactuSettings::endIncidence();
        }

        return $result;
    }

    /**
     * ENVÍO REAL A AEAT.
     *
     * El propio sender debe ser seguro aunque sea
     * invocado directamente, sin pasar por la cola.
     *
     * @return array<string, mixed>
     */
    public function send(
        int $submissionId
    ): array {
        /*
         * ==================================================
         * PREFLIGHT BLOQUEANTE
         * ==================================================
         */
        $preflight =
            $this->preflight(
                $submissionId
            );

        $transportReady =
            (bool) (
                $preflight[
                    'transport_ready'
                ]
                ?? false
            );

        if (!$transportReady) {
            $transportError =
                trim(
                    (string) (
                        $preflight[
                            'transport_error'
                        ]
                        ?? ''
                    )
                );

            throw new RuntimeException(
                $transportError !== ''
                    ? sprintf(
                        'Transporte VERI*FACTU no preparado: %s',
                        $transportError
                    )
                    : 'Transporte VERI*FACTU no preparado.'
            );
        }

        /*
         * ==================================================
         * CLAIM ATOMICO
         * ==================================================
         */
        if (
            !$this->repository
                ->claimForSending(
                    $submissionId
                )
        ) {
            throw new RuntimeException(
                'La remisión VERI*FACTU ya está siendo procesada o ha dejado de estar pendiente.'
            );
        }

        $submission =
            $this->repository
                ->findById(
                    $submissionId
                );

        if ($submission === null) {
            throw new RuntimeException(
                'No se pudo recuperar la remisión VERI*FACTU después de reclamarla.'
            );
        }

        $startedAt =
            microtime(
                true
            );

        try {
            /*
             * ==================================================
             * TRANSPORTE REAL
             * ==================================================
             */
            $transportResult =
                $this->transport
                    ->send(
                        (string) $submission[
                            'endpoint'
                        ],
                        (string) $submission[
                            'request_xml'
                        ]
                    );

            $httpStatus =
                (int) (
                    $transportResult[
                        'http_status'
                    ]
                    ?? 0
                );

            $responseXml =
                (string) (
                    $transportResult[
                        'response_xml'
                    ]
                    ?? ''
                );

            $durationMs =
                (int) (
                    $transportResult[
                        'duration_ms'
                    ]
                    ?? 0
                );

            /*
             * ==================================================
             * CLASIFICACION HTTP / SOAP
             * ==================================================
             */
            $classification =
                $this->responseClassifier
                    ->classify(
                        $httpStatus,
                        $responseXml
                    );

            $isFiscalResponse =
                (bool) (
                    $classification[
                        'is_fiscal_response'
                    ]
                    ?? false
                );

            /*
             * ==================================================
             * RESPUESTA NO FISCAL
             * ==================================================
             *
             * Hubo respuesta HTTP, por lo que NO se
             * considera transport_error.
             *
             * La clasificación decide si podrá
             * reintentarse.
             */
            if (!$isFiscalResponse) {
                $retryable =
                    (bool) (
                        $classification[
                            'retryable'
                        ]
                        ?? false
                    );

                $reason =
                    trim(
                        (string) (
                            $classification[
                                'reason'
                            ]
                            ?? 'response_error'
                        )
                    );

                $message =
                    trim(
                        (string) (
                            $classification[
                                'message'
                            ]
                            ?? 'Respuesta VERI*FACTU no procesable fiscalmente.'
                        )
                    );

                $this->repository
                    ->markResponseError(
                        $submissionId,
                        $httpStatus,
                        $responseXml,
                        $durationMs,
                        $retryable,
                        $reason,
                        $message
                    );

                /*
                 * Una respuesta no fiscal reintentable
                 * representa una indisponibilidad técnica
                 * o temporal de la comunicación.
                 *
                 * Desde este momento las nuevas remisiones
                 * se prepararán con Incidencia=S.
                 *
                 * Los errores permanentes de petición
                 * (por ejemplo HTTP 4xx / SOAP Client)
                 * no activan incidencia.
                 */
                if ($retryable) {
                    VerifactuSettings::startIncidence(
                        sprintf(
                            'Respuesta técnica VERI*FACTU: %s%s',
                            $reason !== ''
                                ? $reason
                                : 'response_error',
                            $message !== ''
                                ? ' - ' . $message
                                : ''
                        )
                    );
                }

                $updated =
                    $this->repository
                        ->findById(
                            $submissionId
                        );

                if ($updated === null) {
                    throw new RuntimeException(
                        'La respuesta VERI*FACTU fue clasificada, pero la remisión no pudo recuperarse.'
                    );
                }

                return [
                    'submission' =>
                        $updated,

                    'classification' =>
                        $classification,

                    'fiscal_result' =>
                        null,
                ];
            }

            /*
             * ==================================================
             * RESPUESTA FISCAL PROCESABLE
             * ==================================================
             *
             * El HTTP/SOAP es estructuralmente apto para
             * pasar al procesador fiscal.
             *
             * sending -> sent
             */
            $this->repository
                ->markSent(
                    $submissionId,
                    $httpStatus,
                    $responseXml,
                    $durationMs
                );

            /*
             * ==================================================
             * PROCESAMIENTO FISCAL
             * ==================================================
             */
            $fiscalResult =
                $this->processStoredResponse(
                    $submissionId
                );

            $updated =
                $this->repository
                    ->findById(
                        $submissionId
                    );

            if ($updated === null) {
                throw new RuntimeException(
                    'La remisión fue procesada pero no pudo recuperarse posteriormente.'
                );
            }

            return [
                'submission' =>
                    $updated,

                'classification' =>
                    $classification,

                'fiscal_result' =>
                    $fiscalResult,
            ];
        } catch (Throwable $e) {
            /*
             * ==================================================
             * ERROR ANTES DE OBTENER RESPUESTA HTTP UTILIZABLE
             * ==================================================
             *
             * Solamente se convierte en transport_error si
             * la remisión sigue en estado sending.
             *
             * Si ya pasó a:
             *
             * - sent
             * - response_error
             *
             * no debe reescribirse como fallo de transporte.
             */
            $current =
                $this->repository
                    ->findById(
                        $submissionId
                    );

            $currentStatus =
                is_array(
                    $current
                )
                    ? trim(
                        (string) (
                            $current[
                                'transport_status'
                            ]
                            ?? ''
                        )
                    )
                    : '';

            if ($currentStatus === 'sending') {
                $durationMs =
                    (int) round(
                        (
                            microtime(
                                true
                            )
                            - $startedAt
                        )
                        * 1000
                    );

                try {
                    $this->repository
                        ->markTransportError(
                            $submissionId,
                            $e->getMessage(),
                            $durationMs
                        );

                    /*
                     * No hemos obtenido una respuesta HTTP
                     * utilizable de AEAT.
                     *
                     * Consideramos iniciada una incidencia
                     * de comunicación para las siguientes
                     * remisiones.
                     */
                    VerifactuSettings::startIncidence(
                        sprintf(
                            'Error de transporte VERI*FACTU: %s',
                            $e->getMessage()
                        )
                    );
                } catch (Throwable) {
                    /*
                     * Conservamos siempre la excepción
                     * original.
                     */
                }
            }

            throw $e;
        }
    }
}
