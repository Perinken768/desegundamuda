<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Integration;

use DSM\Pagos\Payment\Payment;
use DSM\Suscripciones\Application\CreateSubscriptionFromPlan;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PaymentSubscriptionIntegration
{
    public static function register(): void
    {
        add_action(
            'dsm_payment_paid',
            [
                self::class,
                'handlePaymentPaid',
            ],
            10,
            1
        );
    }

    public static function handlePaymentPaid(
        mixed $payment
    ): void {
        if (!$payment instanceof Payment) {
            return;
        }

        if (!$payment->isPaid()) {
            return;
        }

        if (
            $payment->getPurpose()
            !== 'subscription'
        ) {
            return;
        }

        if (
            $payment->getSourceType()
            !== 'subscription_plan'
        ) {
            return;
        }

        $planId =
            $payment->getSourceId();

        if (
            $planId === null
            || $planId <= 0
        ) {
            return;
        }

        try {
            $useCase =
                new CreateSubscriptionFromPlan();

            $useCase->execute(
                customerId:
                    $payment->getCustomerId(),

                planId:
                    $planId,

                paymentId:
                    $payment->getId(),

                pricePaid:
                    $payment->getAmount(),

                currency:
                    $payment->getCurrency()
            );
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Suscripciones] No se pudo crear la suscripción del pago %d: %s',
                    $payment->getId(),
                    $exception->getMessage()
                )
            );
        }
    }

    private function __construct()
    {
    }
}
