<?php

declare(strict_types=1);

namespace DSM\Multitienda\Frontend;

use DSM\Catalogo\Image\ProductImageService;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Multitienda\Integration\CatalogStoreService;
use DSM\Multitienda\Support\CustomerContext;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class StoreProductImageController
{
    public const UPLOAD_ACTION =
        'dsm_multistore_product_image_upload';

    public const COVER_ACTION =
        'dsm_multistore_product_image_cover';

    public const DELETE_ACTION =
        'dsm_multistore_product_image_delete';

    public const NONCE_FIELD =
        'dsm_multistore_product_image_nonce';

    public static function register(): void
    {
        foreach (
            [
                self::UPLOAD_ACTION =>
                    'handleUpload',

                self::COVER_ACTION =>
                    'handleCover',

                self::DELETE_ACTION =>
                    'handleDelete',
            ]
            as $action => $callback
        ) {
            add_action(
                'admin_post_' . $action,
                [
                    self::class,
                    $callback,
                ]
            );

            add_action(
                'admin_post_nopriv_' . $action,
                [
                    self::class,
                    $callback,
                ]
            );
        }
    }

    public static function handleUpload(): never
    {
        try {
            [
                $customerId,
                $productId,
            ] =
                self::requireProductContext();

            check_admin_referer(
                self::getUploadNonceAction(
                    $productId
                ),
                self::NONCE_FIELD
            );

            $service =
                new ProductImageService();

            $service->uploadFiles(
                $productId,
                'product_images'
            );

            self::redirect(
                $productId,
                'uploaded'
            );
        } catch (Throwable $exception) {
            self::redirectError(
                self::postedProductId(),
                $exception
            );
        }
    }

    public static function handleCover(): never
    {
        try {
            [
                $customerId,
                $productId,
            ] =
                self::requireProductContext();

            $imageId =
                isset($_POST['image_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'image_id'
                            ]
                        )
                    )
                    : 0;

            if ($imageId <= 0) {
                throw new RuntimeException(
                    'La imagen seleccionada no es válida.'
                );
            }

            check_admin_referer(
                self::getCoverNonceAction(
                    $productId,
                    $imageId
                ),
                self::NONCE_FIELD
            );

            $service =
                new ProductImageService();

            $service->setCover(
                $productId,
                $imageId
            );

            self::redirect(
                $productId,
                'cover-updated'
            );
        } catch (Throwable $exception) {
            self::redirectError(
                self::postedProductId(),
                $exception
            );
        }
    }

    public static function handleDelete(): never
    {
        try {
            [
                $customerId,
                $productId,
            ] =
                self::requireProductContext();

            $imageId =
                isset($_POST['image_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'image_id'
                            ]
                        )
                    )
                    : 0;

            if ($imageId <= 0) {
                throw new RuntimeException(
                    'La imagen seleccionada no es válida.'
                );
            }

            check_admin_referer(
                self::getDeleteNonceAction(
                    $productId,
                    $imageId
                ),
                self::NONCE_FIELD
            );

            $service =
                new ProductImageService();

            /*
             * Estas imágenes son subidas específicamente
             * para el producto, así que eliminamos también
             * el attachment de WordPress.
             */
            $service->remove(
                $productId,
                $imageId,
                true
            );

            self::redirect(
                $productId,
                'deleted'
            );
        } catch (Throwable $exception) {
            self::redirectError(
                self::postedProductId(),
                $exception
            );
        }
    }

    public static function getUploadNonceAction(
        int $productId
    ): string {
        return self::UPLOAD_ACTION
            . '_'
            . $productId;
    }

    public static function getCoverNonceAction(
        int $productId,
        int $imageId
    ): string {
        return self::COVER_ACTION
            . '_'
            . $productId
            . '_'
            . $imageId;
    }

    public static function getDeleteNonceAction(
        int $productId,
        int $imageId
    ): string {
        return self::DELETE_ACTION
            . '_'
            . $productId
            . '_'
            . $imageId;
    }

    /**
     * @return array{0:int,1:int}
     */
    private static function requireProductContext(): array
    {
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

        $productId =
            self::postedProductId();

        if ($productId <= 0) {
            throw new RuntimeException(
                'El identificador del producto no es válido.'
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

        return [
            $customerId,
            $productId,
        ];
    }

    private static function postedProductId(): int
    {
        return isset($_POST['product_id'])
            ? absint(
                wp_unslash(
                    (string) $_POST[
                        'product_id'
                    ]
                )
            )
            : 0;
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

                    'image_notice' =>
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
        int $productId,
        Throwable $exception
    ): never {
        wp_safe_redirect(
            add_query_arg(
                [
                    'store_section' =>
                        'edit-product',

                    'product_id' =>
                        $productId,

                    'image_notice' =>
                        'error',

                    'image_error' =>
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
