<?php

declare(strict_types=1);

namespace DSM\Multitienda\Frontend;

use DSM\Catalogo\Application\CreateProductVariant;
use DSM\Catalogo\Inventory\StockMovementRepository;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Stock\StockService;
use DSM\Catalogo\Variant\ProductVariantRepository;
use DSM\Multitienda\Integration\CatalogStoreService;
use DSM\Multitienda\Support\CustomerContext;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class StoreProductVariantController
{
    public const CREATE_ACTION =
        'dsm_multistore_create_variant';

    public const NONCE_FIELD =
        'dsm_multistore_variant_nonce';

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

        add_action(
            'admin_post_nopriv_'
            . self::CREATE_ACTION,
            [
                self::class,
                'handleCreate',
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
                (int) (
                    $context['id']
                    ?? 0
                );

            if ($customerId <= 0) {
                throw new RuntimeException(
                    'No se pudo identificar al cliente.'
                );
            }

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

            $catalogStoreService =
                new CatalogStoreService();

            $store =
                $catalogStoreService
                    ->requireStoreForCustomer(
                        $customerId
                    );

            $product =
                $catalogStoreService
                    ->getProductForCustomer(
                        $customerId,
                        $productId
                    );

            check_admin_referer(
                self::getCreateNonceAction(
                    $product->getId()
                ),
                self::NONCE_FIELD
            );

            $productRepository =
                new ProductRepository();

            $variantRepository =
                new ProductVariantRepository();

            $movementRepository =
                new StockMovementRepository();

            $stockService =
                new StockService(
                    $movementRepository
                );

            $createVariant =
                new CreateProductVariant(
                    $productRepository,
                    $variantRepository,
                    $stockService
                );

            $result =
                $createVariant->execute(
                    storeId:
                        $store->getId(),

                    customerId:
                        $customerId,

                    productId:
                        $product->getId(),

                    data:
                        self::getVariantData()
                );

            $variant =
                $result['variant']
                ?? null;

            if ($variant === null) {
                throw new RuntimeException(
                    'La variante se creó, pero no pudo recuperarse.'
                );
            }

            self::redirect(
                [
                    'store_section' =>
                        'edit-product',

                    'product_id' =>
                        $product->getId(),

                    'variant_notice' =>
                        'created',

                    'variant_id' =>
                        $variant->getId(),
                ]
            );
        } catch (Throwable $exception) {
            self::redirect(
                [
                    'store_section' =>
                        'edit-product',

                    'product_id' =>
                        isset($_POST['product_id'])
                            ? absint(
                                wp_unslash(
                                    (string) $_POST[
                                        'product_id'
                                    ]
                                )
                            )
                            : 0,

                    'variant_notice' =>
                        'error',

                    'variant_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function getCreateNonceAction(
        int $productId
    ): string {
        return self::CREATE_ACTION
            . '_'
            . $productId;
    }

    /**
     * @return array<string, mixed>
     */
    private static function getVariantData(): array
    {
        return [
            'sku' =>
                self::getNullableText(
                    'sku'
                ),

            'barcode' =>
                self::getNullableText(
                    'barcode'
                ),

            'size_value' =>
                self::getNullableText(
                    'size_value'
                ),

            'color_value' =>
                self::getNullableText(
                    'color_value'
                ),

            'condition_code' =>
                self::getNullableKey(
                    'condition_code'
                ),

            'price' =>
                self::getNullableDecimal(
                    'price'
                ),

            'original_price' =>
                self::getNullableDecimal(
                    'original_price'
                ),

            'cost_price' =>
                self::getNullableDecimal(
                    'cost_price'
                ),

            'low_stock_threshold' =>
                self::getNullablePositiveInt(
                    'low_stock_threshold'
                ),

            /*
             * En Multitienda sí activamos
             * el control real de existencias.
             */
            'track_stock' =>
                true,

            'stock_quantity' =>
                self::getNonNegativeInt(
                    'initial_stock'
                ),

            'is_default' =>
                isset(
                    $_POST['is_default']
                ),

            'is_active' =>
                true,

            'sort_order' =>
                self::getNonNegativeInt(
                    'sort_order'
                ),

            /*
             * Customer DSM:
             * no asumimos usuario WordPress.
             */
            'user_id' =>
                null,

            'notes' =>
                'Carga inicial desde DSM Multitienda.',
        ];
    }

    private static function getNullableText(
        string $field
    ): ?string {
        if (!isset($_POST[$field])) {
            return null;
        }

        $value =
            trim(
                sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[$field]
                    )
                )
            );

        return $value !== ''
            ? $value
            : null;
    }

    private static function getNullableKey(
        string $field
    ): ?string {
        if (!isset($_POST[$field])) {
            return null;
        }

        $value =
            sanitize_key(
                wp_unslash(
                    (string) $_POST[$field]
                )
            );

        return $value !== ''
            ? $value
            : null;
    }

    private static function getNullableDecimal(
        string $field
    ): ?float {
        if (!isset($_POST[$field])) {
            return null;
        }

        $value =
            trim(
                wp_unslash(
                    (string) $_POST[$field]
                )
            );

        if ($value === '') {
            return null;
        }

        $value =
            str_replace(
                ',',
                '.',
                $value
            );

        if (!is_numeric($value)) {
            throw new RuntimeException(
                'Uno de los importes de la variante no es válido.'
            );
        }

        return round(
            (float) $value,
            2
        );
    }

    private static function getNullablePositiveInt(
        string $field
    ): ?int {
        if (!isset($_POST[$field])) {
            return null;
        }

        $value =
            trim(
                wp_unslash(
                    (string) $_POST[$field]
                )
            );

        if ($value === '') {
            return null;
        }

        $number =
            (int) $value;

        if ($number < 0) {
            throw new RuntimeException(
                'El umbral de stock no puede ser negativo.'
            );
        }

        return $number;
    }

    private static function getNonNegativeInt(
        string $field
    ): int {
        if (!isset($_POST[$field])) {
            return 0;
        }

        $number =
            (int) wp_unslash(
                (string) $_POST[$field]
            );

        if ($number < 0) {
            throw new RuntimeException(
                'La cantidad no puede ser negativa.'
            );
        }

        return $number;
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private static function redirect(
        array $arguments
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
