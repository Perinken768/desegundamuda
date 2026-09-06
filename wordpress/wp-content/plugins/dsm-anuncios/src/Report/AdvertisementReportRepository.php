<?php

declare(strict_types=1);

namespace DSM\Anuncios\Report;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class AdvertisementReportRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table =
            $wpdb->prefix
            . 'dsm_ad_reports';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(
        array $data
    ): int {
        global $wpdb;

        $advertisementId =
            max(
                0,
                (int) (
                    $data['advertisement_id']
                    ?? 0
                )
            );

        $reporterCustomerId =
            max(
                0,
                (int) (
                    $data['reporter_customer_id']
                    ?? 0
                )
            );

        $reportedCustomerId =
            max(
                0,
                (int) (
                    $data['reported_customer_id']
                    ?? 0
                )
            );

        $reasonCode =
            sanitize_key(
                (string) (
                    $data['reason_code']
                    ?? ''
                )
            );

        $details =
            trim(
                sanitize_textarea_field(
                    (string) (
                        $data['details']
                        ?? ''
                    )
                )
            );

        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El anuncio denunciado no es válido.'
            );
        }

        if ($reporterCustomerId <= 0) {
            throw new RuntimeException(
                'El cliente denunciante no es válido.'
            );
        }

        if ($reportedCustomerId <= 0) {
            throw new RuntimeException(
                'El propietario del anuncio no es válido.'
            );
        }

        if (
            !AdvertisementReport::isValidReason(
                $reasonCode
            )
        ) {
            throw new RuntimeException(
                'El motivo de denuncia no es válido.'
            );
        }

        if (
            $this->existsForReporter(
                $advertisementId,
                $reporterCustomerId
            )
        ) {
            throw new RuntimeException(
                'Ya has denunciado este anuncio.'
            );
        }

        $now =
            current_time(
                'mysql',
                true
            );

        $inserted =
            $wpdb->insert(
                $this->table,
                [
                    'advertisement_id' =>
                        $advertisementId,

                    'reporter_customer_id' =>
                        $reporterCustomerId,

                    'reported_customer_id' =>
                        $reportedCustomerId,

                    'reason_code' =>
                        $reasonCode,

                    'details' =>
                        $details !== ''
                            ? $details
                            : null,

                    'status' =>
                        AdvertisementReport::
                            STATUS_PENDING,

                    'admin_notes' =>
                        null,

                    'reviewed_by_user_id' =>
                        null,

                    'reviewed_at' =>
                        null,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ],
                [
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                'No se pudo registrar la denuncia: '
                . $wpdb->last_error
            );
        }

        $reportId =
            (int) $wpdb->insert_id;

        if ($reportId <= 0) {
            throw new RuntimeException(
                'La denuncia se creó sin un identificador válido.'
            );
        }

        return $reportId;
    }

    public function existsForReporter(
        int $advertisementId,
        int $reporterCustomerId
    ): bool {
        global $wpdb;

        if (
            $advertisementId <= 0
            || $reporterCustomerId <= 0
        ) {
            return false;
        }

        $reportId =
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT id
                    FROM {$this->table}
                    WHERE advertisement_id = %d
                      AND reporter_customer_id = %d
                    LIMIT 1
                    ",
                    $advertisementId,
                    $reporterCustomerId
                )
            );

        return (int) $reportId > 0;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(
        int $reportId
    ): ?array {
        global $wpdb;

        if ($reportId <= 0) {
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
                    $reportId
                ),
                ARRAY_A
            );

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * Devuelve los anuncios agrupados según el estado
     * administrativo de sus denuncias.
     *
     * Buckets:
     *
     * - active:
     *   existe al menos una denuncia pending/reviewing.
     *
     * - dismissed:
     *   no existen activas y existe alguna dismissed.
     *
     * - actioned:
     *   no existen activas y existe alguna actioned.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAdvertisementGroups(
        string $bucket = 'active',
        int $limit = 50,
        int $offset = 0
    ): array {
        global $wpdb;

        $bucket =
            sanitize_key(
                $bucket
            );

        if (
            !in_array(
                $bucket,
                [
                    'active',
                    'dismissed',
                    'actioned',
                ],
                true
            )
        ) {
            $bucket = 'active';
        }

        $limit =
            max(
                1,
                min(
                    200,
                    $limit
                )
            );

        $offset =
            max(
                0,
                $offset
            );

        $adsTable =
            $wpdb->prefix
            . 'dsm_ads';

        $activeCondition = "
            SUM(
                CASE
                    WHEN reports.status IN (
                        'pending',
                        'reviewing'
                    )
                    THEN 1
                    ELSE 0
                END
            )
        ";

        $actionedCondition = "
            SUM(
                CASE
                    WHEN reports.status = 'actioned'
                    THEN 1
                    ELSE 0
                END
            )
        ";

        $dismissedCondition = "
            SUM(
                CASE
                    WHEN reports.status = 'dismissed'
                    THEN 1
                    ELSE 0
                END
            )
        ";

        $having =
            match ($bucket) {
                'actioned' =>
                    "{$activeCondition} = 0
                    AND {$actionedCondition} > 0",

                'dismissed' =>
                    "{$activeCondition} = 0
                    AND {$actionedCondition} = 0
                    AND {$dismissedCondition} > 0",

                default =>
                    "{$activeCondition} > 0",
            };

        $sql =
            $wpdb->prepare(
                "
                SELECT
                    advertisements.id
                        AS advertisement_id,

                    advertisements.customer_id
                        AS reported_customer_id,

                    advertisements.title,
                    advertisements.slug,
                    advertisements.status
                        AS advertisement_status,

                    COUNT(reports.id)
                        AS total_reports,

                    {$activeCondition}
                        AS active_reports,

                    {$dismissedCondition}
                        AS dismissed_reports,

                    {$actionedCondition}
                        AS actioned_reports,

                    MIN(reports.created_at)
                        AS first_reported_at,

                    MAX(reports.created_at)
                        AS last_reported_at

                FROM {$this->table} reports

                INNER JOIN {$adsTable} advertisements
                    ON advertisements.id =
                        reports.advertisement_id

                GROUP BY
                    advertisements.id,
                    advertisements.customer_id,
                    advertisements.title,
                    advertisements.slug,
                    advertisements.status

                HAVING {$having}

                ORDER BY
                    last_reported_at DESC,
                    advertisements.id DESC

                LIMIT %d
                OFFSET %d
                ",
                $limit,
                $offset
            );

        $rows =
            $wpdb->get_results(
                $sql,
                ARRAY_A
            );

        return is_array($rows)
            ? $rows
            : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByAdvertisementId(
        int $advertisementId
    ): array {
        global $wpdb;

        if ($advertisementId <= 0) {
            return [];
        }

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT
                        *
                    FROM {$this->table}
                    WHERE advertisement_id = %d
                    ORDER BY
                        created_at DESC,
                        id DESC
                    ",
                    $advertisementId
                ),
                ARRAY_A
            );

        return is_array($rows)
            ? $rows
            : [];
    }

    public function countActiveGroups(): int
    {
        global $wpdb;

        $result =
            $wpdb->get_var(
                "
                SELECT COUNT(*)
                FROM (
                    SELECT advertisement_id
                    FROM {$this->table}
                    GROUP BY advertisement_id
                    HAVING SUM(
                        CASE
                            WHEN status IN (
                                'pending',
                                'reviewing'
                            )
                            THEN 1
                            ELSE 0
                        END
                    ) > 0
                ) grouped_reports
                "
            );

        return max(
            0,
            (int) $result
        );
    }

    /**
     * Marca todas las denuncias activas de un anuncio
     * como ignoradas/descartadas.
     */
    public function dismissActiveByAdvertisement(
        int $advertisementId,
        int $userId,
        ?string $adminNotes = null
    ): int {
        return $this->resolveActiveByAdvertisement(
            $advertisementId,
            $userId,
            AdvertisementReport::STATUS_DISMISSED,
            $adminNotes
        );
    }

    /**
     * Marca todas las denuncias activas de un anuncio
     * como gestionadas después de tomar una medida.
     */
    public function actionActiveByAdvertisement(
        int $advertisementId,
        int $userId,
        ?string $adminNotes = null
    ): int {
        return $this->resolveActiveByAdvertisement(
            $advertisementId,
            $userId,
            AdvertisementReport::STATUS_ACTIONED,
            $adminNotes
        );
    }

    /**
     * @return int Número de denuncias actualizadas.
     */
    private function resolveActiveByAdvertisement(
        int $advertisementId,
        int $userId,
        string $newStatus,
        ?string $adminNotes
    ): int {
        global $wpdb;

        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El anuncio indicado no es válido.'
            );
        }

        if ($userId <= 0) {
            throw new RuntimeException(
                'El usuario administrador no es válido.'
            );
        }

        if (
            !in_array(
                $newStatus,
                [
                    AdvertisementReport::STATUS_DISMISSED,
                    AdvertisementReport::STATUS_ACTIONED,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El estado final de la denuncia no es válido.'
            );
        }

        $adminNotes =
            trim(
                sanitize_textarea_field(
                    (string) $adminNotes
                )
            );

        $now =
            current_time(
                'mysql',
                true
            );

        $updated =
            $wpdb->query(
                $wpdb->prepare(
                    "
                    UPDATE {$this->table}
                    SET
                        status = %s,
                        admin_notes = %s,
                        reviewed_by_user_id = %d,
                        reviewed_at = %s,
                        updated_at = %s
                    WHERE advertisement_id = %d
                      AND status IN (
                          %s,
                          %s
                      )
                    ",
                    $newStatus,
                    $adminNotes !== ''
                        ? $adminNotes
                        : null,
                    $userId,
                    $now,
                    $now,
                    $advertisementId,
                    AdvertisementReport::STATUS_PENDING,
                    AdvertisementReport::STATUS_REVIEWING
                )
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudieron actualizar las denuncias: '
                . $wpdb->last_error
            );
        }

        return max(
            0,
            (int) $updated
        );
    }


    public function countPendingByAdvertisement(
        int $advertisementId
    ): int {
        global $wpdb;

        if ($advertisementId <= 0) {
            return 0;
        }

        return max(
            0,
            (int) $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$this->table}
                    WHERE advertisement_id = %d
                      AND status IN (
                          %s,
                          %s
                      )
                    ",
                    $advertisementId,
                    AdvertisementReport::
                        STATUS_PENDING,
                    AdvertisementReport::
                        STATUS_REVIEWING
                )
            )
        );
    }
}
