<?php

declare(strict_types=1);

namespace DSM\Catalogo\Inventory;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class StockMovement
{
    public function __construct(
        private readonly int $id,
        private readonly int $productId,
        private readonly int $variantId,
        private readonly int $storeId,
        private readonly string $movementType,
        private readonly int $quantityDelta,
        private readonly int $reservedDelta,
        private readonly int $stockQuantityBefore,
        private readonly int $stockQuantityAfter,
        private readonly int $stockReservedBefore,
        private readonly int $stockReservedAfter,
        private readonly ?string $referenceType,
        private readonly ?int $referenceId,
        private readonly ?int $customerId,
        private readonly ?int $userId,
        private readonly ?string $notes,
        private readonly DateTimeImmutable $createdAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador del movimiento no es válido.'
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

        if (
            !StockMovementType::isValid(
                $this->movementType
            )
        ) {
            throw new InvalidArgumentException(
                'El tipo de movimiento de stock no es válido.'
            );
        }

        if (
            $this->stockQuantityBefore < 0
            || $this->stockQuantityAfter < 0
        ) {
            throw new InvalidArgumentException(
                'El stock físico no puede ser negativo.'
            );
        }

        if (
            $this->stockReservedBefore < 0
            || $this->stockReservedAfter < 0
        ) {
            throw new InvalidArgumentException(
                'El stock reservado no puede ser negativo.'
            );
        }

        if (
            $this->stockReservedBefore
            > $this->stockQuantityBefore
        ) {
            throw new InvalidArgumentException(
                'El stock reservado anterior no puede superar el stock físico anterior.'
            );
        }

        if (
            $this->stockReservedAfter
            > $this->stockQuantityAfter
        ) {
            throw new InvalidArgumentException(
                'El stock reservado posterior no puede superar el stock físico posterior.'
            );
        }

        if (
            $this->stockQuantityAfter
            !== $this->stockQuantityBefore
                + $this->quantityDelta
        ) {
            throw new InvalidArgumentException(
                'El cambio de stock físico no coincide con los valores anterior y posterior.'
            );
        }

        if (
            $this->stockReservedAfter
            !== $this->stockReservedBefore
                + $this->reservedDelta
        ) {
            throw new InvalidArgumentException(
                'El cambio de stock reservado no coincide con los valores anterior y posterior.'
            );
        }

        if (
            $this->referenceId !== null
            && $this->referenceId <= 0
        ) {
            throw new InvalidArgumentException(
                'El identificador de referencia no es válido.'
            );
        }

        if (
            $this->customerId !== null
            && $this->customerId <= 0
        ) {
            throw new InvalidArgumentException(
                'El identificador del cliente no es válido.'
            );
        }

        if (
            $this->userId !== null
            && $this->userId <= 0
        ) {
            throw new InvalidArgumentException(
                'El identificador del usuario de WordPress no es válido.'
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

            movementType: (string) (
                $data['movement_type']
                ?? ''
            ),

            quantityDelta: (int) (
                $data['quantity_delta']
                ?? 0
            ),

            reservedDelta: (int) (
                $data['reserved_delta']
                ?? 0
            ),

            stockQuantityBefore: (int) (
                $data['stock_quantity_before']
                ?? 0
            ),

            stockQuantityAfter: (int) (
                $data['stock_quantity_after']
                ?? 0
            ),

            stockReservedBefore: (int) (
                $data['stock_reserved_before']
                ?? 0
            ),

            stockReservedAfter: (int) (
                $data['stock_reserved_after']
                ?? 0
            ),

            referenceType: self::nullableString(
                $data['reference_type']
                ?? null
            ),

            referenceId: self::nullableInt(
                $data['reference_id']
                ?? null
            ),

            customerId: self::nullableInt(
                $data['customer_id']
                ?? null
            ),

            userId: self::nullableInt(
                $data['user_id']
                ?? null
            ),

            notes: self::nullableString(
                $data['notes']
                ?? null
            ),

            createdAt: self::requiredDateTime(
                $data['created_at']
                ?? null,
                'created_at'
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

    public function getMovementType(): string
    {
        return $this->movementType;
    }

    public function getQuantityDelta(): int
    {
        return $this->quantityDelta;
    }

    public function getReservedDelta(): int
    {
        return $this->reservedDelta;
    }

    public function getStockQuantityBefore(): int
    {
        return $this->stockQuantityBefore;
    }

    public function getStockQuantityAfter(): int
    {
        return $this->stockQuantityAfter;
    }

    public function getStockReservedBefore(): int
    {
        return $this->stockReservedBefore;
    }

    public function getStockReservedAfter(): int
    {
        return $this->stockReservedAfter;
    }

    public function getAvailableStockBefore(): int
    {
        return max(
            0,
            $this->stockQuantityBefore
            - $this->stockReservedBefore
        );
    }

    public function getAvailableStockAfter(): int
    {
        return max(
            0,
            $this->stockQuantityAfter
            - $this->stockReservedAfter
        );
    }

    public function getReferenceType(): ?string
    {
        return $this->referenceType;
    }

    public function getReferenceId(): ?int
    {
        return $this->referenceId;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function affectsPhysicalStock(): bool
    {
        return StockMovementType::affectsPhysicalStock(
            $this->movementType
        );
    }

    public function affectsReservedStock(): bool
    {
        return StockMovementType::affectsReservedStock(
            $this->movementType
        );
    }

    public function increasesPhysicalStock(): bool
    {
        return $this->quantityDelta > 0;
    }

    public function decreasesPhysicalStock(): bool
    {
        return $this->quantityDelta < 0;
    }

    public function increasesReservedStock(): bool
    {
        return $this->reservedDelta > 0;
    }

    public function decreasesReservedStock(): bool
    {
        return $this->reservedDelta < 0;
    }

    public function hasReference(): bool
    {
        return $this->referenceType !== null
            && $this->referenceId !== null;
    }

    public function wasPerformedByCustomer(): bool
    {
        return $this->customerId !== null;
    }

    public function wasPerformedByWordPressUser(): bool
    {
        return $this->userId !== null;
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

        return new DateTimeImmutable(
            (string) $value
        );
    }
}