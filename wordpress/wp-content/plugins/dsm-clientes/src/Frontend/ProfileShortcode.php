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

/**
 * Muestra el formulario público de edición del perfil.
 */
final class ProfileShortcode
{
    public const SHORTCODE =
        'dsm_customer_profile';

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
        $authenticatedCustomer =
            new AuthenticatedCustomer(
                new CustomerSessionRepository(),
                new CustomerRepository()
            );

        $customer =
            $authenticatedCustomer->resolve();

        if ($customer === null) {
            wp_safe_redirect(
                home_url(
                    '/iniciar-sesion/'
                )
            );

            exit;
        }

        $profileRepository =
            new CustomerProfileRepository();

        $profile =
            $profileRepository
                ->findByCustomerId(
                    $customer->getId()
                );

        if ($profile === null) {
            return sprintf(
                '<div class="dsm-account-notice dsm-account-notice--error">%s</div>',
                esc_html__(
                    'No se pudo recuperar tu perfil.',
                    'dsm-clientes'
                )
            );
        }

        $updated =
            isset(
                $_GET['profile_updated']
            )
            && sanitize_key(
                wp_unslash(
                    (string) $_GET[
                        'profile_updated'
                    ]
                )
            ) === '1';

        $hasError =
            isset(
                $_GET['profile_error']
            );

        return TemplateRenderer::render(
            'account/profile-form',
            [
                'customer' =>
                    $customer,

                'profile' =>
                    $profile,

                'updated' =>
                    $updated,

                'hasError' =>
                    $hasError,

                /*
                 * Indica si el cliente ya dispone de:
                 *
                 * - teléfono válido;
                 * - al menos un método autorizado.
                 */
                'hasValidContactMethod' =>
                    $profile
                        ->hasValidContactMethod(),

                'allowsPhoneCalls' =>
                    $profile
                        ->allowsPhoneCalls(),

                'allowsWhatsapp' =>
                    $profile
                        ->allowsWhatsapp(),

                'normalizedPhone' =>
                    $profile->getPhone()
                    ?? '',
            ]
        );
    }

    private function __construct()
    {
    }
}