<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Application\LoginCustomer;
use DSM\Clientes\Application\LogoutCustomer;
use DSM\Clientes\Application\RegisterCustomer;
use DSM\Clientes\Authentication\CustomerCookie;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Authentication\LoginResult;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Application\SendCustomerVerificationEmail;
use DSM\Clientes\Authentication\EmailVerificationRepository;
use DSM\Clientes\Profile\CustomerProfileRepository;
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
            'admin_post_nopriv_dsm_customer_register',
            [self::class, 'handleRegister']
        );

        add_action(
            'admin_post_dsm_customer_register',
            [self::class, 'handleRegister']
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

        try {
            $login = new LoginCustomer(
                new CustomerRepository(),
                new CustomerSessionRepository()
            );

            $result = $login->execute(
                $email,
                $password,
                self::getIpAddress(),
                self::getUserAgent()
            );

            self::persistLogin($result);

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

    public static function handleRegister(): void
    {
        check_admin_referer(
            'dsm_customer_register',
            'dsm_register_nonce'
        );

        $displayName = isset($_POST['display_name'])
            ? sanitize_text_field(
                wp_unslash($_POST['display_name'])
            )
            : '';

        $email = isset($_POST['email'])
            ? sanitize_email(
                wp_unslash($_POST['email'])
            )
            : '';

        $password = isset($_POST['password'])
            ? (string) wp_unslash($_POST['password'])
            : '';

        $passwordConfirmation = isset(
            $_POST['password_confirmation']
        )
            ? (string) wp_unslash(
                $_POST['password_confirmation']
            )
            : '';

        $validationError = self::validateRegistrationInput(
            $displayName,
            $email,
            $password,
            $passwordConfirmation
        );

        if ($validationError !== null) {
            self::redirectRegisterError(
                $validationError
            );
        }

        $customerRepository = new CustomerRepository();

        if ($customerRepository->emailExists($email)) {
            self::redirectRegisterError(
                'email_exists'
            );
        }

        try {
            $register = new RegisterCustomer(
                $customerRepository,
                new CustomerProfileRepository()
            );

            $registration = $register->execute(
                $email,
                $password,
                $displayName
            );

            $verificationEmail =
                new SendCustomerVerificationEmail(
                    new EmailVerificationRepository()
            );

            $verificationEmail->execute(
                $registration['customer']
            );

            /*
             * Iniciamos sesión automáticamente después
             * de completar correctamente el registro.
             */
            $login = new LoginCustomer(
                $customerRepository,
                new CustomerSessionRepository()
            );

            $result = $login->execute(
                $email,
                $password,
                self::getIpAddress(),
                self::getUserAgent()
            );

            self::persistLogin($result);

            wp_safe_redirect(
                home_url('/mi-cuenta/')
            );

            exit;
        } catch (Throwable $exception) {
            self::redirectRegisterError(
                'registration_failed'
            );
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

    private static function validateRegistrationInput(
        string $displayName,
        string $email,
        string $password,
        string $passwordConfirmation
    ): ?string {
        if ($displayName === '' || mb_strlen($displayName) > 150) {
            return 'invalid_display_name';
        }

        if (!is_email($email)) {
            return 'invalid_email';
        }

        if (mb_strlen($password) < 10) {
            return 'password_too_short';
        }

        if (!hash_equals($password, $passwordConfirmation)) {
            return 'password_mismatch';
        }

        return null;
    }

    private static function persistLogin(
        LoginResult $result
    ): void {
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
    }

    private static function redirectRegisterError(
        string $errorCode
    ): never {
        wp_safe_redirect(
            add_query_arg(
                'register_error',
                $errorCode,
                home_url('/registro/')
            )
        );

        exit;
    }

    private static function getIpAddress(): ?string
    {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            return null;
        }

        return sanitize_text_field(
            wp_unslash($_SERVER['REMOTE_ADDR'])
        );
    }

    private static function getUserAgent(): ?string
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return null;
        }

        return sanitize_text_field(
            wp_unslash($_SERVER['HTTP_USER_AGENT'])
        );
    }

    private function __construct()
    {
    }
}