<?php

declare(strict_types=1);

namespace DSM\Catalogo\Application;

use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Variant\ProductVariant;
use DSM\Catalogo\Variant\ProductVariantRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class UpdateProductVariant
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductVariantRepository $variantRepository
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function execute(
        int $storeId,
        int $customerId,
        int $variantId,
        array $data
    ): ProductVariant {
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

        if ($variantId <= 0) {
            throw new RuntimeException(
                'El identificador de la variante no es válido.'
            );
        }

        $variant =
            $this->variantRepository->findById(
                $variantId
            );

        if ($variant === null) {
            throw new RuntimeException(
                'No se encontró la variante.'
            );
        }

        if ($variant->isArchived()) {
            throw new RuntimeException(
                'No se puede editar una variante archivada.'
            );
        }

        $product =
            $this->productRepository->findById(
                $variant->getProductId()
            );

        if ($product === null) {
            throw new RuntimeException(
                'No se encontró el producto asociado.'
            );
        }

        if (!$product->belongsToStore($storeId)) {
            throw new RuntimeException(
                'La variante no pertenece a la tienda indicada.'
            );
        }

        if (!$product->canBeEdited()) {
            throw new RuntimeException(
                'El producto no admite cambios en sus variantes.'
            );
        }

        if (
            array_key_exists(
                'stock_quantity',
                $data
            )
            || array_key_exists(
                'stock_reserved',
                $data
            )
        ) {
            throw new RuntimeException(
                'El stock no se puede modificar desde la edición de la variante.'
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

        if (
            $originalPrice !== null
            && $price !== null
            && $originalPrice < $price
        ) {
            throw new RuntimeException(
                'El precio original no puede ser inferior al precio de venta.'
            );
        }

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

        if (
            !$trackStock
            && (
                $variant->getStockQuantity() > 0
                || $variant->getStockReserved() > 0
            )
        ) {
            throw new RuntimeException(
                'No se puede desactivar el control de stock mientras existan unidades físicas o reservadas.'
            );
        }

        $isDefault = array_key_exists(
            'is_default',
            $data
        )
            ? self::normalizeBoolean(
                $data['is_default']
            )
            : $variant->isDefault();

        $isActive = array_key_exists(
            'is_active',
            $data
        )
            ? self::normalizeBoolean(
                $data['is_active']
            )
            : $variant->isActive();

        if (
            !$isActive
            && $variant->getStockReserved() > 0
        ) {
            throw new RuntimeException(
                'No se puede desactivar una variante con stock reservado.'
            );
        }

        if (
            !$isActive
            && $variant->isDefault()
        ) {
            throw new RuntimeException(
                'No se puede desactivar la variante predeterminada.'
            );
        }

        $sortOrder = array_key_exists(
            'sort_order',
            $data
        )
            ? self::normalizeNonNegativeInt(
                $data['sort_order'],
                'El orden'
            )
            : $variant->getSortOrder();

        $this->variantRepository->update(
            $variantId,
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
                    $trackStock,

                'is_default' =>
                    $isDefault,

                'is_active' =>
                    $isActive,

                'sort_order' =>
                    $sortOrder,
            ]
        );

        $updatedVariant =
            $this->variantRepository->findById(
                $variantId
            );

        if ($updatedVariant === null) {
            throw new RuntimeException(
                'La variante se actualizó, pero no pudo recuperarse.'
            );
        }

        return $updatedVariant;
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