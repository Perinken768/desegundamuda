<?php

declare(strict_types=1);

namespace DSM\Publicidad\Advertising;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class AdvertisingBannerRepository
{
    private readonly string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_advertising_banners';
    }

    /**
     * Devuelve únicamente los banners propiedad
     * de un cliente DSM.
     *
     * @return array<int, AdvertisingBanner>
     */
    public function findByCustomer(
        int $customerId
    ): array {
        global $wpdb;

        if ($customerId <= 0) {
            return [];
        }

        $sql =
            $wpdb->prepare(
                "
                SELECT *
                FROM {$this->tableName}
                WHERE customer_id = %d
                ORDER BY
                    created_at DESC,
                    id DESC
                ",
                $customerId
            );

        $rows =
            $wpdb->get_results(
                $sql,
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $banners = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $banners[] =
                AdvertisingBanner::fromArray(
                    $row
                );
        }

        return $banners;
    }

    public function belongsToCustomer(
        int $bannerId,
        int $customerId
    ): bool {
        global $wpdb;

        if (
            $bannerId <= 0
            || $customerId <= 0
        ) {
            return false;
        }

        $value =
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT id
                    FROM {$this->tableName}
                    WHERE id = %d
                      AND customer_id = %d
                    LIMIT 1
                    ",
                    $bannerId,
                    $customerId
                )
            );

        return $value !== null;
    }

    public function findById(
        int $bannerId
    ): ?AdvertisingBanner {
        global $wpdb;

        if ($bannerId <= 0) {
            return null;
        }

        $sql =
            $wpdb->prepare(
                "
                SELECT *
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1
                ",
                $bannerId
            );

        $row =
            $wpdb->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($row)
            ? AdvertisingBanner::fromArray(
                $row
            )
            : null;
    }

    /**
     * @return array<int, AdvertisingBanner>
     */
    public function findAll(): array
    {
        global $wpdb;

        $rows =
            $wpdb->get_results(
                "
                SELECT *
                FROM {$this->tableName}
                ORDER BY
                    priority DESC,
                    id DESC
                ",
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $banners = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $banners[] =
                AdvertisingBanner::fromArray(
                    $row
                );
        }

        return $banners;
    }

    public function create(
        string $title,
        int $imageAttachmentId,
        string $targetUrl,
        ?int $areaId,
        int $priority,
        string $status,
        ?string $startsAt,
        ?string $endsAt,
        ?int $customerId = null
    ): AdvertisingBanner {
        global $wpdb;

        $title =
            sanitize_text_field(
                trim($title)
            );

        $targetUrl =
            esc_url_raw(
                trim($targetUrl)
            );

        $areaId =
            $areaId !== null
            && $areaId > 0
                ? $areaId
                : null;

        $customerId =
            $customerId !== null
            && $customerId > 0
                ? $customerId
                : null;

        $priority =
            (int) $priority;

        $status =
            sanitize_key(
                $status
            );

        if ($title === '') {
            throw new RuntimeException(
                'El título es obligatorio.'
            );
        }

        if ($imageAttachmentId <= 0) {
            throw new RuntimeException(
                'La imagen es obligatoria.'
            );
        }

        if (!wp_attachment_is_image(
            $imageAttachmentId
        )) {
            throw new RuntimeException(
                'El adjunto seleccionado no es una imagen válida.'
            );
        }

        if ($targetUrl === '') {
            throw new RuntimeException(
                'La URL de destino es obligatoria.'
            );
        }

        if (
            !in_array(
                $status,
                [
                    'draft',
                    'active',
                    'inactive',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El estado del banner no es válido.'
            );
        }

        $now =
            current_time(
                'mysql',
                true
            );

        $result =
            $wpdb->insert(
                $this->tableName,
                [
                    'customer_id' =>
                        $customerId,

                    'title' =>
                        $title,

                    'image_attachment_id' =>
                        $imageAttachmentId,

                    'target_url' =>
                        $targetUrl,

                    'area_id' =>
                        $areaId,

                    'priority' =>
                        $priority,

                    'status' =>
                        $status,

                    'starts_at' =>
                        $startsAt,

                    'ends_at' =>
                        $endsAt,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ],
                [
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear el banner.'
            );
        }

        $bannerId =
            (int) $wpdb->insert_id;

        return $this->findById(
            $bannerId
        )
        ?? throw new RuntimeException(
            'El banner se creó pero no pudo recuperarse.'
        );
    }

    public function update(
        int $bannerId,
        string $title,
        int $imageAttachmentId,
        string $targetUrl,
        ?int $areaId,
        int $priority,
        string $status,
        ?string $startsAt,
        ?string $endsAt
    ): AdvertisingBanner {
        global $wpdb;

        $existing =
            $this->findById(
                $bannerId
            );

        if ($existing === null) {
            throw new RuntimeException(
                'No se encontró el banner.'
            );
        }

        $title =
            sanitize_text_field(
                trim($title)
            );

        $targetUrl =
            esc_url_raw(
                trim($targetUrl)
            );

        $areaId =
            $areaId !== null
            && $areaId > 0
                ? $areaId
                : null;

        $status =
            sanitize_key(
                $status
            );

        if ($title === '') {
            throw new RuntimeException(
                'El título es obligatorio.'
            );
        }

        if (
            $imageAttachmentId <= 0
            || !wp_attachment_is_image(
                $imageAttachmentId
            )
        ) {
            throw new RuntimeException(
                'La imagen seleccionada no es válida.'
            );
        }

        if ($targetUrl === '') {
            throw new RuntimeException(
                'La URL de destino es obligatoria.'
            );
        }

        if (
            !in_array(
                $status,
                [
                    'draft',
                    'active',
                    'inactive',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El estado del banner no es válido.'
            );
        }

        $result =
            $wpdb->update(
                $this->tableName,
                [
                    'title' =>
                        $title,

                    'image_attachment_id' =>
                        $imageAttachmentId,

                    'target_url' =>
                        $targetUrl,

                    'area_id' =>
                        $areaId,

                    'priority' =>
                        $priority,

                    'status' =>
                        $status,

                    'starts_at' =>
                        $startsAt,

                    'ends_at' =>
                        $endsAt,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $bannerId,
                ],
                [
                    '%s',
                    '%d',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                ],
                [
                    '%d',
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo actualizar el banner.'
            );
        }

        return $this->findById(
            $bannerId
        )
        ?? throw new RuntimeException(
            'El banner fue actualizado pero no pudo recuperarse.'
        );
    }

    public function delete(
        int $bannerId
    ): void {
        global $wpdb;

        if ($bannerId <= 0) {
            throw new RuntimeException(
                'El identificador del banner no es válido.'
            );
        }

        $result =
            $wpdb->delete(
                $this->tableName,
                [
                    'id' =>
                        $bannerId,
                ],
                [
                    '%d',
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo eliminar el banner.'
            );
        }
    }

    /**
     * Devuelve el primer banner vigente aplicable.
     *
     * Se mantiene por compatibilidad con consumidores
     * que únicamente necesitan una publicidad.
     */
    public function findCurrentForArea(
        int $areaId = 0
    ): ?AdvertisingBanner {
        $banners =
            $this->findCurrentForAreaAll(
                $areaId
            );

        return $banners[0]
            ?? null;
    }

    /**
     * Devuelve todas las publicidades vigentes aplicables
     * al contexto territorial.
     *
     * Con isla:
     * - devuelve todas las publicidades válidas de esa isla;
     * - si no existe ninguna, utiliza las globales.
     *
     * Sin isla:
     * - devuelve publicidad válida de cualquier territorio.
     *
     * @return array<int, AdvertisingBanner>
     */
    public function findCurrentForAreaAll(
        int $areaId = 0
    ): array {
        if ($areaId > 0) {
            $banners =
                $this->findEligibleCurrentBanners(
                    areaId:
                        $areaId,

                    allAreas:
                        false
                );

            if ($banners !== []) {
                return $banners;
            }

            return $this->findEligibleCurrentBanners(
                areaId:
                    null,

                allAreas:
                    false
            );
        }

        return $this->findEligibleCurrentBanners(
            areaId:
                null,

            allAreas:
                true
        );
    }

    /**
     * @return array<int, AdvertisingBanner>
     */
    private function findEligibleCurrentBanners(
        ?int $areaId,
        bool $allAreas
    ): array {
        global $wpdb;

        $now =
            current_time(
                'mysql',
                true
            );

        if ($allAreas) {
            $sql =
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->tableName}
                    WHERE status = 'active'
                      AND (
                            starts_at IS NULL
                            OR starts_at <= %s
                      )
                      AND (
                            ends_at IS NULL
                            OR ends_at >= %s
                      )
                    ORDER BY
                        priority DESC,
                        id DESC
                    LIMIT 100
                    ",
                    $now,
                    $now
                );
        } elseif ($areaId !== null) {
            $sql =
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->tableName}
                    WHERE status = 'active'
                      AND area_id = %d
                      AND (
                            starts_at IS NULL
                            OR starts_at <= %s
                      )
                      AND (
                            ends_at IS NULL
                            OR ends_at >= %s
                      )
                    ORDER BY
                        priority DESC,
                        id DESC
                    LIMIT 100
                    ",
                    $areaId,
                    $now,
                    $now
                );
        } else {
            $sql =
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->tableName}
                    WHERE status = 'active'
                      AND area_id IS NULL
                      AND (
                            starts_at IS NULL
                            OR starts_at <= %s
                      )
                      AND (
                            ends_at IS NULL
                            OR ends_at >= %s
                      )
                    ORDER BY
                        priority DESC,
                        id DESC
                    LIMIT 100
                    ",
                    $now,
                    $now
                );
        }

        $rows =
            $wpdb->get_results(
                $sql,
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $banners = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $banner =
                AdvertisingBanner::fromArray(
                    $row
                );

            /*
             * La publicidad administrativa no necesita
             * una suscripción.
             */
            if ($banner->isAdministrative()) {
                $banners[] =
                    $banner;

                continue;
            }

            $customerId =
                $banner->getCustomerId();

            if (
                $customerId === null
                || $customerId <= 0
            ) {
                continue;
            }

            /*
             * La publicidad de un cliente únicamente puede
             * participar mientras conserve advertising.
             */
            $hasAdvertising =
                (bool) apply_filters(
                    'dsm_customer_has_advertising',
                    false,
                    $customerId
                );

            if (!$hasAdvertising) {
                continue;
            }

            $banners[] =
                $banner;
        }

        return $banners;
    }
}
