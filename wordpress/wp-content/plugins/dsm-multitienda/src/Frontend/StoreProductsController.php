<?php

declare(strict_types=1);

namespace DSM\Multitienda\Frontend;

use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Product\ProductStatus;
use DSM\Multitienda\Integration\CatalogStoreService;
use DSM\Multitienda\Support\CustomerContext;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class StoreProductsController
{
    public const STATUS_ACTION =
        'dsm_multistore_product_status';

    public const NONCE_FIELD =
        'dsm_multistore_product_nonce';

    public static function register(): void
    {
        add_action(
            'admin_post_'
            . self::STATUS_ACTION,
            [
                self::class,
                'handleStatus',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::STATUS_ACTION,
            [
                self::class,
                'handleStatus',
            ]
        );
    }

    public static function handleStatus(): never
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

            $newStatus =
                isset($_POST['product_status'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_POST[
                                'product_status'
                            ]
                        )
                    )
                    : '';

            if ($productId <= 0) {
                throw new RuntimeException(
                    'El identificador del producto no es válido.'
                );
            }

            check_admin_referer(
                self::getNonceAction(
                    $productId
                ),
                self::NONCE_FIELD
            );

            if (
                !in_array(
                    $newStatus,
                    [
                        ProductStatus::ACTIVE,
                        ProductStatus::INACTIVE,
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'El estado solicitado no está permitido.'
                );
            }

            $catalogService =
                new CatalogStoreService();

            $store =
                $catalogService
                    ->requireStoreForCustomer(
                        $customerId
                    );

            $repository =
                new ProductRepository();

            if (
                !$repository->belongsToStore(
                    $productId,
                    $store->getId()
                )
            ) {
                throw new RuntimeException(
                    'El producto no pertenece a tu tienda.'
                );
            }

            $product =
                $repository->findById(
                    $productId
                );

            if ($product === null) {
                throw new RuntimeException(
                    'No se encontró el producto.'
                );
            }

            if (
                $product->getStatus()
                === ProductStatus::ARCHIVED
            ) {
                throw new RuntimeException(
                    'Un producto archivado no puede cambiar de estado.'
                );
            }

            $repository->updateStatus(
                productId:
                    $productId,

                status:
                    $newStatus,

                updatedByCustomerId:
                    $customerId
            );

            self::redirect(
                [
                    'store_section' =>
                        'products',

                    'product_status_notice' =>
                        'updated',
                ]
            );
        } catch (Throwable $exception) {
            self::redirect(
                [
                    'store_section' =>
                        'products',

                    'product_status_notice' =>
                        'error',

                    'product_status_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function getNonceAction(
        int $productId
    ): string {
        return self::STATUS_ACTION
            . '_'
            . $productId;
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private static function redirect(
        array $arguments = []
    ): never {
        $url =
            add_query_arg(
                $arguments,
                home_url(
                    '/mi-tienda/'
                )
            );

        wp_safe_redirect(
            $url
        );

        exit;
    }

    private function __construct()
    {
    }
}
