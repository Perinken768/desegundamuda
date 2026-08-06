<?php

declare(strict_types=1);

namespace DSM\Ubicaciones\Area;

use RuntimeException;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repositorio de áreas territoriales.
 *
 * Permite:
 *
 * - buscar por ID;
 * - buscar por país y slug;
 * - listar todas;
 * - listar activas;
 * - listar por país;
 * - listar por área padre;
 * - crear y actualizar áreas;
 * - activar o desactivar registros.
 */
final class AreaRepository
{
    private wpdb $database;

    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->database =
            $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_location_areas';
    }

    public function findById(
        int $areaId
    ): ?Area {
        if ($areaId <= 0) {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    country_id,
                    parent_id,
                    name,
                    slug,
                    area_type,
                    code,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1
                ",
                $areaId
            );

        if (!is_string($sql)) {
            return null;
        }

        $row =
            $this->database->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($row)
            ? $this->hydrate($row)
            : null;
    }

    public function findByCountryAndSlug(
        int $countryId,
        string $slug
    ): ?Area {
        if ($countryId <= 0) {
            return null;
        }

        $slug =
            sanitize_title(
                $slug
            );

        if ($slug === '') {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    country_id,
                    parent_id,
                    name,
                    slug,
                    area_type,
                    code,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE country_id = %d
                  AND slug = %s
                LIMIT 1
                ",
                $countryId,
                $slug
            );

        if (!is_string($sql)) {
            return null;
        }

        $row =
            $this->database->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($row)
            ? $this->hydrate($row)
            : null;
    }

    /**
     * @return array<int, Area>
     */
    public function findAll(): array
    {
        $rows =
            $this->database->get_results(
                "
                SELECT
                    id,
                    country_id,
                    parent_id,
                    name,
                    slug,
                    area_type,
                    code,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                ORDER BY
                    country_id ASC,
                    parent_id ASC,
                    sort_order ASC,
                    name ASC,
                    id ASC
                ",
                ARRAY_A
            );

        return $this->hydrateMany(
            $rows
        );
    }

    /**
     * @return array<int, Area>
     */
    public function findActive(): array
    {
        $rows =
            $this->database->get_results(
                "
                SELECT
                    id,
                    country_id,
                    parent_id,
                    name,
                    slug,
                    area_type,
                    code,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE is_active = 1
                ORDER BY
                    country_id ASC,
                    parent_id ASC,
                    sort_order ASC,
                    name ASC,
                    id ASC
                ",
                ARRAY_A
            );

        return $this->hydrateMany(
            $rows
        );
    }

    /**
     * @return array<int, Area>
     */
    public function findByCountryId(
        int $countryId,
        bool $onlyActive = false
    ): array {
        if ($countryId <= 0) {
            return [];
        }

        $activeCondition =
            $onlyActive
                ? 'AND is_active = 1'
                : '';

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    country_id,
                    parent_id,
                    name,
                    slug,
                    area_type,
                    code,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE country_id = %d
                {$activeCondition}
                ORDER BY
                    parent_id ASC,
                    sort_order ASC,
                    name ASC,
                    id ASC
                ",
                $countryId
            );

        if (!is_string($sql)) {
            return [];
        }

        $rows =
            $this->database->get_results(
                $sql,
                ARRAY_A
            );

        return $this->hydrateMany(
            $rows
        );
    }

    /**
     * @return array<int, Area>
     */
    public function findByParentId(
        int $parentId,
        bool $onlyActive = false
    ): array {
        if ($parentId <= 0) {
            return [];
        }

        $activeCondition =
            $onlyActive
                ? 'AND is_active = 1'
                : '';

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    country_id,
                    parent_id,
                    name,
                    slug,
                    area_type,
                    code,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE parent_id = %d
                {$activeCondition}
                ORDER BY
                    sort_order ASC,
                    name ASC,
                    id ASC
                ",
                $parentId
            );

        if (!is_string($sql)) {
            return [];
        }

        $rows =
            $this->database->get_results(
                $sql,
                ARRAY_A
            );

        return $this->hydrateMany(
            $rows
        );
    }

    /**
     * @return array<int, Area>
     */
    public function findRootByCountryId(
        int $countryId,
        bool $onlyActive = false
    ): array {
        if ($countryId <= 0) {
            return [];
        }

        $activeCondition =
            $onlyActive
                ? 'AND is_active = 1'
                : '';

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    country_id,
                    parent_id,
                    name,
                    slug,
                    area_type,
                    code,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE country_id = %d
                  AND parent_id IS NULL
                {$activeCondition}
                ORDER BY
                    sort_order ASC,
                    name ASC,
                    id ASC
                ",
                $countryId
            );

        if (!is_string($sql)) {
            return [];
        }

        $rows =
            $this->database->get_results(
                $sql,
                ARRAY_A
            );

        return $this->hydrateMany(
            $rows
        );
    }

    public function create(
        int $countryId,
        ?int $parentId,
        string $name,
        string $areaType,
        ?string $code,
        int $sortOrder = 0,
        bool $isActive = true
    ): Area {
        if ($countryId <= 0) {
            throw new RuntimeException(
                'El identificador del país no es válido.'
            );
        }

        $parentId =
            $parentId !== null
            && $parentId > 0
                ? $parentId
                : null;

        $name =
            sanitize_text_field(
                trim($name)
            );

        $areaType =
            $this->normalizeAreaType(
                $areaType
            );

        $code =
            $this->normalizeNullableText(
                $code
            );

        $sortOrder =
            max(
                0,
                $sortOrder
            );

        if ($name === '') {
            throw new RuntimeException(
                'El nombre del área no puede estar vacío.'
            );
        }

        if (
            mb_strlen(
                $name
            ) > 150
        ) {
            throw new RuntimeException(
                'El nombre del área es demasiado largo.'
            );
        }

        if ($parentId !== null) {
            $parent =
                $this->findById(
                    $parentId
                );

            if ($parent === null) {
                throw new RuntimeException(
                    'No se encontró el área superior.'
                );
            }

            if (
                $parent->getCountryId()
                !== $countryId
            ) {
                throw new RuntimeException(
                    'El área superior pertenece a otro país.'
                );
            }
        }

        $slug =
            $this->generateUniqueSlug(
                $countryId,
                $name
            );

        $now =
            current_time(
                'mysql',
                true
            );

        $result =
            $this->database->insert(
                $this->tableName,
                [
                    'country_id' =>
                        $countryId,

                    'parent_id' =>
                        $parentId,

                    'name' =>
                        $name,

                    'slug' =>
                        $slug,

                    'area_type' =>
                        $areaType,

                    'code' =>
                        $code,

                    'is_active' =>
                        $isActive
                            ? 1
                            : 0,

                    'sort_order' =>
                        $sortOrder,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ],
                [
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear el área territorial.'
            );
        }

        $area =
            $this->findById(
                (int) $this->database
                    ->insert_id
            );

        if ($area === null) {
            throw new RuntimeException(
                'El área fue creada pero no pudo recuperarse.'
            );
        }

        return $area;
    }

    public function update(
        int $areaId,
        int $countryId,
        ?int $parentId,
        string $name,
        string $areaType,
        ?string $code,
        int $sortOrder,
        bool $isActive
    ): Area {
        if ($areaId <= 0) {
            throw new RuntimeException(
                'El identificador del área no es válido.'
            );
        }

        $area =
            $this->findById(
                $areaId
            );

        if ($area === null) {
            throw new RuntimeException(
                'No se encontró el área territorial.'
            );
        }

        if ($countryId <= 0) {
            throw new RuntimeException(
                'El identificador del país no es válido.'
            );
        }

        $parentId =
            $parentId !== null
            && $parentId > 0
                ? $parentId
                : null;

        if ($parentId === $areaId) {
            throw new RuntimeException(
                'Un área no puede ser su propia área superior.'
            );
        }

        $name =
            sanitize_text_field(
                trim($name)
            );

        $areaType =
            $this->normalizeAreaType(
                $areaType
            );

        $code =
            $this->normalizeNullableText(
                $code
            );

        $sortOrder =
            max(
                0,
                $sortOrder
            );

        if ($name === '') {
            throw new RuntimeException(
                'El nombre del área no puede estar vacío.'
            );
        }

        if (
            mb_strlen(
                $name
            ) > 150
        ) {
            throw new RuntimeException(
                'El nombre del área es demasiado largo.'
            );
        }

        if ($parentId !== null) {
            $parent =
                $this->findById(
                    $parentId
                );

            if ($parent === null) {
                throw new RuntimeException(
                    'No se encontró el área superior.'
                );
            }

            if (
                $parent->getCountryId()
                !== $countryId
            ) {
                throw new RuntimeException(
                    'El área superior pertenece a otro país.'
                );
            }

            if (
                $this->wouldCreateCircularReference(
                    $areaId,
                    $parentId
                )
            ) {
                throw new RuntimeException(
                    'La jerarquía produciría una referencia circular.'
                );
            }
        }

        $slug =
            $this->generateUniqueSlug(
                $countryId,
                $name,
                $areaId
            );

        $result =
            $this->database->update(
                $this->tableName,
                [
                    'country_id' =>
                        $countryId,

                    'parent_id' =>
                        $parentId,

                    'name' =>
                        $name,

                    'slug' =>
                        $slug,

                    'area_type' =>
                        $areaType,

                    'code' =>
                        $code,

                    'is_active' =>
                        $isActive
                            ? 1
                            : 0,

                    'sort_order' =>
                        $sortOrder,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $areaId,
                ],
                [
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                ],
                [
                    '%d',
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo actualizar el área territorial.'
            );
        }

        $updatedArea =
            $this->findById(
                $areaId
            );

        if ($updatedArea === null) {
            throw new RuntimeException(
                'El área fue actualizada pero no pudo recuperarse.'
            );
        }

        return $updatedArea;
    }

    public function setActive(
        int $areaId,
        bool $isActive
    ): Area {
        $area =
            $this->findById(
                $areaId
            );

        if ($area === null) {
            throw new RuntimeException(
                'No se encontró el área territorial.'
            );
        }

        return $this->update(
            $areaId,
            $area->getCountryId(),
            $area->getParentId(),
            $area->getName(),
            $area->getAreaType(),
            $area->getCode(),
            $area->getSortOrder(),
            $isActive
        );
    }

    private function normalizeAreaType(
        string $areaType
    ): string {
        $areaType =
            sanitize_key(
                $areaType
            );

        $allowedTypes = [
            Area::TYPE_REGION,
            Area::TYPE_ISLAND,
            Area::TYPE_PROVINCE,
            Area::TYPE_COUNTY,
            Area::TYPE_COMMERCIAL_ZONE,
            Area::TYPE_OTHER,
        ];

        return in_array(
            $areaType,
            $allowedTypes,
            true
        )
            ? $areaType
            : Area::TYPE_OTHER;
    }

    private function generateUniqueSlug(
        int $countryId,
        string $name,
        ?int $excludedAreaId = null
    ): string {
        $baseSlug =
            sanitize_title(
                $name
            );

        if ($baseSlug === '') {
            $baseSlug =
                'area';
        }

        $slug =
            $baseSlug;

        $suffix =
            2;

        while (
            $this->slugExists(
                $countryId,
                $slug,
                $excludedAreaId
            )
        ) {
            $slug =
                $baseSlug
                . '-'
                . $suffix;

            $suffix++;
        }

        return $slug;
    }

    private function slugExists(
        int $countryId,
        string $slug,
        ?int $excludedAreaId = null
    ): bool {
        $parameters = [
            $countryId,
            $slug,
        ];

        $excludedCondition = '';

        if (
            $excludedAreaId !== null
            && $excludedAreaId > 0
        ) {
            $excludedCondition =
                'AND id <> %d';

            $parameters[] =
                $excludedAreaId;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT COUNT(*)
                FROM {$this->tableName}
                WHERE country_id = %d
                  AND slug = %s
                {$excludedCondition}
                ",
                ...$parameters
            );

        if (!is_string($sql)) {
            return false;
        }

        return (int) $this->database
            ->get_var($sql) > 0;
    }

    private function wouldCreateCircularReference(
        int $areaId,
        int $proposedParentId
    ): bool {
        $visited = [];

        $currentParentId =
            $proposedParentId;

        while ($currentParentId > 0) {
            if ($currentParentId === $areaId) {
                return true;
            }

            if (
                in_array(
                    $currentParentId,
                    $visited,
                    true
                )
            ) {
                return true;
            }

            $visited[] =
                $currentParentId;

            $parentArea =
                $this->findById(
                    $currentParentId
                );

            if (
                $parentArea === null
                || $parentArea
                    ->getParentId() === null
            ) {
                return false;
            }

            $currentParentId =
                (int) $parentArea
                    ->getParentId();
        }

        return false;
    }

    private function normalizeNullableText(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            sanitize_text_field(
                trim($value)
            );

        return $value === ''
            ? null
            : $value;
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, Area>
     */
    private function hydrateMany(
        mixed $rows
    ): array {
        if (!is_array($rows)) {
            return [];
        }

        $areas = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $areas[] =
                $this->hydrate(
                    $row
                );
        }

        return $areas;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row
    ): Area {
        return new Area(
            (int) (
                $row['id']
                ?? 0
            ),

            (int) (
                $row['country_id']
                ?? 0
            ),

            isset($row['parent_id'])
            && $row['parent_id'] !== null
                ? (int) $row['parent_id']
                : null,

            (string) (
                $row['name']
                ?? ''
            ),

            (string) (
                $row['slug']
                ?? ''
            ),

            (string) (
                $row['area_type']
                ?? Area::TYPE_OTHER
            ),

            isset($row['code'])
            && $row['code'] !== null
                ? (string) $row['code']
                : null,

            (int) (
                $row['is_active']
                ?? 0
            ) === 1,

            max(
                0,
                (int) (
                    $row['sort_order']
                    ?? 0
                )
            ),

            (string) (
                $row['created_at']
                ?? ''
            ),

            (string) (
                $row['updated_at']
                ?? ''
            )
        );
    }
}