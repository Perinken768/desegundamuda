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

final class SubscriptionCancelController
{
    public const ACTION =
        'dsm_subscription_cancel_renewal';

    public const NONCE_FIELD =
        'dsm_subscription_cancel_nonce';

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
                    'Esta suscripción no puede cancelarse desde Stripe.'
                );
            }

            if (
                $subscription
                    ->hasCancellationRequested()
                && !$subscription
                    ->isAutoRenew()
            ) {
                self::redirectToPlans(
                    [
                        'subscription_status' =>
                            'cancellation_requested',
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
             * Primero Stripe.
             *
             * Solo modificamos DSM si Stripe confirma
             * correctamente el cambio.
             */
            $stripe =
                new StripeSubscriptionManager();

            $stripe
                ->scheduleCancellationAtPeriodEnd(
                    $providerSubscriptionId
                );

            /*
             * IMPORTANTE:
             *
             * status sigue siendo active.
             * ends_at no cambia.
             * Solo detenemos la renovación automática.
             */
            $repository
                ->requestCancellationAtPeriodEnd(
                    $subscription->getId()
                );

            self::redirectToPlans(
                [
                    'subscription_status' =>
                        'cancellation_requested',
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
                'No puedes cancelar una suscripción durante una sesión administrativa temporal.'
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
