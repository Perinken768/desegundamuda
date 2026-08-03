<?php

declare(strict_types=1);

namespace DSM\Catalogo\Variant;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class ProductVariant
{
    public function __construct(
        private readonly int $id,
        private readonly int $productId,
        private readonly ?string $sku,
        private readonly ?string $barcode,
        private readonly ?string $sizeValue,
        private readonly ?string $colorValue,
        private readonly ?string $conditionCode,
        private readonly ?float $price,
        private readonly ?float $originalPrice,
        private readonly ?float $costPrice,
        private readonly int $stockQuantity,
        private readonly int $stockReserved,
        private readonly ?int $lowStockThreshold,
        private readonly bool $trackStock,
        private readonly bool $default,
        private readonly bool $active,
        private readonly int $sortOrder,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
        private readonly ?DateTimeImmutable $archivedAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la variante no es válido.'
            );
        }

        if ($this->productId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del producto no es válido.'
            );
        }

        if (
            $this->price !== null
            && $this->price < 0
        ) {
            throw new InvalidArgumentException(
                'El precio de la variante no puede ser negativo.'
            );
        }

        if (
            $this->originalPrice !== null
            && $this->originalPrice < 0
        ) {
            throw new InvalidArgumentException(
                'El precio original de la variante no puede ser negativo.'
            );
        }

        if (
            $this->costPrice !== null
            && $this->costPrice < 0
        ) {
            throw new InvalidArgumentException(
                'El precio de coste de la variante no puede ser negativo.'
            );
        }

        if ($this->stockQuantity < 0) {
            throw new InvalidArgumentException(
                'El stock total no puede ser negativo.'
            );
        }

        if ($this->stockReserved < 0) {
            throw new InvalidArgumentException(
                'El stock reservado no puede ser negativo.'
            );
        }

        if (
            $this->stockReserved
            > $this->stockQuantity
        ) {
            throw new InvalidArgumentException(
                'El stock reservado no puede superar el stock total.'
            );
        }

        if (
            $this->lowStockThreshold !== null
            && $this->lowStockThreshold < 0
        ) {
            throw new InvalidArgumentException(
                'El umbral de stock bajo no puede ser negativo.'
            );
        }

        if ($this->sortOrder < 0) {
            throw new InvalidArgumentException(
                'El orden de la variante no puede ser negativo.'
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

            sku: self::nullableString(
                $data['sku']
                ?? null
            ),

            barcode: self::nullableString(
                $data['barcode']
                ?? null
            ),

            sizeValue: self::nullableString(
                $data['size_value']
                ?? null
            ),

            colorValue: self::nullableString(
                $data['color_value']
                ?? null
            ),

            conditionCode: self::nullableString(
                $data['condition_code']
                ?? null
            ),

            price: self::nullableFloat(
                $data['price']
                ?? null
            ),

            originalPrice: self::nullableFloat(
                $data['original_price']
                ?? null
            ),

            costPrice: self::nullableFloat(
                $data['cost_price']
                ?? null
            ),

            stockQuantity: (int) (
                $data['stock_quantity']
                ?? 0
            ),

            stockReserved: (int) (
                $data['stock_reserved']
                ?? 0
            ),

            lowStockThreshold: self::nullableNonNegativeInt(
                $data['low_stock_threshold']
                ?? null
            ),

            trackStock: self::toBool(
                $data['track_stock']
                ?? true
            ),

            default: self::toBool(
                $data['is_default']
                ?? false
            ),

            active: self::toBool(
                $data['is_active']
                ?? false
            ),

            sortOrder: (int) (
                $data['sort_order']
                ?? 0
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
            ),

            archivedAt: self::nullableDateTime(
                $data['archived_at']
                ?? null
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

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }

    public function getSizeValue(): ?string
    {
        return $this->sizeValue;
    }

    public function getColorValue(): ?string
    {
        return $this->colorValue;
    }

    public function getConditionCode(): ?string
    {
        return $this->conditionCode;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getOriginalPrice(): ?float
    {
        return $this->originalPrice;
    }

    public function getCostPrice(): ?float
    {
        return $this->costPrice;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function getStockReserved(): int
    {
        return $this->stockReserved;
    }

    public function getAvailableStock(): int
    {
        if (!$this->trackStock) {
            return PHP_INT_MAX;
        }

        return max(
            0,
            $this->stockQuantity
            - $this->stockReserved
        );
    }

    public function getLowStockThreshold(): ?int
    {
        return $this->lowStockThreshold;
    }

    public function tracksStock(): bool
    {
        return $this->trackStock;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getArchivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function hasSku(): bool
    {
        return $this->sku !== null;
    }

    public function hasBarcode(): bool
    {
        return $this->barcode !== null;
    }

    public function hasSize(): bool
    {
        return $this->sizeValue !== null;
    }

    public function hasColor(): bool
    {
        return $this->colorValue !== null;
    }

    public function hasConditionCode(): bool
    {
        return $this->conditionCode !== null;
    }

    public function hasSpecificPrice(): bool
    {
        return $this->price !== null;
    }

    public function hasSpecificOriginalPrice(): bool
    {
        return $this->originalPrice !== null;
    }

    public function hasSpecificCostPrice(): bool
    {
        return $this->costPrice !== null;
    }

    public function hasAvailableStock(): bool
    {
        return !$this->trackStock
            || $this->getAvailableStock() > 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->trackStock
            && $this->getAvailableStock() <= 0;
    }

    public function isLowStock(): bool
    {
        if (
            !$this->trackStock
            || $this->lowStockThreshold === null
        ) {
            return false;
        }

        return $this->getAvailableStock()
            <= $this->lowStockThreshold;
    }

    public function canReserve(
        int $quantity
    ): bool {
        if (
            !$this->active
            || $quantity <= 0
        ) {
            return false;
        }

        if (!$this->trackStock) {
            return true;
        }

        return $this->getAvailableStock()
            >= $quantity;
    }

    public function isArchived(): bool
    {
        return $this->archivedAt !== null;
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

    private static function nullableFloat(
        mixed $value
    ): ?float {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        return (float) $value;
    }

    private static function nullableNonNegativeInt(
        mixed $value
    ): ?int {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $integer = (int) $value;

        return $integer >= 0
            ? $integer
            : null;
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

        return new DateTimeImmutable(
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

    private static function toBool(
        mixed $value
    ): bool {
        return in_array(
            $value,
            [
                true,
                1,
                '1',
            ],
            true
        );
    }
}