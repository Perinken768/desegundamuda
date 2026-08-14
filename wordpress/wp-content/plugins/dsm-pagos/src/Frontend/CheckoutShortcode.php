<?php

declare(strict_types=1);

namespace DSM\Pagos\Frontend;

use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Pagos\Payment\PaymentRepository;
use DSM\Pagos\Provider\PaymentProviderManager;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CheckoutShortcode
{
    public const SHORTCODE =
        'dsm_payment_checkout';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [
                self::class,
                'render',
            ]
        );
    }

    public static function render(): string
    {
        $customerId =
            self::resolveAuthenticatedCustomerId();

        if ($customerId <= 0) {
            wp_safe_redirect(
                home_url(
                    '/iniciar-sesion/'
                )
            );

            exit;
        }

        $paymentId =
            isset($_GET['payment_id'])
                ? absint(
                    wp_unslash(
                        (string) $_GET['payment_id']
                    )
                )
                : 0;

        $advertisementId =
            isset($_GET['advertisement_id'])
                ? absint(
                    wp_unslash(
                        (string) $_GET['advertisement_id']
                    )
                )
                : 0;

        if ($paymentId <= 0) {
            return self::renderError(
                __(
                    'No se indicó un pago válido.',
                    'dsm-pagos'
                )
            );
        }

        try {
            $repository =
                new PaymentRepository();

            $payment =
                $repository->findById(
                    $paymentId
                );

            if ($payment === null) {
                return self::renderError(
                    __(
                        'No se encontró el pago indicado.',
                        'dsm-pagos'
                    )
                );
            }

            if (
                !$payment->belongsToCustomer(
                    $customerId
                )
            ) {
                return self::renderError(
                    __(
                        'El pago no pertenece al cliente actual.',
                        'dsm-pagos'
                    )
                );
            }

            if (!$payment->isPending()) {
                return self::renderError(
                    __(
                        'Este pago ya no está pendiente.',
                        'dsm-pagos'
                    )
                );
            }

            $providers =
                PaymentProviderManager::registry()
                    ->available();

            return self::renderTemplate(
                [
                    'payment' =>
                        $payment,

                    'advertisementId' =>
                        $advertisementId,

                    'providers' =>
                        $providers,
                ]
            );
        } catch (Throwable $exception) {
            return self::renderError(
                $exception->getMessage()
            );
        }
    }

    /**
     * @param array<string, mixed> $variables
     */
    private static function renderTemplate(
        array $variables
    ): string {
        $template =
            DSM_PAGOS_PATH
            . 'templates/account/'
            . 'checkout.php';

        if (!is_file($template)) {
            return self::renderError(
                __(
                    'No se encontró la plantilla de pago.',
                    'dsm-pagos'
                )
            );
        }

        extract(
            $variables,
            EXTR_SKIP
        );

        ob_start();

        include $template;

        $output =
            ob_get_clean();

        return is_string($output)
            ? $output
            : '';
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

    private static function renderError(
        string $message
    ): string {
        return sprintf(
            '<div class="dsm-account-notice dsm-account-notice--error">%s</div>',
            esc_html(
                $message
            )
        );
    }

    private function __construct()
    {
    }
}
