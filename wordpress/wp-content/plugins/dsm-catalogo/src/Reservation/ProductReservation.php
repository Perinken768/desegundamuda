<?php

declare(strict_types=1);

namespace DSM\Catalogo\Reservation;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class ProductReservation
{
    public function __construct(
        private readonly int $id,
        private readonly int $productId,
        private readonly int $variantId,
        private readonly int $storeId,
        private readonly int $sellerCustomerId,
        private readonly ?int $buyerCustomerId,
        private readonly ?int $conversationId,
        private readonly ?string $externalContact,
        private readonly int $quantity,
        private readonly string $status,
        private readonly DateTimeImmutable $reservedAt,
        private readonly ?DateTimeImmutable $releasedAt,
        private readonly ?DateTimeImmutable $completedAt,
        private readonly ?DateTimeImmutable $cancelledAt,
        private readonly ?DateTimeImmutable $expiredAt,
        private readonly ?DateTimeImmutable $expiresAt,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la reserva no es válido.'
            );
        }

        if ($this->productId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del producto no es válido.'
            );
        }

        if ($this->variantId <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la variante no es válido.'
            );
        }

        if ($this->storeId <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la tienda no es válido.'
            );
        }

        if ($this->sellerCustomerId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del vendedor no es válido.'
            );
        }

        if (
            $this->buyerCustomerId !== null
            && $this->buyerCustomerId <= 0
        ) {
            throw new InvalidArgumentException(
                'El identificador del comprador no es válido.'
            );
        }

        if (
            $this->conversationId !== null
            && $this->conversationId <= 0
        ) {
            throw new InvalidArgumentException(
                'El identificador de la conversación no es válido.'
            );
        }

        if ($this->quantity <= 0) {
            throw new InvalidArgumentException(
                'La cantidad reservada debe ser mayor que cero.'
            );
        }

        if (
            !ProductReservationStatus::isValid(
                $this->status
            )
        ) {
            throw new InvalidArgumentException(
                'El estado de la reserva no es válido.'
            );
        }

        if (
            $this->buyerCustomerId === null
            && $this->conversationId === null
            && $this->externalContact === null
        ) {
            throw new InvalidArgumentException(
                'La reserva debe tener un comprador, conversación o contacto externo.'
            );
        }

        if (
            $this->expiresAt !== null
            && $this->expiresAt < $this->reservedAt
        ) {
            throw new InvalidArgumentException(
                'La fecha de caducidad no puede ser anterior a la reserva.'
            );
        }

        if (
            $this->expiredAt !== null
            && $this->expiredAt < $this->reservedAt
        ) {
            throw new InvalidArgumentException(
                'La fecha real de expiración no puede ser anterior a la reserva.'
            );
        }

        if (
            $this->status === ProductReservationStatus::EXPIRED
            && $this->expiredAt === null
        ) {
            throw new InvalidArgumentException(
                'Una reserva expirada debe tener fecha real de expiración.'
            );
        }

        if (
            $this->status !== ProductReservationStatus::EXPIRED
            && $this->expiredAt !== null
        ) {
            throw new InvalidArgumentException(
                'Solo una reserva expirada puede tener fecha real de expiración.'
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(
        array $data
    ): self {
        return new self(
            id: (int) (
                $data['id']
                ?? 0
            ),

            productId: (int) (
                $data['product_id']
                ?? 0
            ),

            variantId: (int) (
                $data['variant_id']
                ?? 0
            ),

            storeId: (int) (
                $data['store_id']
                ?? 0
            ),

            sellerCustomerId: (int) (
                $data['seller_customer_id']
                ?? 0
            ),

            buyerCustomerId: self::nullableInt(
                $data['buyer_customer_id']
                ?? null
            ),

            conversationId: self::nullableInt(
                $data['conversation_id']
                ?? null
            ),

            externalContact: self::nullableString(
                $data['external_contact']
                ?? null
            ),

            quantity: (int) (
                $data['quantity']
                ?? 0
            ),

            status: (string) (
                $data['status']
                ?? ''
            ),

            reservedAt: self::requiredDateTime(
                $data['reserved_at']
                ?? null,
                'reserved_at'
            ),

            releasedAt: self::nullableDateTime(
                $data['released_at']
                ?? null
            ),

            completedAt: self::nullableDateTime(
                $data['completed_at']
                ?? null
            ),

            cancelledAt: self::nullableDateTime(
                $data['cancelled_at']
                ?? null
            ),

            expiredAt: self::nullableDateTime(
                $data['expired_at']
                ?? null
            ),

            expiresAt: self::nullableDateTime(
                $data['expires_at']
                ?? null
            ),

            createdAt: self::requiredDateTime(
                $data['created_at']
                ?? null,
                'created_at'
            ),

            updatedAt: self::requiredDateTime(
                $data['updated_at']
                ?? null,
                'updated_at'
            )
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getVariantId(): int
    {
        return $this->variantId;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function getSellerCustomerId(): int
    {
        return $this->sellerCustomerId;
    }

    public function getBuyerCustomerId(): ?int
    {
        return $this->buyerCustomerId;
    }

    public function getConversationId(): ?int
    {
        return $this->conversationId;
    }

    public function getExternalContact(): ?string
    {
        return $this->externalContact;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReservedAt(): DateTimeImmutable
    {
        return $this->reservedAt;
    }

    public function getReleasedAt(): ?DateTimeImmutable
    {
        return $this->releasedAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function getExpiredAt(): ?DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return ProductReservationStatus::isOpen(
            $this->status
        );
    }

    public function isClosed(): bool
    {
        return ProductReservationStatus::isClosed(
            $this->status
        );
    }

    public function isExpired(): bool
    {
        return $this->status
            === ProductReservationStatus::EXPIRED;
    }

    public function canBeReleased(): bool
    {
        return ProductReservationStatus::canBeReleased(
            $this->status
        );
    }

    public function canBeCompleted(): bool
    {
        return ProductReservationStatus::canBeCompleted(
            $this->status
        );
    }

    public function canBeCancelled(): bool
    {
        return ProductReservationStatus::canBeCancelled(
            $this->status
        );
    }

    public function canExpire(): bool
    {
        return ProductReservationStatus::canExpire(
            $this->status
        );
    }

    public function hasBuyerCustomer(): bool
    {
        return $this->buyerCustomerId !== null;
    }

    public function hasConversation(): bool
    {
        return $this->conversationId !== null;
    }

    public function hasExternalContact(): bool
    {
        return $this->externalContact !== null;
    }

    public function hasExpiration(): bool
    {
        return $this->expiresAt !== null;
    }

    public function hasExpiredAt(): bool
    {
        return $this->expiredAt !== null;
    }

    public function isExpiredAt(
        DateTimeImmutable $moment
    ): bool {
        return $this->isActive()
            && $this->expiresAt !== null
            && $this->expiresAt <= $moment;
    }

    public function belongsToStore(
        int $storeId
    ): bool {
        return $this->storeId === $storeId;
    }

    public function belongsToSeller(
        int $customerId
    ): bool {
        return $this->sellerCustomerId
            === $customerId;
    }

    private static function nullableInt(
        mixed $value
    ): ?int {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $integer = (int) $value;

        return $integer > 0
            ? $integer
            : null;
    }

    private static function nullableString(
        mixed $value
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        return trim(
            (string) $value
        );
    }

    private static function nullableDateTime(
        mixed $value
    ): ?DateTimeImmutable {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        try {
            return new DateTimeImmutable(
                (string) $value
            );
        } catch (\Throwable) {
            throw new InvalidArgumentException(
                'La fecha indicada no es válida.'
            );
        }
    }

    private static function requiredDateTime(
        mixed $value,
        string $field
    ): DateTimeImmutable {
        if (
            $value === null
            || $value === ''
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'El campo %s es obligatorio.',
                    $field
                )
            );
        }

        try {
            return new DateTimeImmutable(
                (string) $value
            );
        } catch (\Throwable) {
            throw new InvalidArgumentException(
                sprintf(
                    'El campo %s no contiene una fecha válida.',
                    $field
                )
            );
        }
    }
}