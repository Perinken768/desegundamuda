<?php

declare(strict_types=1);

namespace DSM\Catalogo\Product;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class ProductRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_products';
    }

    public function findById(
        int $productId
    ): ?Product {
        global $wpdb;

        if ($productId <= 0) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    store_id,
                    brand_id,
                    name,
                    slug,
                    description,
                    internal_reference,
                    base_sku,
                    default_price,
                    original_price,
                    cost_price,
                    purchase_date,
                    tax_rate,
                    track_stock,
                    status,
                    created_by_customer_id,
                    updated_by_customer_id,
                    created_at,
                    updated_at,
                    archived_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1",
                $productId
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return Product::fromArray($row);
    }

    public function findBySlug(
        int $storeId,
        string $slug
    ): ?Product {
        global $wpdb;

        if ($storeId <= 0) {
            return null;
        }

        $slug = sanitize_title($slug);

        if ($slug === '') {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    store_id,
                    brand_id,
                    name,
                    slug,
                    description,
                    internal_reference,
                    base_sku,
                    default_price,
                    original_price,
                    cost_price,
                    purchase_date,
                    tax_rate,
                    track_stock,
                    status,
                    created_by_customer_id,
                    updated_by_customer_id,
                    created_at,
                    updated_at,
                    archived_at
                FROM {$this->tableName}
                WHERE store_id = %d
                  AND slug = %s
                LIMIT 1",
                $storeId,
                $slug
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return Product::fromArray($row);
    }

    public function findByInternalReference(
        int $storeId,
        string $internalReference
    ): ?Product {
        global $wpdb;

        if ($storeId <= 0) {
            return null;
        }

        $internalReference =
            trim($internalReference);

        if ($internalReference === '') {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    store_id,
                    brand_id,
                    name,
                    slug,
                    description,
                    internal_reference,
                    base_sku,
                    default_price,
                    original_price,
                    cost_price,
                    purchase_date,
                    tax_rate,
                    track_stock,
                    status,
                    created_by_customer_id,
                    updated_by_customer_id,
                    created_at,
                    updated_at,
                    archived_at
                FROM {$this->tableName}
                WHERE store_id = %d
                  AND internal_reference = %s
                LIMIT 1",
                $storeId,
                $internalReference
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return Product::fromArray($row);
    }

    public function findByBaseSku(
        int $storeId,
        string $baseSku
    ): ?Product {
        global $wpdb;

        if ($storeId <= 0) {
            return null;
        }

        $baseSku = trim($baseSku);

        if ($baseSku === '') {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    store_id,
                    brand_id,
                    name,
                    slug,
                    description,
                    internal_reference,
                    base_sku,
                    default_price,
                    original_price,
                    cost_price,
                    purchase_date,
                    tax_rate,
                    track_stock,
                    status,
                    created_by_customer_id,
                    updated_by_customer_id,
                    created_at,
                    updated_at,
                    archived_at
                FROM {$this->tableName}
                WHERE store_id = %d
                  AND base_sku = %s
                LIMIT 1",
                $storeId,
                $baseSku
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return Product::fromArray($row);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(
        array $data
    ): int {
        global $wpdb;

        $storeId = (int) (
            $data['store_id']
            ?? 0
        );

        $brandId =
            self::nullablePositiveInt(
                $data['brand_id']
                ?? null
            );

        $name = trim(
            (string) (
                $data['name']
                ?? ''
            )
        );

        $description =
            self::normalizeNullableTextarea(
                $data['description']
                ?? null
            );

        $internalReference =
            self::normalizeNullableShortText(
                $data['internal_reference']
                ?? null,
                100
            );

        $baseSku =
            self::normalizeNullableShortText(
                $data['base_sku']
                ?? null,
                100
            );

        $defaultPrice =
            self::normalizePrice(
                $data['default_price']
                ?? 0
            );

        $originalPrice =
            self::normalizeNullablePrice(
                $data['original_price']
                ?? null
            );

        $costPrice =
            self::normalizeNullablePrice(
                $data['cost_price']
                ?? null
            );

        $purchaseDate =
            self::normalizeNullableDate(
                $data['purchase_date']
                ?? null
            );

        $taxRate =
            self::normalizeNullableTaxRate(
                $data['tax_rate']
                ?? null
            );

        $trackStock =
            self::normalizeBoolean(
                $data['track_stock']
                ?? true
            );

        $status = sanitize_key(
            (string) (
                $data['status']
                ?? ProductStatus::DRAFT
            )
        );

        $createdByCustomerId = (int) (
            $data['created_by_customer_id']
            ?? 0
        );

        if ($storeId <= 0) {
            throw new RuntimeException(
                'El identificador de la tienda no es válido.'
            );
        }

        if ($name === '') {
            throw new RuntimeException(
                'El nombre del producto es obligatorio.'
            );
        }

        if (mb_strlen($name) > 180) {
            throw new RuntimeException(
                'El nombre del producto no puede superar los 180 caracteres.'
            );
        }

        if (
            !ProductStatus::isValid(
                $status
            )
        ) {
            throw new RuntimeException(
                'El estado del producto no es válido.'
            );
        }

        if ($createdByCustomerId <= 0) {
            throw new RuntimeException(
                'El creador del producto no es válido.'
            );
        }

        if (
            $internalReference !== null
            && $this->findByInternalReference(
                $storeId,
                $internalReference
            ) !== null
        ) {
            throw new RuntimeException(
                'Ya existe un producto con esa referencia interna en la tienda.'
            );
        }

        if (
            $baseSku !== null
            && $this->findByBaseSku(
                $storeId,
                $baseSku
            ) !== null
        ) {
            throw new RuntimeException(
                'Ya existe un producto con ese SKU base en la tienda.'
            );
        }

        $slugSource = isset($data['slug'])
            ? (string) $data['slug']
            : $name;

        $slug = $this->generateUniqueSlug(
            $storeId,
            $slugSource
        );

        $now = current_time(
            'mysql',
            true
        );

        $result = $wpdb->insert(
            $this->tableName,
            [
                'store_id' =>
                    $storeId,

                'brand_id' =>
                    $brandId,

                'name' =>
                    $name,

                'slug' =>
                    $slug,

                'description' =>
                    $description,

                'internal_reference' =>
                    $internalReference,

                'base_sku' =>
                    $baseSku,

                'default_price' =>
                    $defaultPrice,

                'original_price' =>
                    $originalPrice,

                'cost_price' =>
                    $costPrice,

                'purchase_date' =>
                    $purchaseDate,

                'tax_rate' =>
                    $taxRate,

                'track_stock' =>
                    $trackStock
                        ? 1
                        : 0,

                'status' =>
                    $status,

                'created_by_customer_id' =>
                    $createdByCustomerId,

                'updated_by_customer_id' =>
                    null,

                'created_at' =>
                    $now,

                'updated_at' =>
                    $now,

                'archived_at' =>
                    null,
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear el producto: %s',
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
        int $productId,
        int $updatedByCustomerId,
        array $data
    ): void {
        global $wpdb;

        if ($productId <= 0) {
            throw new RuntimeException(
                'El identificador del producto no es válido.'
            );
        }

        if ($updatedByCustomerId <= 0) {
            throw new RuntimeException(
                'El usuario que modifica el producto no es válido.'
            );
        }

        $product = $this->findById(
            $productId
        );

        if ($product === null) {
            throw new RuntimeException(
                'No se encontró el producto.'
            );
        }

        if (!$product->canBeEdited()) {
            throw new RuntimeException(
                'El producto no se puede editar en su estado actual.'
            );
        }

        $brandId = array_key_exists(
            'brand_id',
            $data
        )
            ? self::nullablePositiveInt(
                $data['brand_id']
            )
            : $product->getBrandId();

        $name = array_key_exists(
            'name',
            $data
        )
            ? trim((string) $data['name'])
            : $product->getName();

        if ($name === '') {
            throw new RuntimeException(
                'El nombre del producto es obligatorio.'
            );
        }

        if (mb_strlen($name) > 180) {
            throw new RuntimeException(
                'El nombre del producto no puede superar los 180 caracteres.'
            );
        }

        $description = array_key_exists(
            'description',
            $data
        )
            ? self::normalizeNullableTextarea(
                $data['description']
            )
            : $product->getDescription();

        $internalReference = array_key_exists(
            'internal_reference',
            $data
        )
            ? self::normalizeNullableShortText(
                $data['internal_reference'],
                100
            )
            : $product
                ->getInternalReference();

        $baseSku = array_key_exists(
            'base_sku',
            $data
        )
            ? self::normalizeNullableShortText(
                $data['base_sku'],
                100
            )
            : $product->getBaseSku();

        if ($internalReference !== null) {
            $existing =
                $this->findByInternalReference(
                    $product->getStoreId(),
                    $internalReference
                );

            if (
                $existing !== null
                && $existing->getId()
                    !== $productId
            ) {
                throw new RuntimeException(
                    'Ya existe un producto con esa referencia interna en la tienda.'
                );
            }
        }

        if ($baseSku !== null) {
            $existing =
                $this->findByBaseSku(
                    $product->getStoreId(),
                    $baseSku
                );

            if (
                $existing !== null
                && $existing->getId()
                    !== $productId
            ) {
                throw new RuntimeException(
                    'Ya existe un producto con ese SKU base en la tienda.'
                );
            }
        }

        $defaultPrice = array_key_exists(
            'default_price',
            $data
        )
            ? self::normalizePrice(
                $data['default_price']
            )
            : $product->getDefaultPrice();

        $originalPrice = array_key_exists(
            'original_price',
            $data
        )
            ? self::normalizeNullablePrice(
                $data['original_price']
            )
            : $product->getOriginalPrice();

        $costPrice = array_key_exists(
            'cost_price',
            $data
        )
            ? self::normalizeNullablePrice(
                $data['cost_price']
            )
            : $product->getCostPrice();

        $purchaseDate = array_key_exists(
            'purchase_date',
            $data
        )
            ? self::normalizeNullableDate(
                $data['purchase_date']
            )
            : (
                $product
                    ->getPurchaseDate()
                    ?->format('Y-m-d')
            );

        $taxRate = array_key_exists(
            'tax_rate',
            $data
        )
            ? self::normalizeNullableTaxRate(
                $data['tax_rate']
            )
            : $product->getTaxRate();

        $trackStock = array_key_exists(
            'track_stock',
            $data
        )
            ? self::normalizeBoolean(
                $data['track_stock']
            )
            : $product->tracksStock();

        $slug = $product->getSlug();

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
                $product->getStoreId(),
                $slugSource,
                $productId
            );
        }

        $updated = $wpdb->update(
            $this->tableName,
            [
                'brand_id' =>
                    $brandId,

                'name' =>
                    $name,

                'slug' =>
                    $slug,

                'description' =>
                    $description,

                'internal_reference' =>
                    $internalReference,

                'base_sku' =>
                    $baseSku,

                'default_price' =>
                    $defaultPrice,

                'original_price' =>
                    $originalPrice,

                'cost_price' =>
                    $costPrice,

                'purchase_date' =>
                    $purchaseDate,

                'tax_rate' =>
                    $taxRate,

                'track_stock' =>
                    $trackStock
                        ? 1
                        : 0,

                'updated_by_customer_id' =>
                    $updatedByCustomerId,

                'updated_at' =>
                    current_time(
                        'mysql',
                        true
                    ),
            ],
            [
                'id' =>
                    $productId,
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo actualizar el producto: %s',
                    $wpdb->last_error
                )
            );
        }
    }

    public function updateStatus(
        int $productId,
        string $status,
        int $updatedByCustomerId
    ): void {
        global $wpdb;

        if ($productId <= 0) {
            throw new RuntimeException(
                'El identificador del producto no es válido.'
            );
        }

        if (
            !ProductStatus::isValid(
                $status
            )
        ) {
            throw new RuntimeException(
                'El estado del producto no es válido.'
            );
        }

        if ($updatedByCustomerId <= 0) {
            throw new RuntimeException(
                'El usuario que modifica el producto no es válido.'
            );
        }

        $product = $this->findById(
            $productId
        );

        if ($product === null) {
            throw new RuntimeException(
                'No se encontró el producto.'
            );
        }

        $archivedAt =
            $product->getArchivedAt()
                ?->format('Y-m-d H:i:s');

        if (
            $status
            === ProductStatus::ARCHIVED
        ) {
            $archivedAt = current_time(
                'mysql',
                true
            );
        } elseif (
            $product->getStatus()
            === ProductStatus::ARCHIVED
        ) {
            throw new RuntimeException(
                'Un producto archivado no se puede reactivar.'
            );
        }

        $updated = $wpdb->update(
            $this->tableName,
            [
                'status' =>
                    $status,

                'updated_by_customer_id' =>
                    $updatedByCustomerId,

                'updated_at' =>
                    current_time(
                        'mysql',
                        true
                    ),

                'archived_at' =>
                    $archivedAt,
            ],
            [
                'id' =>
                    $productId,
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo actualizar el estado del producto: %s',
                    $wpdb->last_error
                )
            );
        }
    }

    public function belongsToStore(
        int $productId,
        int $storeId
    ): bool {
        global $wpdb;

        if (
            $productId <= 0
            || $storeId <= 0
        ) {
            return false;
        }

        $found = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                FROM {$this->tableName}
                WHERE id = %d
                  AND store_id = %d
                LIMIT 1",
                $productId,
                $storeId
            )
        );

        return $found !== null;
    }

    /**
     * @return array<int, Product>
     */
    public function findByStore(
        int $storeId,
        int $limit = 100,
        int $offset = 0,
        ?string $status = null
    ): array {
        global $wpdb;

        if ($storeId <= 0) {
            return [];
        }

        $limit = max(
            1,
            min(250, $limit)
        );

        $offset = max(
            0,
            $offset
        );

        $parameters = [
            $storeId,
        ];

        $where = "
            WHERE store_id = %d
        ";

        if (
            $status !== null
            && ProductStatus::isValid($status)
        ) {
            $where .= "
                AND status = %s
            ";

            $parameters[] = $status;
        }

        $parameters[] = $limit;
        $parameters[] = $offset;

        $query = $wpdb->prepare(
            "SELECT
                id,
                store_id,
                brand_id,
                name,
                slug,
                description,
                internal_reference,
                base_sku,
                default_price,
                original_price,
                cost_price,
                purchase_date,
                tax_rate,
                track_stock,
                status,
                created_by_customer_id,
                updated_by_customer_id,
                created_at,
                updated_at,
                archived_at
            FROM {$this->tableName}
            {$where}
            ORDER BY
                created_at DESC,
                id DESC
            LIMIT %d
            OFFSET %d",
            ...$parameters
        );

        $rows = $wpdb->get_results(
            $query,
            ARRAY_A
        );

        return $this->hydrateRows(
            $rows
        );
    }

    public function countByStore(
        int $storeId,
        ?string $status = null
    ): int {
        global $wpdb;

        if ($storeId <= 0) {
            return 0;
        }

        if (
            $status !== null
            && ProductStatus::isValid($status)
        ) {
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                    FROM {$this->tableName}
                    WHERE store_id = %d
                      AND status = %s",
                    $storeId,
                    $status
                )
            );
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$this->tableName}
                WHERE store_id = %d",
                $storeId
            )
        );
    }

    private function generateUniqueSlug(
        int $storeId,
        string $source,
        ?int $excludeProductId = null
    ): string {
        $baseSlug = sanitize_title(
            $source
        );

        if ($baseSlug === '') {
            $baseSlug = 'producto';
        }

        $candidate = $baseSlug;
        $suffix = 2;

        while (
            $this->slugExists(
                $storeId,
                $candidate,
                $excludeProductId
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
        int $storeId,
        string $slug,
        ?int $excludeProductId = null
    ): bool {
        global $wpdb;

        if (
            $excludeProductId !== null
            && $excludeProductId > 0
        ) {
            $found = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$this->tableName}
                    WHERE store_id = %d
                      AND slug = %s
                      AND id <> %d
                    LIMIT 1",
                    $storeId,
                    $slug,
                    $excludeProductId
                )
            );
        } else {
            $found = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$this->tableName}
                    WHERE store_id = %d
                      AND slug = %s
                    LIMIT 1",
                    $storeId,
                    $slug
                )
            );
        }

        return $found !== null;
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, Product>
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
            ): Product =>
                Product::fromArray($row),
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

    private static function normalizePrice(
        mixed $value
    ): float {
        if (is_string($value)) {
            $value = str_replace(
                ',',
                '.',
                $value
            );
        }

        $price = round(
            (float) $value,
            2
        );

        if ($price < 0) {
            throw new RuntimeException(
                'El precio no puede ser negativo.'
            );
        }

        return $price;
    }

    private static function normalizeNullablePrice(
        mixed $value
    ): ?float {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        return self::normalizePrice(
            $value
        );
    }

    private static function normalizeNullableTaxRate(
        mixed $value
    ): ?float {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(
                ',',
                '.',
                $value
            );
        }

        $taxRate = round(
            (float) $value,
            2
        );

        if (
            $taxRate < 0
            || $taxRate > 100
        ) {
            throw new RuntimeException(
                'El tipo impositivo debe estar entre 0 y 100.'
            );
        }

        return $taxRate;
    }

    private static function normalizeNullableDate(
        mixed $value
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        $date = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value
        );

        $errors =
            \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || (
                is_array($errors)
                && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                )
            )
        ) {
            throw new RuntimeException(
                'La fecha de compra no es válida.'
            );
        }

        return $date->format(
            'Y-m-d'
        );
    }

    private static function normalizeNullableShortText(
        mixed $value,
        int $maximumLength
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        $text = sanitize_text_field(
            (string) $value
        );

        if (
            mb_strlen($text)
            > $maximumLength
        ) {
            throw new RuntimeException(
                sprintf(
                    'El texto no puede superar los %d caracteres.',
                    $maximumLength
                )
            );
        }

        return $text;
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