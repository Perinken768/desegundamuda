<?php

declare(strict_types=1);

namespace DSM\Catalogo\Inventory;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class StockMovementRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_stock_movements';
    }

    public function findById(
        int $movementId
    ): ?StockMovement {
        global $wpdb;

        if ($movementId <= 0) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    product_id,
                    variant_id,
                    store_id,
                    movement_type,
                    quantity_delta,
                    reserved_delta,
                    stock_quantity_before,
                    stock_quantity_after,
                    stock_reserved_before,
                    stock_reserved_after,
                    reference_type,
                    reference_id,
                    customer_id,
                    user_id,
                    notes,
                    created_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1",
                $movementId
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return StockMovement::fromArray($row);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(
        array $data
    ): int {
        global $wpdb;

        $productId = (int) (
            $data['product_id']
            ?? 0
        );

        $variantId = (int) (
            $data['variant_id']
            ?? 0
        );

        $storeId = (int) (
            $data['store_id']
            ?? 0
        );

        $movementType = sanitize_key(
            (string) (
                $data['movement_type']
                ?? ''
            )
        );

        $quantityDelta = (int) (
            $data['quantity_delta']
            ?? 0
        );

        $reservedDelta = (int) (
            $data['reserved_delta']
            ?? 0
        );

        $stockQuantityBefore = self::normalizeNonNegativeInt(
            $data['stock_quantity_before']
            ?? 0,
            'El stock físico anterior'
        );

        $stockQuantityAfter = self::normalizeNonNegativeInt(
            $data['stock_quantity_after']
            ?? 0,
            'El stock físico posterior'
        );

        $stockReservedBefore = self::normalizeNonNegativeInt(
            $data['stock_reserved_before']
            ?? 0,
            'El stock reservado anterior'
        );

        $stockReservedAfter = self::normalizeNonNegativeInt(
            $data['stock_reserved_after']
            ?? 0,
            'El stock reservado posterior'
        );

        $referenceType = self::normalizeNullableKey(
            $data['reference_type']
            ?? null
        );

        $referenceId = self::nullablePositiveInt(
            $data['reference_id']
            ?? null
        );

        $customerId = self::nullablePositiveInt(
            $data['customer_id']
            ?? null
        );

        $userId = self::nullablePositiveInt(
            $data['user_id']
            ?? null
        );

        $notes = self::normalizeNullableTextarea(
            $data['notes']
            ?? null
        );

        if ($productId <= 0) {
            throw new RuntimeException(
                'El identificador del producto no es válido.'
            );
        }

        if ($variantId <= 0) {
            throw new RuntimeException(
                'El identificador de la variante no es válido.'
            );
        }

        if ($storeId <= 0) {
            throw new RuntimeException(
                'El identificador de la tienda no es válido.'
            );
        }

        if (
            !StockMovementType::isValid(
                $movementType
            )
        ) {
            throw new RuntimeException(
                'El tipo de movimiento de stock no es válido.'
            );
        }

        if (
            $stockQuantityAfter
            !== $stockQuantityBefore
                + $quantityDelta
        ) {
            throw new RuntimeException(
                'El cambio de stock físico no coincide con los valores anterior y posterior.'
            );
        }

        if (
            $stockReservedAfter
            !== $stockReservedBefore
                + $reservedDelta
        ) {
            throw new RuntimeException(
                'El cambio de stock reservado no coincide con los valores anterior y posterior.'
            );
        }

        if (
            $stockReservedBefore
            > $stockQuantityBefore
        ) {
            throw new RuntimeException(
                'El stock reservado anterior no puede superar el stock físico anterior.'
            );
        }

        if (
            $stockReservedAfter
            > $stockQuantityAfter
        ) {
            throw new RuntimeException(
                'El stock reservado posterior no puede superar el stock físico posterior.'
            );
        }

        if (
            $referenceId !== null
            && $referenceType === null
        ) {
            throw new RuntimeException(
                'El tipo de referencia es obligatorio cuando existe un identificador de referencia.'
            );
        }

        if (
            $referenceType !== null
            && $referenceId === null
        ) {
            throw new RuntimeException(
                'El identificador de referencia es obligatorio cuando existe un tipo de referencia.'
            );
        }

        $createdAt = isset($data['created_at'])
            && trim((string) $data['created_at']) !== ''
                ? (string) $data['created_at']
                : current_time(
                    'mysql',
                    true
                );

        $result = $wpdb->insert(
            $this->tableName,
            [
                'product_id' =>
                    $productId,

                'variant_id' =>
                    $variantId,

                'store_id' =>
                    $storeId,

                'movement_type' =>
                    $movementType,

                'quantity_delta' =>
                    $quantityDelta,

                'reserved_delta' =>
                    $reservedDelta,

                'stock_quantity_before' =>
                    $stockQuantityBefore,

                'stock_quantity_after' =>
                    $stockQuantityAfter,

                'stock_reserved_before' =>
                    $stockReservedBefore,

                'stock_reserved_after' =>
                    $stockReservedAfter,

                'reference_type' =>
                    $referenceType,

                'reference_id' =>
                    $referenceId,

                'customer_id' =>
                    $customerId,

                'user_id' =>
                    $userId,

                'notes' =>
                    $notes,

                'created_at' =>
                    $createdAt,
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo registrar el movimiento de stock: %s',
                    $wpdb->last_error
                )
            );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * @return array<int, StockMovement>
     */
    public function findByVariant(
        int $variantId,
        int $limit = 100,
        int $offset = 0
    ): array {
        global $wpdb;

        if ($variantId <= 0) {
            return [];
        }

        $limit = max(
            1,
            min(500, $limit)
        );

        $offset = max(
            0,
            $offset
        );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    id,
                    product_id,
                    variant_id,
                    store_id,
                    movement_type,
                    quantity_delta,
                    reserved_delta,
                    stock_quantity_before,
                    stock_quantity_after,
                    stock_reserved_before,
                    stock_reserved_after,
                    reference_type,
                    reference_id,
                    customer_id,
                    user_id,
                    notes,
                    created_at
                FROM {$this->tableName}
                WHERE variant_id = %d
                ORDER BY
                    created_at DESC,
                    id DESC
                LIMIT %d
                OFFSET %d",
                $variantId,
                $limit,
                $offset
            ),
            ARRAY_A
        );

        return $this->hydrateRows(
            $rows
        );
    }

    /**
     * @return array<int, StockMovement>
     */
    public function findByProduct(
        int $productId,
        int $limit = 100,
        int $offset = 0
    ): array {
        global $wpdb;

        if ($productId <= 0) {
            return [];
        }

        $limit = max(
            1,
            min(500, $limit)
        );

        $offset = max(
            0,
            $offset
        );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    id,
                    product_id,
                    variant_id,
                    store_id,
                    movement_type,
                    quantity_delta,
                    reserved_delta,
                    stock_quantity_before,
                    stock_quantity_after,
                    stock_reserved_before,
                    stock_reserved_after,
                    reference_type,
                    reference_id,
                    customer_id,
                    user_id,
                    notes,
                    created_at
                FROM {$this->tableName}
                WHERE product_id = %d
                ORDER BY
                    created_at DESC,
                    id DESC
                LIMIT %d
                OFFSET %d",
                $productId,
                $limit,
                $offset
            ),
            ARRAY_A
        );

        return $this->hydrateRows(
            $rows
        );
    }

    /**
     * @return array<int, StockMovement>
     */
    public function findByStore(
        int $storeId,
        int $limit = 100,
        int $offset = 0,
        ?string $movementType = null
    ): array {
        global $wpdb;

        if ($storeId <= 0) {
            return [];
        }

        $limit = max(
            1,
            min(500, $limit)
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
            $movementType !== null
            && StockMovementType::isValid(
                $movementType
            )
        ) {
            $where .= "
                AND movement_type = %s
            ";

            $parameters[] =
                $movementType;
        }

        $parameters[] = $limit;
        $parameters[] = $offset;

        $query = $wpdb->prepare(
            "SELECT
                id,
                product_id,
                variant_id,
                store_id,
                movement_type,
                quantity_delta,
                reserved_delta,
                stock_quantity_before,
                stock_quantity_after,
                stock_reserved_before,
                stock_reserved_after,
                reference_type,
                reference_id,
                customer_id,
                user_id,
                notes,
                created_at
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

    /**
     * @return array<int, StockMovement>
     */
    public function findByReference(
        string $referenceType,
        int $referenceId
    ): array {
        global $wpdb;

        $referenceType = sanitize_key(
            $referenceType
        );

        if (
            $referenceType === ''
            || $referenceId <= 0
        ) {
            return [];
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    id,
                    product_id,
                    variant_id,
                    store_id,
                    movement_type,
                    quantity_delta,
                    reserved_delta,
                    stock_quantity_before,
                    stock_quantity_after,
                    stock_reserved_before,
                    stock_reserved_after,
                    reference_type,
                    reference_id,
                    customer_id,
                    user_id,
                    notes,
                    created_at
                FROM {$this->tableName}
                WHERE reference_type = %s
                  AND reference_id = %d
                ORDER BY
                    created_at ASC,
                    id ASC",
                $referenceType,
                $referenceId
            ),
            ARRAY_A
        );

        return $this->hydrateRows(
            $rows
        );
    }

    public function countByVariant(
        int $variantId
    ): int {
        global $wpdb;

        if ($variantId <= 0) {
            return 0;
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$this->tableName}
                WHERE variant_id = %d",
                $variantId
            )
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
                WHERE product_id = %d",
                $productId
            )
        );
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, StockMovement>
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
            ): StockMovement =>
                StockMovement::fromArray($row),
            $rows
        );
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
}