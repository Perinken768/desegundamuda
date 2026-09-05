<?php

declare(strict_types=1);

namespace DSM\Favoritos\Favorite;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class Favorite
{
    public const TYPE_ADVERTISEMENT =
        'advertisement';

    public const TYPE_STORE_PRODUCT =
        'store_product';

    private const ALLOWED_TYPES = [
        self::TYPE_ADVERTISEMENT,
        self::TYPE_STORE_PRODUCT,
    ];

    public function __construct(
        private readonly int $id,
        private readonly int $customerId,
        private readonly string $itemType,
        private readonly int $itemId,
        private readonly DateTimeImmutable $createdAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El ID del favorito debe ser mayor que cero.'
            );
        }

        if ($this->customerId <= 0) {
            throw new InvalidArgumentException(
                'El ID del cliente debe ser mayor que cero.'
            );
        }

        if (
            !in_array(
                $this->itemType,
                self::ALLOWED_TYPES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'El tipo de favorito no es válido.'
            );
        }

        if ($this->itemId <= 0) {
            throw new InvalidArgumentException(
                'El ID del elemento favorito debe ser mayor que cero.'
            );
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isAdvertisement(): bool
    {
        return $this->itemType
            === self::TYPE_ADVERTISEMENT;
    }

    public function isStoreProduct(): bool
    {
        return $this->itemType
            === self::TYPE_STORE_PRODUCT;
    }

    /*
     * Compatibilidad temporal con el código actual
     * de DSM Favoritos.
     */
    public function getAdvertisementId(): int
    {
        return $this->isAdvertisement()
            ? $this->itemId
            : 0;
    }

    public function belongsToCustomer(
        int $customerId
    ): bool {
        return $this->customerId
            === $customerId;
    }

    public function belongsToItem(
        string $itemType,
        int $itemId
    ): bool {
        return $this->itemType
            === $itemType
            && $this->itemId
            === $itemId;
    }

    public function belongsToAdvertisement(
        int $advertisementId
    ): bool {
        return $this->isAdvertisement()
            && $this->itemId
                === $advertisementId;
    }

    public function matches(
        int $customerId,
        int $advertisementId
    ): bool {
        return $this->customerId
            === $customerId
            && $this->belongsToAdvertisement(
                $advertisementId
            );
    }

    public function matchesItem(
        int $customerId,
        string $itemType,
        int $itemId
    ): bool {
        return $this->customerId
            === $customerId
            && $this->belongsToItem(
                $itemType,
                $itemId
            );
    }

    public static function isValidType(
        string $itemType
    ): bool {
        return in_array(
            $itemType,
            self::ALLOWED_TYPES,
            true
        );
    }
}
