<?php

declare(strict_types=1);

namespace DSM\Catalogo\Product;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class Product
{
    public function __construct(
        private readonly int $id,
        private readonly int $storeId,
        private readonly ?int $brandId,
        private readonly string $name,
        private readonly string $slug,
        private readonly ?string $description,
        private readonly ?string $internalReference,
        private readonly ?string $baseSku,
        private readonly float $defaultPrice,
        private readonly ?float $originalPrice,
        private readonly ?float $costPrice,
        private readonly ?DateTimeImmutable $purchaseDate,
        private readonly ?float $taxRate,
        private readonly bool $trackStock,
        private readonly string $status,
        private readonly int $createdByCustomerId,
        private readonly ?int $updatedByCustomerId,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
        private readonly ?DateTimeImmutable $archivedAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador del producto no es válido.'
            );
        }

        if ($this->storeId <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la tienda no es válido.'
            );
        }

        if (
            $this->brandId !== null
            && $this->brandId <= 0
        ) {
            throw new InvalidArgumentException(
                'El identificador de la marca no es válido.'
            );
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException(
                'El nombre del producto es obligatorio.'
            );
        }

        if (trim($this->slug) === '') {
            throw new InvalidArgumentException(
                'El slug del producto es obligatorio.'
            );
        }

        if ($this->defaultPrice < 0) {
            throw new InvalidArgumentException(
                'El precio de venta no puede ser negativo.'
            );
        }

        if (
            $this->originalPrice !== null
            && $this->originalPrice < 0
        ) {
            throw new InvalidArgumentException(
                'El precio original no puede ser negativo.'
            );
        }

        if (
            $this->costPrice !== null
            && $this->costPrice < 0
        ) {
            throw new InvalidArgumentException(
                'El precio de coste no puede ser negativo.'
            );
        }

        if (
            $this->taxRate !== null
            && (
                $this->taxRate < 0
                || $this->taxRate > 100
            )
        ) {
            throw new InvalidArgumentException(
                'El tipo impositivo debe estar entre 0 y 100.'
            );
        }

        if (!ProductStatus::isValid($this->status)) {
            throw new InvalidArgumentException(
                'El estado del producto no es válido.'
            );
        }

        if ($this->createdByCustomerId <= 0) {
            throw new InvalidArgumentException(
                'El creador del producto no es válido.'
            );
        }

        if (
            $this->updatedByCustomerId !== null
            && $this->updatedByCustomerId <= 0
        ) {
            throw new InvalidArgumentException(
                'El usuario que modificó el producto no es válido.'
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

            storeId: (int) (
                $data['store_id']
                ?? 0
            ),

            brandId: self::nullableInt(
                $data['brand_id']
                ?? null
            ),

            name: (string) (
                $data['name']
                ?? ''
            ),

            slug: (string) (
                $data['slug']
                ?? ''
            ),

            description: self::nullableString(
                $data['description']
                ?? null
            ),

            internalReference: self::nullableString(
                $data['internal_reference']
                ?? null
            ),

            baseSku: self::nullableString(
                $data['base_sku']
                ?? null
            ),

            defaultPrice: (float) (
                $data['default_price']
                ?? 0
            ),

            originalPrice: self::nullableFloat(
                $data['original_price']
                ?? null
            ),

            costPrice: self::nullableFloat(
                $data['cost_price']
                ?? null
            ),

            purchaseDate: self::nullableDate(
                $data['purchase_date']
                ?? null
            ),

            taxRate: self::nullableFloat(
                $data['tax_rate']
                ?? null
            ),

            trackStock: self::toBool(
                $data['track_stock']
                ?? true
            ),

            status: (string) (
                $data['status']
                ?? ''
            ),

            createdByCustomerId: (int) (
                $data['created_by_customer_id']
                ?? 0
            ),

            updatedByCustomerId: self::nullableInt(
                $data['updated_by_customer_id']
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

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function getBrandId(): ?int
    {
        return $this->brandId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getInternalReference(): ?string
    {
        return $this->internalReference;
    }

    public function getBaseSku(): ?string
    {
        return $this->baseSku;
    }

    public function getDefaultPrice(): float
    {
        return $this->defaultPrice;
    }

    public function getOriginalPrice(): ?float
    {
        return $this->originalPrice;
    }

    public function getCostPrice(): ?float
    {
        return $this->costPrice;
    }

    public function getPurchaseDate(): ?DateTimeImmutable
    {
        return $this->purchaseDate;
    }

    public function getTaxRate(): ?float
    {
        return $this->taxRate;
    }

    public function tracksStock(): bool
    {
        return $this->trackStock;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedByCustomerId(): int
    {
        return $this->createdByCustomerId;
    }

    public function getUpdatedByCustomerId(): ?int
    {
        return $this->updatedByCustomerId;
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

    public function hasBrand(): bool
    {
        return $this->brandId !== null;
    }

    public function hasDescription(): bool
    {
        return $this->description !== null;
    }

    public function hasInternalReference(): bool
    {
        return $this->internalReference !== null;
    }

    public function hasBaseSku(): bool
    {
        return $this->baseSku !== null;
    }

    public function hasOriginalPrice(): bool
    {
        return $this->originalPrice !== null;
    }

    public function hasCostPrice(): bool
    {
        return $this->costPrice !== null;
    }

    public function hasPurchaseDate(): bool
    {
        return $this->purchaseDate !== null;
    }

    public function hasTaxRate(): bool
    {
        return $this->taxRate !== null;
    }

    public function belongsToStore(
        int $storeId
    ): bool {
        return $this->storeId === $storeId;
    }

    public function canBeEdited(): bool
    {
        return ProductStatus::canBeEdited(
            $this->status
        );
    }

    public function canGenerateAdvertisements(): bool
    {
        return ProductStatus::canGenerateAdvertisements(
            $this->status
        );
    }

    public function canReceiveStock(): bool
    {
        return ProductStatus::canReceiveStock(
            $this->status
        );
    }

    public function isActive(): bool
    {
        return $this->status
            === ProductStatus::ACTIVE;
    }

    public function isArchived(): bool
    {
        return ProductStatus::isArchived(
            $this->status
        );
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

    private static function nullableDate(
        mixed $value
    ): ?DateTimeImmutable {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            (string) $value
        );

        $errors = DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || (
                is_array($errors)
                && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                )
            )
        ) {
            throw new InvalidArgumentException(
                'La fecha de compra no es válida.'
            );
        }

        return $date;
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