<?php

declare(strict_types=1);

namespace DSM\Catalogo\Application;

use DSM\Catalogo\Brand\BrandRepository;
use DSM\Catalogo\Product\Product;
use DSM\Catalogo\Product\ProductRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class UpdateProduct
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly BrandRepository $brandRepository
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function execute(
        int $storeId,
        int $customerId,
        int $productId,
        array $data
    ): Product {
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

        if (!$product->belongsToStore($storeId)) {
            throw new RuntimeException(
                'El producto no pertenece a la tienda indicada.'
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

        if ($brandId !== null) {
            $brand =
                $this->brandRepository->findById(
                    $brandId
                );

            if ($brand === null) {
                throw new RuntimeException(
                    'La marca seleccionada no existe.'
                );
            }

            if (!$brand->canBeSelected()) {
                throw new RuntimeException(
                    'La marca seleccionada no está disponible.'
                );
            }
        }

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

        if (
            $originalPrice !== null
            && $originalPrice < $defaultPrice
        ) {
            throw new RuntimeException(
                'El precio original no puede ser inferior al precio de venta.'
            );
        }

        $costPrice = array_key_exists(
            'cost_price',
            $data
        )
            ? self::normalizeNullablePrice(
                $data['cost_price']
            )
            : $product->getCostPrice();

        $taxRate = array_key_exists(
            'tax_rate',
            $data
        )
            ? self::normalizeNullableTaxRate(
                $data['tax_rate']
            )
            : $product->getTaxRate();

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
            : $product->getInternalReference();

        $baseSku = array_key_exists(
            'base_sku',
            $data
        )
            ? self::normalizeNullableShortText(
                $data['base_sku'],
                100
            )
            : $product->getBaseSku();

        $trackStock = array_key_exists(
            'track_stock',
            $data
        )
            ? self::normalizeBoolean(
                $data['track_stock']
            )
            : $product->tracksStock();

        $updateData = [
            'brand_id' =>
                $brandId,

            'name' =>
                $name,

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
                $trackStock,
        ];

        if (array_key_exists('slug', $data)) {
            $updateData['slug'] =
                (string) $data['slug'];
        }

        $this->productRepository->update(
            $productId,
            $customerId,
            $updateData
        );

        $updatedProduct =
            $this->productRepository->findById(
                $productId
            );

        if ($updatedProduct === null) {
            throw new RuntimeException(
                'El producto se actualizó, pero no pudo recuperarse.'
            );
        }

        return $updatedProduct;
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
                trim($value)
            );
        }

        if (!is_numeric($value)) {
            throw new RuntimeException(
                'El precio de venta no es válido.'
            );
        }

        $price = round(
            (float) $value,
            2
        );

        if ($price < 0) {
            throw new RuntimeException(
                'El precio de venta no puede ser negativo.'
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
                trim($value)
            );
        }

        if (!is_numeric($value)) {
            throw new RuntimeException(
                'El tipo impositivo no es válido.'
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