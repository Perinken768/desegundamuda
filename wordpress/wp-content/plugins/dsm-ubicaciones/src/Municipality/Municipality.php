<?php

declare(strict_types=1);

namespace DSM\Ubicaciones\Municipality;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Representa un municipio asociado a un área territorial.
 *
 * Ejemplos:
 *
 * - Telde pertenece al área Gran Canaria.
 * - Adeje pertenece al área Tenerife.
 * - Alcalá de Henares podría pertenecer al área Madrid.
 */
final class Municipality
{
    public function __construct(
        private readonly int $id,
        private readonly int $areaId,
        private readonly string $name,
        private readonly string $slug,
        private readonly ?string $code,
        private readonly ?string $postalCode,
        private readonly bool $isActive,
        private readonly int $sortOrder,
        private readonly string $createdAt,
        private readonly string $updatedAt
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAreaId(): int
    {
        return $this->areaId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /**
     * Convierte el municipio en una estructura neutral
     * para formularios, filtros e integraciones.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' =>
                $this->id,

            'area_id' =>
                $this->areaId,

            'name' =>
                $this->name,

            'slug' =>
                $this->slug,

            'code' =>
                $this->code,

            'postal_code' =>
                $this->postalCode,

            'is_active' =>
                $this->isActive,

            'sort_order' =>
                $this->sortOrder,

            'created_at' =>
                $this->createdAt,

            'updated_at' =>
                $this->updatedAt,
        ];
    }
}