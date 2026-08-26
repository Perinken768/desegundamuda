<?php

declare(strict_types=1);

namespace DSM\Mfa\Frontend;

use DSM\Clientes\Application\CreateCustomerLoginSession;
use DSM\Clientes\Authentication\CustomerCookie;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Mfa\Challenge\MfaChallenge;
use DSM\Mfa\Challenge\MfaChallengeRepository;
use DSM\Mfa\Challenge\MfaChallengeService;
use DSM\Mfa\Mail\MfaCodeMailer;
use DSM\Mfa\Support\TemplateRenderer;
use RuntimeException;
use Throwable;
use WP_User;

if (!defined('ABSPATH')) {
    exit;
}

final class MfaChallengeController
{
    private const QUERY_VAR =
        'dsm_mfa_verify';

    public static function register(): void
    {
        add_action(
            'init',
            [
                self::class,
                'registerRewrite',
            ]
        );

        add_filter(
            'query_vars',
            [
                self::class,
                'registerQueryVar',
            ]
        );

        add_action(
            'template_redirect',
            [
                self::class,
                'renderChallenge',
            ]
        );

        add_action(
            'admin_post_nopriv_dsm_mfa_verify',
            [
                self::class,
                'handleVerify',
            ]
        );

        add_action(
            'admin_post_dsm_mfa_verify',
            [
                self::class,
                'handleVerify',
            ]
        );

        add_action(
            'admin_post_nopriv_dsm_mfa_resend',
            [
                self::class,
                'handleResend',
            ]
        );

        add_action(
            'admin_post_dsm_mfa_resend',
            [
                self::class,
                'handleResend',
            ]
        );

        add_action(
            'wp_enqueue_scripts',
            [
                self::class,
                'enqueueAssets',
            ]
        );
    }

    public static function registerRewrite(): void
    {
        add_rewrite_rule(
            '^verificar-acceso/?$',
            'index.php?'
            . self::QUERY_VAR
            . '=1',
            'top'
        );
    }

    public static function registerQueryVar(
        array $queryVars
    ): array {
        $queryVars[] =
            self::QUERY_VAR;

        return $queryVars;
    }

    public static function renderChallenge(): void
    {
        if (
            (string) get_query_var(
                self::QUERY_VAR
            ) !== '1'
        ) {
            return;
        }

        $token =
            isset($_GET['token'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_GET['token']
                    )
                )
                : '';

        $redirectTo =
            self::getSafeRedirect();

        $repository =
            new MfaChallengeRepository();

        $service =
            new MfaChallengeService(
                $repository
            );

        $challenge =
            $token !== ''
                ? $service->find(
                    $token
                )
                : null;

        $error =
            isset($_GET['mfa_error'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'mfa_error'
                        ]
                    )
                )
                : '';

        $sent =
            isset($_GET['mfa_sent'])
            && sanitize_key(
                wp_unslash(
                    (string) $_GET[
                        'mfa_sent'
                    ]
                )
            ) === '1';

        status_header(200);

        echo TemplateRenderer::render(
            'challenge/verify.php',
            [
                'token' =>
                    $token,

                'challenge' =>
                    $challenge,

                'redirectTo' =>
                    $redirectTo,

                'error' =>
                    $error,

                'sent' =>
                    $sent,
            ]
        );

