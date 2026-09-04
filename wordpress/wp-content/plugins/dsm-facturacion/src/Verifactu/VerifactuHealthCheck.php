<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuHealthCheck
{
    /**
     * Diagnóstico seguro del subsistema VERI*FACTU.
     *
     * No realiza ninguna llamada de red.
     *
     * @return array<string, mixed>
     */
    public function inspect(): array
    {
        global $wpdb;

        $recordsTable =
            $wpdb->prefix
            . 'dsm_verifactu_records';

        $submissionsTable =
            $wpdb->prefix
            . 'dsm_verifactu_submissions';

        $chainsTable =
            $wpdb->prefix
            . 'dsm_verifactu_chains';

        $settings =
            VerifactuSettings::get();

        $incidence =
            VerifactuSettings::getIncidenceState();

        $submissionRepository =
            new VerifactuSubmissionRepository();

        $waitState =
            $submissionRepository
                ->getAeatTransportWaitState();

        $counts = [
            'records_total' =>
                (int) $wpdb->get_var(
                    "
                    SELECT COUNT(*)
                    FROM {$recordsTable}
                    "
                ),

            'records_pending_aeat' =>
                (int) $wpdb->get_var(
                    "
                    SELECT COUNT(*)
                    FROM {$recordsTable}
                    WHERE aeat_record_status IS NULL
                    "
                ),

            'records_ready_for_submission' =>
                (int) $wpdb->get_var(
                    "
                    SELECT COUNT(*)
                    FROM {$recordsTable}
                    WHERE aeat_record_status IS NULL
                      AND payload_xml IS NOT NULL
                      AND payload_xml <> ''
                    "
                ),

            'records_without_payload' =>
                (int) $wpdb->get_var(
                    "
                    SELECT COUNT(*)
                    FROM {$recordsTable}
                    WHERE aeat_record_status IS NULL
                      AND (
                            payload_xml IS NULL
                            OR payload_xml = ''
                      )
                    "
                ),

            'orphaned_ready' =>
                (int) $wpdb->get_var(
                    "
                    SELECT COUNT(*)
                    FROM {$recordsTable} r
                    WHERE r.aeat_record_status IS NULL
                      AND r.payload_xml IS NOT NULL
                      AND r.payload_xml <> ''
                      AND NOT EXISTS (
                            SELECT 1
                            FROM {$submissionsTable} s
                            WHERE s.record_id = r.id
                      )
                    "
                ),

            'submissions_total' =>
                (int) $wpdb->get_var(
                    "
                    SELECT COUNT(*)
                    FROM {$submissionsTable}
                    "
                ),

            'pending' =>
                (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT COUNT(*)
                        FROM {$submissionsTable}
                        WHERE transport_status = %s
                        ",
                        'pending'
                    )
                ),

            'sending' =>
                (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT COUNT(*)
                        FROM {$submissionsTable}
                        WHERE transport_status = %s
                        ",
                        'sending'
                    )
                ),

            'transport_error' =>
                (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT COUNT(*)
                        FROM {$submissionsTable}
                        WHERE transport_status = %s
                        ",
                        'transport_error'
                    )
                ),

            'response_error' =>
                (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT COUNT(*)
                        FROM {$submissionsTable}
                        WHERE transport_status = %s
                        ",
                        'response_error'
                    )
                ),

            'retryable' =>
                (int) $wpdb->get_var(
                    "
                    SELECT COUNT(*)
                    FROM {$submissionsTable}
                    WHERE retryable = 1
                    "
                ),
        ];

        $chain =
            $wpdb->get_row(
                "
                SELECT
                    id,
                    environment,
                    issuer_tax_id,
                    system_id,
                    installation_id,
                    last_sequence,
                    last_record_id,
                    last_hash
                FROM {$chainsTable}
                ORDER BY id DESC
                LIMIT 1
                ",
                ARRAY_A
            );

        $cronNext =
            wp_next_scheduled(
                VerifactuCronRunner::HOOK
            );

        $certificateStatus =
            VerifactuCertificateConfig::getSafeStatus();

        $enabled =
            VerifactuSettings::isEnabled();

        $environment =
            (string) (
                $settings[
                    'environment'
                ]
                ?? ''
            );

        $readyLocal =
            $environment !== ''
            && $cronNext !== false
            && isset(
                $settings[
                    'installation_id'
                ]
            )
            && trim(
                (string) $settings[
                    'installation_id'
                ]
            ) !== '';

        return [
            'ready_local' =>
                $readyLocal,

            'network_enabled' =>
                $enabled,

            'environment' =>
                $environment,

            'endpoint' =>
                VerifactuSettings::getEndpoint(
                    $settings
                ),

            'system_id' =>
                (string) (
                    $settings[
                        'system_id'
                    ]
                    ?? ''
                ),

            'installation_id' =>
                (string) (
                    $settings[
                        'installation_id'
                    ]
                    ?? ''
                ),

            'certificate_status' =>
                $certificateStatus,

            'certificate_configured' =>
                VerifactuCertificateConfig::isConfigured(),

            'incidence' =>
                $incidence,

            'aeat_wait' =>
                $waitState,

            'queue' =>
                $counts,

            'chain' =>
                is_array($chain)
                    ? $chain
                    : null,

            'cron' => [
                'hook' =>
                    VerifactuCronRunner::HOOK,

                'scheduled' =>
                    $cronNext !== false,

                'next_timestamp' =>
                    $cronNext !== false
                        ? (int) $cronNext
                        : null,

                'next_utc' =>
                    $cronNext !== false
                        ? gmdate(
                            'Y-m-d H:i:s',
                            (int) $cronNext
                        )
                        : null,
            ],

            'php' => [
                'curl' =>
                    extension_loaded(
                        'curl'
                    ),

                'openssl' =>
                    extension_loaded(
                        'openssl'
                    ),

                'dom' =>
                    extension_loaded(
                        'dom'
                    ),
            ],
        ];
    }
}
