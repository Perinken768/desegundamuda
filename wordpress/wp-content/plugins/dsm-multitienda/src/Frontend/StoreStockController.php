<?php

declare(strict_types=1);

namespace DSM\Multitienda\Frontend;

use DSM\Catalogo\Application\AdjustStock;
use DSM\Catalogo\Application\ReplenishStock;
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

final class StoreStockController
{
    public const REPLENISH_ACTION =
        'dsm_multistore_replenish_stock';

    public const ADJUST_ACTION =
        'dsm_multistore_adjust_stock';

    public const NONCE_FIELD =
        'dsm_multistore_stock_nonce';

    public static function register(): void
    {
        self::registerAction(
            self::REPLENISH_ACTION,
            'handleReplenish'
        );

        self::registerAction(
            self::ADJUST_ACTION,
            'handleAdjust'
        );
    }

    public static function handleReplenish(): never
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
                self::getPositivePostedInt(
                    'product_id'
                );

            $variantId =
                self::getPositivePostedInt(
                    'variant_id'
                );

            $quantity =
                self::getPositivePostedInt(
                    'quantity'
                );

            $notes =
                self::getRequiredNotes();

            $store =
                self::requireStoreAndProduct(
                    $customerId,
                    $productId
                );

            check_admin_referer(
                self::getReplenishNonceAction(
                    $variantId
                ),
                self::NONCE_FIELD
            );

            self::assertVariantBelongsToProduct(
                $variantId,
                $productId
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

            $useCase =
                new ReplenishStock(
                    $productRepository,
                    $variantRepository,
                    $stockService
                );

            $useCase->execute(
                storeId:
                    $store->getId(),

                customerId:
                    $customerId,

                variantId:
                    $variantId,

                quantity:
                    $quantity,

                userId:
                    null,

                notes:
                    $notes
            );

            self::redirect(
                $productId,
                'replenished'
            );
        } catch (Throwable $exception) {
            self::redirectError(
                $exception
            );
        }
    }

    public static function handleAdjust(): never
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
                self::getPositivePostedInt(
                    'product_id'
                );

            $variantId =
                self::getPositivePostedInt(
                    'variant_id'
                );

            $quantityDelta =
                self::getSignedPostedInt(
                    'quantity_delta'
                );

            if ($quantityDelta === 0) {
                throw new RuntimeException(
                    'El ajuste de stock no puede ser 0.'
                );
            }

            $notes =
                self::getRequiredNotes();

            $store =
                self::requireStoreAndProduct(
                    $customerId,
                    $productId
                );

            check_admin_referer(
                self::getAdjustNonceAction(
                    $variantId
                ),
                self::NONCE_FIELD
            );

            self::assertVariantBelongsToProduct(
                $variantId,
                $productId
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

            $useCase =
                new AdjustStock(
                    $productRepository,
                    $variantRepository,
                    $stockService
                );

            $useCase->execute(
                storeId:
                    $store->getId(),

                customerId:
                    $customerId,

                variantId:
                    $variantId,

                quantityDelta:
                    $quantityDelta,

                userId:
                    null,

                notes:
                    $notes
            );

            self::redirect(
                $productId,
                'adjusted'
            );
        } catch (Throwable $exception) {
            self::redirectError(
                $exception
            );
        }
    }

    public static function getReplenishNonceAction(
        int $variantId
    ): string {
        return self::REPLENISH_ACTION
            . '_'
            . $variantId;
    }

    public static function getAdjustNonceAction(
        int $variantId
    ): string {
        return self::ADJUST_ACTION
            . '_'
            . $variantId;
    }

    private static function requireStoreAndProduct(
        int $customerId,
        int $productId
    ): \DSM\Multitienda\Store\Store {
        $service =
            new CatalogStoreService();

        $store =
            $service->requireStoreForCustomer(
                $customerId
            );

        /*
         * Además valida que el producto
         * pertenece a esta tienda.
         */
        $service->getProductForCustomer(
            $customerId,
            $productId
        );

        return $store;
    }

    private static function assertVariantBelongsToProduct(
        int $variantId,
        int $productId
    ): void {
        $repository =
            new ProductVariantRepository();

        $variant =
            $repository->findById(
                $variantId
            );

        if ($variant === null) {
            throw new RuntimeException(
                'No se encontró la variante.'
            );
        }

        if (
            $variant->getProductId()
            !== $productId
        ) {
            throw new RuntimeException(
                'La variante no pertenece al producto indicado.'
            );
        }

        if ($variant->isArchived()) {
            throw new RuntimeException(
                'No se puede modificar el stock de una variante archivada.'
            );
        }

        if (!$variant->isActive()) {
            throw new RuntimeException(
                'No se puede modificar el stock de una variante inactiva.'
            );
        }

        if (!$variant->tracksStock()) {
            throw new RuntimeException(
                'Esta variante no tiene control de stock.'
            );
        }
    }

    private static function getPositivePostedInt(
        string $field
    ): int {
        if (!isset($_POST[$field])) {
            throw new RuntimeException(
                'Falta un dato obligatorio.'
            );
        }

        $value =
            (int) wp_unslash(
                (string) $_POST[$field]
            );

        if ($value <= 0) {
            throw new RuntimeException(
                'La cantidad debe ser mayor que cero.'
            );
        }

        return $value;
    }

    private static function getSignedPostedInt(
        string $field
    ): int {
        if (!isset($_POST[$field])) {
            throw new RuntimeException(
                'Falta el ajuste de stock.'
            );
        }

        $raw =
            trim(
                wp_unslash(
                    (string) $_POST[$field]
                )
            );

        if (
            $raw === ''
            || !preg_match(
                '/^-?\d+$/',
                $raw
            )
        ) {
            throw new RuntimeException(
                'El ajuste de stock debe ser un número entero.'
            );
        }

        return (int) $raw;
    }

    private static function getRequiredNotes(): string
    {
        if (!isset($_POST['notes'])) {
            throw new RuntimeException(
                'Debes indicar el motivo del movimiento.'
            );
        }

        $notes =
            trim(
                sanitize_textarea_field(
                    wp_unslash(
                        (string) $_POST[
                            'notes'
                        ]
                    )
                )
            );

        if ($notes === '') {
            throw new RuntimeException(
                'Debes indicar el motivo del movimiento.'
            );
        }

        return $notes;
    }

    private static function registerAction(
        string $action,
        string $method
    ): void {
        add_action(
            'admin_post_'
            . $action,
            [
                self::class,
                $method,
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . $action,
            [
                self::class,
                $method,
            ]
        );
    }

    private static function redirect(
        int $productId,
        string $notice
    ): never {
        wp_safe_redirect(
            add_query_arg(
                [
                    'store_section' =>
                        'edit-product',

                    'product_id' =>
                        $productId,

                    'stock_notice' =>
                        $notice,
                ],
                home_url(
                    '/mi-tienda/'
                )
            )
        );

        exit;
    }

    private static function redirectError(
        Throwable $exception
    ): never {
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

        wp_safe_redirect(
            add_query_arg(
                [
                    'store_section' =>
                        'edit-product',

                    'product_id' =>
                        $productId,

                    'stock_notice' =>
                        'error',

                    'stock_error' =>
                        $exception->getMessage(),
                ],
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
