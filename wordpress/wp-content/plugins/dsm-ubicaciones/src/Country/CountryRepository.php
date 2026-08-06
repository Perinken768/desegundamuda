<?php

declare(strict_types=1);

namespace DSM\Ubicaciones\Country;

use RuntimeException;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repositorio de países.
 *
 * Permite:
 *
 * - buscar por ID;
 * - buscar por código ISO;
 * - listar todos;
 * - listar únicamente activos;
 * - crear y actualizar países;
 * - activar o desactivar registros.
 */
final class CountryRepository
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
            . 'dsm_countries';
    }

    public function findById(
        int $countryId
    ): ?Country {
        if ($countryId <= 0) {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    name,
                    slug,
                    iso_code,
                    phone_prefix,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1
                ",
                $countryId
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

    public function findByIsoCode(
        string $isoCode
    ): ?Country {
        $isoCode =
            strtoupper(
                sanitize_text_field(
                    trim($isoCode)
                )
            );

        if ($isoCode === '') {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    name,
                    slug,
                    iso_code,
                    phone_prefix,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE iso_code = %s
                LIMIT 1
                ",
                $isoCode
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
     * @return array<int, Country>
     */
    public function findAll(): array
    {
        $rows =
            $this->database->get_results(
                "
                SELECT
                    id,
                    name,
                    slug,
                    iso_code,
                    phone_prefix,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                ORDER BY
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
     * @return array<int, Country>
     */
    public function findActive(): array
    {
        $rows =
            $this->database->get_results(
                "
                SELECT
                    id,
                    name,
                    slug,
                    iso_code,
                    phone_prefix,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE is_active = 1
                ORDER BY
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

    public function create(
        string $name,
        string $isoCode,
        ?string $phonePrefix,
        int $sortOrder = 0,
        bool $isActive = true
    ): Country {
        $name =
            sanitize_text_field(
                trim($name)
            );

        $isoCode =
            strtoupper(
                sanitize_text_field(
                    trim($isoCode)
                )
            );

        $phonePrefix =
            $this->normalizeNullableText(
                $phonePrefix
            );

        $sortOrder =
            max(
                0,
                $sortOrder
            );

        if ($name === '') {
            throw new RuntimeException(
                'El nombre del país no puede estar vacío.'
            );
        }

        if (
            mb_strlen(
                $name
            ) > 150
        ) {
            throw new RuntimeException(
                'El nombre del país es demasiado largo.'
            );
        }

        if (
            preg_match(
                '/^[A-Z]{2,3}$/',
                $isoCode
            ) !== 1
        ) {
            throw new RuntimeException(
                'El código ISO debe contener dos o tres letras.'
            );
        }

        if (
            $this->findByIsoCode(
                $isoCode
            ) !== null
        ) {
            throw new RuntimeException(
                'Ya existe un país con ese código ISO.'
            );
        }

        $slug =
            $this->generateUniqueSlug(
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
                    'name' =>
                        $name,

                    'slug' =>
                        $slug,

                    'iso_code' =>
                        $isoCode,

                    'phone_prefix' =>
                        $phonePrefix,

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
                'No se pudo crear el país.'
            );
        }

        $country =
            $this->findById(
                (int) $this->database
                    ->insert_id
            );

        if ($country === null) {
            throw new RuntimeException(
                'El país fue creado pero no pudo recuperarse.'
            );
        }

        return $country;
    }

    public function update(
        int $countryId,
        string $name,
        string $isoCode,
        ?string $phonePrefix,
        int $sortOrder,
        bool $isActive
    ): Country {
        if ($countryId <= 0) {
            throw new RuntimeException(
                'El identificador del país no es válido.'
            );
        }

        $country =
            $this->findById(
                $countryId
            );

        if ($country === null) {
            throw new RuntimeException(
                'No se encontró el país.'
            );
        }

        $name =
            sanitize_text_field(
                trim($name)
            );

        $isoCode =
            strtoupper(
                sanitize_text_field(
                    trim($isoCode)
                )
            );

        $phonePrefix =
            $this->normalizeNullableText(
                $phonePrefix
            );

        $sortOrder =
            max(
                0,
                $sortOrder
            );

        if ($name === '') {
            throw new RuntimeException(
                'El nombre del país no puede estar vacío.'
            );
        }

        if (
            mb_strlen(
                $name
            ) > 150
        ) {
            throw new RuntimeException(
                'El nombre del país es demasiado largo.'
            );
        }

        if (
            preg_match(
                '/^[A-Z]{2,3}$/',
                $isoCode
            ) !== 1
        ) {
            throw new RuntimeException(
                'El código ISO debe contener dos o tres letras.'
            );
        }

        $countryWithSameIso =
            $this->findByIsoCode(
                $isoCode
            );

        if (
            $countryWithSameIso !== null
            && $countryWithSameIso
                ->getId() !== $countryId
        ) {
            throw new RuntimeException(
                'Ya existe otro país con ese código ISO.'
            );
        }

        $slug =
            $this->generateUniqueSlug(
                $name,
                $countryId
            );

        $result =
            $this->database->update(
                $this->tableName,
                [
                    'name' =>
                        $name,

                    'slug' =>
                        $slug,

                    'iso_code' =>
                        $isoCode,

                    'phone_prefix' =>
                        $phonePrefix,

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
                        $countryId,
                ],
                [
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
                'No se pudo actualizar el país.'
            );
        }

        $updatedCountry =
            $this->findById(
                $countryId
            );

        if ($updatedCountry === null) {
            throw new RuntimeException(
                'El país fue actualizado pero no pudo recuperarse.'
            );
        }

        return $updatedCountry;
    }

    public function setActive(
        int $countryId,
        bool $isActive
    ): Country {
        $country =
            $this->findById(
                $countryId
            );

        if ($country === null) {
            throw new RuntimeException(
                'No se encontró el país.'
            );
        }

        return $this->update(
            $countryId,
            $country->getName(),
            $country->getIsoCode(),
            $country->getPhonePrefix(),
            $country->getSortOrder(),
            $isActive
        );
    }

    private function generateUniqueSlug(
        string $name,
        ?int $excludedCountryId = null
    ): string {
        $baseSlug =
            sanitize_title(
                $name
            );

        if ($baseSlug === '') {
            $baseSlug =
                'pais';
        }

        $slug =
            $baseSlug;

        $suffix =
            2;

        while (
            $this->slugExists(
                $slug,
                $excludedCountryId
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
        string $slug,
        ?int $excludedCountryId = null
    ): bool {
        $parameters = [
            $slug,
        ];

        $whereExcluded = '';

        if (
            $excludedCountryId !== null
            && $excludedCountryId > 0
        ) {
            $whereExcluded =
                'AND id <> %d';

            $parameters[] =
                $excludedCountryId;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT COUNT(*)
                FROM {$this->tableName}
                WHERE slug = %s
                {$whereExcluded}
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
     * @return array<int, Country>
     */
    private function hydrateMany(
        mixed $rows
    ): array {
        if (!is_array($rows)) {
            return [];
        }

        $countries = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $countries[] =
                $this->hydrate(
                    $row
                );
        }

        return $countries;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row
    ): Country {
        return new Country(
            (int) (
                $row['id']
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

            (string) (
                $row['iso_code']
                ?? ''
            ),

            isset($row['phone_prefix'])
            && $row['phone_prefix'] !== null
                ? (string) $row[
                    'phone_prefix'
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