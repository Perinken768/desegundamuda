<?php

declare(strict_types=1);

namespace DSM\Directos\Direct;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectItemRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table =
            $wpdb->prefix
            . 'dsm_live_items';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByLiveId(
        int $liveId
    ): array {
        global $wpdb;

        if ($liveId <= 0) {
            return [];
        }

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT
                        id,
                        live_id,
                        source_type,
                        advertisement_id,
                        product_id,
                        variant_id,
                        position,
                        live_number,
                        allocated_quantity,
                        status,
                        created_at,
                        updated_at
                    FROM {$this->table}
                    WHERE live_id = %d
                    ORDER BY
                        position ASC,
                        live_number ASC,
                        id ASC
                    ",
                    $liveId
                ),
                ARRAY_A
            );

        return is_array($rows)
            ? $rows
            : [];
    }

    public function hasAdvertisement(
        int $liveId,
        int $advertisementId
    ): bool {
        global $wpdb;

        if (
            $liveId <= 0
            || $advertisementId <= 0
        ) {
            return false;
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COUNT(*)
                FROM {$this->table}
                WHERE live_id = %d
                  AND source_type = 'advertisement'
                  AND advertisement_id = %d
                ",
                $liveId,
                $advertisementId
            )
        ) > 0;
    }

    public function hasVariant(
        int $liveId,
        int $variantId
    ): bool {
        global $wpdb;

        if (
            $liveId <= 0
            || $variantId <= 0
        ) {
            return false;
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COUNT(*)
                FROM {$this->table}
                WHERE live_id = %d
                  AND source_type = 'inventory'
                  AND variant_id = %d
                ",
                $liveId,
                $variantId
            )
        ) > 0;
    }

    public function addAdvertisement(
        int $liveId,
        int $advertisementId
    ): void {
        if (
            $liveId <= 0
            || $advertisementId <= 0
        ) {
            throw new RuntimeException(
                'No se pudo identificar el anuncio.'
            );
        }

        if (
            $this->hasAdvertisement(
                $liveId,
                $advertisementId
            )
        ) {
            return;
        }

        $this->insert(
            liveId:
                $liveId,

            sourceType:
                'advertisement',

            advertisementId:
                $advertisementId,

            productId:
                null,

            variantId:
                null
        );
    }

    public function removeAdvertisement(
        int $liveId,
        int $advertisementId
    ): void {
        global $wpdb;

        if (
            $liveId <= 0
            || $advertisementId <= 0
        ) {
            return;
        }

        $deleted =
            $wpdb->delete(
                $this->table,
                [
                    'live_id' =>
                        $liveId,

                    'source_type' =>
                        'advertisement',

                    'advertisement_id' =>
                        $advertisementId,
                ],
                [
                    '%d',
                    '%s',
                    '%d',
                ]
            );

        if ($deleted === false) {
            throw new RuntimeException(
                'No se pudo quitar el anuncio del directo.'
            );
        }

        $this->renumber(
            $liveId
        );
    }

    public function addVariant(
        int $liveId,
        int $productId,
        int $variantId
    ): void {
        if (
            $liveId <= 0
            || $productId <= 0
            || $variantId <= 0
        ) {
            throw new RuntimeException(
                'No se pudo identificar la variante.'
            );
        }

        if (
            $this->hasVariant(
                $liveId,
                $variantId
            )
        ) {
            return;
        }

        $this->insert(
            liveId:
                $liveId,

            sourceType:
                'inventory',

            advertisementId:
                null,

            productId:
                $productId,

            variantId:
                $variantId
        );
    }

    public function removeVariant(
        int $liveId,
        int $variantId
    ): void {
        global $wpdb;

        if (
            $liveId <= 0
            || $variantId <= 0
        ) {
            return;
        }

        $deleted =
            $wpdb->delete(
                $this->table,
                [
                    'live_id' =>
                        $liveId,

                    'source_type' =>
                        'inventory',

                    'variant_id' =>
                        $variantId,
                ],
                [
                    '%d',
                    '%s',
                    '%d',
                ]
            );

        if ($deleted === false) {
            throw new RuntimeException(
                'No se pudo quitar la variante del directo.'
            );
        }

        $this->renumber(
            $liveId
        );
    }

    private function insert(
        int $liveId,
        string $sourceType,
        ?int $advertisementId,
        ?int $productId,
        ?int $variantId
    ): void {
        global $wpdb;

        $nextPosition =
            $this->nextPosition(
                $liveId
            );

        $now =
            current_time(
                'mysql',
                true
            );

        $inserted =
            $wpdb->insert(
                $this->table,
                [
                    'live_id' =>
                        $liveId,

                    'source_type' =>
                        $sourceType,

                    'advertisement_id' =>
                        $advertisementId,

                    'product_id' =>
                        $productId,

                    'variant_id' =>
                        $variantId,

                    'position' =>
                        $nextPosition,

                    'live_number' =>
                        $nextPosition,

                    /*
                     * Compatibilidad con esquema actual.
                     *
                     * Para inventario NO representa una
                     * cantidad reservada para el directo.
                     */
                    'allocated_quantity' =>
                        1,

                    'status' =>
                        'available',

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ],
                [
                    '%d',
                    '%s',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                'No se pudo añadir el elemento al directo: '
                . $wpdb->last_error
            );
        }
    }

    private function nextPosition(
        int $liveId
    ): int {
        global $wpdb;

        return max(
            1,
            (int) $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COALESCE(MAX(position), 0) + 1
                    FROM {$this->table}
                    WHERE live_id = %d
                    ",
                    $liveId
                )
            )
        );
    }

    private function renumber(
        int $liveId
    ): void {
        global $wpdb;

        $ids =
            $wpdb->get_col(
                $wpdb->prepare(
                    "
                    SELECT id
                    FROM {$this->table}
                    WHERE live_id = %d
                    ORDER BY
                        position ASC,
                        id ASC
                    ",
                    $liveId
                )
            );

        if (!is_array($ids)) {
            return;
        }

        $position = 1;

        foreach ($ids as $id) {
            $wpdb->update(
                $this->table,
                [
                    'position' =>
                        $position,

                    'live_number' =>
                        $position,
                ],
                [
                    'id' =>
                        (int) $id,
                ],
                [
                    '%d',
                    '%d',
                ],
                [
                    '%d',
                ]
            );

            $position++;
        }
    }
}
