<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Integration;

use DSM\Pagos\Payment\Payment;
use DSM\Suscripciones\Subscription\SubscriptionPlanRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionCheckoutIntegration
{
    private const FILTER =
        'dsm_payment_subscription_checkout_data';

    public static function register(): void
    {
        add_filter(
            self::FILTER,
            [
                self::class,
                'provideCheckoutData',
            ],
            10,
            2
        );
    }

    /**
     * Proporciona a dsm-pagos los datos necesarios
     * para crear una suscripción recurrente.
     *
     * dsm-pagos no necesita conocer directamente
     * el modelo de planes de dsm-suscripciones.
     *
     * @param mixed $checkoutData
     *
     * @return array<string, mixed>|mixed
     */
    public static function provideCheckoutData(
        mixed $checkoutData,
        Payment $payment
    ): mixed {
        /*
         * Solo intervenimos en pagos destinados
         * realmente a contratar una suscripción.
         */
        if (
            $payment->getPurpose()
            !== 'subscription'
        ) {
            return $checkoutData;
        }

        if (
            $payment->getSourceType()
            !== 'subscription_plan'
        ) {
            return $checkoutData;
        }

        $planId =
            $payment->getSourceId()
            ?? 0;

        if ($planId <= 0) {
            throw new RuntimeException(
                'El pago de suscripción no contiene un plan válido.'
            );
        }

        $repository =
            new SubscriptionPlanRepository();

        $plan =
            $repository->findById(
                $planId
            );

        if ($plan === null) {
            throw new RuntimeException(
                'No se encontró el plan asociado al pago.'
            );
        }

        if (!$plan->isActive()) {
            throw new RuntimeException(
                'El plan asociado al pago no está activo.'
            );
        }

        if ($plan->isFree()) {
            throw new RuntimeException(
                'Un plan gratuito no puede enviarse a un checkout recurrente.'
            );
        }

        /*
         * Protección contra manipulaciones o inconsistencias:
         *
         * por defecto, el importe del Payment debe seguir
         * coincidiendo con el precio actual del plan.
         *
         * Otros módulos DSM pueden autorizar una excepción
         * comercial concreta mediante filtro.
         *
         * Ejemplo:
         * DSM Ofertas puede permitir Payment = 0 € cuando
         * existe un periodo gratuito válido y trazable.
         */
        $priceMatches =
            abs(
                $plan->getPrice()
                - $payment->getAmount()
            ) <= 0.00001;

        if (!$priceMatches) {
            $priceOverrideAllowed =
                (bool) apply_filters(
                    'dsm_subscription_checkout_price_override_allowed',
                    false,
                    $payment,
                    $plan
                );

            if (!$priceOverrideAllowed) {
                throw new RuntimeException(
                    'El precio del plan no coincide con el pago pendiente.'
                );
            }
        }

        if (
            strtoupper(
                $plan->getCurrency()
            )
            !== strtoupper(
                $payment->getCurrency()
            )
        ) {
            throw new RuntimeException(
                'La moneda del plan no coincide con el pago pendiente.'
            );
        }

        $interval =
            strtolower(
                trim(
                    $plan->getBillingInterval()
                )
            );

        if (
            !in_array(
                $interval,
                [
                    'day',
                    'week',
                    'month',
                    'year',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'El intervalo de facturación "%s" no es compatible con Stripe.',
                    $interval
                )
            );
        }

        $intervalCount =
            $plan->getBillingIntervalCount();

        if ($intervalCount <= 0) {
            throw new RuntimeException(
                'La frecuencia de facturación del plan no es válida.'
            );
        }

        return [
            'plan_id' =>
                $plan->getId(),

            'plan_code' =>
                $plan->getCode(),

            'plan_name' =>
                $plan->getName(),

            'interval' =>
                $interval,

            'interval_count' =>
                $intervalCount,
        ];
    }

    private function __construct()
    {
    }
}