        exit;
    }

    public static function handleVerify(): never
    {
        $token =
            isset($_POST['token'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[
                            'token'
                        ]
                    )
                )
                : '';

        $code =
            isset($_POST['code'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[
                            'code'
                        ]
                    )
                )
                : '';

        $redirectTo =
            self::getSafeRedirect(
                $_POST['redirect_to']
                    ?? null
            );

        check_admin_referer(
            'dsm_mfa_verify_'
            . $token,
            'dsm_mfa_nonce'
        );

        try {
            $repository =
                new MfaChallengeRepository();

            $service =
                new MfaChallengeService(
                    $repository
                );

            /*
             * Debemos conservar los datos del desafío
             * antes de verificarlo porque un código
             * correcto elimina el desafío.
             */
            $challenge =
                $service->find(
                    $token
                );

            if (
                !$challenge
                    instanceof MfaChallenge
            ) {
                throw new RuntimeException(
                    'El desafío MFA no está disponible.'
                );
            }

            $context =
                $challenge->getContext();

            if (
                !in_array(
                    $context,
                    [
                        'customer',
                        'wordpress',
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'El contexto MFA no es válido.'
                );
            }

            $identifier =
                (int) $challenge
                    ->getIdentifier();

            if ($identifier <= 0) {
                throw new RuntimeException(
                    'El identificador MFA no es válido.'
                );
            }

            if (
                !$service->verify(
                    $token,
                    $code
                )
            ) {
                self::redirectError(
                    $token,
                    $redirectTo,
                    'invalid_code'
                );
            }

            if ($context === 'customer') {
                self::completeCustomerLogin(
                    $identifier
                );
            } else {
                self::completeWordPressLogin(
                    $identifier
                );
            }

            wp_safe_redirect(
                $redirectTo
            );

            exit;
        } catch (Throwable $exception) {
            error_log(
                '[DSM MFA] Error completando autenticación MFA: '
                . $exception->getMessage()
                . ' | '
                . $exception->getFile()
                . ':'
                . $exception->getLine()
            );

            self::redirectError(
                $token,
                $redirectTo,
                'challenge_error'
            );
        }
    }

    public static function handleResend(): never
    {
        $token =
            isset($_POST['token'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[
                            'token'
                        ]
                    )
                )
                : '';

        $redirectTo =
            self::getSafeRedirect(
                $_POST['redirect_to']
                    ?? null
            );

        check_admin_referer(
            'dsm_mfa_resend_'
            . $token,
            'dsm_mfa_resend_nonce'
        );

        try {
            $repository =
                new MfaChallengeRepository();

            $service =
                new MfaChallengeService(
                    $repository
                );

            /*
             * Conservamos el desafío actual antes de
             * generar el nuevo código.
             *
             * Si el envío por correo falla podremos
             * restaurarlo, manteniendo válido el código
             * anterior.
             */
            $previousChallenge =
                $service->find(
                    $token
                );

            if (
                !$previousChallenge
                    instanceof MfaChallenge
            ) {
                throw new RuntimeException(
                    'El desafío MFA ya no está disponible.'
                );
            }

            $result =
                $service->resend(
                    $token
                );

            $challenge =
                $result[
                    'challenge'
                ];

            $code =
                $result[
                    'code'
                ];

            try {
                $mailer =
                    new MfaCodeMailer();

                $mailer->send(
                    $challenge->getEmail(),
                    $code
                );
            } catch (Throwable $mailException) {
                /*
                 * El nuevo código se había guardado ya.
                 * Como el correo no llegó, recuperamos
                 * el desafío anterior.
                 */
                $repository->save(
                    $previousChallenge
                );

                throw $mailException;
            }

            wp_safe_redirect(
                add_query_arg(
                    [
                        'token' =>
                            $token,

                        'redirect_to' =>
                            $redirectTo,

                        'mfa_sent' =>
                            '1',
                    ],
                    home_url(
                        '/verificar-acceso/'
                    )
                )
            );

            exit;
        } catch (Throwable $exception) {
            error_log(
                '[DSM MFA] Error reenviando código: '
                . $exception->getMessage()
                . ' | '
                . $exception->getFile()
                . ':'
                . $exception->getLine()
            );

            self::redirectError(
                $token,
                $redirectTo,
                'resend_error'
            );
        }
    }

    public static function enqueueAssets(): void
    {
        if (
            (string) get_query_var(
                self::QUERY_VAR
            ) !== '1'
        ) {
            return;
        }

        wp_enqueue_style(
            'dsm-mfa',
            DSM_MFA_URL
            . 'assets/css/mfa.css',
            [],
            DSM_MFA_VERSION
        );
    }

    private static function redirectError(
        string $token,
        string $redirectTo,
        string $error
    ): never {
        wp_safe_redirect(
            add_query_arg(
                [
                    'token' =>
                        $token,

                    'redirect_to' =>
                        $redirectTo,

                    'mfa_error' =>
                        $error,
                ],
                home_url(
                    '/verificar-acceso/'
                )
            )
        );

        exit;
    }

    private static function getSafeRedirect(
        mixed $redirectTo = null
    ): string {
        if ($redirectTo === null) {
            $redirectTo =
                $_GET['redirect_to']
                    ?? '';
        }

        if (!is_string($redirectTo)) {
            $redirectTo = '';
        }

        $redirectTo =
            trim(
                wp_unslash(
                    $redirectTo
                )
            );

        if ($redirectTo === '') {
            return home_url(
                '/mi-cuenta/'
            );
        }

        $safeRedirect =
            wp_validate_redirect(
                $redirectTo,
                home_url(
                    '/mi-cuenta/'
                )
            );

        return $safeRedirect !== ''
            ? $safeRedirect
            : home_url(
                '/mi-cuenta/'
            );
    }

    private static function completeCustomerLogin(
        int $customerId
    ): void {
        $customerRepository =
            new CustomerRepository();

        $customer =
            $customerRepository
                ->findById(
                    $customerId
                );

        if ($customer === null) {
            throw new RuntimeException(
                'No se pudo recuperar la cuenta.'
            );
        }

        $createSession =
            new CreateCustomerLoginSession(
                new CustomerSessionRepository()
            );

        $result =
            $createSession->execute(
                $customer,
                self::getIpAddress(),
                self::getUserAgent()
            );

        $expiresTimestamp =
            strtotime(
                $result
                    ->getSession()
                    ->getExpiresAt()
                . ' UTC'
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

    private static function completeWordPressLogin(
        int $userId
    ): void {
        $user =
            get_user_by(
                'id',
                $userId
            );

        if (!$user instanceof WP_User) {
            throw new RuntimeException(
                'No se pudo recuperar el usuario de WordPress.'
            );
        }

        wp_set_current_user(
            $user->ID
        );

        wp_set_auth_cookie(
            $user->ID,
            false,
            is_ssl()
        );

        do_action(
            'wp_login',
            $user->user_login,
            $user
        );
    }

    private static function getIpAddress(): ?string
    {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            return null;
        }

        return sanitize_text_field(
            wp_unslash(
                $_SERVER['REMOTE_ADDR']
            )
        );
    }

    private static function getUserAgent(): ?string
    {
        if (
            !isset(
                $_SERVER[
                    'HTTP_USER_AGENT'
                ]
            )
        ) {
            return null;
        }

        return sanitize_text_field(
            wp_unslash(
                $_SERVER[
                    'HTTP_USER_AGENT'
                ]
            )
        );
    }

    private function __construct()
    {
    }
}
