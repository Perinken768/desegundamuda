<?php

declare(strict_types=1);

namespace DSM\Directos\Direct;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectReservationRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_live_reservations';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(
        array $data
    ): int {
        global $wpdb;

        $liveId =
            max(
                0,
                (int) (
                    $data['live_id']
                    ?? 0
                )
            );

        $liveItemId =
            max(
                0,
                (int) (
                    $data['live_item_id']
                    ?? 0
                )
            );

        $buyerCustomerId =
            max(
                0,
                (int) (
                    $data['buyer_customer_id']
                    ?? 0
                )
            );

        $sellerCustomerId =
            max(
                0,
                (int) (
                    $data['seller_customer_id']
                    ?? 0
                )
            );

        $advertisementId =
            self::nullablePositiveInt(
                $data['advertisement_id']
                ?? null
            );

        $productId =
            self::nullablePositiveInt(
                $data['product_id']
                ?? null
            );

        $variantId =
            self::nullablePositiveInt(
                $data['variant_id']
                ?? null
            );

        $sourceReservationId =
            self::nullablePositiveInt(
                $data['source_reservation_id']
                ?? null
            );

        $quantity =
            max(
                1,
                (int) (
                    $data['quantity']
                    ?? 1
                )
            );

        if (
            $liveId <= 0
            || $liveItemId <= 0
            || $buyerCustomerId <= 0
            || $sellerCustomerId <= 0
        ) {
            throw new RuntimeException(
                'Los datos de la reserva del directo no son válidos.'
            );
        }

        if (
            $buyerCustomerId
            === $sellerCustomerId
        ) {
            throw new RuntimeException(
                'No puedes reservar tus propias prendas.'
            );
        }

        if (
            $advertisementId === null
            && (
                $productId === null
                || $variantId === null
            )
        ) {
            throw new RuntimeException(
                'No se pudo identificar la prenda reservada.'
            );
        }

        $now =
            current_time(
                'mysql',
                true
            );

        $inserted =
            $wpdb->insert(
                $this->tableName,
                [
                    'live_id' =>
                        $liveId,

                    'live_item_id' =>
                        $liveItemId,

                    'buyer_customer_id' =>
                        $buyerCustomerId,

                    'seller_customer_id' =>
                        $sellerCustomerId,

                    'advertisement_id' =>
                        $advertisementId,

                    'product_id' =>
                        $productId,

                    'variant_id' =>
                        $variantId,

                    'quantity' =>
                        $quantity,

                    'source_reservation_id' =>
                        $sourceReservationId,

                    'status' =>
                        'reserved',

                    'reserved_at' =>
                        $now,

                    'released_at' =>
                        null,

                    'completed_at' =>
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
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                'No se pudo registrar la reserva en DSM Directos: '
                . $wpdb->last_error
            );
        }

        $reservationId =
            (int) $wpdb->insert_id;

        if ($reservationId <= 0) {
            throw new RuntimeException(
                'La reserva de DSM Directos no obtuvo un identificador válido.'
            );
        }

        return $reservationId;
    }

    public function buyerAlreadyReservedAdvertisement(
        int $buyerCustomerId,
        int $advertisementId
    ): bool {
        global $wpdb;

        if (
            $buyerCustomerId <= 0
            || $advertisementId <= 0
        ) {
            return false;
        }

        $count =
            (int) $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$this->tableName}
                    WHERE buyer_customer_id = %d
                      AND advertisement_id = %d
                      AND status = 'reserved'
                    ",
                    $buyerCustomerId,
                    $advertisementId
                )
            );

        return $count > 0;
    }

    private static function nullablePositiveInt(
        mixed $value
    ): ?int {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $integer =
            (int) $value;

        return $integer > 0
            ? $integer
            : null;
    }
}
