<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Profile\CustomerProfileRepository;
use DSM\Clientes\Support\TemplateRenderer;

if (!defined('ABSPATH')) {
    exit;
}

final class ProfileShortcode
{
    public const SHORTCODE = 'dsm_customer_profile';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [self::class, 'render']
        );
    }

    public static function render(): string
    {
        $auth = new AuthenticatedCustomer(
            new CustomerSessionRepository(),
            new CustomerRepository()
        );

        $customer = $auth->resolve();

        if ($customer === null) {
            wp_safe_redirect(
                home_url('/iniciar-sesion/')
            );

            exit;
        }

        $profile = (
            new CustomerProfileRepository()
        )->findByCustomerId(
            $customer->getId()
        );

        if ($profile === null) {
            return '';
        }

        return TemplateRenderer::render(
            'account/profile-form',
            [
                'customer' => $customer,
                'profile' => $profile,
                'updated' => isset($_GET['profile_updated']),
                'hasError' => isset($_GET['profile_error']),
            ]
        );
    }

    private function __construct()
    {
    }
}