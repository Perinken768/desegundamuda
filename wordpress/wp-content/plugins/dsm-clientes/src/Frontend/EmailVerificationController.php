<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Application\VerifyCustomerEmail;
use DSM\Clientes\Authentication\EmailVerificationRepository;
use DSM\Clientes\Customer\CustomerRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class EmailVerificationController
{
    public static function register(): void
    {
        add_action(
            'admin_post_nopriv_dsm_customer_verify_email',
            [self::class, 'handleVerification']
        );

        add_action(
            'admin_post_dsm_customer_verify_email',
            [self::class, 'handleVerification']
        );
    }

    public static function handleVerification(): void
    {
        $token = isset($_GET['token'])
            ? sanitize_text_field(
                wp_unslash($_GET['token'])
            )
            : '';

        try {
            $service = new VerifyCustomerEmail(
                new EmailVerificationRepository(),
                new CustomerRepository()
            );

            $service->execute($token);

            wp_safe_redirect(
                add_query_arg(
                    'email_verified',
                    '1',
                    home_url('/mi-cuenta/')
                )
            );

            exit;
        } catch (Throwable $exception) {
            wp_safe_redirect(
                add_query_arg(
                    'verification_error',
                    'invalid_or_expired',
                    home_url('/mi-cuenta/')
                )
            );

            exit;
        }
    }

    private function __construct()
    {
    }
}