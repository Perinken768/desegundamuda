<?php

declare(strict_types=1);

namespace DSM\Anuncios\Category;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CategoryRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_categories';
    }

    public function findById(
        int $categoryId
    ): ?Category {
        global $wpdb;

        if ($categoryId <= 0) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    parent_id,
                    name,
                    slug,
                    description,
                    marketplace_allowed,
                    store_allowed,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1",
                $categoryId
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return Category::fromArray($row);
    }

    public function findBySlug(
        string $slug
    ): ?Category {
        global $wpdb;

        $slug = sanitize_title($slug);

        if ($slug === '') {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    parent_id,
                    name,
                    slug,
                    description,
                    marketplace_allowed,
                    store_allowed,
                    is_active,
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

        return Category::fromArray($row);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(
        array $data
    ): int {
        global $wpdb;

        $parentId = self::nullablePositiveInt(
            $data['parent_id'] ?? null
        );

        $name = trim(
            (string) (
                $data['name'] ?? ''
            )
        );

        $description =
            self::normalizeNullableText(
                $data['description'] ?? null
            );

        $marketplaceAllowed =
            self::normalizeBoolean(
                $data['marketplace_allowed']
                ?? true
            );

        $storeAllowed =
            self::normalizeBoolean(
                $data['store_allowed']
                ?? true
            );

        $active =
            self::normalizeBoolean(
                $data['is_active']
                ?? true
            );

        $sortOrder = max(
            0,
            (int) (
                $data['sort_order'] ?? 0
            )
        );

        if ($name === '') {
            throw new RuntimeException(
                'El nombre de la categoría es obligatorio.'
            );
        }

        if ($parentId !== null) {
            $parent = $this->findById(
                $parentId
            );

            if ($parent === null) {
                throw new RuntimeException(
                    'La categoría superior no existe.'
                );
            }
        }

        $slugSource = isset($data['slug'])
            ? (string) $data['slug']
            : $name;

        $slug = $this->generateUniqueSlug(
            $slugSource
        );

        $now = current_time(
            'mysql',
            true
        );

        $result = $wpdb->insert(
            $this->tableName,
            [
                'parent_id' =>
                    $parentId,

                'name' =>
                    $name,

                'slug' =>
                    $slug,

                'description' =>
                    $description,

                'marketplace_allowed' =>
                    $marketplaceAllowed
                        ? 1
                        : 0,

                'store_allowed' =>
                    $storeAllowed
                        ? 1
                        : 0,

                'is_active' =>
                    $active
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
                $parentId === null
                    ? null
                    : '%d',

                '%s',
                '%s',

                $description === null
                    ? null
                    : '%s',

                '%d',
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear la categoría.'
            );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        int $categoryId,
        array $data
    ): void {
        global $wpdb;

        if ($categoryId <= 0) {
            throw new RuntimeException(
                'El identificador de la categoría no es válido.'
            );
        }

        $category = $this->findById(
            $categoryId
        );

        if ($category === null) {
            throw new RuntimeException(
                'No se encontró la categoría.'
            );
        }

        $parentId = array_key_exists(
            'parent_id',
            $data
        )
            ? self::nullablePositiveInt(
                $data['parent_id']
            )
            : $category->getParentId();

        if ($parentId === $categoryId) {
            throw new RuntimeException(
                'Una categoría no puede depender de sí misma.'
            );
        }

        if ($parentId !== null) {
            $parent = $this->findById(
                $parentId
            );

            if ($parent === null) {
                throw new RuntimeException(
                    'La categoría superior no existe.'
                );
            }

            if (
                $this->isDescendantOf(
                    $parentId,
                    $categoryId
                )
            ) {
                throw new RuntimeException(
                    'No se puede crear una relación circular entre categorías.'
                );
            }
        }

        $name = isset($data['name'])
            ? trim((string) $data['name'])
            : $category->getName();

        if ($name === '') {
            throw new RuntimeException(
                'El nombre de la categoría es obligatorio.'
            );
        }

        $description = array_key_exists(
            'description',
            $data
        )
            ? self::normalizeNullableText(
                $data['description']
            )
            : $category->getDescription();

        $marketplaceAllowed = array_key_exists(
            'marketplace_allowed',
            $data
        )
            ? self::normalizeBoolean(
                $data['marketplace_allowed']
            )
            : $category
                ->isMarketplaceAllowed();

        $storeAllowed = array_key_exists(
            'store_allowed',
            $data
        )
            ? self::normalizeBoolean(
                $data['store_allowed']
            )
            : $category
                ->isStoreAllowed();

        $active = array_key_exists(
            'is_active',
            $data
        )
            ? self::normalizeBoolean(
                $data['is_active']
            )
            : $category->isActive();

        $sortOrder = array_key_exists(
            'sort_order',
            $data
        )
            ? max(
                0,
                (int) $data['sort_order']
            )
            : $category->getSortOrder();

        $slug = $category->getSlug();

        if (
            isset($data['slug'])
            || isset($data['name'])
        ) {
            $slugSource = isset($data['slug'])
                ? (string) $data['slug']
                : $name;

            $slug = $this->generateUniqueSlug(
                $slugSource,
                $categoryId
            );
        }

        $updated = $wpdb->update(
            $this->tableName,
            [
                'parent_id' =>
                    $parentId,

                'name' =>
                    $name,

                'slug' =>
                    $slug,

                'description' =>
                    $description,

                'marketplace_allowed' =>
                    $marketplaceAllowed
                        ? 1
                        : 0,

                'store_allowed' =>
                    $storeAllowed
                        ? 1
                        : 0,

                'is_active' =>
                    $active
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
                'id' => $categoryId,
            ],
            [
                $parentId === null
                    ? null
                    : '%d',

                '%s',
                '%s',

                $description === null
                    ? null
                    : '%s',

                '%d',
                '%d',
                '%d',
                '%d',
                '%s',
            ],
            [
                '%d',
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo actualizar la categoría.'
            );
        }
    }

    public function setActive(
        int $categoryId,
        bool $active
    ): void {
        global $wpdb;

        if ($categoryId <= 0) {
            throw new RuntimeException(
                'El identificador de la categoría no es válido.'
            );
        }

        if ($this->findById($categoryId) === null) {
            throw new RuntimeException(
                'No se encontró la categoría.'
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
                'id' => $categoryId,
            ],
            [
                '%d',
                '%s',
            ],
            [
                '%d',
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo modificar el estado de la categoría.'
            );
        }
    }

    /**
     * @return array<int, Category>
     */
    public function findAll(
        bool $onlyActive = false
    ): array {
        global $wpdb;

        $where = $onlyActive
            ? 'WHERE is_active = 1'
            : '';

        $rows = $wpdb->get_results(
            "SELECT
                id,
                parent_id,
                name,
                slug,
                description,
                marketplace_allowed,
                store_allowed,
                is_active,
                sort_order,
                created_at,
                updated_at
            FROM {$this->tableName}
            {$where}
            ORDER BY
                parent_id IS NOT NULL,
                parent_id ASC,
                sort_order ASC,
                name ASC",
            ARRAY_A
        );

        return $this->hydrateRows($rows);
    }

    /**
     * @return array<int, Category>
     */
    public function findRoots(
        bool $onlyActive = true
    ): array {
        global $wpdb;

        $whereActive = $onlyActive
            ? 'AND is_active = 1'
            : '';

        $rows = $wpdb->get_results(
            "SELECT
                id,
                parent_id,
                name,
                slug,
                description,
                marketplace_allowed,
                store_allowed,
                is_active,
                sort_order,
                created_at,
                updated_at
            FROM {$this->tableName}
            WHERE parent_id IS NULL
            {$whereActive}
            ORDER BY
                sort_order ASC,
                name ASC",
            ARRAY_A
        );

        return $this->hydrateRows($rows);
    }

    /**
     * @return array<int, Category>
     */
    public function findChildren(
        int $parentId,
        bool $onlyActive = true
    ): array {
        global $wpdb;

        if ($parentId <= 0) {
            return [];
        }

        $activeCondition = $onlyActive
            ? 'AND is_active = 1'
            : '';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    id,
                    parent_id,
                    name,
                    slug,
                    description,
                    marketplace_allowed,
                    store_allowed,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE parent_id = %d
                {$activeCondition}
                ORDER BY
                    sort_order ASC,
                    name ASC",
                $parentId
            ),
            ARRAY_A
        );

        return $this->hydrateRows($rows);
    }

    /**
     * Categorías seleccionables por clientes.
     *
     * @return array<int, Category>
     */
    public function findMarketplaceCategories(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT
                id,
                parent_id,
                name,
                slug,
                description,
                marketplace_allowed,
                store_allowed,
                is_active,
                sort_order,
                created_at,
                updated_at
            FROM {$this->tableName}
            WHERE is_active = 1
              AND marketplace_allowed = 1
            ORDER BY
                parent_id IS NOT NULL,
                parent_id ASC,
                sort_order ASC,
                name ASC",
            ARRAY_A
        );

        return $this->hydrateRows($rows);
    }

    /**
     * Categorías permitidas en tiendas profesionales.
     *
     * @return array<int, Category>
     */
    public function findStoreCategories(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT
                id,
                parent_id,
                name,
                slug,
                description,
                marketplace_allowed,
                store_allowed,
                is_active,
                sort_order,
                created_at,
                updated_at
            FROM {$this->tableName}
            WHERE is_active = 1
              AND store_allowed = 1
            ORDER BY
                parent_id IS NOT NULL,
                parent_id ASC,
                sort_order ASC,
                name ASC",
            ARRAY_A
        );

        return $this->hydrateRows($rows);
    }

    public function canBeUsedInMarketplace(
        int $categoryId
    ): bool {
        $category = $this->findById(
            $categoryId
        );

        return $category !== null
            && $category
                ->canBeUsedInMarketplace();
    }

    public function canBeUsedInStore(
        int $categoryId
    ): bool {
        $category = $this->findById(
            $categoryId
        );

        return $category !== null
            && $category
                ->canBeUsedInStore();
    }

    private function generateUniqueSlug(
        string $source,
        ?int $excludeCategoryId = null
    ): string {
        $baseSlug = sanitize_title(
            $source
        );

        if ($baseSlug === '') {
            $baseSlug = 'categoria';
        }

        $candidate = $baseSlug;
        $suffix = 2;

        while (
            $this->slugExists(
                $candidate,
                $excludeCategoryId
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
        ?int $excludeCategoryId = null
    ): bool {
        global $wpdb;

        if (
            $excludeCategoryId !== null
            && $excludeCategoryId > 0
        ) {
            $found = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$this->tableName}
                    WHERE slug = %s
                      AND id <> %d
                    LIMIT 1",
                    $slug,
                    $excludeCategoryId
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

    private function isDescendantOf(
        int $possibleDescendantId,
        int $categoryId
    ): bool {
        $visited = [];
        $currentId = $possibleDescendantId;

        while ($currentId > 0) {
            if (isset($visited[$currentId])) {
                return true;
            }

            $visited[$currentId] = true;

            if ($currentId === $categoryId) {
                return true;
            }

            $category = $this->findById(
                $currentId
            );

            if (
                $category === null
                || $category->getParentId() === null
            ) {
                return false;
            }

            $currentId =
                $category->getParentId();
        }

        return false;
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, Category>
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
            ): Category =>
                Category::fromArray($row),
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

    private static function normalizeNullableText(
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