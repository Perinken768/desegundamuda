<?php

declare(strict_types=1);

namespace DSM\Ubicaciones\Country;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Representa un país disponible en DSM Ubicaciones.
 *
 * Ejemplos:
 *
 * - España
 * - Portugal
 * - Francia
 */
final class Country
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $slug,
        private readonly string $isoCode,
        private readonly ?string $phonePrefix,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Devuelve el código ISO almacenado.
     *
     * Ejemplo:
     *
     * ES
     */
    public function getIsoCode(): string
    {
        return $this->isoCode;
    }

    /**
     * Devuelve el prefijo telefónico internacional.
     *
     * Ejemplo:
     *
     * +34
     */
    public function getPhonePrefix(): ?string
    {
        return $this->phonePrefix;
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
     * Devuelve el nombre acompañado de su código ISO.
     *
     * Ejemplo:
     *
     * España (ES)
     */
    public function getDisplayLabel(): string
    {
        if ($this->isoCode === '') {
            return $this->name;
        }

        return sprintf(
            '%s (%s)',
            $this->name,
            $this->isoCode
        );
    }

    /**
     * Convierte el país en una estructura neutral para filtros,
     * formularios públicos e integraciones con otros plugins.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' =>
                $this->id,

            'name' =>
                $this->name,

            'slug' =>
                $this->slug,

            'iso_code' =>
                $this->isoCode,

            'phone_prefix' =>
                $this->phonePrefix,

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