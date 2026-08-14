<?php

declare(strict_types=1);

namespace DSM\Favoritos\Frontend;

use DSM\Favoritos\Application\AddFavorite;
use DSM\Favoritos\Application\RemoveFavorite;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gestiona las acciones HTTP de favoritos realizadas
 * por clientes desde el frontend.
 *
 * Acciones:
 *
 * - añadir un anuncio a favoritos;
 * - quitar un anuncio de favoritos.
 *
 * El controlador nunca utiliza el ID del usuario de
 * WordPress como customer_id.
 *
 * El cliente DSM se obtiene mediante el contrato público:
 *
 * dsm_current_customer_context
 */
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

    /**
     * Registra las acciones admin-post.
     */
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
                'handleGuest',
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
                'handleGuest',
            ]
        );
    }

    /**
     * Añade un anuncio a favoritos.
     */
    public function handleAdd(): void
    {
        try {
            $advertisementId =
                $this->resolveAdvertisementId();

            $this->verifyNonce(
                self::ACTION_ADD,
                $advertisementId
            );

            $customer =
                $this->resolveCurrentCustomer();

            $this->addFavorite
                ->execute(
                    (int) $customer['id'],
                    $advertisementId
                );

            $this->redirect(
                'added',
                $advertisementId
            );
        } catch (Throwable $exception) {
            $this->redirectError(
                $exception
            );
        }
    }

    /**
     * Quita un anuncio de favoritos.
     */
    public function handleRemove(): void
    {
        try {
            $advertisementId =
                $this->resolveAdvertisementId();

            $this->verifyNonce(
                self::ACTION_REMOVE,
                $advertisementId
            );

            $customer =
                $this->resolveCurrentCustomer();

            $this->removeFavorite
                ->execute(
                    (int) $customer['id'],
                    $advertisementId
                );

            $this->redirect(
                'removed',
                $advertisementId
            );
        } catch (Throwable $exception) {
            $this->redirectError(
                $exception
            );
        }
    }

    /**
     * Gestiona peticiones realizadas sin una sesión
     * autenticada.
     *
     * En lugar de dejar admin-post sin respuesta,
     * enviamos al login de WordPress y conservamos
     * la URL desde la que se realizó la acción.
     */
    public function handleGuest(): void
    {
        $redirectUrl =
            $this->resolveRedirectUrl();

        wp_safe_redirect(
            wp_login_url(
                $redirectUrl
            )
        );

        exit;
    }

    /**
     * Devuelve la acción utilizada para generar/verificar
     * el nonce de un anuncio.
     */
    public static function getNonceAction(
        string $action,
        int $advertisementId
    ): string {
        $action =
            sanitize_key(
                $action
            );

        if (
            !in_array(
                $action,
                [
                    self::ACTION_ADD,
                    self::ACTION_REMOVE,
                ],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'La acción de favorito no es válida.'
            );
        }

        if ($advertisementId <= 0) {
            throw new \InvalidArgumentException(
                'El ID del anuncio debe ser mayor que cero.'
            );
        }

        return self::NONCE_ACTION_PREFIX
            . $action
            . '_'
            . $advertisementId;
    }

    /**
     * Obtiene el contexto neutral proporcionado por
     * dsm-clientes.
     *
     * @return array{id:int,status:string}
     */
    private function resolveCurrentCustomer(): array
    {
        $context =
            apply_filters(
                'dsm_current_customer_context',
                null
            );

        if (!is_array($context)) {
            throw new RuntimeException(
                'No se pudo identificar al cliente.'
            );
        }

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

        $status =
            sanitize_key(
                (string) (
                    $context['status']
                    ?? ''
                )
            );

        if ($status !== 'active') {
            throw new RuntimeException(
                'La cuenta del cliente no está activa.'
            );
        }

        return [
            'id' =>
                $customerId,

            'status' =>
                $status,
        ];
    }

    /**
     * Obtiene advertisement_id de la petición.
     */
    private function resolveAdvertisementId(): int
    {
        $advertisementId =
            isset($_POST['advertisement_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'advertisement_id'
                        ]
                    )
                )
                : 0;

        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El anuncio indicado no es válido.'
            );
        }

        return $advertisementId;
    }

    /**
     * Verifica el nonce correspondiente a la acción y
     * al anuncio.
     */
    private function verifyNonce(
        string $action,
        int $advertisementId
    ): void {
        $nonce =
            isset($_POST[self::NONCE_FIELD])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[
                            self::NONCE_FIELD
                        ]
                    )
                )
                : '';

        if ($nonce === '') {
            throw new RuntimeException(
                'No se pudo validar la solicitud.'
            );
        }

        $valid =
            wp_verify_nonce(
                $nonce,
                self::getNonceAction(
                    $action,
                    $advertisementId
                )
            );

        if ($valid === false) {
            throw new RuntimeException(
                'La solicitud ha caducado o no es válida.'
            );
        }
    }

    /**
     * Redirección tras una operación correcta.
     */
    private function redirect(
        string $result,
        int $advertisementId
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

                    'dsm_favorite_ad' =>
                        $advertisementId,
                ],
                $redirectUrl
            );

        wp_safe_redirect(
            $redirectUrl
        );

        exit;
    }

    /**
     * Redirección tras un error.
     */
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

    /**
     * Obtiene una URL de retorno segura.
     *
     * El formulario podrá enviar redirect_to.
     * Si no existe, usamos el referer.
     * Como último recurso volvemos a home_url().
     */
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

        /*
         * Evitamos conservar mensajes anteriores de
         * favoritos en la nueva redirección.
         */
        $redirectUrl =
            remove_query_arg(
                [
                    'dsm_favorite_result',
                    'dsm_favorite_ad',
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
