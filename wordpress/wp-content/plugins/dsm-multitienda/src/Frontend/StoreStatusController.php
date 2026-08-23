<?php

declare(strict_types=1);

namespace DSM\Multitienda\Frontend;

use DSM\Multitienda\Application\MultistoreAccessService;
use DSM\Multitienda\Store\StoreRepository;
use DSM\Multitienda\Store\StoreStatus;
use DSM\Multitienda\Support\CustomerContext;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class StoreStatusController
{
    public const PUBLISH_ACTION =
        'dsm_multistore_publish_store';

    public const HIDE_ACTION =
        'dsm_multistore_hide_store';

    public const NONCE_FIELD =
        'dsm_multistore_status_nonce';

    public static function register(): void
    {
        add_action(
            'admin_post_'
            . self::PUBLISH_ACTION,
            [
                self::class,
                'handlePublish',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::PUBLISH_ACTION,
            [
                self::class,
                'handlePublish',
            ]
        );

        add_action(
            'admin_post_'
            . self::HIDE_ACTION,
            [
                self::class,
                'handleHide',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::HIDE_ACTION,
            [
                self::class,
                'handleHide',
            ]
        );
    }

    public static function handlePublish(): never
    {
        try {
            $context =
                CustomerContext::requireCurrentActive();

            $customerId =
                (int) (
                    $context['id']
                    ?? 0
                );

            $accessService =
                new MultistoreAccessService();

            $accessService->assertHasAccess(
                $customerId
            );

            $repository =
                new StoreRepository();

            $store =
                $repository->findByCustomerId(
                    $customerId
                );

            if ($store === null) {
                throw new RuntimeException(
                    'No se encontró la tienda del cliente.'
                );
            }

            check_admin_referer(
                self::getPublishNonceAction(
                    $store->getId()
                ),
                self::NONCE_FIELD
            );

            if ($store->isSuspended()) {
                throw new RuntimeException(
                    'La tienda está suspendida y no puede publicarse.'
                );
            }

            /*
             * =================================================
             * REQUISITOS MÍNIMOS DE PUBLICACIÓN
             * =================================================
             */

            if (
                trim(
                    $store->getName()
                ) === ''
            ) {
                throw new RuntimeException(
                    'Debes indicar el nombre de la tienda antes de publicarla.'
                );
            }

            if (
                trim(
                    $store->getSlug()
                ) === ''
            ) {
                throw new RuntimeException(
                    'Debes indicar la URL de la tienda antes de publicarla.'
                );
            }

            if (
                trim(
                    (string) $store->getIsland()
                ) === ''
            ) {
                throw new RuntimeException(
                    'Debes indicar la isla de la tienda antes de publicarla.'
                );
            }

            if (
                trim(
                    (string) $store->getLocationText()
                ) === ''
            ) {
                throw new RuntimeException(
                    'Debes indicar la ubicación de la tienda antes de publicarla.'
                );
            }

            if (!$store->isActive()) {
                $repository->updateStatus(
                    $store->getId(),
                    StoreStatus::ACTIVE
                );
            }

            self::redirect(
                [
                    'store_status' =>
                        'published',
                ]
            );
        } catch (Throwable $exception) {
            self::redirect(
                [
                    'store_status' =>
                        'error',

                    'store_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function handleHide(): never
    {
        try {
            $context =
                CustomerContext::requireCurrentActive();

            $customerId =
                (int) (
                    $context['id']
                    ?? 0
                );

            $accessService =
                new MultistoreAccessService();

            $accessService->assertHasAccess(
                $customerId
            );

            $repository =
                new StoreRepository();

            $store =
                $repository->findByCustomerId(
                    $customerId
                );

            if ($store === null) {
                throw new RuntimeException(
                    'No se encontró la tienda del cliente.'
                );
            }

            check_admin_referer(
                self::getHideNonceAction(
                    $store->getId()
                ),
                self::NONCE_FIELD
            );

            if ($store->isSuspended()) {
                throw new RuntimeException(
                    'La tienda está suspendida y su estado solo puede gestionarlo un administrador.'
                );
            }

            if ($store->isDraft()) {
                throw new RuntimeException(
                    'La tienda todavía está en borrador.'
                );
            }

            if (!$store->isHidden()) {
                $repository->updateStatus(
                    $store->getId(),
                    StoreStatus::HIDDEN
                );
            }

            self::redirect(
                [
                    'store_status' =>
                        'hidden',
                ]
            );
        } catch (Throwable $exception) {
            self::redirect(
                [
                    'store_status' =>
                        'error',

                    'store_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function getPublishNonceAction(
        int $storeId
    ): string {
        return self::PUBLISH_ACTION
            . '_'
            . $storeId;
    }

    public static function getHideNonceAction(
        int $storeId
    ): string {
        return self::HIDE_ACTION
            . '_'
            . $storeId;
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