<?php

declare(strict_types=1);

namespace DSM\Facturacion\Frontend;

use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Facturacion\Billing\BillingProfileRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class BillingProfileShortcode
{
    public const SHORTCODE =
        'dsm_billing_profile';

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
        try {
            $authenticatedCustomer =
                new AuthenticatedCustomer(
                    new CustomerSessionRepository(),
                    new CustomerRepository()
                );

            $customer =
                $authenticatedCustomer->resolve();

            if ($customer === null) {
                return sprintf(
                    '<div class="dsm-account-notice dsm-account-notice--warning">%s</div>',
                    esc_html__(
                        'Debes iniciar sesión para gestionar tus datos fiscales.',
                        'dsm-facturacion'
                    )
                );
            }

            $repository =
                new BillingProfileRepository();

            $billingProfile =
                $repository
                    ->findByCustomerId(
                        $customer->getId()
                    );

            $status =
                isset(
                    $_GET[
                        'billing_status'
                    ]
                )
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'billing_status'
                            ]
                        )
                    )
                    : '';

            ob_start();

            require DSM_FACTURACION_PATH
                . 'templates/account/'
                . 'billing-profile.php';

            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            error_log(
                '[DSM Facturación] No se pudo cargar el perfil fiscal: '
                . $exception->getMessage()
            );

            return sprintf(
                '<div class="dsm-account-notice dsm-account-notice--error">%s</div>',
                esc_html__(
                    'No se pudieron cargar tus datos fiscales.',
                    'dsm-facturacion'
                )
            );
        }
    }

    private function __construct()
    {
    }
}
