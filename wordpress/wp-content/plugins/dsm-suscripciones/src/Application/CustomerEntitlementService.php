<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Application;

use DSM\Suscripciones\Subscription\SubscriptionPlan;
use DSM\Suscripciones\Subscription\SubscriptionPlanRepository;
use DSM\Suscripciones\Subscription\SubscriptionRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerEntitlementService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository =
            new SubscriptionRepository(),

        private readonly SubscriptionPlanRepository $planRepository =
            new SubscriptionPlanRepository()
    ) {
    }

    /**
     * Indica si un cliente dispone actualmente de una prestación.
     *
     * Ejemplos:
     *
     * multistore
     * advertising
     * max_active_ads
     */
    public function hasFeature(
        int $customerId,
        string $featureKey
    ): bool {
        $featureKey =
            sanitize_key(
                $featureKey
            );

        if (
            $customerId <= 0
            || $featureKey === ''
        ) {
            return false;
        }

        foreach (
            $this->getEffectivePlans(
                $customerId
            )
            as $plan
        ) {
            if (
                !$plan->hasFeature(
                    $featureKey
                )
            ) {
                continue;
            }

            $value =
                $plan->getFeature(
                    $featureKey
                );

            if (
                $value === null
                || $value === ''
                || $value === '0'
                || $value === 'false'
                || $value === 'no'
                || $value === 'off'
            ) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function hasMultistore(
        int $customerId
    ): bool {
        return $this->hasFeature(
            $customerId,
            'multistore'
        );
    }

    public function hasAdvertising(
        int $customerId
    ): bool {
        return $this->hasFeature(
            $customerId,
            'advertising'
        );
    }

    /**
     * Devuelve el máximo de anuncios activos permitidos.
     *
     * -1 = ilimitados.
     */
    public function getMaxActiveAds(
        int $customerId
    ): int {
        if ($customerId <= 0) {
            return 0;
        }

        $limits = [];

        foreach (
            $this->getEffectivePlans(
                $customerId
            )
            as $plan
        ) {
            if (
                !$plan->hasFeature(
                    'max_active_ads'
                )
            ) {
                continue;
            }

            $limit =
                $plan->getFeatureAsInt(
                    'max_active_ads',
                    0
                );

            if ($limit === -1) {
                return -1;
            }

            if ($limit > 0) {
                $limits[] = $limit;
            }
        }

        if ($limits !== []) {
            return max(
                $limits
            );
        }

        /*
         * Si el cliente no tiene ninguna suscripción
         * explícita, aplicamos el plan Free como derecho
         * base de la plataforma.
         */
        $freePlan =
            $this->planRepository
                ->findByCode(
                    'free'
                );

        if (
            $freePlan !== null
            && $freePlan->isActive()
        ) {
            return $freePlan->getFeatureAsInt(
                'max_active_ads',
                0
            );
        }

        return 0;
    }

    /**
     * Devuelve el valor efectivo de una prestación.
     */
    public function getFeatureValue(
        int $customerId,
        string $featureKey,
        ?string $default = null
    ): ?string {
        $featureKey =
            sanitize_key(
                $featureKey
            );

        if (
            $customerId <= 0
            || $featureKey === ''
        ) {
            return $default;
        }

        foreach (
            $this->getEffectivePlans(
                $customerId
            )
            as $plan
        ) {
            if (
                !$plan->hasFeature(
                    $featureKey
                )
            ) {
                continue;
            }

            return $plan->getFeature(
                $featureKey,
                $default
            );
        }

        return $default;
    }

    /**
     * @return array<int, SubscriptionPlan>
     */
    public function getEffectivePlans(
        int $customerId
    ): array {
        if ($customerId <= 0) {
            return [];
        }

        $subscriptions =
            $this->subscriptionRepository
                ->findActiveByCustomerId(
                    $customerId
                );

        $plans = [];

        foreach ($subscriptions as $subscription) {
            $plan =
                $this->planRepository
                    ->findById(
                        $subscription->getPlanId()
                    );

            if (
                $plan === null
                || !$plan->isActive()
            ) {
                continue;
            }

            $plans[$plan->getId()] =
                $plan;
        }

        return array_values(
            $plans
        );
    }
}
