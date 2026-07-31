<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Application\LoginCustomer;
use DSM\Clientes\Application\LogoutCustomer;
use DSM\Clientes\Authentication\CustomerCookie;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class AuthController
{
    public static function register(): void
    {
        add_action(
            'admin_post_nopriv_dsm_customer_login',
            [self::class, 'handleLogin']
        );

        add_action(
            'admin_post_dsm_customer_login',
            [self::class, 'handleLogin']
        );

        add_action(
            'admin_post_nopriv_dsm_customer_logout',
            [self::class, 'handleLogout']
        );

        add_action(
            'admin_post_dsm_customer_logout',
            [self::class, 'handleLogout']
        );
    }

    public static function handleLogin(): void
    {
        check_admin_referer(
            'dsm_customer_login',
            'dsm_login_nonce'
        );

        $email = isset($_POST['email'])
            ? sanitize_email(wp_unslash($_POST['email']))
            : '';

        $password = isset($_POST['password'])
            ? (string) wp_unslash($_POST['password'])
            : '';

        $ipAddress = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(
                wp_unslash($_SERVER['REMOTE_ADDR'])
            )
            : null;

        $userAgent = isset($_SERVER['HTTP_USER_AGENT'])
            ? sanitize_text_field(
                wp_unslash($_SERVER['HTTP_USER_AGENT'])
            )
            : null;

        try {
            $login = new LoginCustomer(
                new CustomerRepository(),
                new CustomerSessionRepository()
            );

            $result = $login->execute(
                $email,
                $password,
                $ipAddress,
                $userAgent
            );

            $expiresTimestamp = strtotime(
                $result->getSession()->getExpiresAt() . ' UTC'
            );

            if ($expiresTimestamp === false) {
                throw new RuntimeException(
                    'No se pudo calcular la expiración de la sesión.'
                );
            }

            CustomerCookie::set(
                $result->getToken(),
                $expiresTimestamp
            );

            wp_safe_redirect(
                home_url('/mi-cuenta/')
            );

            exit;
        } catch (Throwable $exception) {
            wp_safe_redirect(
                add_query_arg(
                    'login_error',
                    'invalid_credentials',
                    home_url('/iniciar-sesion/')
                )
            );

            exit;
        }
    }

    public static function handleLogout(): void
    {
        check_admin_referer(
            'dsm_customer_logout',
            'dsm_logout_nonce'
        );

        $logout = new LogoutCustomer(
            new CustomerSessionRepository()
        );

        $logout->execute();

        wp_safe_redirect(
            home_url('/')
        );

        exit;
    }

    private function __construct()
    {
    }
}