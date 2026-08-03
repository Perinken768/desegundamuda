<?php

declare(strict_types=1);

namespace DSM\Catalogo\Application;

use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Stock\StockResult;
use DSM\Catalogo\Stock\StockService;
use DSM\Catalogo\Variant\ProductVariant;
use DSM\Catalogo\Variant\ProductVariantRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CreateProductVariant
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductVariantRepository $variantRepository,
        private readonly StockService $stockService
    ) {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{
     *     variant: ProductVariant,
     *     stock: StockResult|null
     * }
     */
    public function execute(
        int $storeId,
        int $customerId,
        int $productId,
        array $data
    ): array {
        global $wpdb;

        if ($storeId <= 0) {
            throw new RuntimeException(
                'El identificador de la tienda no es válido.'
            );
        }

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($productId <= 0) {
            throw new RuntimeException(
                'El identificador del producto no es válido.'
            );
        }

        $product =
            $this->productRepository->findById(
                $productId
            );

        if ($product === null) {
            throw new RuntimeException(
                'No se encontró el producto.'
            );
        }

        if (
            !$product->belongsToStore(
                $storeId
            )
        ) {
            throw new RuntimeException(
                'El producto no pertenece a la tienda indicada.'
            );
        }

        if (!$product->canBeEdited()) {
            throw new RuntimeException(
                'El producto no admite nuevas variantes en su estado actual.'
            );
        }

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

        if (
            $originalPrice !== null
            && $price !== null
            && $originalPrice < $price
        ) {
            throw new RuntimeException(
                'El precio original de la variante no puede ser inferior al precio de venta.'
            );
        }

        $initialStock =
            self::normalizeNonNegativeInt(
                $data['stock_quantity']
                ?? 0,
                'El stock inicial'
            );

        $lowStockThreshold =
            self::normalizeNullableNonNegativeInt(
                $data['low_stock_threshold']
                ?? null
            );

        $trackStock =
            self::normalizeBoolean(
                $data['track_stock']
                ?? $product->tracksStock()
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

        if (
            !$trackStock
            && $initialStock > 0
        ) {
            throw new RuntimeException(
                'No se puede indicar stock inicial en una variante sin control de existencias.'
            );
        }

        $notes =
            self::normalizeNullableTextarea(
                $data['notes']
                ?? null
            );

        $userId =
            self::nullablePositiveInt(
                $data['user_id']
                ?? null
            );

        $started = $wpdb->query(
            'START TRANSACTION'
        );

        if ($started === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo iniciar la transacción de creación de variante: %s',
                    $wpdb->last_error
                )
            );
        }

        try {
            /*
             * La variante se crea inicialmente con stock cero.
             *
             * El stock inicial se registra posteriormente mediante
             * StockService para que exista un movimiento de inventario
             * completo y auditable.
             */
            $variantId =
                $this->variantRepository->create(
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
                            0,

                        'stock_reserved' =>
                            0,

                        'low_stock_threshold' =>
                            $lowStockThreshold,

                        'track_stock' =>
                            $trackStock,

                        'is_default' =>
                            $default,

                        'is_active' =>
                            $active,

                        'sort_order' =>
                            $sortOrder,
                    ],
                    false
                );

            $stockResult = null;

            if (
                $trackStock
                && $initialStock > 0
            ) {
                $stockResult =
                    $this->stockService
                        ->initializeWithinTransaction(
                            variantId:
                                $variantId,

                            storeId:
                                $storeId,

                            quantity:
                                $initialStock,

                            customerId:
                                $customerId,

                            userId:
                                $userId,

                            notes:
                                $notes
                                ?? 'Carga inicial de existencias.'
                        );
            }

            $variant =
                $this->variantRepository->findById(
                    $variantId
                );

            if ($variant === null) {
                throw new RuntimeException(
                    'La variante se creó, pero no pudo recuperarse.'
                );
            }

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

            return [
                'variant' =>
                    $variant,

                'stock' =>
                    $stockResult,
            ];
        } catch (Throwable $exception) {
            $wpdb->query(
                'ROLLBACK'
            );

            throw $exception;
        }
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
                'El precio de la variante no es válido.'
            );
        }

        $price = round(
            (float) $value,
            2
        );

        if ($price < 0) {
            throw new RuntimeException(
                'El precio de la variante no puede ser negativo.'
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