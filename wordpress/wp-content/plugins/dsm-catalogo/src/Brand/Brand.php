<?php

declare(strict_types=1);

namespace DSM\Catalogo\Brand;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class Brand
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $slug,
        private readonly ?string $description,
        private readonly ?string $website,
        private readonly ?int $logoId,
        private readonly bool $active,
        private readonly bool $verified,
        private readonly int $sortOrder,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la marca no es válido.'
            );
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException(
                'El nombre de la marca es obligatorio.'
            );
        }

        if (trim($this->slug) === '') {
            throw new InvalidArgumentException(
                'El slug de la marca es obligatorio.'
            );
        }

        if (
            $this->logoId !== null
            && $this->logoId <= 0
        ) {
            throw new InvalidArgumentException(
                'El identificador del logotipo no es válido.'
            );
        }

        if ($this->sortOrder < 0) {
            throw new InvalidArgumentException(
                'El orden de la marca no puede ser negativo.'
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

            website: self::nullableString(
                $data['website']
                ?? null
            ),

            logoId: self::nullableInt(
                $data['logo_id']
                ?? null
            ),

            active: self::toBool(
                $data['is_active']
                ?? false
            ),

            verified: self::toBool(
                $data['is_verified']
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
            )
        );
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function getLogoId(): ?int
    {
        return $this->logoId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isVerified(): bool
    {
        return $this->verified;
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

    public function canBeSelected(): bool
    {
        return $this->active
            && $this->verified;
    }

    public function hasLogo(): bool
    {
        return $this->logoId !== null;
    }

    public function hasWebsite(): bool
    {
        return $this->website !== null;
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