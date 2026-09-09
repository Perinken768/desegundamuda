<?php

declare(strict_types=1);

namespace DSM\Ofertas\Integration;

use DSM\Ofertas\Application\RedeemOffer;
use DSM\Ofertas\Offer\OfferCheckoutIntentRepository;
use DSM\Ofertas\Offer\OfferRedemptionRepository;
use DSM\Ofertas\Offer\OfferResolution;
use DSM\Pagos\Payment\Payment;
use DSM\Suscripciones\Subscription\SubscriptionRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class OfferRedemptionIntegration
{
    private const CHECKOUT_COMPLETED_ACTION =
        'dsm_stripe_subscription_checkout_completed';

    public static function register(): void
    {
        add_action(
            self::CHECKOUT_COMPLETED_ACTION,
            [
                self::class,
                'handleCheckoutCompleted',
            ],
            20,
            2
        );
    }

    /**
     * @param array<string, mixed> $stripeData
     */
    public static function handleCheckoutCompleted(
        Payment $payment,
        array $stripeData
    ): void {
        $intentRepository =
            new OfferCheckoutIntentRepository();

        $intent =
            $intentRepository
                ->findByPaymentId(
                    $payment->getId()
                );

        if ($intent === null) {
            return;
        }

        if (
            (string) $intent->status
            === 'completed'
        ) {
            return;
        }

        if (
            (string) $intent->status
            !== 'pending'
        ) {
            throw new RuntimeException(
                'La intención de oferta no está pendiente.'
            );
        }

        if (
            (int) $intent->customer_id
            !== $payment->getCustomerId()
        ) {
            throw new RuntimeException(
                'El cliente de la intención no coincide con el pago.'
            );
        }

        $planId =
            $payment->getSourceId();

        if (
            $planId === null
            || $planId <= 0
            || $planId
                !== (int) $intent->plan_id
        ) {
            throw new RuntimeException(
                'El plan de la intención no coincide con el pago.'
            );
        }

        /*
         * PaymentSubscriptionIntegration ya debe haber creado
         * la suscripción al confirmarse el Payment.
         */
        $subscription =
            (
                new SubscriptionRepository()
            )->findByPaymentId(
                $payment->getId()
            );

        if ($subscription === null) {
            throw new RuntimeException(
                'La suscripción DSM todavía no existe.'
            );
        }

        $redemptionRepository =
            new OfferRedemptionRepository();

        /*
         * Idempotencia ante webhooks repetidos.
         */
        if (
            $redemptionRepository
                ->existsForOfferCustomerPlan(
                    (int) $intent->offer_id,
                    (int) $intent->customer_id,
                    (int) $intent->plan_id
                )
        ) {
            $intentRepository
                ->markCompleted(
                    $payment->getId()
                );

            return;
        }

        $originalPrice =
            round(
                (float)
                $intent->original_price,
                2
            );

        $finalPrice =
            round(
                (float)
                $intent->final_price,
                2
            );

        $resolution =
            new OfferResolution(
                offerId:
                    (int) $intent->offer_id,

                ruleId:
                    (int) $intent->offer_rule_id,

                planId:
                    (int) $intent->plan_id,

                offerCode:
                    'checkout-intent',

                offerName:
                    'Oferta aplicada en checkout',

                scope:
                    'checkout',

                benefitType:
                    (string)
                    $intent->benefit_type,

                benefitValue:
                    (float)
                    $intent->benefit_value,

                durationMonths:
                    $intent->duration_months !== null
                        ? (int)
                            $intent->duration_months
                        : null,

                originalPrice:
                    $originalPrice,

                discountAmount:
                    round(
                        max(
                            0,
                            $originalPrice
                            - $finalPrice
                        ),
                        2
                    ),

                finalPrice:
                    $finalPrice,

                currency:
                    (string)
                    $intent->currency
            );

        (
            new RedeemOffer()
        )->execute(
            (int) $intent->customer_id,
            $resolution,
            $subscription->getId()
        );

        $intentRepository
            ->markCompleted(
                $payment->getId()
            );
    }

    private function __construct()
    {
    }
}
