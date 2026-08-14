<?php

declare(strict_types=1);

namespace DSM\Promocionar\Integration;

use DSM\Pagos\Payment\Payment;
use DSM\Promocionar\Application\CreatePromotionWalletFromPlan;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PaymentPromotionIntegration
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
            !== 'promotion'
        ) {
            return;
        }

        if (
            $payment->getSourceType()
            !== 'promotion_plan'
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
                new CreatePromotionWalletFromPlan();

            $useCase->execute(
                customerId:
                    $payment->getCustomerId(),

                planId:
                    $planId,

                sourceType:
                    'payment',

                sourceReference:
                    'payment-'
                    . $payment->getId(),

                paymentId:
                    $payment->getId()
            );
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Promocionar] No se pudo crear el saldo del pago %d: %s',
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
