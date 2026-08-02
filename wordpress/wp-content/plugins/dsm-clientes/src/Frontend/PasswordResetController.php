<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Application\RequestCustomerPasswordReset;
use DSM\Clientes\Application\ResetCustomerPassword;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Authentication\PasswordResetRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Customer\CustomerStatus;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PasswordResetController
{
    public static function register(): void
    {
        add_action(
            'admin_post_nopriv_dsm_customer_request_password_reset',
            [self::class, 'handleRequest']
        );

        add_action(
            'admin_post_dsm_customer_request_password_reset',
            [self::class, 'handleRequest']
        );

        add_action(
            'admin_post_nopriv_dsm_customer_reset_password',
            [self::class, 'handleReset']
        );

        add_action(
            'admin_post_dsm_customer_reset_password',
            [self::class, 'handleReset']
        );
    }

    public static function handleRequest(): void
    {
        check_admin_referer(
            'dsm_customer_request_password_reset',
            'dsm_password_reset_nonce'
        );

        $email = isset($_POST['email'])
            ? sanitize_email(
                wp_unslash($_POST['email'])
            )
            : '';

        try {
            $customer = (new CustomerRepository())
                ->findByEmail($email);

            if (
                $customer !== null
                && !in_array(
                    $customer->getStatus(),
                    [
                        CustomerStatus::BLOCKED,
                        CustomerStatus::SUSPENDED,
                        CustomerStatus::DELETION_PENDING,
                    ],
                    true
                )
            ) {
                (new RequestCustomerPasswordReset(
                    new PasswordResetRepository()
                ))->execute($customer);
            }

            self::redirectForgot('sent');
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] Error solicitando recuperación: '
                . $exception->getMessage()
            );

            self::redirectForgot('error');
        }
    }

    public static function handleReset(): void
    {
        check_admin_referer(
            'dsm_customer_reset_password',
            'dsm_reset_password_nonce'
        );

        $token = isset($_POST['token'])
            ? sanitize_text_field(
                wp_unslash($_POST['token'])
            )
            : '';

        $password = isset($_POST['password'])
            ? (string) wp_unslash($_POST['password'])
            : '';

        $passwordConfirmation =
            isset($_POST['password_confirmation'])
                ? (string) wp_unslash(
                    $_POST['password_confirmation']
                )
                : '';

        try {
            (new ResetCustomerPassword(
                new PasswordResetRepository(),
                new CustomerRepository(),
                new CustomerSessionRepository()
            ))->execute(
                $token,
                $password,
                $passwordConfirmation
            );

            wp_safe_redirect(
                add_query_arg(
                    'account_status',
                    'password_reset',
                    home_url('/iniciar-sesion/')
                )
            );

            exit;
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] Error restableciendo contraseña: '
                . $exception->getMessage()
            );

            wp_safe_redirect(
                add_query_arg(
                    [
                        'token' => $token,
                        'reset_status' => 'error',
                    ],
                    home_url('/restablecer-contrasena/')
                )
            );

            exit;
        }
    }

    private static function redirectForgot(
        string $status
    ): never {
        wp_safe_redirect(
            add_query_arg(
                'reset_status',
                $status,
                home_url('/recuperar-contrasena/')
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}