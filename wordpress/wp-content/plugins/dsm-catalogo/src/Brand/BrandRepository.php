<?php

declare(strict_types=1);

namespace DSM\Catalogo\Brand;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class BrandRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_brands';
    }

    public function findById(
        int $brandId
    ): ?Brand {
        global $wpdb;

        if ($brandId <= 0) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    name,
                    slug,
                    description,
                    website,
                    logo_id,
                    is_active,
                    is_verified,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1",
                $brandId
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return Brand::fromArray($row);
    }

    public function findBySlug(
        string $slug
    ): ?Brand {
        global $wpdb;

        $slug = sanitize_title($slug);

        if ($slug === '') {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    name,
                    slug,
                    description,
                    website,
                    logo_id,
                    is_active,
                    is_verified,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE slug = %s
                LIMIT 1",
                $slug
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return Brand::fromArray($row);
    }

    public function findByName(
        string $name
    ): ?Brand {
        global $wpdb;

        $name = trim($name);

        if ($name === '') {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    name,
                    slug,
                    description,
                    website,
                    logo_id,
                    is_active,
                    is_verified,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE name = %s
                LIMIT 1",
                $name
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return Brand::fromArray($row);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(
        array $data
    ): int {
        global $wpdb;

        $name = trim(
            (string) (
                $data['name']
                ?? ''
            )
        );

        if ($name === '') {
            throw new RuntimeException(
                'El nombre de la marca es obligatorio.'
            );
        }

        if (mb_strlen($name) > 120) {
            throw new RuntimeException(
                'El nombre de la marca no puede superar los 120 caracteres.'
            );
        }

        if ($this->findByName($name) !== null) {
            throw new RuntimeException(
                'Ya existe una marca con ese nombre.'
            );
        }

        $slugSource = isset($data['slug'])
            ? (string) $data['slug']
            : $name;

        $slug = $this->generateUniqueSlug(
            $slugSource
        );

        $description =
            self::normalizeNullableTextarea(
                $data['description']
                ?? null
            );

        $website =
            self::normalizeNullableUrl(
                $data['website']
                ?? null
            );

        $logoId =
            self::nullablePositiveInt(
                $data['logo_id']
                ?? null
            );

        $active =
            self::normalizeBoolean(
                $data['is_active']
                ?? true
            );

        $verified =
            self::normalizeBoolean(
                $data['is_verified']
                ?? true
            );

        $sortOrder = max(
            0,
            (int) (
                $data['sort_order']
                ?? 0
            )
        );

        $now = current_time(
            'mysql',
            true
        );

        $result = $wpdb->insert(
            $this->tableName,
            [
                'name' =>
                    $name,

                'slug' =>
                    $slug,

                'description' =>
                    $description,

                'website' =>
                    $website,

                'logo_id' =>
                    $logoId,

                'is_active' =>
                    $active
                        ? 1
                        : 0,

                'is_verified' =>
                    $verified
                        ? 1
                        : 0,

                'sort_order' =>
                    $sortOrder,

                'created_at' =>
                    $now,

                'updated_at' =>
                    $now,
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear la marca: %s',
                    $wpdb->last_error
                )
            );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        int $brandId,
        array $data
    ): void {
        global $wpdb;

        if ($brandId <= 0) {
            throw new RuntimeException(
                'El identificador de la marca no es válido.'
            );
        }

        $brand = $this->findById(
            $brandId
        );

        if ($brand === null) {
            throw new RuntimeException(
                'No se encontró la marca.'
            );
        }

        $name = array_key_exists(
            'name',
            $data
        )
            ? trim((string) $data['name'])
            : $brand->getName();

        if ($name === '') {
            throw new RuntimeException(
                'El nombre de la marca es obligatorio.'
            );
        }

        if (mb_strlen($name) > 120) {
            throw new RuntimeException(
                'El nombre de la marca no puede superar los 120 caracteres.'
            );
        }

        $existingByName =
            $this->findByName($name);

        if (
            $existingByName !== null
            && $existingByName->getId()
                !== $brandId
        ) {
            throw new RuntimeException(
                'Ya existe una marca con ese nombre.'
            );
        }

        $slug = $brand->getSlug();

        if (
            array_key_exists('slug', $data)
            || array_key_exists('name', $data)
        ) {
            $slugSource = array_key_exists(
                'slug',
                $data
            )
                ? (string) $data['slug']
                : $name;

            $slug = $this->generateUniqueSlug(
                $slugSource,
                $brandId
            );
        }

        $description = array_key_exists(
            'description',
            $data
        )
            ? self::normalizeNullableTextarea(
                $data['description']
            )
            : $brand->getDescription();

        $website = array_key_exists(
            'website',
            $data
        )
            ? self::normalizeNullableUrl(
                $data['website']
            )
            : $brand->getWebsite();

        $logoId = array_key_exists(
            'logo_id',
            $data
        )
            ? self::nullablePositiveInt(
                $data['logo_id']
            )
            : $brand->getLogoId();

        $active = array_key_exists(
            'is_active',
            $data
        )
            ? self::normalizeBoolean(
                $data['is_active']
            )
            : $brand->isActive();

        $verified = array_key_exists(
            'is_verified',
            $data
        )
            ? self::normalizeBoolean(
                $data['is_verified']
            )
            : $brand->isVerified();

        $sortOrder = array_key_exists(
            'sort_order',
            $data
        )
            ? max(
                0,
                (int) $data['sort_order']
            )
            : $brand->getSortOrder();

        $updated = $wpdb->update(
            $this->tableName,
            [
                'name' =>
                    $name,

                'slug' =>
                    $slug,

                'description' =>
                    $description,

                'website' =>
                    $website,

                'logo_id' =>
                    $logoId,

                'is_active' =>
                    $active
                        ? 1
                        : 0,

                'is_verified' =>
                    $verified
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
                    $brandId,
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo actualizar la marca: %s',
                    $wpdb->last_error
                )
            );
        }
    }

    public function setActive(
        int $brandId,
        bool $active
    ): void {
        global $wpdb;

        if ($brandId <= 0) {
            throw new RuntimeException(
                'El identificador de la marca no es válido.'
            );
        }

        if ($this->findById($brandId) === null) {
            throw new RuntimeException(
                'No se encontró la marca.'
            );
        }

        $updated = $wpdb->update(
            $this->tableName,
            [
                'is_active' =>
                    $active
                        ? 1
                        : 0,

                'updated_at' =>
                    current_time(
                        'mysql',
                        true
                    ),
            ],
            [
                'id' =>
                    $brandId,
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo modificar el estado de la marca.'
            );
        }
    }

    public function setVerified(
        int $brandId,
        bool $verified
    ): void {
        global $wpdb;

        if ($brandId <= 0) {
            throw new RuntimeException(
                'El identificador de la marca no es válido.'
            );
        }

        if ($this->findById($brandId) === null) {
            throw new RuntimeException(
                'No se encontró la marca.'
            );
        }

        $updated = $wpdb->update(
            $this->tableName,
            [
                'is_verified' =>
                    $verified
                        ? 1
                        : 0,

                'updated_at' =>
                    current_time(
                        'mysql',
                        true
                    ),
            ],
            [
                'id' =>
                    $brandId,
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo modificar la verificación de la marca.'
            );
        }
    }

    /**
     * @return array<int, Brand>
     */
    public function findAll(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT
                id,
                name,
                slug,
                description,
                website,
                logo_id,
                is_active,
                is_verified,
                sort_order,
                created_at,
                updated_at
            FROM {$this->tableName}
            ORDER BY
                sort_order ASC,
                name ASC",
            ARRAY_A
        );

        return $this->hydrateRows($rows);
    }

    /**
     * Marcas que pueden seleccionarse en formularios.
     *
     * @return array<int, Brand>
     */
    public function findSelectable(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT
                id,
                name,
                slug,
                description,
                website,
                logo_id,
                is_active,
                is_verified,
                sort_order,
                created_at,
                updated_at
            FROM {$this->tableName}
            WHERE is_active = 1
              AND is_verified = 1
            ORDER BY
                sort_order ASC,
                name ASC",
            ARRAY_A
        );

        return $this->hydrateRows($rows);
    }

    public function canBeSelected(
        int $brandId
    ): bool {
        if ($brandId <= 0) {
            return false;
        }

        $brand = $this->findById(
            $brandId
        );

        return $brand !== null
            && $brand->canBeSelected();
    }

    private function generateUniqueSlug(
        string $source,
        ?int $excludeBrandId = null
    ): string {
        $baseSlug = sanitize_title(
            $source
        );

        if ($baseSlug === '') {
            $baseSlug = 'marca';
        }

        $candidate = $baseSlug;
        $suffix = 2;

        while (
            $this->slugExists(
                $candidate,
                $excludeBrandId
            )
        ) {
            $candidate =
                $baseSlug
                . '-'
                . $suffix;

            $suffix++;
        }

        return $candidate;
    }

    private function slugExists(
        string $slug,
        ?int $excludeBrandId = null
    ): bool {
        global $wpdb;

        if (
            $excludeBrandId !== null
            && $excludeBrandId > 0
        ) {
            $found = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$this->tableName}
                    WHERE slug = %s
                      AND id <> %d
                    LIMIT 1",
                    $slug,
                    $excludeBrandId
                )
            );
        } else {
            $found = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$this->tableName}
                    WHERE slug = %s
                    LIMIT 1",
                    $slug
                )
            );
        }

        return $found !== null;
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, Brand>
     */
    private function hydrateRows(
        mixed $rows
    ): array {
        if (!is_array($rows)) {
            return [];
        }

        return array_map(
            static fn (
                array $row
            ): Brand =>
                Brand::fromArray($row),
            $rows
        );
    }

    private static function nullablePositiveInt(
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

    private static function normalizeNullableTextarea(
        mixed $value
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        return sanitize_textarea_field(
            (string) $value
        );
    }

    private static function normalizeNullableUrl(
        mixed $value
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        $url = esc_url_raw(
            trim((string) $value)
        );

        if ($url === '') {
            throw new RuntimeException(
                'La dirección web de la marca no es válida.'
            );
        }

        return $url;
    }

    private static function normalizeBoolean(
        mixed $value
    ): bool {
        return in_array(
            $value,
            [
                true,
                1,
                '1',
                'yes',
                'on',
            ],
            true
        );
    }
}