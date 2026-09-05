<?php

declare(strict_types=1);

namespace DSM\Favoritos\Frontend;

use DSM\Favoritos\Application\AddFavorite;
use DSM\Favoritos\Application\RemoveFavorite;
use DSM\Favoritos\Favorite\Favorite;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class FavoriteController
{
    public const ACTION_ADD =
        'dsm_favorite_add';

    public const ACTION_REMOVE =
        'dsm_favorite_remove';

    public const NONCE_FIELD =
        'dsm_favorite_nonce';

    private const NONCE_ACTION_PREFIX =
        'dsm_favorite_';

    private AddFavorite $addFavorite;

    private RemoveFavorite $removeFavorite;

    public function __construct(
        ?AddFavorite $addFavorite = null,
        ?RemoveFavorite $removeFavorite = null
    ) {
        $this->addFavorite =
            $addFavorite
            ?? new AddFavorite();

        $this->removeFavorite =
            $removeFavorite
            ?? new RemoveFavorite();
    }

    public function register(): void
    {
        add_action(
            'admin_post_'
            . self::ACTION_ADD,
            [
                $this,
                'handleAdd',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::ACTION_ADD,
            [
                $this,
                'handleAdd',
            ]
        );

        add_action(
            'admin_post_'
            . self::ACTION_REMOVE,
            [
                $this,
                'handleRemove',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::ACTION_REMOVE,
            [
                $this,
                'handleRemove',
            ]
        );
    }

    public function handleAdd(): void
    {
        try {
            [
                $itemType,
                $itemId,
            ] = $this->resolveItem();

            $this->verifyNonce(
                self::ACTION_ADD,
                $itemType,
                $itemId
            );

            $customer =
                $this->resolveCurrentCustomer();

            $this->addFavorite
                ->execute(
                    (int) $customer['id'],
                    $itemType,
                    $itemId
                );

            $this->redirect(
                'added',
                $itemType,
                $itemId
            );
        } catch (Throwable $exception) {
            $this->redirectError(
                $exception
            );
        }
    }

    public function handleRemove(): void
    {
        try {
            [
                $itemType,
                $itemId,
            ] = $this->resolveItem();

            $this->verifyNonce(
                self::ACTION_REMOVE,
                $itemType,
                $itemId
            );

            $customer =
                $this->resolveCurrentCustomer();

            $this->removeFavorite
                ->execute(
                    (int) $customer['id'],
                    $itemType,
                    $itemId
                );

            $this->redirect(
                'removed',
                $itemType,
                $itemId
            );
        } catch (Throwable $exception) {
            $this->redirectError(
                $exception
            );
        }
    }

    public static function getNonceAction(
        string $action,
        int $itemId,
        string $itemType = Favorite::TYPE_ADVERTISEMENT
    ): string {
        $action =
            sanitize_key(
                $action
            );

        $itemType =
            sanitize_key(
                $itemType
            );

        return self::NONCE_ACTION_PREFIX
            . $action
            . '_'
            . $itemType
            . '_'
            . max(
                0,
                $itemId
            );
    }

    /**
     * @return array{0:string,1:int}
     */
    private function resolveItem(): array
    {
        $itemType = '';
        $itemId = 0;

        /*
         * Nuevo formato genérico.
         */
        if (
            isset($_POST['item_type'])
            && is_scalar(
                $_POST['item_type']
            )
        ) {
            $itemType =
                sanitize_key(
                    (string) wp_unslash(
                        $_POST['item_type']
                    )
                );
        }

        if (
            isset($_POST['item_id'])
            && is_scalar(
                $_POST['item_id']
            )
        ) {
            $itemId =
                max(
                    0,
                    (int) $_POST['item_id']
                );
        }

        /*
         * Compatibilidad con formularios antiguos
         * de anuncios.
         */
        if (
            $itemType === ''
            && isset(
                $_POST['advertisement_id']
            )
        ) {
            $itemType =
                Favorite::TYPE_ADVERTISEMENT;

            $itemId =
                max(
                    0,
                    (int) $_POST[
                        'advertisement_id'
                    ]
                );
        }

        if (
            !Favorite::isValidType(
                $itemType
            )
        ) {
            throw new RuntimeException(
                'El tipo de favorito no es válido.'
            );
        }

        if ($itemId <= 0) {
            throw new RuntimeException(
                'No se pudo identificar el elemento favorito.'
            );
        }

        return [
            $itemType,
            $itemId,
        ];
    }

    private function verifyNonce(
        string $action,
        string $itemType,
        int $itemId
    ): void {
        $nonce = '';

        if (
            isset(
                $_POST[
                    self::NONCE_FIELD
                ]
            )
            && is_scalar(
                $_POST[
                    self::NONCE_FIELD
                ]
            )
        ) {
            $nonce =
                (string) wp_unslash(
                    $_POST[
                        self::NONCE_FIELD
                    ]
                );
        }

        if (
            $nonce === ''
            || !wp_verify_nonce(
                $nonce,
                self::getNonceAction(
                    $action,
                    $itemId,
                    $itemType
                )
            )
        ) {
            throw new RuntimeException(
                'La solicitud de favoritos no es válida.'
            );
        }
    }

    /**
     * @return array{id:int,status:string}
     */
    private function resolveCurrentCustomer(): array
    {
        $customer =
            apply_filters(
                'dsm_current_customer_context',
                null
            );

        if (!is_array($customer)) {
            throw new RuntimeException(
                'Debes iniciar sesión para gestionar favoritos.'
            );
        }

        $customerId =
            max(
                0,
                (int) (
                    $customer['id']
                    ?? 0
                )
            );

        $status =
            sanitize_key(
                (string) (
                    $customer['status']
                    ?? ''
                )
            );

        if (
            $customerId <= 0
            || $status !== 'active'
        ) {
            throw new RuntimeException(
                'Debes iniciar sesión para gestionar favoritos.'
            );
        }

        return [
            'id' =>
                $customerId,

            'status' =>
                $status,
        ];
    }

    private function redirect(
        string $result,
        string $itemType,
        int $itemId
    ): never {
        $redirectUrl =
            $this->resolveRedirectUrl();

        $redirectUrl =
            add_query_arg(
                [
                    'dsm_favorite_result' =>
                        sanitize_key(
                            $result
                        ),

                    'dsm_favorite_type' =>
                        $itemType,

                    'dsm_favorite_item' =>
                        $itemId,
                ],
                $redirectUrl
            );

        wp_safe_redirect(
            $redirectUrl
        );

        exit;
    }

    private function redirectError(
        Throwable $exception
    ): never {
        $redirectUrl =
            $this->resolveRedirectUrl();

        $message =
            trim(
                $exception->getMessage()
            );

        if ($message === '') {
            $message =
                'No se pudo completar la operación.';
        }

        $redirectUrl =
            add_query_arg(
                [
                    'dsm_favorite_result' =>
                        'error',

                    'dsm_favorite_message' =>
                        $message,
                ],
                $redirectUrl
            );

        wp_safe_redirect(
            $redirectUrl
        );

        exit;
    }

    private function resolveRedirectUrl(): string
    {
        $redirectUrl = '';

        if (
            isset($_POST['redirect_to'])
            && is_scalar(
                $_POST['redirect_to']
            )
        ) {
            $redirectUrl =
                trim(
                    (string) wp_unslash(
                        $_POST['redirect_to']
                    )
                );
        }

        if ($redirectUrl === '') {
            $referer =
                wp_get_referer();

            if (is_string($referer)) {
                $redirectUrl =
                    trim(
                        $referer
                    );
            }
        }

        if ($redirectUrl === '') {
            $redirectUrl =
                home_url('/');
        }

        $redirectUrl =
            remove_query_arg(
                [
                    'dsm_favorite_result',
                    'dsm_favorite_ad',
                    'dsm_favorite_type',
                    'dsm_favorite_item',
                    'dsm_favorite_message',
                ],
                $redirectUrl
            );

        return wp_validate_redirect(
            $redirectUrl,
            home_url('/')
        );
    }
}
