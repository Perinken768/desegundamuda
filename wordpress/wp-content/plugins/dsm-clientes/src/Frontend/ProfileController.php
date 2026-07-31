<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Application\UpdateCustomerProfile;
use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Profile\CustomerProfileRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ProfileController
{
    public static function register(): void
    {
        add_action(
            'admin_post_nopriv_dsm_customer_profile_update',
            [self::class, 'handleUpdate']
        );

        add_action(
            'admin_post_dsm_customer_profile_update',
            [self::class, 'handleUpdate']
        );
    }

    public static function handleUpdate(): void
    {
        check_admin_referer(
            'dsm_customer_profile_update',
            'dsm_profile_nonce'
        );

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

        $displayName = isset($_POST['display_name'])
            ? sanitize_text_field(
                wp_unslash($_POST['display_name'])
            )
            : '';

        $phone = isset($_POST['phone'])
            ? sanitize_text_field(
                wp_unslash($_POST['phone'])
            )
            : null;

        $whatsappPhone = isset($_POST['whatsapp_phone'])
            ? sanitize_text_field(
                wp_unslash($_POST['whatsapp_phone'])
            )
            : null;

        $bio = isset($_POST['bio'])
            ? sanitize_textarea_field(
                wp_unslash($_POST['bio'])
            )
            : null;

        try {
            $service = new UpdateCustomerProfile(
                new CustomerProfileRepository()
            );

            $service->execute(
                $customer->getId(),
                $displayName,
                $phone,
                $whatsappPhone,
                $bio
            );

            wp_safe_redirect(
                add_query_arg(
                    'profile_updated',
                    '1',
                    home_url('/editar-perfil/')
                )
            );

            exit;
        } catch (Throwable $exception) {
            wp_safe_redirect(
                add_query_arg(
                    'profile_error',
                    'invalid_data',
                    home_url('/editar-perfil/')
                )
            );

            exit;
        }
    }

    private function __construct()
    {
    }
}