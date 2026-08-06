<?php

declare(strict_types=1);

namespace DSM\Ubicaciones\Municipality;

use RuntimeException;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repositorio de municipios.
 *
 * Permite:
 *
 * - buscar por ID;
 * - buscar por área y slug;
 * - listar todos;
 * - listar activos;
 * - listar por área;
 * - crear y actualizar municipios;
 * - activar o desactivar registros.
 */
final class MunicipalityRepository
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
            . 'dsm_municipalities';
    }

    public function findById(
        int $municipalityId
    ): ?Municipality {
        if ($municipalityId <= 0) {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    area_id,
                    name,
                    slug,
                    code,
                    postal_code,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1
                ",
                $municipalityId
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

    public function findByAreaAndSlug(
        int $areaId,
        string $slug
    ): ?Municipality {
        if ($areaId <= 0) {
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
                    area_id,
                    name,
                    slug,
                    code,
                    postal_code,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE area_id = %d
                  AND slug = %s
                LIMIT 1
                ",
                $areaId,
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
     * @return array<int, Municipality>
     */
    public function findAll(): array
    {
        $rows =
            $this->database->get_results(
                "
                SELECT
                    id,
                    area_id,
                    name,
                    slug,
                    code,
                    postal_code,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                ORDER BY
                    area_id ASC,
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
     * @return array<int, Municipality>
     */
    public function findActive(): array
    {
        $rows =
            $this->database->get_results(
                "
                SELECT
                    id,
                    area_id,
                    name,
                    slug,
                    code,
                    postal_code,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE is_active = 1
                ORDER BY
                    area_id ASC,
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
     * @return array<int, Municipality>
     */
    public function findByAreaId(
        int $areaId,
        bool $onlyActive = false
    ): array {
        if ($areaId <= 0) {
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
                    area_id,
                    name,
                    slug,
                    code,
                    postal_code,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE area_id = %d
                {$activeCondition}
                ORDER BY
                    sort_order ASC,
                    name ASC,
                    id ASC
                ",
                $areaId
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
        int $areaId,
        string $name,
        ?string $code,
        ?string $postalCode,
        int $sortOrder = 0,
        bool $isActive = true
    ): Municipality {
        if ($areaId <= 0) {
            throw new RuntimeException(
                'El identificador del área no es válido.'
            );
        }

        $name =
            sanitize_text_field(
                trim($name)
            );

        $code =
            $this->normalizeNullableText(
                $code
            );

        $postalCode =
            $this->normalizeNullableText(
                $postalCode
            );

        $sortOrder =
            max(
                0,
                $sortOrder
            );

        if ($name === '') {
            throw new RuntimeException(
                'El nombre del municipio no puede estar vacío.'
            );
        }

        if (
            mb_strlen(
                $name
            ) > 150
        ) {
            throw new RuntimeException(
                'El nombre del municipio es demasiado largo.'
            );
        }

        $slug =
            $this->generateUniqueSlug(
                $areaId,
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
                    'area_id' =>
                        $areaId,

                    'name' =>
                        $name,

                    'slug' =>
                        $slug,

                    'code' =>
                        $code,

                    'postal_code' =>
                        $postalCode,

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
                'No se pudo crear el municipio.'
            );
        }

        $municipality =
            $this->findById(
                (int) $this->database
                    ->insert_id
            );

        if ($municipality === null) {
            throw new RuntimeException(
                'El municipio fue creado pero no pudo recuperarse.'
            );
        }

        return $municipality;
    }

    public function update(
        int $municipalityId,
        int $areaId,
        string $name,
        ?string $code,
        ?string $postalCode,
        int $sortOrder,
        bool $isActive
    ): Municipality {
        if ($municipalityId <= 0) {
            throw new RuntimeException(
                'El identificador del municipio no es válido.'
            );
        }

        $municipality =
            $this->findById(
                $municipalityId
            );

        if ($municipality === null) {
            throw new RuntimeException(
                'No se encontró el municipio.'
            );
        }

        if ($areaId <= 0) {
            throw new RuntimeException(
                'El identificador del área no es válido.'
            );
        }

        $name =
            sanitize_text_field(
                trim($name)
            );

        $code =
            $this->normalizeNullableText(
                $code
            );

        $postalCode =
            $this->normalizeNullableText(
                $postalCode
            );

        $sortOrder =
            max(
                0,
                $sortOrder
            );

        if ($name === '') {
            throw new RuntimeException(
                'El nombre del municipio no puede estar vacío.'
            );
        }

        if (
            mb_strlen(
                $name
            ) > 150
        ) {
            throw new RuntimeException(
                'El nombre del municipio es demasiado largo.'
            );
        }

        $slug =
            $this->generateUniqueSlug(
                $areaId,
                $name,
                $municipalityId
            );

        $result =
            $this->database->update(
                $this->tableName,
                [
                    'area_id' =>
                        $areaId,

                    'name' =>
                        $name,

                    'slug' =>
                        $slug,

                    'code' =>
                        $code,

                    'postal_code' =>
                        $postalCode,

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
                        $municipalityId,
                ],
                [
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
                'No se pudo actualizar el municipio.'
            );
        }

        $updatedMunicipality =
            $this->findById(
                $municipalityId
            );

        if ($updatedMunicipality === null) {
            throw new RuntimeException(
                'El municipio fue actualizado pero no pudo recuperarse.'
            );
        }

        return $updatedMunicipality;
    }

    public function setActive(
        int $municipalityId,
        bool $isActive
    ): Municipality {
        $municipality =
            $this->findById(
                $municipalityId
            );

        if ($municipality === null) {
            throw new RuntimeException(
                'No se encontró el municipio.'
            );
        }

        return $this->update(
            $municipalityId,
            $municipality->getAreaId(),
            $municipality->getName(),
            $municipality->getCode(),
            $municipality->getPostalCode(),
            $municipality->getSortOrder(),
            $isActive
        );
    }

    private function generateUniqueSlug(
        int $areaId,
        string $name,
        ?int $excludedMunicipalityId = null
    ): string {
        $baseSlug =
            sanitize_title(
                $name
            );

        if ($baseSlug === '') {
            $baseSlug =
                'municipio';
        }

        $slug =
            $baseSlug;

        $suffix =
            2;

        while (
            $this->slugExists(
                $areaId,
                $slug,
                $excludedMunicipalityId
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
        int $areaId,
        string $slug,
        ?int $excludedMunicipalityId = null
    ): bool {
        $parameters = [
            $areaId,
            $slug,
        ];

        $excludedCondition = '';

        if (
            $excludedMunicipalityId !== null
            && $excludedMunicipalityId > 0
        ) {
            $excludedCondition =
                'AND id <> %d';

            $parameters[] =
                $excludedMunicipalityId;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT COUNT(*)
                FROM {$this->tableName}
                WHERE area_id = %d
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
     * @return array<int, Municipality>
     */
    private function hydrateMany(
        mixed $rows
    ): array {
        if (!is_array($rows)) {
            return [];
        }

        $municipalities = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $municipalities[] =
                $this->hydrate(
                    $row
                );
        }

        return $municipalities;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row
    ): Municipality {
        return new Municipality(
            (int) (
                $row['id']
                ?? 0
            ),

            (int) (
                $row['area_id']
                ?? 0
            ),

            (string) (
                $row['name']
                ?? ''
            ),

            (string) (
                $row['slug']
                ?? ''
            ),

            isset($row['code'])
            && $row['code'] !== null
                ? (string) $row['code']
                : null,

            isset($row['postal_code'])
            && $row['postal_code'] !== null
                ? (string) $row[
                    'postal_code'
                ]
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