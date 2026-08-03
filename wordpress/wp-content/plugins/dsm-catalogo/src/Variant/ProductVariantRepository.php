<?php

declare(strict_types=1);

namespace DSM\Catalogo\Variant;

use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ProductVariantRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_product_variants';
    }

    public function findById(
        int $variantId
    ): ?ProductVariant {
        global $wpdb;

        if ($variantId <= 0) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    product_id,
                    sku,
                    barcode,
                    size_value,
                    color_value,
                    condition_code,
                    price,
                    original_price,
                    cost_price,
                    stock_quantity,
                    stock_reserved,
                    low_stock_threshold,
                    track_stock,
                    is_default,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at,
                    archived_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1",
                $variantId
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return ProductVariant::fromArray(
            $row
        );
    }

    public function findBySku(
        string $sku
    ): ?ProductVariant {
        global $wpdb;

        $sku = trim($sku);

        if ($sku === '') {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    product_id,
                    sku,
                    barcode,
                    size_value,
                    color_value,
                    condition_code,
                    price,
                    original_price,
                    cost_price,
                    stock_quantity,
                    stock_reserved,
                    low_stock_threshold,
                    track_stock,
                    is_default,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at,
                    archived_at
                FROM {$this->tableName}
                WHERE sku = %s
                LIMIT 1",
                $sku
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return ProductVariant::fromArray(
            $row
        );
    }

    public function findByBarcode(
        string $barcode
    ): ?ProductVariant {
        global $wpdb;

        $barcode = trim($barcode);

        if ($barcode === '') {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    product_id,
                    sku,
                    barcode,
                    size_value,
                    color_value,
                    condition_code,
                    price,
                    original_price,
                    cost_price,
                    stock_quantity,
                    stock_reserved,
                    low_stock_threshold,
                    track_stock,
                    is_default,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at,
                    archived_at
                FROM {$this->tableName}
                WHERE barcode = %s
                LIMIT 1",
                $barcode
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return ProductVariant::fromArray(
            $row
        );
    }

    public function findDefaultByProduct(
        int $productId
    ): ?ProductVariant {
        global $wpdb;

        if ($productId <= 0) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    product_id,
                    sku,
                    barcode,
                    size_value,
                    color_value,
                    condition_code,
                    price,
                    original_price,
                    cost_price,
                    stock_quantity,
                    stock_reserved,
                    low_stock_threshold,
                    track_stock,
                    is_default,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at,
                    archived_at
                FROM {$this->tableName}
                WHERE product_id = %d
                  AND is_default = 1
                  AND archived_at IS NULL
                ORDER BY id ASC
                LIMIT 1",
                $productId
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return ProductVariant::fromArray(
            $row
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(
        array $data,
        bool $manageTransaction = true
    ): int {
        global $wpdb;

        $productId = (int) (
            $data['product_id']
            ?? 0
        );

        $sku =
            self::normalizeNullableShortText(
                $data['sku']
                ?? null,
                120
            );

        $barcode =
            self::normalizeNullableShortText(
                $data['barcode']
                ?? null,
                120
            );

        $sizeValue =
            self::normalizeNullableShortText(
                $data['size_value']
                ?? null,
                80
            );

        $colorValue =
            self::normalizeNullableShortText(
                $data['color_value']
                ?? null,
                100
            );

        $conditionCode =
            self::normalizeNullableKey(
                $data['condition_code']
                ?? null
            );

        $price =
            self::normalizeNullablePrice(
                $data['price']
                ?? null
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

        $stockQuantity =
            self::normalizeNonNegativeInt(
                $data['stock_quantity']
                ?? 0,
                'El stock total'
            );

        $stockReserved =
            self::normalizeNonNegativeInt(
                $data['stock_reserved']
                ?? 0,
                'El stock reservado'
            );

        $lowStockThreshold =
            self::normalizeNullableNonNegativeInt(
                $data['low_stock_threshold']
                ?? null
            );

        $trackStock =
            self::normalizeBoolean(
                $data['track_stock']
                ?? true
            );

        $default =
            self::normalizeBoolean(
                $data['is_default']
                ?? false
            );

        $active =
            self::normalizeBoolean(
                $data['is_active']
                ?? true
            );

        $sortOrder =
            self::normalizeNonNegativeInt(
                $data['sort_order']
                ?? 0,
                'El orden'
            );

        if ($productId <= 0) {
            throw new RuntimeException(
                'El identificador del producto no es válido.'
            );
        }

        if (
            $stockReserved
            > $stockQuantity
        ) {
            throw new RuntimeException(
                'El stock reservado no puede superar el stock total.'
            );
        }

        if (
            $sku !== null
            && $this->findBySku($sku) !== null
        ) {
            throw new RuntimeException(
                'Ya existe una variante con ese SKU.'
            );
        }

        if (
            $barcode !== null
            && $this->findByBarcode($barcode) !== null
        ) {
            throw new RuntimeException(
                'Ya existe una variante con ese código de barras.'
            );
        }

        $existingVariants =
            $this->countByProduct(
                $productId
            );

        if ($existingVariants === 0) {
            $default = true;
        }

        $now = current_time(
            'mysql',
            true
        );

        if ($manageTransaction) {
            $started = $wpdb->query(
                'START TRANSACTION'
            );

            if ($started === false) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo iniciar la transacción de la variante: %s',
                        $wpdb->last_error
                    )
                );
            }
        }

        try {
            if ($default) {
                $cleared = $wpdb->update(
                    $this->tableName,
                    [
                        'is_default' =>
                            0,

                        'updated_at' =>
                            $now,
                    ],
                    [
                        'product_id' =>
                            $productId,
                    ],
                    [
                        '%d',
                        '%s',
                    ],
                    [
                        '%d',
                    ]
                );

                if ($cleared === false) {
                    throw new RuntimeException(
                        'No se pudo preparar la variante predeterminada.'
                    );
                }
            }

            $result = $wpdb->insert(
                $this->tableName,
                [
                    'product_id' =>
                        $productId,

                    'sku' =>
                        $sku,

                    'barcode' =>
                        $barcode,

                    'size_value' =>
                        $sizeValue,

                    'color_value' =>
                        $colorValue,

                    'condition_code' =>
                        $conditionCode,

                    'price' =>
                        $price,

                    'original_price' =>
                        $originalPrice,

                    'cost_price' =>
                        $costPrice,

                    'stock_quantity' =>
                        $stockQuantity,

                    'stock_reserved' =>
                        $stockReserved,

                    'low_stock_threshold' =>
                        $lowStockThreshold,

                    'track_stock' =>
                        $trackStock
                            ? 1
                            : 0,

                    'is_default' =>
                        $default
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

                    'archived_at' =>
                        null,
                ]
            );

            if ($result === false) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo crear la variante: %s',
                        $wpdb->last_error
                    )
                );
            }

            $variantId =
                (int) $wpdb->insert_id;

            if ($manageTransaction) {
                $committed = $wpdb->query(
                    'COMMIT'
                );

                if ($committed === false) {
                    throw new RuntimeException(
                        sprintf(
                            'No se pudo confirmar la creación de la variante: %s',
                            $wpdb->last_error
                        )
                    );
                }
            }

            return $variantId;
        } catch (Throwable $exception) {
            if ($manageTransaction) {
                $wpdb->query(
                    'ROLLBACK'
                );
            }

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        int $variantId,
        array $data
    ): void {
        global $wpdb;

        if ($variantId <= 0) {
            throw new RuntimeException(
                'El identificador de la variante no es válido.'
            );
        }

        $variant = $this->findById(
            $variantId
        );

        if ($variant === null) {
            throw new RuntimeException(
                'No se encontró la variante.'
            );
        }

        if ($variant->isArchived()) {
            throw new RuntimeException(
                'Una variante archivada no se puede editar.'
            );
        }

        $sku = array_key_exists(
            'sku',
            $data
        )
            ? self::normalizeNullableShortText(
                $data['sku'],
                120
            )
            : $variant->getSku();

        $barcode = array_key_exists(
            'barcode',
            $data
        )
            ? self::normalizeNullableShortText(
                $data['barcode'],
                120
            )
            : $variant->getBarcode();

        if ($sku !== null) {
            $existing =
                $this->findBySku(
                    $sku
                );

            if (
                $existing !== null
                && $existing->getId()
                    !== $variantId
            ) {
                throw new RuntimeException(
                    'Ya existe una variante con ese SKU.'
                );
            }
        }

        if ($barcode !== null) {
            $existing =
                $this->findByBarcode(
                    $barcode
                );

            if (
                $existing !== null
                && $existing->getId()
                    !== $variantId
            ) {
                throw new RuntimeException(
                    'Ya existe una variante con ese código de barras.'
                );
            }
        }

        $sizeValue = array_key_exists(
            'size_value',
            $data
        )
            ? self::normalizeNullableShortText(
                $data['size_value'],
                80
            )
            : $variant->getSizeValue();

        $colorValue = array_key_exists(
            'color_value',
            $data
        )
            ? self::normalizeNullableShortText(
                $data['color_value'],
                100
            )
            : $variant->getColorValue();

        $conditionCode = array_key_exists(
            'condition_code',
            $data
        )
            ? self::normalizeNullableKey(
                $data['condition_code']
            )
            : $variant->getConditionCode();

        $price = array_key_exists(
            'price',
            $data
        )
            ? self::normalizeNullablePrice(
                $data['price']
            )
            : $variant->getPrice();

        $originalPrice = array_key_exists(
            'original_price',
            $data
        )
            ? self::normalizeNullablePrice(
                $data['original_price']
            )
            : $variant->getOriginalPrice();

        $costPrice = array_key_exists(
            'cost_price',
            $data
        )
            ? self::normalizeNullablePrice(
                $data['cost_price']
            )
            : $variant->getCostPrice();

        $lowStockThreshold = array_key_exists(
            'low_stock_threshold',
            $data
        )
            ? self::normalizeNullableNonNegativeInt(
                $data['low_stock_threshold']
            )
            : $variant->getLowStockThreshold();

        $trackStock = array_key_exists(
            'track_stock',
            $data
        )
            ? self::normalizeBoolean(
                $data['track_stock']
            )
            : $variant->tracksStock();

        $default = array_key_exists(
            'is_default',
            $data
        )
            ? self::normalizeBoolean(
                $data['is_default']
            )
            : $variant->isDefault();

        $active = array_key_exists(
            'is_active',
            $data
        )
            ? self::normalizeBoolean(
                $data['is_active']
            )
            : $variant->isActive();

        $sortOrder = array_key_exists(
            'sort_order',
            $data
        )
            ? self::normalizeNonNegativeInt(
                $data['sort_order'],
                'El orden'
            )
            : $variant->getSortOrder();

        $now = current_time(
            'mysql',
            true
        );

        $started = $wpdb->query(
            'START TRANSACTION'
        );

        if ($started === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo iniciar la transacción de actualización: %s',
                    $wpdb->last_error
                )
            );
        }

        try {
            if ($default) {
                $cleared = $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$this->tableName}
                        SET
                            is_default = 0,
                            updated_at = %s
                        WHERE product_id = %d
                          AND id <> %d",
                        $now,
                        $variant->getProductId(),
                        $variantId
                    )
                );

                if ($cleared === false) {
                    throw new RuntimeException(
                        'No se pudo cambiar la variante predeterminada.'
                    );
                }
            } elseif ($variant->isDefault()) {
                throw new RuntimeException(
                    'No puedes quitar la variante predeterminada sin seleccionar otra.'
                );
            }

            $updated = $wpdb->update(
                $this->tableName,
                [
                    'sku' =>
                        $sku,

                    'barcode' =>
                        $barcode,

                    'size_value' =>
                        $sizeValue,

                    'color_value' =>
                        $colorValue,

                    'condition_code' =>
                        $conditionCode,

                    'price' =>
                        $price,

                    'original_price' =>
                        $originalPrice,

                    'cost_price' =>
                        $costPrice,

                    'low_stock_threshold' =>
                        $lowStockThreshold,

                    'track_stock' =>
                        $trackStock
                            ? 1
                            : 0,

                    'is_default' =>
                        $default
                            ? 1
                            : 0,

                    'is_active' =>
                        $active
                            ? 1
                            : 0,

                    'sort_order' =>
                        $sortOrder,

                    'updated_at' =>
                        $now,
                ],
                [
                    'id' =>
                        $variantId,
                ]
            );

            if ($updated === false) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo actualizar la variante: %s',
                        $wpdb->last_error
                    )
                );
            }

            $committed = $wpdb->query(
                'COMMIT'
            );

            if ($committed === false) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo confirmar la actualización de la variante: %s',
                        $wpdb->last_error
                    )
                );
            }
        } catch (Throwable $exception) {
            $wpdb->query(
                'ROLLBACK'
            );

            throw $exception;
        }
    }

    public function setDefault(
        int $variantId
    ): void {
        global $wpdb;

        $variant = $this->findById(
            $variantId
        );

        if ($variant === null) {
            throw new RuntimeException(
                'No se encontró la variante.'
            );
        }

        if ($variant->isArchived()) {
            throw new RuntimeException(
                'Una variante archivada no puede ser predeterminada.'
            );
        }

        $now = current_time(
            'mysql',
            true
        );

        $started = $wpdb->query(
            'START TRANSACTION'
        );

        if ($started === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo iniciar la transacción: %s',
                    $wpdb->last_error
                )
            );
        }

        try {
            $cleared = $wpdb->update(
                $this->tableName,
                [
                    'is_default' =>
                        0,

                    'updated_at' =>
                        $now,
                ],
                [
                    'product_id' =>
                        $variant->getProductId(),
                ]
            );

            if ($cleared === false) {
                throw new RuntimeException(
                    'No se pudo limpiar la variante predeterminada anterior.'
                );
            }

            $updated = $wpdb->update(
                $this->tableName,
                [
                    'is_default' =>
                        1,

                    'updated_at' =>
                        $now,
                ],
                [
                    'id' =>
                        $variantId,
                ]
            );

            if ($updated === false) {
                throw new RuntimeException(
                    'No se pudo establecer la variante predeterminada.'
                );
            }

            $committed = $wpdb->query(
                'COMMIT'
            );

            if ($committed === false) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo confirmar la variante predeterminada: %s',
                        $wpdb->last_error
                    )
                );
            }
        } catch (Throwable $exception) {
            $wpdb->query(
                'ROLLBACK'
            );

            throw $exception;
        }
    }

    public function setActive(
        int $variantId,
        bool $active
    ): void {
        global $wpdb;

        $variant = $this->findById(
            $variantId
        );

        if ($variant === null) {
            throw new RuntimeException(
                'No se encontró la variante.'
            );
        }

        if ($variant->isArchived()) {
            throw new RuntimeException(
                'Una variante archivada no puede cambiar de estado.'
            );
        }

        if (
            !$active
            && $variant->isDefault()
        ) {
            throw new RuntimeException(
                'No se puede desactivar la variante predeterminada.'
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
                    $variantId,
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo modificar el estado de la variante.'
            );
        }
    }

    public function archive(
        int $variantId
    ): void {
        global $wpdb;

        $variant = $this->findById(
            $variantId
        );

        if ($variant === null) {
            throw new RuntimeException(
                'No se encontró la variante.'
            );
        }

        if ($variant->isArchived()) {
            return;
        }

        if ($variant->isDefault()) {
            throw new RuntimeException(
                'No se puede archivar la variante predeterminada.'
            );
        }

        if (
            $variant->getStockReserved()
            > 0
        ) {
            throw new RuntimeException(
                'No se puede archivar una variante con stock reservado.'
            );
        }

        $now = current_time(
            'mysql',
            true
        );

        $updated = $wpdb->update(
            $this->tableName,
            [
                'is_active' =>
                    0,

                'archived_at' =>
                    $now,

                'updated_at' =>
                    $now,
            ],
            [
                'id' =>
                    $variantId,
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo archivar la variante.'
            );
        }
    }

    /**
     * @return array<int, ProductVariant>
     */
    public function findByProduct(
        int $productId,
        bool $onlyActive = false
    ): array {
        global $wpdb;

        if ($productId <= 0) {
            return [];
        }

        $activeCondition =
            $onlyActive
                ? 'AND is_active = 1'
                : '';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    id,
                    product_id,
                    sku,
                    barcode,
                    size_value,
                    color_value,
                    condition_code,
                    price,
                    original_price,
                    cost_price,
                    stock_quantity,
                    stock_reserved,
                    low_stock_threshold,
                    track_stock,
                    is_default,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at,
                    archived_at
                FROM {$this->tableName}
                WHERE product_id = %d
                  AND archived_at IS NULL
                  {$activeCondition}
                ORDER BY
                    is_default DESC,
                    sort_order ASC,
                    id ASC",
                $productId
            ),
            ARRAY_A
        );

        return $this->hydrateRows(
            $rows
        );
    }

    /**
     * @return array<int, ProductVariant>
     */
    public function findAvailableByProduct(
        int $productId
    ): array {
        global $wpdb;

        if ($productId <= 0) {
            return [];
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    id,
                    product_id,
                    sku,
                    barcode,
                    size_value,
                    color_value,
                    condition_code,
                    price,
                    original_price,
                    cost_price,
                    stock_quantity,
                    stock_reserved,
                    low_stock_threshold,
                    track_stock,
                    is_default,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at,
                    archived_at
                FROM {$this->tableName}
                WHERE product_id = %d
                  AND archived_at IS NULL
                  AND is_active = 1
                  AND (
                        track_stock = 0
                        OR stock_quantity > stock_reserved
                  )
                ORDER BY
                    is_default DESC,
                    sort_order ASC,
                    id ASC",
                $productId
            ),
            ARRAY_A
        );

        return $this->hydrateRows(
            $rows
        );
    }

    public function countByProduct(
        int $productId
    ): int {
        global $wpdb;

        if ($productId <= 0) {
            return 0;
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$this->tableName}
                WHERE product_id = %d
                  AND archived_at IS NULL",
                $productId
            )
        );
    }

    public function getTotalAvailableStockByProduct(
        int $productId
    ): int {
        global $wpdb;

        if ($productId <= 0) {
            return 0;
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    track_stock,
                    stock_quantity,
                    stock_reserved
                FROM {$this->tableName}
                WHERE product_id = %d
                  AND is_active = 1
                  AND archived_at IS NULL",
                $productId
            ),
            ARRAY_A
        );

        if (!is_array($rows)) {
            return 0;
        }

        $total = 0;

        foreach ($rows as $row) {
            $trackStock = in_array(
                $row['track_stock']
                ?? false,
                [
                    true,
                    1,
                    '1',
                ],
                true
            );

            if (!$trackStock) {
                return PHP_INT_MAX;
            }

            $total += max(
                0,
                (int) (
                    $row['stock_quantity']
                    ?? 0
                )
                - (int) (
                    $row['stock_reserved']
                    ?? 0
                )
            );
        }

        return $total;
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, ProductVariant>
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
            ): ProductVariant =>
                ProductVariant::fromArray(
                    $row
                ),
            $rows
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

    private static function normalizeNullableKey(
        mixed $value
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        $key = sanitize_key(
            (string) $value
        );

        return $key !== ''
            ? $key
            : null;
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

        if (is_string($value)) {
            $value = str_replace(
                ',',
                '.',
                trim($value)
            );
        }

        if (!is_numeric($value)) {
            throw new RuntimeException(
                'El precio no es válido.'
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

    private static function normalizeNonNegativeInt(
        mixed $value,
        string $fieldName
    ): int {
        $integer = (int) $value;

        if ($integer < 0) {
            throw new RuntimeException(
                sprintf(
                    '%s no puede ser negativo.',
                    $fieldName
                )
            );
        }

        return $integer;
    }

    private static function normalizeNullableNonNegativeInt(
        mixed $value
    ): ?int {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $integer = (int) $value;

        if ($integer < 0) {
            throw new RuntimeException(
                'El umbral de stock bajo no puede ser negativo.'
            );
        }

        return $integer;
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