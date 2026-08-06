<?php

declare(strict_types=1);

namespace DSM\Anuncios\Advertisement;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class Advertisement
{
    public function __construct(
        private readonly int $id,
        private readonly int $customerId,
        private readonly ?int $storeId,
        private readonly int $categoryId,
        private readonly ?int $areaId,
        private readonly ?int $municipalityId,
        private readonly string $title,
        private readonly string $slug,
        private readonly string $description,
        private readonly ?string $brand,
        private readonly float $price,
        private readonly ?float $originalPrice,
        private readonly ?DateTimeImmutable $purchaseDate,
        private readonly string $conditionCode,
        private readonly string $status,
        private readonly ?string $rejectionReason,
        private readonly ?DateTimeImmutable $reservedAt,
        private readonly ?DateTimeImmutable $publishedAt,
        private readonly ?DateTimeImmutable $closedAt,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador del anuncio no es válido.'
            );
        }

        if ($this->customerId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($this->categoryId <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la categoría no es válido.'
            );
        }

        if (trim($this->title) === '') {
            throw new InvalidArgumentException(
                'El título del anuncio no puede estar vacío.'
            );
        }

        if (trim($this->slug) === '') {
            throw new InvalidArgumentException(
                'El slug del anuncio no puede estar vacío.'
            );
        }

        if ($this->price < 0) {
            throw new InvalidArgumentException(
                'El precio del anuncio no puede ser negativo.'
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

        if (trim($this->conditionCode) === '') {
            throw new InvalidArgumentException(
                'El estado de conservación no puede estar vacío.'
            );
        }

        if (!AdvertisementStatus::isValid($this->status)) {
            throw new InvalidArgumentException(
                'El estado del anuncio no es válido.'
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

            customerId: (int) (
                $data['customer_id']
                ?? 0
            ),

            storeId: self::nullableInt(
                $data['store_id']
                ?? null
            ),

            categoryId: (int) (
                $data['category_id']
                ?? 0
            ),

            areaId: self::nullableInt(
                $data['area_id']
                ?? null
            ),

            municipalityId: self::nullableInt(
                $data['municipality_id']
                ?? null
            ),

            title: (string) (
                $data['title']
                ?? ''
            ),

            slug: (string) (
                $data['slug']
                ?? ''
            ),

            description: (string) (
                $data['description']
                ?? ''
            ),

            brand: self::nullableString(
                $data['brand']
                ?? null
            ),

            price: (float) (
                $data['price']
                ?? 0
            ),

            originalPrice: self::nullableFloat(
                $data['original_price']
                ?? null
            ),

            purchaseDate: self::nullableDate(
                $data['purchase_date']
                ?? null
            ),

            conditionCode: (string) (
                $data['condition_code']
                ?? ''
            ),

            status: (string) (
                $data['status']
                ?? ''
            ),

            rejectionReason: self::nullableString(
                $data['rejection_reason']
                ?? null
            ),

            reservedAt: self::nullableDateTime(
                $data['reserved_at']
                ?? null
            ),

            publishedAt: self::nullableDateTime(
                $data['published_at']
                ?? null
            ),

            closedAt: self::nullableDateTime(
                $data['closed_at']
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

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getStoreId(): ?int
    {
        return $this->storeId;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getAreadId(): ?int
    {
        return $this->areaId;
    }

    public function getMunicipalityId(): ?int
    {
        return $this->municipalityId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getOriginalPrice(): ?float
    {
        return $this->originalPrice;
    }

    public function getPurchaseDate(): ?DateTimeImmutable
    {
        return $this->purchaseDate;
    }

    public function getConditionCode(): string
    {
        return $this->conditionCode;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getReservedAt(): ?DateTimeImmutable
    {
        return $this->reservedAt;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function belongsToCustomer(
        int $customerId
    ): bool {
        return $this->customerId
            === $customerId;
    }

    public function isEditableByCustomer(): bool
    {
        return AdvertisementStatus::canBeEditedByCustomer(
            $this->status
        );
    }

    public function isPublic(): bool
    {
        return AdvertisementStatus::isPublic(
            $this->status
        );
    }

    public function countsTowardsActiveLimit(): bool
    {
        return AdvertisementStatus::countsTowardsActiveLimit(
            $this->status
        );
    }

    public function isReserved(): bool
    {
        return $this->status
            === AdvertisementStatus::RESERVED;
    }

    public function isClosed(): bool
    {
        return $this->status
            === AdvertisementStatus::CLOSED;
    }

    public function hasBrand(): bool
    {
        return $this->brand !== null;
    }

    public function hasOriginalPrice(): bool
    {
        return $this->originalPrice !== null;
    }

    public function hasPurchaseDate(): bool
    {
        return $this->purchaseDate !== null;
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
}