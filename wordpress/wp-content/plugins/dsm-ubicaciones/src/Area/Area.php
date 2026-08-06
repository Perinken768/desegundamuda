<?php

declare(strict_types=1);

namespace DSM\Ubicaciones\Area;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Representa un área territorial.
 *
 * Un área puede ser:
 *
 * - región;
 * - isla;
 * - provincia;
 * - comarca;
 * - zona comercial;
 * - cualquier otra división futura.
 */
final class Area
{
    public const TYPE_REGION =
        'region';

    public const TYPE_ISLAND =
        'island';

    public const TYPE_PROVINCE =
        'province';

    public const TYPE_COUNTY =
        'county';

    public const TYPE_COMMERCIAL_ZONE =
        'commercial_zone';

    public const TYPE_OTHER =
        'other';

    private const ALLOWED_TYPES = [
        self::TYPE_REGION,
        self::TYPE_ISLAND,
        self::TYPE_PROVINCE,
        self::TYPE_COUNTY,
        self::TYPE_COMMERCIAL_ZONE,
        self::TYPE_OTHER,
    ];

    public function __construct(
        private readonly int $id,
        private readonly int $countryId,
        private readonly ?int $parentId,
        private readonly string $name,
        private readonly string $slug,
        private readonly string $areaType,
        private readonly ?string $code,
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

    public function getCountryId(): int
    {
        return $this->countryId;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function hasParent(): bool
    {
        return $this->parentId !== null
            && $this->parentId > 0;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getAreaType(): string
    {
        return in_array(
            $this->areaType,
            self::ALLOWED_TYPES,
            true
        )
            ? $this->areaType
            : self::TYPE_OTHER;
    }

    public function getCode(): ?string
    {
        return $this->code;
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

    public function isRegion(): bool
    {
        return $this->getAreaType()
            === self::TYPE_REGION;
    }

    public function isIsland(): bool
    {
        return $this->getAreaType()
            === self::TYPE_ISLAND;
    }

    public function isProvince(): bool
    {
        return $this->getAreaType()
            === self::TYPE_PROVINCE;
    }

    public function isCounty(): bool
    {
        return $this->getAreaType()
            === self::TYPE_COUNTY;
    }

    public function isCommercialZone(): bool
    {
        return $this->getAreaType()
            === self::TYPE_COMMERCIAL_ZONE;
    }

    /**
     * Devuelve una etiqueta legible del tipo de área.
     */
    public function getAreaTypeLabel(): string
    {
        return match ($this->getAreaType()) {
            self::TYPE_REGION =>
                __(
                    'Región',
                    'dsm-ubicaciones'
                ),

            self::TYPE_ISLAND =>
                __(
                    'Isla',
                    'dsm-ubicaciones'
                ),

            self::TYPE_PROVINCE =>
                __(
                    'Provincia',
                    'dsm-ubicaciones'
                ),

            self::TYPE_COUNTY =>
                __(
                    'Comarca',
                    'dsm-ubicaciones'
                ),

            self::TYPE_COMMERCIAL_ZONE =>
                __(
                    'Zona comercial',
                    'dsm-ubicaciones'
                ),

            default =>
                __(
                    'Otra',
                    'dsm-ubicaciones'
                ),
        };
    }

    /**
     * Convierte el área en una estructura neutral.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' =>
                $this->id,

            'country_id' =>
                $this->countryId,

            'parent_id' =>
                $this->parentId,

            'name' =>
                $this->name,

            'slug' =>
                $this->slug,

            'area_type' =>
                $this->getAreaType(),

            'area_type_label' =>
                $this->getAreaTypeLabel(),

            'code' =>
                $this->code,

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