<?php

declare(strict_types=1);

namespace DSM\Ofertas\Integration;

use DSM\Ofertas\Offer\OfferCheckoutIntentRepository;
use DSM\Pagos\Payment\Payment;
use DSM\Suscripciones\Subscription\SubscriptionPlan;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionCheckoutIntegration
{
    private const CHECKOUT_FILTER =
        'dsm_payment_subscription_checkout_data';

    private const PRICE_OVERRIDE_FILTER =
        'dsm_subscription_checkout_price_override_allowed';

    private const PAYMENT_STATUS_FILTER =
        'dsm_stripe_checkout_payment_status_allowed';

    public static function register(): void
    {
        add_filter(
            self::CHECKOUT_FILTER,
            [
                self::class,
                'extendCheckoutData',
            ],
            20,
            2
        );

        add_filter(
            self::PRICE_OVERRIDE_FILTER,
            [
                self::class,
                'allowPriceOverride',
            ],
            10,
            3
        );

        add_filter(
            self::PAYMENT_STATUS_FILTER,
            [
                self::class,
                'allowTrialCheckoutStatus',
            ],
            10,
            5
        );
    }

    public static function allowPriceOverride(
        bool $allowed,
        Payment $payment,
        SubscriptionPlan $plan
    ): bool {
        if ($allowed) {
            return true;
        }

        $intent =
            self::findValidFreeTrialIntent(
                $payment
            );

        if ($intent === null) {
            return false;
        }

        if (
            (int) $intent->plan_id
            !== $plan->getId()
        ) {
            return false;
        }

        if (
            abs(
                (float) $intent->original_price
                - $plan->getPrice()
            ) > 0.00001
        ) {
            return false;
        }

        return abs(
            (float) $intent->final_price
            - $payment->getAmount()
        ) <= 0.00001;
    }

    public static function allowTrialCheckoutStatus(
        bool $allowed,
        string $paymentStatus,
        string $mode,
        Payment $payment,
        object $session
    ): bool {
        if ($allowed) {
            return true;
        }

        if ($mode !== 'subscription') {
            return false;
        }

        if (
            $paymentStatus
            !== 'no_payment_required'
        ) {
            return false;
        }

        $intent =
            self::findValidFreeTrialIntent(
                $payment
            );

        if ($intent === null) {
            return false;
        }

        /*
         * Un checkout gratuito solo es legítimo para
         * nuestra intención de trial si DSM esperaba
         * cobrar exactamente 0 € en ese momento.
         */
        if (
            abs(
                $payment->getAmount()
            ) > 0.00001
        ) {
            return false;
        }

        if (
            abs(
                (float) $intent->final_price
            ) > 0.00001
        ) {
            return false;
        }

        return true;
    }

    public static function extendCheckoutData(
        mixed $checkoutData,
        Payment $payment
    ): mixed {
        if (!is_array($checkoutData)) {
            return $checkoutData;
        }

        $intent =
            self::findValidFreeTrialIntent(
                $payment
            );

        if ($intent === null) {
            return $checkoutData;
        }

        $trialEnd =
            trim(
                (string) (
                    $intent->trial_end
                    ?? ''
                )
            );

        if ($trialEnd === '') {
            return $checkoutData;
        }

        $timestamp =
            strtotime(
                $trialEnd
                . ' UTC'
            );

        if (
            $timestamp === false
            || $timestamp <= time()
        ) {
            return $checkoutData;
        }

        $checkoutData[
            'trial_end'
        ] =
            $timestamp;

        $checkoutData[
            'recurring_amount'
        ] =
            (float)
            $intent->original_price;

        $checkoutData[
            'offer_id'
        ] =
            (int)
            $intent->offer_id;

        $checkoutData[
            'offer_rule_id'
        ] =
            (int)
            $intent->offer_rule_id;

        return $checkoutData;
    }

    private static function findValidFreeTrialIntent(
        Payment $payment
    ): ?object {
        if (
            $payment->getPurpose()
            !== 'subscription'
            || $payment->getSourceType()
                !== 'subscription_plan'
        ) {
            return null;
        }

        $intent =
            (
                new OfferCheckoutIntentRepository()
            )->findByPaymentId(
                $payment->getId()
            );

        if ($intent === null) {
            return null;
        }

        if (
            (string) $intent->status
            !== 'pending'
        ) {
            return null;
        }

        if (
            (string) $intent->benefit_type
            !== 'free_months'
        ) {
            return null;
        }

        if (
            (int) $intent->customer_id
            !== $payment->getCustomerId()
        ) {
            return null;
        }

        if (
            (int) $intent->plan_id
            !== (
                $payment->getSourceId()
                ?? 0
            )
        ) {
            return null;
        }

        if (
            strtoupper(
                (string) $intent->currency
            )
            !== strtoupper(
                $payment->getCurrency()
            )
        ) {
            return null;
        }

        if (
            (int) $intent->duration_months
            <= 0
        ) {
            return null;
        }

        if (
            (float) $intent->original_price
            <= 0
        ) {
            return null;
        }

        if (
            abs(
                (float) $intent->final_price
                - $payment->getAmount()
            ) > 0.00001
        ) {
            return null;
        }

        return $intent;
    }

    private function __construct()
    {
    }
}
