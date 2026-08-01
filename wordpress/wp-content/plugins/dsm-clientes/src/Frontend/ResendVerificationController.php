<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Application\SendCustomerVerificationEmail;
use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Authentication\EmailVerificationRepository;
use DSM\Clientes\Customer\CustomerRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ResendVerificationController
{
    private const ACTION = 'dsm_customer_resend_verification';
    private const NONCE_ACTION = 'dsm_customer_resend_verification';
    private const NONCE_NAME = 'dsm_resend_verification_nonce';

    public static function register(): void
    {
        /*
         * Los clientes de DSM no son usuarios de wp_users.
         * Por eso WordPress los considera visitantes no autenticados
         * y necesitamos admin_post_nopriv.
         */
        add_action(
            'admin_post_nopriv_' . self::ACTION,
            [self::class, 'handle']
        );

        /*
         * También lo registramos para administradores de WordPress
         * que puedan estar probando el flujo.
         */
        add_action(
            'admin_post_' . self::ACTION,
            [self::class, 'handle']
        );
    }

    public static function handle(): void
    {
        check_admin_referer(
            self::NONCE_ACTION,
            self::NONCE_NAME
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

        if ($customer->getEmailVerifiedAt() !== null) {
            wp_safe_redirect(
                add_query_arg(
                    'verification_status',
                    'already_verified',
                    home_url('/mi-cuenta/')
                )
            );

            exit;
        }

        try {
            $service = new SendCustomerVerificationEmail(
                new EmailVerificationRepository()
            );

            $service->execute($customer);

            $status = 'resent';
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] No se pudo reenviar la verificación: '
                . $exception->getMessage()
            );

            $status = 'resend_error';
        }

        wp_safe_redirect(
            add_query_arg(
                'verification_status',
                $status,
                home_url('/mi-cuenta/')
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}