<?php

declare(strict_types=1);

namespace DSM\Catalogo\Stock;

use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class StockResult
{
    public function __construct(
        private readonly int $productId,
        private readonly int $variantId,
        private readonly int $movementId,
        private readonly string $movementType,
        private readonly int $quantityDelta,
        private readonly int $reservedDelta,
        private readonly int $stockQuantityBefore,
        private readonly int $stockQuantityAfter,
        private readonly int $stockReservedBefore,
        private readonly int $stockReservedAfter
    ) {
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

        if ($this->movementId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del movimiento no es válido.'
            );
        }

        if (trim($this->movementType) === '') {
            throw new InvalidArgumentException(
                'El tipo de movimiento es obligatorio.'
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
                'El cambio de stock físico no es coherente.'
            );
        }

        if (
            $this->stockReservedAfter
            !== $this->stockReservedBefore
                + $this->reservedDelta
        ) {
            throw new InvalidArgumentException(
                'El cambio de stock reservado no es coherente.'
            );
        }
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getVariantId(): int
    {
        return $this->variantId;
    }

    public function getMovementId(): int
    {
        return $this->movementId;
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

    public function physicalStockIncreased(): bool
    {
        return $this->quantityDelta > 0;
    }

    public function physicalStockDecreased(): bool
    {
        return $this->quantityDelta < 0;
    }

    public function reservedStockIncreased(): bool
    {
        return $this->reservedDelta > 0;
    }

    public function reservedStockDecreased(): bool
    {
        return $this->reservedDelta < 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->getAvailableStockAfter()
            <= 0;
    }

    public function becameOutOfStock(): bool
    {
        return $this->getAvailableStockBefore() > 0
            && $this->getAvailableStockAfter() <= 0;
    }

    public function becameAvailable(): bool
    {
        return $this->getAvailableStockBefore() <= 0
            && $this->getAvailableStockAfter() > 0;
    }

    /**
     * @return array<string, int|string|bool>
     */
    public function toArray(): array
    {
        return [
            'product_id' =>
                $this->productId,

            'variant_id' =>
                $this->variantId,

            'movement_id' =>
                $this->movementId,

            'movement_type' =>
                $this->movementType,

            'quantity_delta' =>
                $this->quantityDelta,

            'reserved_delta' =>
                $this->reservedDelta,

            'stock_quantity_before' =>
                $this->stockQuantityBefore,

            'stock_quantity_after' =>
                $this->stockQuantityAfter,

            'stock_reserved_before' =>
                $this->stockReservedBefore,

            'stock_reserved_after' =>
                $this->stockReservedAfter,

            'available_stock_before' =>
                $this->getAvailableStockBefore(),

            'available_stock_after' =>
                $this->getAvailableStockAfter(),

            'out_of_stock' =>
                $this->isOutOfStock(),

            'became_out_of_stock' =>
                $this->becameOutOfStock(),

            'became_available' =>
                $this->becameAvailable(),
        ];
    }
}