<?php

declare(strict_types=1);

namespace DSM\Multitienda\Frontend;

use DSM\Multitienda\Application\CreateStore;
use DSM\Multitienda\Application\MultistoreAccessService;
use DSM\Multitienda\Application\StoreLogoService;
use DSM\Multitienda\Store\StoreRepository;
use DSM\Multitienda\Support\CustomerContext;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class StoreProfileController
{
    public const CREATE_ACTION =
        'dsm_multistore_create_store';

    public const UPDATE_ACTION =
        'dsm_multistore_update_store';

    public const NONCE_FIELD =
        'dsm_multistore_store_nonce';

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
                (int) (
                    $context['id']
                    ?? 0
                );

            check_admin_referer(
                self::getCreateNonceAction(
                    $customerId
                ),
                self::NONCE_FIELD
            );

            $useCase =
                new CreateStore();

            $store =
                $useCase->execute(
                    customerId:
                        $customerId,

                    name:
                        self::getPostedText(
                            'name'
                        ),

                    slug:
                        self::getPostedText(
                            'slug'
                        ),

                    description:
                        self::getPostedTextarea(
                            'description'
                        ),

                    island:
                        self::getPostedText(
                            'island'
                        ),

                    locationText:
                        self::getPostedText(
                            'location_text'
                        )
                );

            self::redirectToMyStore(
                [
                    'store_status' =>
                        'created',

                    'store_id' =>
                        $store->getId(),
                ]
            );
        } catch (Throwable $exception) {
            self::redirectToMyStore(
                [
                    'store_status' =>
                        'error',

                    'store_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function handleUpdate(): never
    {
        $newLogoAttachmentId =
            null;

        try {
            $context =
                CustomerContext::
                    requireCurrentActive();

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
                $repository
                    ->findByCustomerId(
                        $customerId
                    );

            if ($store === null) {
                throw new RuntimeException(
                    'No se encontró la tienda del cliente.'
                );
            }

            check_admin_referer(
                self::getUpdateNonceAction(
                    $store->getId()
                ),
                self::NONCE_FIELD
            );

            $logoService =
                new StoreLogoService();

            $removeLogo =
                isset(
                    $_POST['remove_logo']
                )
                && (string) $_POST[
                    'remove_logo'
                ] === '1';

            $currentLogoAttachmentId =
                $store
                    ->getLogoAttachmentId();

            $logoAttachmentId =
                $currentLogoAttachmentId;

            if ($removeLogo) {
                $logoAttachmentId =
                    null;
            } else {
                $newLogoAttachmentId =
                    $logoService->upload();

                if (
                    $newLogoAttachmentId
                    !== null
                ) {
                    $logoAttachmentId =
                        $newLogoAttachmentId;
                }
            }

            $updatedStore =
                $repository->updateProfile(
                    storeId:
                        $store->getId(),

                    name:
                        self::getPostedText(
                            'name'
                        ),

                    slug:
                        self::getPostedText(
                            'slug'
                        ),

                    description:
                        self::getPostedTextarea(
                            'description'
                        ),

                    logoAttachmentId:
                        $logoAttachmentId,

                    island:
                        self::getPostedText(
                            'island'
                        ),

                    locationText:
                        self::getPostedText(
                            'location_text'
                        )
                );

            /*
             * Solo eliminamos el attachment anterior
             * después de guardar correctamente la tienda.
             */
            if (
                $removeLogo
                && $currentLogoAttachmentId
                    !== null
            ) {
                $logoService->delete(
                    $currentLogoAttachmentId
                );
            }

            if (
                $newLogoAttachmentId !== null
                && $currentLogoAttachmentId
                    !== null
                && $currentLogoAttachmentId
                    !== $newLogoAttachmentId
            ) {
                $logoService->delete(
                    $currentLogoAttachmentId
                );
            }

            self::redirectToMyStore(
                [
                    'store_status' =>
                        'updated',

                    'store_id' =>
                        $updatedStore->getId(),
                ]
            );
        } catch (Throwable $exception) {
            /*
             * Si se ha subido un attachment nuevo
             * pero después falla la actualización,
             * evitamos dejar archivos huérfanos.
             */
            if (
                $newLogoAttachmentId
                !== null
            ) {
                try {
                    $logoService =
                        new StoreLogoService();

                    $logoService->delete(
                        $newLogoAttachmentId
                    );
                } catch (Throwable) {
                    // No ocultamos el error original.
                }
            }

            self::redirectToMyStore(
                [
                    'store_status' =>
                        'error',

                    'store_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function getCreateNonceAction(
        int $customerId
    ): string {
        return self::CREATE_ACTION
            . '_'
            . $customerId;
    }

    public static function getUpdateNonceAction(
        int $storeId
    ): string {
        return self::UPDATE_ACTION
            . '_'
            . $storeId;
    }

    private static function getPostedText(
        string $field
    ): string {
        if (!isset($_POST[$field])) {
            return '';
        }

        return trim(
            sanitize_text_field(
                wp_unslash(
                    (string) $_POST[$field]
                )
            )
        );
    }

    private static function getPostedTextarea(
        string $field
    ): ?string {
        if (!isset($_POST[$field])) {
            return null;
        }

        $value =
            trim(
                sanitize_textarea_field(
                    wp_unslash(
                        (string) $_POST[$field]
                    )
                )
            );

        return $value !== ''
            ? $value
            : null;
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private static function redirectToMyStore(
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