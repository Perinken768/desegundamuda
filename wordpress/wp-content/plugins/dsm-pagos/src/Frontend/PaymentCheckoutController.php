<?php

declare(strict_types=1);

namespace DSM\Pagos\Frontend;

use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Pagos\Payment\PaymentRepository;
use DSM\Pagos\Provider\PaymentProviderManager;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PaymentCheckoutController
{
    public const ACTION =
        'dsm_payment_checkout_start';

    public const NONCE_FIELD =
        'dsm_payment_checkout_nonce';

    public static function register(): void
    {
        add_action(
            'admin_post_'
            . self::ACTION,
            [
                self::class,
                'handle',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::ACTION,
            [
                self::class,
                'handle',
            ]
        );
    }

    public static function handle(): never
    {
        $customerId =
            self::resolveAuthenticatedCustomerId();

        if ($customerId <= 0) {
            self::redirectToLogin();
        }

        $paymentId =
            self::getPostId(
                'payment_id'
            );

        $advertisementId =
            self::getPostId(
                'advertisement_id'
            );

        $providerCode =
            isset($_POST['provider'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_POST['provider']
                    )
                )
                : '';

        check_admin_referer(
            self::getNonceAction(
                $paymentId,
                $providerCode
            ),
            self::NONCE_FIELD
        );

        try {
            if ($paymentId <= 0) {
                throw new RuntimeException(
                    'El identificador del pago no es válido.'
                );
            }

            if ($providerCode === '') {
                throw new RuntimeException(
                    'No se indicó un proveedor de pago válido.'
                );
            }

            $repository =
                new PaymentRepository();

            $payment =
                $repository->findById(
                    $paymentId
                );

            if ($payment === null) {
                throw new RuntimeException(
                    'No se encontró el pago indicado.'
                );
            }

            if (
                !$payment->belongsToCustomer(
                    $customerId
                )
            ) {
                throw new RuntimeException(
                    'El pago no pertenece al cliente actual.'
                );
            }

            if (!$payment->isPending()) {
                throw new RuntimeException(
                    'Solo los pagos pendientes pueden continuar al proveedor.'
                );
            }

            $registry =
                PaymentProviderManager::registry();

            $provider =
                $registry->get(
                    $providerCode
                );

            if (!$provider->isAvailable()) {
                throw new RuntimeException(
                    'El proveedor de pago seleccionado no está disponible.'
                );
            }

            $successUrl =
                add_query_arg(
                    [
                        'payment_id' =>
                            $paymentId,

                        'advertisement_id' =>
                            $advertisementId,

                        'payment_result' =>
                            'success',
                    ],
                    home_url(
                        '/checkout-pago/'
                    )
                );

            $cancelUrl =
                add_query_arg(
                    [
                        'payment_id' =>
                            $paymentId,

                        'advertisement_id' =>
                            $advertisementId,

                        'payment_result' =>
                            'cancelled',
                    ],
                    home_url(
                        '/checkout-pago/'
                    )
                );

            $checkoutUrl =
                $provider->createCheckoutUrl(
                    $payment,
                    $successUrl,
                    $cancelUrl
                );

            if (
                trim(
                    $checkoutUrl
                ) === ''
            ) {
                throw new RuntimeException(
                    'El proveedor no devolvió una URL de pago válida.'
                );
            }

            wp_safe_redirect(
                $checkoutUrl
            );

            exit;
        } catch (Throwable $exception) {
            self::redirectToCheckout(
                $paymentId,
                $advertisementId,
                $exception->getMessage()
            );
        }
    }

    public static function getNonceAction(
        int $paymentId,
        string $providerCode
    ): string {
        return self::ACTION
            . '_'
            . $paymentId
            . '_'
            . sanitize_key(
                $providerCode
            );
    }

    private static function resolveAuthenticatedCustomerId(): int
    {
        try {
            $auth =
                new AuthenticatedCustomer(
                    new CustomerSessionRepository(),
                    new CustomerRepository()
                );

            $customer =
                $auth->resolve();

            return $customer !== null
                ? $customer->getId()
                : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    private static function getPostId(
        string $field
    ): int {
        if (!isset($_POST[$field])) {
            return 0;
        }

        return absint(
            wp_unslash(
                (string) $_POST[$field]
            )
        );
    }

    private static function redirectToLogin(): never
    {
        wp_safe_redirect(
            home_url(
                '/iniciar-sesion/'
            )
        );

        exit;
    }

    private static function redirectToCheckout(
        int $paymentId,
        int $advertisementId,
        string $error
    ): never {
        $url =
            add_query_arg(
                [
                    'payment_id' =>
                        $paymentId,

                    'advertisement_id' =>
                        $advertisementId,

                    'payment_error' =>
                        $error,
                ],
                home_url(
                    '/checkout-pago/'
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
