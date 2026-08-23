<?php

declare(strict_types=1);

namespace DSM\Pagos\Application;

use DSM\Pagos\Payment\Payment;
use DSM\Pagos\Payment\PaymentRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CreateSubscriptionPayment
{
    public function __construct(
        private readonly PaymentRepository $paymentRepository =
            new PaymentRepository()
    ) {
    }

    public function execute(
        int $customerId,
        int $planId,
        string $planCode,
        float $amount,
        string $currency
    ): Payment {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($planId <= 0) {
            throw new RuntimeException(
                'El identificador del plan no es válido.'
            );
        }

        $planCode =
            sanitize_key(
                $planCode
            );

        if ($planCode === '') {
            throw new RuntimeException(
                'El código del plan no es válido.'
            );
        }

        if ($amount <= 0) {
            throw new RuntimeException(
                'El importe del plan debe ser superior a cero.'
            );
        }

        $currency =
            strtoupper(
                trim(
                    $currency
                )
            );

        if (
            strlen($currency) !== 3
            || !ctype_alpha($currency)
        ) {
            throw new RuntimeException(
                'La moneda del plan no es válida.'
            );
        }

        return $this->paymentRepository
            ->create(
                customerId:
                    $customerId,

                purpose:
                    'subscription',

                amount:
                    $amount,

                currency:
                    $currency,

                provider:
                    null,

                providerReference:
                    null,

                sourceType:
                    'subscription_plan',

                sourceId:
                    $planId,

                sourceReference:
                    $planCode
            );
    }
}
