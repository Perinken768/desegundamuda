<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Application;

use DateTimeImmutable;
use DateTimeZone;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Suscripciones\Subscription\Subscription;
use DSM\Suscripciones\Subscription\SubscriptionPlanRepository;
use DSM\Suscripciones\Subscription\SubscriptionRepository;
use DSM\Suscripciones\Subscription\SubscriptionStatus;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class GrantSubscription
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
        string $startsAt,
        ?string $endsAt,
        string $reason
    ): Subscription {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
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

        if ($planId <= 0) {
            throw new RuntimeException(
                'El identificador del plan no es válido.'
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
                'El plan Free no necesita una concesión explícita.'
            );
        }

        $reason =
            trim(
                sanitize_text_field(
                    $reason
                )
            );

        if ($reason === '') {
            throw new RuntimeException(
                'Debes indicar el motivo de la concesión.'
            );
        }

        $startsAt =
            trim(
                $startsAt
            );

        if ($startsAt === '') {
            throw new RuntimeException(
                'La fecha de inicio es obligatoria.'
            );
        }

        /*
         * Los campos datetime-local del navegador contienen
         * fecha y hora local, sin zona horaria.
         *
         * Interpretamos el valor usando la zona configurada
         * en WordPress y lo convertimos a UTC antes de
         * almacenarlo.
         */
        $localTimezone =
            wp_timezone();

        $utcTimezone =
            new DateTimeZone(
                'UTC'
            );

        $startDateLocal =
            new DateTimeImmutable(
                $startsAt,
                $localTimezone
            );

        $startDateUtc =
            $startDateLocal
                ->setTimezone(
                    $utcTimezone
                );

        $endDateUtc = null;

        if (
            $endsAt !== null
            && trim($endsAt) !== ''
        ) {
            $endDateLocal =
                new DateTimeImmutable(
                    trim(
                        $endsAt
                    ),
                    $localTimezone
                );

            if (
                $endDateLocal
                <= $startDateLocal
            ) {
                throw new RuntimeException(
                    'La fecha de fin debe ser posterior a la fecha de inicio.'
                );
            }

            $endDateUtc =
                $endDateLocal
                    ->setTimezone(
                        $utcTimezone
                    );
        }

        $existing =
            $this->subscriptionRepository
                ->findActiveByCustomerAndPlan(
                    $customerId,
                    $planId
                );

        if ($existing !== null) {
            throw new RuntimeException(
                'El cliente ya tiene una suscripción activa a este plan.'
            );
        }

        $administratorId =
            get_current_user_id();

        $sourceReference =
            sprintf(
                'admin_%d_%s',
                $administratorId,
                sanitize_title(
                    $reason
                )
            );

        return $this->subscriptionRepository
            ->create(
                customerId:
                    $customerId,

                planId:
                    $planId,

                startsAt:
                    $startDateUtc->format(
                        'Y-m-d H:i:s'
                    ),

                endsAt:
                    $endDateUtc?->format(
                        'Y-m-d H:i:s'
                    ),

                status:
                    SubscriptionStatus::ACTIVE,

                paymentId:
                    null,

                pricePaid:
                    0.00,

                currency:
                    $plan->getCurrency(),

                sourceType:
                    'admin_grant',

                sourceReference:
                    $sourceReference,

                autoRenew:
                    false
            );
    }
}