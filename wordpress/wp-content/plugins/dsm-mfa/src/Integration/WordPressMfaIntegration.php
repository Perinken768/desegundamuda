<?php

declare(strict_types=1);

namespace DSM\Mfa\Integration;

use DSM\Mfa\Challenge\MfaChallengeRepository;
use DSM\Mfa\Challenge\MfaChallengeService;
use DSM\Mfa\Mail\MfaCodeMailer;
use Throwable;
use WP_Error;
use WP_User;

if (!defined('ABSPATH')) {
    exit;
}

final class WordPressMfaIntegration
{
    public const CONTEXT =
        'wordpress';

    public static function register(): void
    {
        add_filter(
            'wp_authenticate_user',
            [
                self::class,
                'requireMfa',
            ],
            100,
            2
        );
    }

    public static function requireMfa(
        WP_User|WP_Error $user,
        string $password
    ): WP_User|WP_Error {
        if (is_wp_error($user)) {
            return $user;
        }

        /*
         * WP-CLI queda fuera del MFA interactivo.
         *
         * Esto además nos permite recuperar el acceso
         * desde terminal si estamos desarrollando.
         */
        if (
            defined('WP_CLI')
            && WP_CLI
        ) {
            return $user;
        }

        /*
         * Solo interceptamos el login interactivo normal
         * de WordPress.
         */
        $scriptName =
            isset($_SERVER['SCRIPT_NAME'])
                ? basename(
                    (string) $_SERVER['SCRIPT_NAME']
                )
                : '';

        if ($scriptName !== 'wp-login.php') {
            return $user;
        }

        $email =
            sanitize_email(
                $user->user_email
            );

        if (
            $user->ID <= 0
            || !is_email($email)
        ) {
            return new WP_Error(
                'dsm_mfa_invalid_user',
                __(
                    'No se pudo preparar la verificación en dos pasos.',
                    'dsm-mfa'
                )
            );
        }

        try {
            $repository =
                new MfaChallengeRepository();

            $service =
                new MfaChallengeService(
                    $repository
                );

            $result =
                $service->create(
                    self::CONTEXT,
                    (string) $user->ID,
                    $email
                );

            $challenge =
                $result['challenge'];

            $mailer =
                new MfaCodeMailer();

            $mailer->send(
                $challenge->getEmail(),
                $result['code']
            );

            $redirectTo =
                isset($_POST['redirect_to'])
                    ? trim(
                        (string) wp_unslash(
                            $_POST['redirect_to']
                        )
                    )
                    : '';

            if ($redirectTo === '') {
                $redirectTo =
                    admin_url();
            }

            $redirectTo =
                wp_validate_redirect(
                    $redirectTo,
                    admin_url()
                );

            $verifyUrl =
                add_query_arg(
                    [
                        'token' =>
                            $challenge->getToken(),

                        'redirect_to' =>
                            $redirectTo,
                    ],
                    home_url(
                        '/verificar-acceso/'
                    )
                );

            /*
             * Detenemos aquí el login nativo.
             *
             * WordPress todavía no ha generado su cookie
             * de autenticación.
             */
            wp_safe_redirect(
                $verifyUrl
            );

            exit;
        } catch (Throwable $exception) {
            error_log(
                '[DSM MFA] Error preparando MFA WordPress: '
                . $exception->getMessage()
            );

            return new WP_Error(
                'dsm_mfa_error',
                __(
                    'No se pudo iniciar la verificación en dos pasos.',
                    'dsm-mfa'
                )
            );
        }
    }

    private function __construct()
    {
    }
}
