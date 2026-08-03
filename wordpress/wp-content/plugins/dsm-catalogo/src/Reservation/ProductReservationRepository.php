<?php

declare(strict_types=1);

namespace DSM\Catalogo\Reservation;

use DateTimeImmutable;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ProductReservationRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_product_reservations';
    }

    public function findById(
        int $reservationId
    ): ?ProductReservation {
        global $wpdb;

        if ($reservationId <= 0) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    product_id,
                    variant_id,
                    store_id,
                    seller_customer_id,
                    buyer_customer_id,
                    conversation_id,
                    external_contact,
                    quantity,
                    status,
                    reserved_at,
                    released_at,
                    completed_at,
                    cancelled_at,
                    expired_at,
                    expires_at,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1",
                $reservationId
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return ProductReservation::fromArray(
            $row
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(
        array $data
    ): int {
        global $wpdb;

        $productId = (int) (
            $data['product_id']
            ?? 0
        );

        $variantId = (int) (
            $data['variant_id']
            ?? 0
        );

        $storeId = (int) (
            $data['store_id']
            ?? 0
        );

        $sellerCustomerId = (int) (
            $data['seller_customer_id']
            ?? 0
        );

        $buyerCustomerId =
            self::nullablePositiveInt(
                $data['buyer_customer_id']
                ?? null
            );

        $conversationId =
            self::nullablePositiveInt(
                $data['conversation_id']
                ?? null
            );

        $externalContact =
            self::normalizeNullableShortText(
                $data['external_contact']
                ?? null,
                190
            );

        $quantity = (int) (
            $data['quantity']
            ?? 0
        );

        $status = sanitize_key(
            (string) (
                $data['status']
                ?? ProductReservationStatus::ACTIVE
            )
        );

        if ($productId <= 0) {
            throw new RuntimeException(
                'El identificador del producto no es válido.'
            );
        }

        if ($variantId <= 0) {
            throw new RuntimeException(
                'El identificador de la variante no es válido.'
            );
        }

        if ($storeId <= 0) {
            throw new RuntimeException(
                'El identificador de la tienda no es válido.'
            );
        }

        if ($sellerCustomerId <= 0) {
            throw new RuntimeException(
                'El identificador del vendedor no es válido.'
            );
        }

        if ($quantity <= 0) {
            throw new RuntimeException(
                'La cantidad reservada debe ser mayor que cero.'
            );
        }

        if (
            !ProductReservationStatus::isValid(
                $status
            )
        ) {
            throw new RuntimeException(
                'El estado de la reserva no es válido.'
            );
        }

        /*
         * Toda reserva nueva debe crearse en estado activo.
         * Los estados finales se alcanzan mediante los métodos
         * específicos del repositorio.
         */
        if (
            $status
            !== ProductReservationStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Una reserva nueva debe crearse en estado activo.'
            );
        }

        if (
            $buyerCustomerId === null
            && $conversationId === null
            && $externalContact === null
        ) {
            throw new RuntimeException(
                'La reserva debe tener un comprador, conversación o contacto externo.'
            );
        }

        $reservedAt =
            self::normalizeDateTime(
                $data['reserved_at']
                ?? null
            )
            ?? current_time(
                'mysql',
                true
            );

        $expiresAt =
            self::normalizeDateTime(
                $data['expires_at']
                ?? null
            );

        if (
            $expiresAt !== null
            && new DateTimeImmutable(
                $expiresAt
            ) < new DateTimeImmutable(
                $reservedAt
            )
        ) {
            throw new RuntimeException(
                'La fecha de caducidad no puede ser anterior a la reserva.'
            );
        }

        $now = current_time(
            'mysql',
            true
        );

        $result = $wpdb->insert(
            $this->tableName,
            [
                'product_id' =>
                    $productId,

                'variant_id' =>
                    $variantId,

                'store_id' =>
                    $storeId,

                'seller_customer_id' =>
                    $sellerCustomerId,

                'buyer_customer_id' =>
                    $buyerCustomerId,

                'conversation_id' =>
                    $conversationId,

                'external_contact' =>
                    $externalContact,

                'quantity' =>
                    $quantity,

                'status' =>
                    ProductReservationStatus::ACTIVE,

                'reserved_at' =>
                    $reservedAt,

                'released_at' =>
                    null,

                'completed_at' =>
                    null,

                'cancelled_at' =>
                    null,

                'expired_at' =>
                    null,

                'expires_at' =>
                    $expiresAt,

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
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear la reserva: %s',
                    $wpdb->last_error
                )
            );
        }

        return (int) $wpdb->insert_id;
    }

    public function markReleased(
        int $reservationId
    ): void {
        $this->close(
            reservationId: $reservationId,
            newStatus:
                ProductReservationStatus::RELEASED,
            dateColumn: 'released_at'
        );
    }

    public function markCompleted(
        int $reservationId
    ): void {
        $this->close(
            reservationId: $reservationId,
            newStatus:
                ProductReservationStatus::COMPLETED,
            dateColumn: 'completed_at'
        );
    }

    public function markCancelled(
        int $reservationId
    ): void {
        $this->close(
            reservationId: $reservationId,
            newStatus:
                ProductReservationStatus::CANCELLED,
            dateColumn: 'cancelled_at'
        );
    }

    public function markExpired(
        int $reservationId,
        ?DateTimeImmutable $expiredAt = null
    ): void {
        $this->close(
            reservationId: $reservationId,
            newStatus:
                ProductReservationStatus::EXPIRED,
            dateColumn: 'expired_at',
            closedAt: $expiredAt
        );
    }

    /**
     * @return array<int, ProductReservation>
     */
    public function findByStore(
        int $storeId,
        int $limit = 100,
        int $offset = 0,
        ?string $status = null
    ): array {
        global $wpdb;

        if ($storeId <= 0) {
            return [];
        }

        $limit = max(
            1,
            min(
                500,
                $limit
            )
        );

        $offset = max(
            0,
            $offset
        );

        $parameters = [
            $storeId,
        ];

        $where = "
            WHERE store_id = %d
        ";

        if (
            $status !== null
            && ProductReservationStatus::isValid(
                $status
            )
        ) {
            $where .= "
                AND status = %s
            ";

            $parameters[] =
                $status;
        }

        $parameters[] =
            $limit;

        $parameters[] =
            $offset;

        $query = $wpdb->prepare(
            "SELECT
                id,
                product_id,
                variant_id,
                store_id,
                seller_customer_id,
                buyer_customer_id,
                conversation_id,
                external_contact,
                quantity,
                status,
                reserved_at,
                released_at,
                completed_at,
                cancelled_at,
                expired_at,
                expires_at,
                created_at,
                updated_at
            FROM {$this->tableName}
            {$where}
            ORDER BY
                reserved_at DESC,
                id DESC
            LIMIT %d
            OFFSET %d",
            ...$parameters
        );

        $rows = $wpdb->get_results(
            $query,
            ARRAY_A
        );

        return $this->hydrateRows(
            $rows
        );
    }

    /**
     * @return array<int, ProductReservation>
     */
    public function findActiveByVariant(
        int $variantId
    ): array {
        global $wpdb;

        if ($variantId <= 0) {
            return [];
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    id,
                    product_id,
                    variant_id,
                    store_id,
                    seller_customer_id,
                    buyer_customer_id,
                    conversation_id,
                    external_contact,
                    quantity,
                    status,
                    reserved_at,
                    released_at,
                    completed_at,
                    cancelled_at,
                    expired_at,
                    expires_at,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE variant_id = %d
                  AND status = %s
                ORDER BY
                    reserved_at ASC,
                    id ASC",
                $variantId,
                ProductReservationStatus::ACTIVE
            ),
            ARRAY_A
        );

        return $this->hydrateRows(
            $rows
        );
    }

    /**
     * @return array<int, ProductReservation>
     */
    public function findExpiredCandidates(
        int $limit = 100,
        ?DateTimeImmutable $moment = null
    ): array {
        global $wpdb;

        $limit = max(
            1,
            min(
                500,
                $limit
            )
        );

        $moment ??= new DateTimeImmutable(
            current_time(
                'mysql',
                true
            )
        );

        $now = $moment->format(
            'Y-m-d H:i:s'
        );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    id,
                    product_id,
                    variant_id,
                    store_id,
                    seller_customer_id,
                    buyer_customer_id,
                    conversation_id,
                    external_contact,
                    quantity,
                    status,
                    reserved_at,
                    released_at,
                    completed_at,
                    cancelled_at,
                    expired_at,
                    expires_at,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE status = %s
                  AND expires_at IS NOT NULL
                  AND expires_at <= %s
                ORDER BY
                    expires_at ASC,
                    id ASC
                LIMIT %d",
                ProductReservationStatus::ACTIVE,
                $now,
                $limit
            ),
            ARRAY_A
        );

        return $this->hydrateRows(
            $rows
        );
    }

    public function countActiveByVariant(
        int $variantId
    ): int {
        global $wpdb;

        if ($variantId <= 0) {
            return 0;
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$this->tableName}
                WHERE variant_id = %d
                  AND status = %s",
                $variantId,
                ProductReservationStatus::ACTIVE
            )
        );
    }

    private function close(
        int $reservationId,
        string $newStatus,
        string $dateColumn,
        ?DateTimeImmutable $closedAt = null
    ): void {
        global $wpdb;

        if ($reservationId <= 0) {
            throw new RuntimeException(
                'El identificador de la reserva no es válido.'
            );
        }

        $allowedTransitions = [
            ProductReservationStatus::RELEASED =>
                'released_at',

            ProductReservationStatus::COMPLETED =>
                'completed_at',

            ProductReservationStatus::CANCELLED =>
                'cancelled_at',

            ProductReservationStatus::EXPIRED =>
                'expired_at',
        ];

        if (
            !array_key_exists(
                $newStatus,
                $allowedTransitions
            )
        ) {
            throw new RuntimeException(
                'El nuevo estado de la reserva no es válido.'
            );
        }

        if (
            $allowedTransitions[$newStatus]
            !== $dateColumn
        ) {
            throw new RuntimeException(
                'La columna de fecha no corresponde con el estado de la reserva.'
            );
        }

        $reservation =
            $this->findById(
                $reservationId
            );

        if ($reservation === null) {
            throw new RuntimeException(
                'No se encontró la reserva.'
            );
        }

        if (!$reservation->isActive()) {
            throw new RuntimeException(
                'La reserva ya está cerrada.'
            );
        }

        $now = (
            $closedAt
            ?? new DateTimeImmutable(
                current_time(
                    'mysql',
                    true
                )
            )
        )->format(
            'Y-m-d H:i:s'
        );

        $updated = $wpdb->update(
            $this->tableName,
            [
                'status' =>
                    $newStatus,

                $dateColumn =>
                    $now,

                'updated_at' =>
                    $now,
            ],
            [
                'id' =>
                    $reservationId,

                'status' =>
                    ProductReservationStatus::ACTIVE,
            ],
            [
                '%s',
                '%s',
                '%s',
            ],
            [
                '%d',
                '%s',
            ]
        );

        if ($updated !== 1) {
            throw new RuntimeException(
                'No se pudo cerrar la reserva.'
            );
        }
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, ProductReservation>
     */
    private function hydrateRows(
        mixed $rows
    ): array {
        if (!is_array($rows)) {
            return [];
        }

        return array_map(
            static fn (
                array $row
            ): ProductReservation =>
                ProductReservation::fromArray(
                    $row
                ),
            $rows
        );
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

    private static function normalizeNullableShortText(
        mixed $value,
        int $maximumLength
    ): ?string {
        if (
            $value === null
            || trim(
                (string) $value
            ) === ''
        ) {
            return null;
        }

        $text = sanitize_text_field(
            (string) $value
        );

        if (
            mb_strlen(
                $text
            ) > $maximumLength
        ) {
            throw new RuntimeException(
                sprintf(
                    'El texto no puede superar los %d caracteres.',
                    $maximumLength
                )
            );
        }

        return $text;
    }

    private static function normalizeDateTime(
        mixed $value
    ): ?string {
        if (
            $value === null
            || trim(
                (string) $value
            ) === ''
        ) {
            return null;
        }

        try {
            return (
                new DateTimeImmutable(
                    (string) $value
                )
            )->format(
                'Y-m-d H:i:s'
            );
        } catch (Throwable) {
            throw new RuntimeException(
                'La fecha indicada no es válida.'
            );
        }
    }
}