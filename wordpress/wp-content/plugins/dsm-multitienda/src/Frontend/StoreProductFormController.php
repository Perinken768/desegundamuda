<?php

declare(strict_types=1);

namespace DSM\Multitienda\Frontend;

use DSM\Catalogo\Application\CreateProduct;
use DSM\Catalogo\Brand\BrandRepository;
use DSM\Catalogo\Image\ProductImageService;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Multitienda\Integration\CatalogStoreService;
use DSM\Multitienda\Support\CustomerContext;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class StoreProductFormController
{
    public const CREATE_ACTION =
        'dsm_multistore_create_product';

    public const UPDATE_ACTION =
        'dsm_multistore_update_product';

    public const NONCE_FIELD =
        'dsm_multistore_product_form_nonce';

    public static function register(): void
    {
        add_action(
            'admin_post_'
            . self::CREATE_ACTION,
            [
                self::class,
                'handleCreate',
            ]
        );

        /*
         * Los clientes DSM no son usuarios
         * autenticados de WordPress.
         */
        add_action(
            'admin_post_nopriv_'
            . self::CREATE_ACTION,
            [
                self::class,
                'handleCreate',
            ]
        );

        add_action(
            'admin_post_'
            . self::UPDATE_ACTION,
            [
                self::class,
                'handleUpdate',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::UPDATE_ACTION,
            [
                self::class,
                'handleUpdate',
            ]
        );
    }

    public static function handleCreate(): never
    {
        try {
            $context =
                CustomerContext::
                    requireCurrentActive();

            $customerId =
                max(
                    0,
                    (int) (
                        $context['id']
                        ?? 0
                    )
                );

            if ($customerId <= 0) {
                throw new RuntimeException(
                    'No se pudo identificar al cliente.'
                );
            }

            $catalogStoreService =
                new CatalogStoreService();

            $store =
                $catalogStoreService
                    ->requireStoreForCustomer(
                        $customerId
                    );

            check_admin_referer(
                self::getCreateNonceAction(
                    $store->getId()
                ),
                self::NONCE_FIELD
            );

            $data =
                self::sanitizeProductInput(
                    $_POST
                );

            $productRepository =
                new ProductRepository();

            $brandRepository =
                new BrandRepository();

            $createProduct =
                new CreateProduct(
                    $productRepository,
                    $brandRepository
                );

            $product =
                $createProduct->execute(
                    storeId:
                        $store->getId(),

                    customerId:
                        $customerId,

                    data:
                        $data
                );

            /*
             * =================================================
             * IMÁGENES DEL PRODUCTO
             * =================================================
             */

            $imageService =
                new ProductImageService();

            $imageService->uploadFiles(
                $product->getId(),
                'product_images'
            );

            self::redirect(
                [
                    'store_section' =>
                        'products',

                    'product_notice' =>
                        'created',

                    'product_id' =>
                        $product->getId(),
                ]
            );
        } catch (Throwable $exception) {
            self::redirect(
                [
                    'store_section' =>
                        'new-product',

                    'product_notice' =>
                        'error',

                    'product_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function handleUpdate(): never
    {
        $productId = 0;

        try {
            $context =
                CustomerContext::
                    requireCurrentActive();

            $customerId =
                max(
                    0,
                    (int) (
                        $context['id']
                        ?? 0
                    )
                );

            if ($customerId <= 0) {
                throw new RuntimeException(
                    'No se pudo identificar al cliente.'
                );
            }

            $catalogStoreService =
                new CatalogStoreService();

            $store =
                $catalogStoreService
                    ->requireStoreForCustomer(
                        $customerId
                    );

            $productId =
                isset($_POST['product_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'product_id'
                            ]
                        )
                    )
                    : 0;

            if ($productId <= 0) {
                throw new RuntimeException(
                    'El identificador del producto no es válido.'
                );
            }

            check_admin_referer(
                self::getUpdateNonceAction(
                    $productId
                ),
                self::NONCE_FIELD
            );

            $productRepository =
                new ProductRepository();

            $product =
                $productRepository->findById(
                    $productId
                );

            if (
                $product === null
                || !$product->belongsToStore(
                    $store->getId()
                )
            ) {
                throw new RuntimeException(
                    'El producto no pertenece a tu tienda.'
                );
            }

            $name =
                isset($_POST['name'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST[
                                'name'
                            ]
                        )
                    )
                    : '';

            $description =
                isset($_POST['description'])
                    ? sanitize_textarea_field(
                        wp_unslash(
                            (string) $_POST[
                                'description'
                            ]
                        )
                    )
                    : '';

            if ($name === '') {
                throw new RuntimeException(
                    'El nombre del producto es obligatorio.'
                );
            }

            $productRepository->update(
                $productId,
                $customerId,
                [
                    'name' =>
                        $name,

                    'description' =>
                        $description,
                ]
            );

            self::redirect(
                [
                    'store_section' =>
                        'products',

                    'product_id' =>
                        $productId,

                    'product_notice' =>
                        'updated',
                ]
            );
        } catch (Throwable $exception) {
            self::redirect(
                [
                    'store_section' =>
                        'products',

                    'product_id' =>
                        $productId,

                    'product_notice' =>
                        'error',

                    'product_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function getUpdateNonceAction(
        int $productId
    ): string {
        return self::UPDATE_ACTION
            . '_'
            . $productId;
    }

    public static function getCreateNonceAction(
        int $storeId
    ): string {
        return self::CREATE_ACTION
            . '_'
            . $storeId;
    }

    /**
     * @param array<string, mixed> $source
     *
     * @return array<string, mixed>
     */
    private static function sanitizeProductInput(
        array $source
    ): array {
        return [
            'category_id' =>
                isset($source['category_id'])
                    ? absint(
                        wp_unslash(
                            (string) $source[
                                'category_id'
                            ]
                        )
                    )
                    : null,

            'brand_id' =>
                isset($source['brand_id'])
                    ? absint(
                        wp_unslash(
                            (string) $source[
                                'brand_id'
                            ]
                        )
                    )
                    : null,

            'name' =>
                isset($source['name'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $source[
                                'name'
                            ]
                        )
                    )
                    : '',

            'slug' =>
                isset($source['slug'])
                    ? sanitize_title(
                        wp_unslash(
                            (string) $source[
                                'slug'
                            ]
                        )
                    )
                    : '',

            'description' =>
                isset($source['description'])
                    ? sanitize_textarea_field(
                        wp_unslash(
                            (string) $source[
                                'description'
                            ]
                        )
                    )
                    : null,

            'internal_reference' =>
                isset(
                    $source[
                        'internal_reference'
                    ]
                )
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $source[
                                'internal_reference'
                            ]
                        )
                    )
                    : null,

            'base_sku' =>
                isset($source['base_sku'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $source[
                                'base_sku'
                            ]
                        )
                    )
                    : null,

            'default_price' =>
                self::sanitizeDecimal(
                    $source['default_price']
                    ?? 0
                ),

            'original_price' =>
                self::sanitizeNullableDecimal(
                    $source['original_price']
                    ?? null
                ),

            'cost_price' =>
                self::sanitizeNullableDecimal(
                    $source['cost_price']
                    ?? null
                ),

            'purchase_date' =>
                isset($source['purchase_date'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $source[
                                'purchase_date'
                            ]
                        )
                    )
                    : null,

            'tax_rate' =>
                self::sanitizeNullableDecimal(
                    $source['tax_rate']
                    ?? null
                ),

            'track_stock' =>
                isset(
                    $source['track_stock']
                )
                && (string) $source[
                    'track_stock'
                ] === '1',
        ];
    }

    private static function sanitizeDecimal(
        mixed $value
    ): float {
        if (is_string($value)) {
            $value =
                str_replace(
                    ',',
                    '.',
                    trim($value)
                );
        }

        if (!is_numeric($value)) {
            throw new RuntimeException(
                'Uno de los importes introducidos no es válido.'
            );
        }

        return round(
            (float) $value,
            2
        );
    }

    private static function sanitizeNullableDecimal(
        mixed $value
    ): ?float {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        return self::sanitizeDecimal(
            $value
        );
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private static function redirect(
        array $arguments = []
    ): never {
        wp_safe_redirect(
            add_query_arg(
                $arguments,
                home_url(
                    '/mi-tienda/'
                )
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}
