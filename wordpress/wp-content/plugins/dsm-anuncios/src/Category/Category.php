<?php

declare(strict_types=1);

namespace DSM\Anuncios\Category;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class Category
{
    public function __construct(
        private readonly int $id,
        private readonly ?int $parentId,
        private readonly string $name,
        private readonly string $slug,
        private readonly ?string $description,
        private readonly bool $marketplaceAllowed,
        private readonly bool $storeAllowed,
        private readonly bool $active,
        private readonly int $sortOrder,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la categoría no es válido.'
            );
        }

        if (
            $this->parentId !== null
            && $this->parentId <= 0
        ) {
            throw new InvalidArgumentException(
                'La categoría superior no es válida.'
            );
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException(
                'El nombre de la categoría es obligatorio.'
            );
        }

        if (trim($this->slug) === '') {
            throw new InvalidArgumentException(
                'El slug de la categoría es obligatorio.'
            );
        }

        if ($this->sortOrder < 0) {
            throw new InvalidArgumentException(
                'El orden de la categoría no puede ser negativo.'
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
            id: (int) ($data['id'] ?? 0),

            parentId: self::nullableInt(
                $data['parent_id'] ?? null
            ),

            name: (string) (
                $data['name'] ?? ''
            ),

            slug: (string) (
                $data['slug'] ?? ''
            ),

            description: self::nullableString(
                $data['description'] ?? null
            ),

            marketplaceAllowed: self::toBool(
                $data['marketplace_allowed'] ?? false
            ),

            storeAllowed: self::toBool(
                $data['store_allowed'] ?? false
            ),

            active: self::toBool(
                $data['is_active'] ?? false
            ),

            sortOrder: (int) (
                $data['sort_order'] ?? 0
            ),

            createdAt: self::requiredDateTime(
                $data['created_at'] ?? null,
                'created_at'
            ),

            updatedAt: self::requiredDateTime(
                $data['updated_at'] ?? null,
                'updated_at'
            )
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
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

    public function isMarketplaceAllowed(): bool
    {
        return $this->marketplaceAllowed;
    }

    public function isStoreAllowed(): bool
    {
        return $this->storeAllowed;
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

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    public function canBeUsedInMarketplace(): bool
    {
        return $this->active
            && $this->marketplaceAllowed;
    }

    public function canBeUsedInStore(): bool
    {
        return $this->active
            && $this->storeAllowed;
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
            || $value === ''
        ) {
            return null;
        }

        return (string) $value;
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