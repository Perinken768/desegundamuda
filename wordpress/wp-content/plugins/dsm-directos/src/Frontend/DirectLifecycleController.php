<?php

declare(strict_types=1);

namespace DSM\Directos\Frontend;

use DSM\Directos\Application\DirectLifecycleService;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectLifecycleController
{
    public const ACTION_OPEN =
        'dsm_directos_open';

    public const ACTION_CLOSE =
        'dsm_directos_close';

    public const NONCE_FIELD =
        'dsm_directos_lifecycle_nonce';

    public static function register(): void
    {
        $controller =
            new self();

        /*
         * Usuario autenticado en WordPress.
         */
        add_action(
            'admin_post_'
            . self::ACTION_OPEN,
            [
                $controller,
                'handleOpen',
            ]
        );

        add_action(
            'admin_post_'
            . self::ACTION_CLOSE,
            [
                $controller,
                'handleClose',
            ]
        );

        /*
         * Cliente DSM.
         *
         * Los clientes de DeSegundaMuda utilizan su propio
         * contexto de sesión y no necesitan ser usuarios
         * autenticados de WordPress.
         */
        add_action(
            'admin_post_nopriv_'
            . self::ACTION_OPEN,
            [
                $controller,
                'handleOpen',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::ACTION_CLOSE,
            [
                $controller,
                'handleClose',
            ]
        );
    }

    public function handleOpen(): never
    {
        try {
            $this->verifyNonce(
                self::ACTION_OPEN
            );

            $customerId =
                $this->resolveCustomerId();

            (
                new DirectLifecycleService()
            )->open(
                $customerId
            );

            $this->redirect(
                status:
                    'opened'
            );
        } catch (Throwable $exception) {
            $this->redirect(
                error:
                    $exception->getMessage()
            );
        }
    }

    public function handleClose(): never
    {
        try {
            $this->verifyNonce(
                self::ACTION_CLOSE
            );

            $customerId =
                $this->resolveCustomerId();

            (
                new DirectLifecycleService()
            )->close(
                $customerId
            );

            $this->redirect(
                status:
                    'closed'
            );
        } catch (Throwable $exception) {
            $this->redirect(
                error:
                    $exception->getMessage()
            );
        }
    }

    private function verifyNonce(
        string $action
    ): void {
        $nonce =
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
                ? (string) wp_unslash(
                    $_POST[
                        self::NONCE_FIELD
                    ]
                )
                : '';

        if (
            $nonce === ''
            || !wp_verify_nonce(
                $nonce,
                $action
            )
        ) {
            throw new RuntimeException(
                'La sesión de seguridad del directo ha caducado. Recarga la página e inténtalo de nuevo.'
            );
        }
    }

    private function resolveCustomerId(): int
    {
        $customer =
            apply_filters(
                'dsm_current_customer_context',
                null
            );

        $customerId =
            is_array($customer)
                ? max(
                    0,
                    (int) (
                        $customer['id']
                        ?? 0
                    )
                )
                : 0;

        if ($customerId <= 0) {
            throw new RuntimeException(
                'No se pudo identificar al propietario del directo.'
            );
        }

        return $customerId;
    }

    private function redirect(
        string $status = '',
        string $error = ''
    ): never {
        $url =
            home_url(
                '/directo/'
            );

        if ($status !== '') {
            $url =
                add_query_arg(
                    'direct_status',
                    sanitize_key(
                        $status
                    ),
                    $url
                );
        }

        if ($error !== '') {
            $url =
                add_query_arg(
                    'direct_error',
                    rawurlencode(
                        $error
                    ),
                    $url
                );
        }

        wp_safe_redirect(
            $url
        );

        exit;
    }

    private function __construct()
    {
    }
}
