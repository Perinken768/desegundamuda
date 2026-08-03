<?php

declare(strict_types=1);

namespace DSM\Catalogo\Application;

use DSM\Catalogo\Brand\BrandRepository;
use DSM\Catalogo\Product\Product;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Product\ProductStatus;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CreateProduct
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

        $name = trim(
            (string) (
                $data['name']
                ?? ''
            )
        );

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

        $brandId = self::nullablePositiveInt(
            $data['brand_id']
            ?? null
        );

        /*
         * La marca es completamente opcional.
         *
         * Cuando se informe, debe existir y estar disponible
         * para selección en el catálogo.
         */
        if ($brandId !== null) {
            $brand = $this->brandRepository->findById(
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

        $defaultPrice = self::normalizePrice(
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

        if (
            $originalPrice !== null
            && $originalPrice < $defaultPrice
        ) {
            throw new RuntimeException(
                'El precio original no puede ser inferior al precio de venta.'
            );
        }

        if (
            $costPrice !== null
            && $costPrice > $defaultPrice
        ) {
            /*
             * No impedimos vender por debajo del coste.
             *
             * Puede ser una liquidación real, pero lo dejamos
             * permitido para no bloquear al vendedor.
             */
        }

        $taxRate =
            self::normalizeNullableTaxRate(
                $data['tax_rate']
                ?? null
            );

        $purchaseDate =
            self::normalizeNullableDate(
                $data['purchase_date']
                ?? null
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

        $trackStock =
            self::normalizeBoolean(
                $data['track_stock']
                ?? true
            );

        /*
         * Todo producto nuevo nace como borrador.
         *
         * Aunque el formulario intente enviar otro estado,
         * se ignorará.
         */
        $productId =
            $this->productRepository->create(
                [
                    'store_id' =>
                        $storeId,

                    'brand_id' =>
                        $brandId,

                    'name' =>
                        $name,

                    'slug' =>
                        $data['slug']
                        ?? $name,

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

                    'status' =>
                        ProductStatus::DRAFT,

                    'created_by_customer_id' =>
                        $customerId,
                ]
            );

        $product =
            $this->productRepository->findById(
                $productId
            );

        if ($product === null) {
            throw new RuntimeException(
                'El producto se creó, pero no pudo recuperarse.'
            );
        }

        return $product;
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