<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Application\ChangeCustomerPassword;
use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerCookie;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Impersonation\CustomerImpersonationCookie;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class AccountPasswordController
{
    public static function register(): void
    {
        add_action(
            'admin_post_nopriv_dsm_customer_change_password',
            [self::class, 'handle']
        );

        add_action(
            'admin_post_dsm_customer_change_password',
            [self::class, 'handle']
        );
    }

    public static function handle(): void
    {
        check_admin_referer(
            'dsm_customer_change_password',
            'dsm_change_password_nonce'
        );

        if (CustomerImpersonationCookie::isActive()) {
            wp_safe_redirect(
                add_query_arg(
                    'account_error',
                    'impersonation_restricted',
                    home_url('/mi-cuenta/')
                )
            );

            exit;
        }

        $currentPassword = isset($_POST['current_password'])
            ? (string) wp_unslash(
                $_POST['current_password']
            )
            : '';

        $newPassword = isset($_POST['new_password'])
            ? (string) wp_unslash(
                $_POST['new_password']
            )
            : '';

        $newPasswordConfirmation =
            isset($_POST['new_password_confirmation'])
                ? (string) wp_unslash(
                    $_POST['new_password_confirmation']
                )
                : '';

        $sessionRepository =
            new CustomerSessionRepository();

        $customerRepository =
            new CustomerRepository();

        $customer = (new AuthenticatedCustomer(
            $sessionRepository,
            $customerRepository
        ))->resolve();

        if ($customer === null) {
            wp_safe_redirect(
                home_url('/iniciar-sesion/')
            );

            exit;
        }

        try {
            (new ChangeCustomerPassword(
                $customerRepository,
                $sessionRepository
            ))->execute(
                $customer,
                $currentPassword,
                $newPassword,
                $newPasswordConfirmation
            );

            CustomerCookie::clear();

            wp_safe_redirect(
                add_query_arg(
                    'account_status',
                    'password_changed',
                    home_url('/iniciar-sesion/')
                )
            );

            exit;
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] Error cambiando contraseña: '
                . $exception->getMessage()
            );

            wp_safe_redirect(
                add_query_arg(
                    'account_error',
                    'password_change_failed',
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