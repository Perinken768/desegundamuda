<?php

declare(strict_types=1);

namespace DSM\Mfa\Integration;

use DSM\Clientes\Customer\Customer;
use DSM\Mfa\Challenge\MfaChallengeRepository;
use DSM\Mfa\Challenge\MfaChallengeService;
use DSM\Mfa\Mail\MfaCodeMailer;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerMfaIntegration
{
    public static function register(): void
    {
        add_filter(
            'dsm_customer_login_mfa_redirect',
            [
                self::class,
                'createChallenge',
            ],
            10,
            5
        );
    }

    public static function createChallenge(
        mixed $redirect,
        mixed $customer,
        mixed $redirectTo,
        mixed $ipAddress = null,
        mixed $userAgent = null
    ): string {
        if (!$customer instanceof Customer) {
            return is_string($redirect)
                ? $redirect
                : '';
        }

        $repository =
            new MfaChallengeRepository();

        $service =
            new MfaChallengeService(
                $repository
            );

        $result =
            $service->create(
                'customer',
                (string) $customer->getId(),
                $customer->getEmail()
            );

        $challenge =
            $result['challenge'];

        try {
            $mailer =
                new MfaCodeMailer();

            $mailer->send(
                $challenge->getEmail(),
                $result['code']
            );
        } catch (Throwable $exception) {
            /*
             * Si el correo falla, no dejamos un desafío
             * pendiente que el usuario nunca podrá resolver.
             */
            $repository->delete(
                $challenge->getToken()
            );

            throw $exception;
        }

        $requestedRedirect =
            is_string(
                $redirectTo
            )
                ? trim(
                    $redirectTo
                )
                : '';

        if ($requestedRedirect === '') {
            $requestedRedirect =
                home_url(
                    '/mi-cuenta/'
                );
        }

        $safeRedirect =
            wp_validate_redirect(
                $requestedRedirect,
                home_url(
                    '/mi-cuenta/'
                )
            );

        if ($safeRedirect === '') {
            $safeRedirect =
                home_url(
                    '/mi-cuenta/'
                );
        }

        return add_query_arg(
            [
                'token' =>
                    $challenge->getToken(),

                'redirect_to' =>
                    $safeRedirect,
            ],
            home_url(
                '/verificar-acceso/'
            )
        );
    }

    private function __construct()
    {
    }
}
