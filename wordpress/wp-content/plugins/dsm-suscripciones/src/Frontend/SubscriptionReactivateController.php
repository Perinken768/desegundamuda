<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Frontend;

use DSM\Clientes\Impersonation\CustomerImpersonationCookie;
use DSM\Pagos\Provider\StripeSubscriptionManager;
use DSM\Suscripciones\Subscription\SubscriptionRepository;
use DSM\Suscripciones\Support\CustomerContext;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionReactivateController
{
    public const ACTION =
        'dsm_subscription_reactivate_renewal';

    public const NONCE_FIELD =
        'dsm_subscription_reactivate_nonce';

    public static function register(): void
    {
        add_action(
            'admin_post_'
            . self::ACTION,
            [
                self::class,
                'handle',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::ACTION,
            [
                self::class,
                'handle',
            ]
        );
    }

    public static function handle(): never
    {
        $customerContext =
            CustomerContext::current();

        if ($customerContext === null) {
            self::redirectToLogin();
        }

        $customerId =
            max(
                0,
                (int) (
                    $customerContext['id']
                    ?? 0
                )
            );

        $subscriptionId =
            isset($_POST['subscription_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'subscription_id'
                        ]
                    )
                )
                : 0;

        check_admin_referer(
            self::getNonceAction(
                $subscriptionId
            ),
            self::NONCE_FIELD
        );

        try {
            self::preventImpersonation();

            CustomerContext::requireActive(
                $customerId
            );

            if ($subscriptionId <= 0) {
                throw new RuntimeException(
                    'La suscripción indicada no es válida.'
                );
            }

            $repository =
                new SubscriptionRepository();

            $subscription =
                $repository->findById(
                    $subscriptionId
                );

            if ($subscription === null) {
                throw new RuntimeException(
                    'No se encontró la suscripción.'
                );
            }

            if (
                $subscription->getCustomerId()
                !== $customerId
            ) {
                throw new RuntimeException(
                    'La suscripción no pertenece al cliente actual.'
                );
            }

            if (!$subscription->isActive()) {
                throw new RuntimeException(
                    'La suscripción ya no está activa.'
                );
            }

            if (
                $subscription->getProvider()
                !== 'stripe'
                || !$subscription
                    ->isProviderManaged()
            ) {
                throw new RuntimeException(
                    'Esta suscripción no puede reactivarse desde Stripe.'
                );
            }

            if (
                !$subscription
                    ->hasCancellationRequested()
                && $subscription
                    ->isAutoRenew()
            ) {
                self::redirectToPlans(
                    [
                        'subscription_status' =>
                            'renewal_reactivated',
                    ]
                );
            }

            $providerSubscriptionId =
                $subscription
                    ->getProviderSubscriptionId();

            if ($providerSubscriptionId === null) {
                throw new RuntimeException(
                    'La suscripción no tiene una referencia Stripe.'
                );
            }

            /*
             * Stripe debe confirmar primero que se ha
             * eliminado la cancelación programada.
             */
            $stripe =
                new StripeSubscriptionManager();

            $stripe
                ->reactivateRenewal(
                    $providerSubscriptionId
                );

            $repository
                ->reactivateRenewal(
                    $subscription->getId()
                );

            self::redirectToPlans(
                [
                    'subscription_status' =>
                        'renewal_reactivated',
                ]
            );
        } catch (Throwable $exception) {
            self::redirectToPlans(
                [
                    'subscription_status' =>
                        'subscription_action_error',

                    'subscription_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function getNonceAction(
        int $subscriptionId
    ): string {
        return self::ACTION
            . '_'
            . $subscriptionId;
    }

    private static function preventImpersonation(): void
    {
        if (
            class_exists(
                CustomerImpersonationCookie::class
            )
            && CustomerImpersonationCookie::isActive()
        ) {
            throw new RuntimeException(
                'No puedes reactivar una suscripción durante una sesión administrativa temporal.'
            );
        }
    }

    private static function redirectToLogin(): never
    {
        wp_safe_redirect(
            add_query_arg(
                [
                    'redirect_to' =>
                        home_url(
                            '/suscripciones/'
                        ),
                ],
                home_url(
                    '/iniciar-sesion/'
                )
            )
        );

        exit;
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private static function redirectToPlans(
        array $arguments
    ): never {
        wp_safe_redirect(
            add_query_arg(
                $arguments,
                home_url(
                    '/suscripciones/'
                )
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}
