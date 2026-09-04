<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuQueueProcessor
{
    private const DEFAULT_LIMIT = 25;

    private const STALE_SENDING_SECONDS = 300;

    private const DEFAULT_RETRY_SECONDS = 60;

    private VerifactuSubmissionRepository $repository;

    private VerifactuRecordRepository $recordRepository;

    private VerifactuSubmissionPreparer $preparer;

    private VerifactuSubmissionSender $sender;

    public function __construct()
    {
        $this->repository =
            new VerifactuSubmissionRepository();

        $this->recordRepository =
            new VerifactuRecordRepository();

        $this->preparer =
            new VerifactuSubmissionPreparer();

        $this->sender =
            new VerifactuSubmissionSender();
    }

    /**
     * Ejecuta una pasada de la cola VERI*FACTU.
     *
     * $allowNetwork = false:
     * - recupera sending abandonados;
     * - crea nuevos attempts para retries vencidos;
     * - inspecciona pending;
     * - nunca transmite.
     *
     * $allowNetwork = true:
     * - transmite únicamente si el preflight
     *   confirma transport_ready=true;
     * - respeta TiempoEsperaEnvio antes de cada POST.
     *
     * @return array<string, mixed>
     */
    public function process(
        bool $allowNetwork = false,
        int $limit = self::DEFAULT_LIMIT
    ): array {
        $limit =
            max(
                1,
                min(
                    100,
                    $limit
                )
            );

        $result = [
            'allow_network' =>
                $allowNetwork,

            'orphans_found' =>
                0,

            'orphans_recovered' =>
                0,

            'orphan_errors' =>
                [],

            'stale_found' =>
                0,

            'stale_recovered' =>
                0,

            'stale_errors' =>
                [],

            'retries_found' =>
                0,

            'retries_created' =>
                0,

            'retry_errors' =>
                [],

            'pending_found' =>
                0,

            'preflight_ready' =>
                0,

            'preflight_blocked' =>
                0,

            /*
             * TiempoEsperaEnvio AEAT.
             */
            'aeat_wait_blocked' =>
                false,

            'aeat_wait_submission_id' =>
                null,

            'aeat_wait_seconds' =>
                0,

            'aeat_wait_remaining_seconds' =>
                0,

            'aeat_wait_until' =>
                null,

            'sent' =>
                0,

            'send_errors' =>
                [],

            'network_calls' =>
                0,
        ];

        /*
         * =============================================
         * 0. RECUPERAR REGISTROS HUERFANOS
         * =============================================
         *
         * Un registro puede haberse persistido correctamente
         * y haberse producido un fallo antes de crear su
         * primera submission.
         *
         * La recuperación es segura porque solamente
         * seleccionamos registros que no tienen ninguna
         * submission asociada.
         */
        $orphans =
            $this->recordRepository
                ->findOrphanedGeneratedRecords(
                    $limit
                );

        $result['orphans_found'] =
            count(
                $orphans
            );

        foreach ($orphans as $record) {
            $recordId =
                $record->getId();

            if ($recordId <= 0) {
                continue;
            }

            try {
                /*
                 * prepare() debe conservar la idempotencia:
                 * si otra ejecución creó la submission entre
                 * la consulta y este punto, no debe producirse
                 * una segunda remisión activa.
                 */
                $this->preparer
                    ->prepare(
                        $recordId
                    );

                $result[
                    'orphans_recovered'
                ]++;
            } catch (Throwable $e) {
                $result[
                    'orphan_errors'
                ][] = [
                    'record_id' =>
                        $recordId,

                    'message' =>
                        $e->getMessage(),
                ];
            }
        }
        /*
         * =============================================
         * 1. RECUPERAR sending ABANDONADOS
         * =============================================
         */
        $stale =
            $this->repository
                ->findStaleSending(
                    self::STALE_SENDING_SECONDS,
                    $limit
                );

        $result['stale_found'] =
            count(
                $stale
            );

        foreach ($stale as $submission) {
            $submissionId =
                (int) (
                    $submission['id']
                    ?? 0
                );

            if ($submissionId <= 0) {
                continue;
            }

            try {
                $recovered =
                    $this->repository
                        ->markStaleSendingRetryable(
                            $submissionId,
                            self::DEFAULT_RETRY_SECONDS
                        );

                if ($recovered) {
                    $result[
                        'stale_recovered'
                    ]++;
                }
            } catch (Throwable $e) {
                $result[
                    'stale_errors'
                ][] = [
                    'submission_id' =>
                        $submissionId,

                    'message' =>
                        $e->getMessage(),
                ];
            }
        }

        /*
         * =============================================
         * 2. CREAR NUEVOS ATTEMPTS DE RETRIES VENCIDOS
         * =============================================
         */
        $dueRetries =
            $this->repository
                ->findDueRetries(
                    $limit
                );

        $result['retries_found'] =
            count(
                $dueRetries
            );

        foreach ($dueRetries as $submission) {
            $submissionId =
                (int) (
                    $submission['id']
                    ?? 0
                );

            if ($submissionId <= 0) {
                continue;
            }

            try {
                $this->preparer
                    ->retry(
                        $submissionId
                    );

                $result[
                    'retries_created'
                ]++;
            } catch (Throwable $e) {
                $result[
                    'retry_errors'
                ][] = [
                    'submission_id' =>
                        $submissionId,

                    'message' =>
                        $e->getMessage(),
                ];
            }
        }

        /*
         * =============================================
         * 3. PROCESAR PENDING
         * =============================================
         */
        $pending =
            $this->repository
                ->findPending(
                    $limit
                );

        $result['pending_found'] =
            count(
                $pending
            );

        foreach ($pending as $submission) {
            $submissionId =
                (int) (
                    $submission['id']
                    ?? 0
                );

            if ($submissionId <= 0) {
                continue;
            }

            /*
             * ==================================================
             * TIEMPOESPERAENVIO
             * ==================================================
             *
             * Se comprueba antes de CADA posible envío.
             *
             * Esto es importante porque el primer envío de
             * esta misma pasada puede recibir un nuevo valor
             * TiempoEsperaEnvio y bloquear los siguientes.
             *
             * El mantenimiento de stale/retries ya se ha
             * realizado antes de llegar aquí.
             */
            if ($allowNetwork) {
                try {
                    $waitState =
                        $this->repository
                            ->getAeatTransportWaitState();

                    if (
                        (bool) (
                            $waitState[
                                'blocked'
                            ]
                            ?? false
                        )
                    ) {
                        $result[
                            'aeat_wait_blocked'
                        ] =
                            true;

                        $result[
                            'aeat_wait_submission_id'
                        ] =
                            $waitState[
                                'submission_id'
                            ]
                            ?? null;

                        $result[
                            'aeat_wait_seconds'
                        ] =
                            (int) (
                                $waitState[
                                    'wait_seconds'
                                ]
                                ?? 0
                            );

                        $result[
                            'aeat_wait_remaining_seconds'
                        ] =
                            (int) (
                                $waitState[
                                    'remaining_seconds'
                                ]
                                ?? 0
                            );

                        $result[
                            'aeat_wait_until'
                        ] =
                            $waitState[
                                'wait_until'
                            ]
                            ?? null;

                        /*
                         * No seguimos recorriendo pending.
                         *
                         * No tendría sentido ejecutar preflight
                         * sobre más submissions si sabemos que
                         * ninguna puede transmitirse todavía.
                         */
                        break;
                    }
                } catch (Throwable $e) {
                    /*
                     * Un error calculando la espera debe
                     * bloquear la red por seguridad.
                     */
                    $result[
                        'aeat_wait_blocked'
                    ] =
                        true;

                    $result[
                        'send_errors'
                    ][] = [
                        'submission_id' =>
                            $submissionId,

                        'stage' =>
                            'aeat_wait',

                        'message' =>
                            $e->getMessage(),
                    ];

                    break;
                }
            }

            $transportReady =
                false;

            try {
                $preflight =
                    $this->sender
                        ->preflight(
                            $submissionId
                        );

                $transportReady =
                    (bool) (
                        $preflight[
                            'transport_ready'
                        ]
                        ?? false
                    );

                if ($transportReady) {
                    $result[
                        'preflight_ready'
                    ]++;
                } else {
                    $result[
                        'preflight_blocked'
                    ]++;
                }
            } catch (Throwable $e) {
                $result[
                    'preflight_blocked'
                ]++;

                $result[
                    'send_errors'
                ][] = [
                    'submission_id' =>
                        $submissionId,

                    'stage' =>
                        'preflight',

                    'message' =>
                        $e->getMessage(),
                ];

                continue;
            }

            /*
             * Nunca enviamos:
             *
             * - si process(false);
             * - o si el transporte no está preparado.
             */
            if (
                !$allowNetwork
                || !$transportReady
            ) {
                continue;
            }

            try {
                /*
                 * Solo contabilizamos llamada de red
                 * cuando efectivamente vamos a ejecutar
                 * sender->send().
                 */
                $result[
                    'network_calls'
                ]++;

                $this->sender
                    ->send(
                        $submissionId
                    );

                $result[
                    'sent'
                ]++;
            } catch (Throwable $e) {
                $result[
                    'send_errors'
                ][] = [
                    'submission_id' =>
                        $submissionId,

                    'stage' =>
                        'send',

                    'message' =>
                        $e->getMessage(),
                ];
            }
        }

        return $result;
    }
}
