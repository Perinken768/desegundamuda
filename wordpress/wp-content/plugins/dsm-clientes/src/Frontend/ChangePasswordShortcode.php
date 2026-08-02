<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Impersonation\CustomerImpersonationCookie;
use DSM\Clientes\Support\TemplateRenderer;

if (!defined('ABSPATH')) {
    exit;
}

final class ChangePasswordShortcode
{
    public const SHORTCODE =
        'dsm_customer_change_password';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [self::class, 'render']
        );
    }

    public static function render(): string
    {
        $customer = (new AuthenticatedCustomer(
            new CustomerSessionRepository(),
            new CustomerRepository()
        ))->resolve();

        if ($customer === null) {
            wp_safe_redirect(
                home_url('/iniciar-sesion/')
            );

            exit;
        }

        return TemplateRenderer::render(
            'account/change-password',
            [
                'customer' => $customer,
                'isImpersonating' =>
                    CustomerImpersonationCookie::isActive(),
            ]
        );
    }

    private function __construct()
    {
    }
}