<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Frontend;

use DSM\Ofertas\Application\ResolveOffer;
use DSM\Ofertas\Offer\OfferCheckoutIntentRepository;
use DSM\Pagos\Application\CreateSubscriptionPayment;
use DSM\Suscripciones\Subscription\SubscriptionPlanRepository;
use DSM\Suscripciones\Subscription\SubscriptionRepository;
use DSM\Suscripciones\Support\CustomerContext;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionPurchaseController
{
    public const ACTION =
        'dsm_subscription_purchase';

    public const NONCE_FIELD =
        'dsm_subscription_purchase_nonce';

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

        $planId =
            isset($_POST['plan_id'])
                ? absint(
                    wp_unslash(
                        (string)
                        $_POST['plan_id']
                    )
                )
                : 0;

        check_admin_referer(
            self::getNonceAction(
                $planId
            ),
            self::NONCE_FIELD
        );

        try {
            CustomerContext::requireActive(
                $customerId
            );

            if ($planId <= 0) {
                throw new RuntimeException(
                    'El identificador del plan no es válido.'
                );
            }

            $planRepository =
                new SubscriptionPlanRepository();

            $plan =
                $planRepository
                    ->findById(
                        $planId
                    );

            if ($plan === null) {
                throw new RuntimeException(
                    'No se encontró el plan de suscripción.'
                );
            }

            if (!$plan->isActive()) {
                throw new RuntimeException(
                    'El plan de suscripción no está disponible.'
                );
            }

            if ($plan->isFree()) {
                throw new RuntimeException(
                    'El plan Free está incluido y no necesita contratación.'
                );
            }

            if ($plan->getPrice() <= 0) {
                throw new RuntimeException(
                    'El plan seleccionado no tiene un precio válido.'
                );
            }

            $subscriptionRepository =
                new SubscriptionRepository();

            $existingSubscription =
                $subscriptionRepository
                    ->findActiveByCustomerAndPlan(
                        $customerId,
                        $planId
                    );

            if ($existingSubscription !== null) {
                throw new RuntimeException(
                    'Ya tienes una suscripción activa a este plan.'
                );
            }

            /*
             * =================================================
             * DSM OFERTAS
             * =================================================
             */

            $offerResolution =
                (
                    new ResolveOffer()
                )->execute(
                    $customerId,
                    $planId,
                    'new_subscription'
                );

            $paymentAmount =
                $plan->getPrice();

            /*
             * En un periodo gratis hoy se cobran 0 €.
             *
             * Stripe seguirá recibiendo posteriormente
             * el precio recurrente real del plan.
             */
            if (
                $offerResolution !== null
                && $offerResolution
                    ->isFreePeriod()
            ) {
                $paymentAmount =
                    0.00;
            }

            /*
             * Los descuentos temporales se conectarán
             * posteriormente mediante Stripe Subscription
             * Schedules.
             *
             * No los aplicamos incorrectamente como un
             * descuento permanente.
             */
            if (
                $offerResolution !== null
                && !$offerResolution
                    ->isFreePeriod()
            ) {
                throw new RuntimeException(
                    'Esta modalidad de descuento todavía no está habilitada para contratación automática.'
                );
            }

            $useCase =
                new CreateSubscriptionPayment();

            $payment =
                $useCase->execute(
                    customerId:
                        $customerId,

                    planId:
                        $plan->getId(),

                    planCode:
                        $plan->getCode(),

                    amount:
                        $paymentAmount,

                    currency:
                        $plan->getCurrency()
                );

            /*
             * La oferta todavía NO se consume.
             *
             * Solo guardamos la intención asociada
             * al Payment.
             */
            if ($offerResolution !== null) {
                (
                    new OfferCheckoutIntentRepository()
                )->create(
                    $payment->getId(),
                    $customerId,
                    $offerResolution,
                    'new_subscription'
                );
            }

            self::redirectToCheckout(
                $payment->getId()
            );
        } catch (Throwable $exception) {
            self::redirectToPlans(
                [
                    'subscription_status' =>
                        'purchase_error',

                    'subscription_error' =>
                        $exception
                            ->getMessage(),
                ]
            );
        }
    }

    public static function getNonceAction(
        int $planId
    ): string {
        return self::ACTION
            . '_'
            . $planId;
    }

    private static function redirectToLogin(): never
    {
        $loginUrl =
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
            );

        wp_safe_redirect(
            $loginUrl
        );

        exit;
    }

    private static function redirectToCheckout(
        int $paymentId
    ): never {
        $url =
            add_query_arg(
                [
                    'payment_id' =>
                        $paymentId,
                ],
                home_url(
                    '/checkout-pago/'
                )
            );

        wp_safe_redirect(
            $url
        );

        exit;
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private static function redirectToPlans(
        array $arguments = []
    ): never {
        $url =
            add_query_arg(
                $arguments,
                home_url(
                    '/suscripciones/'
                )
            );

        wp_safe_redirect(
            $url
        );

        exit;
    }

    private function __construct()
    {
    }
}
