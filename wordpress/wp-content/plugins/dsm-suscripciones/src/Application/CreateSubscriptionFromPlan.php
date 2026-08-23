<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Application;

use DateTimeImmutable;
use DateTimeZone;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Suscripciones\Subscription\Subscription;
use DSM\Suscripciones\Subscription\SubscriptionPlan;
use DSM\Suscripciones\Subscription\SubscriptionPlanRepository;
use DSM\Suscripciones\Subscription\SubscriptionRepository;
use DSM\Suscripciones\Subscription\SubscriptionStatus;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CreateSubscriptionFromPlan
{
    public function __construct(
        private readonly CustomerRepository $customerRepository =
            new CustomerRepository(),

        private readonly SubscriptionPlanRepository $planRepository =
            new SubscriptionPlanRepository(),

        private readonly SubscriptionRepository $subscriptionRepository =
            new SubscriptionRepository()
    ) {
    }

    public function execute(
        int $customerId,
        int $planId,
        int $paymentId,
        float $pricePaid,
        string $currency
    ): Subscription {
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

        if ($paymentId <= 0) {
            throw new RuntimeException(
                'El identificador del pago no es válido.'
            );
        }

        if ($pricePaid < 0) {
            throw new RuntimeException(
                'El importe pagado no puede ser negativo.'
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
                'La moneda no es válida.'
            );
        }

        /*
         * Idempotencia adicional.
         *
         * Si por cualquier motivo el consumidor vuelve
         * a procesar el mismo pago, devolvemos la
         * suscripción ya creada.
         */
        $existingByPayment =
            $this->subscriptionRepository
                ->findByPaymentId(
                    $paymentId
                );

        if ($existingByPayment !== null) {
            return $existingByPayment;
        }

        $customer =
            $this->customerRepository
                ->findById(
                    $customerId
                );

        if ($customer === null) {
            throw new RuntimeException(
                'No se encontró el cliente indicado.'
            );
        }

        if ($customer->getStatus() !== 'active') {
            throw new RuntimeException(
                'El cliente no está activo.'
            );
        }

        $plan =
            $this->planRepository
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
                'El plan de suscripción no está activo.'
            );
        }

        if ($plan->isFree()) {
            throw new RuntimeException(
                'El plan Free no requiere un pago.'
            );
        }

        if (
            strtoupper(
                $plan->getCurrency()
            )
            !== $currency
        ) {
            throw new RuntimeException(
                'La moneda del pago no coincide con la del plan.'
            );
        }

        /*
         * En esta primera versión no permitimos comprar
         * dos veces simultáneamente el mismo plan.
         *
         * Las renovaciones se implementarán después como
         * un flujo explícito.
         */
        $existingActive =
            $this->subscriptionRepository
                ->findActiveByCustomerAndPlan(
                    $customerId,
                    $planId
                );

        if ($existingActive !== null) {
            throw new RuntimeException(
                'El cliente ya tiene una suscripción activa a este plan.'
            );
        }

        $utc =
            new DateTimeZone(
                'UTC'
            );

        $startsAt =
            new DateTimeImmutable(
                'now',
                $utc
            );

        $endsAt =
            $this->calculateEndDate(
                $startsAt,
                $plan
            );

        return $this->subscriptionRepository
            ->create(
                customerId:
                    $customerId,

                planId:
                    $planId,

                startsAt:
                    $startsAt->format(
                        'Y-m-d H:i:s'
                    ),

                endsAt:
                    $endsAt->format(
                        'Y-m-d H:i:s'
                    ),

                status:
                    SubscriptionStatus::ACTIVE,

                paymentId:
                    $paymentId,

                pricePaid:
                    $pricePaid,

                currency:
                    $currency,

                sourceType:
                    'payment',

                sourceReference:
                    'payment-'
                    . $paymentId,

                autoRenew:
                    false
            );
    }

    private function calculateEndDate(
        DateTimeImmutable $startsAt,
        SubscriptionPlan $plan
    ): DateTimeImmutable {
        $count =
            $plan->getBillingIntervalCount();

        if ($count <= 0) {
            throw new RuntimeException(
                'El intervalo del plan no es válido.'
            );
        }

        return match (
            $plan->getBillingInterval()
        ) {
            'day' =>
                $startsAt->modify(
                    sprintf(
                        '+%d day',
                        $count
                    )
                ),

            'week' =>
                $startsAt->modify(
                    sprintf(
                        '+%d week',
                        $count
                    )
                ),

            'month' =>
                $startsAt->modify(
                    sprintf(
                        '+%d month',
                        $count
                    )
                ),

            'year' =>
                $startsAt->modify(
                    sprintf(
                        '+%d year',
                        $count
                    )
                ),

            default =>
                throw new RuntimeException(
                    'El intervalo de facturación del plan no está soportado.'
                ),
        };
    }
}
