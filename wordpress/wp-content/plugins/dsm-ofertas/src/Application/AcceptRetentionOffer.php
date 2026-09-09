<?php

declare(strict_types=1);

namespace DSM\Ofertas\Application;

use DSM\Suscripciones\Subscription\SubscriptionRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class AcceptRetentionOffer
{
    public function execute(
        int $customerId,
        int $subscriptionId
    ): int {
        if (
            $customerId <= 0
            || $subscriptionId <= 0
        ) {
            throw new RuntimeException(
                'El cliente o la suscripción no son válidos.'
            );
        }

        $subscriptionRepository =
            new SubscriptionRepository();

        $subscription =
            $subscriptionRepository
                ->findById(
                    $subscriptionId
                );

        if ($subscription === null) {
            throw new RuntimeException(
                'No se encontró la suscripción.'
            );
        }

        if (
            $subscription->getCustomerId()
            !== $customerId
        ) {
            throw new RuntimeException(
                'La suscripción no pertenece al cliente actual.'
            );
        }

        /*
         * Comprobamos que sigue siendo realmente
         * la suscripción activa del cliente para ese plan.
         */
        $activeSubscription =
            $subscriptionRepository
                ->findActiveByCustomerAndPlan(
                    $customerId,
                    $subscription->getPlanId()
                );

        if (
            $activeSubscription === null
            || $activeSubscription->getId()
                !== $subscriptionId
        ) {
            throw new RuntimeException(
                'La suscripción ya no está activa.'
            );
        }

        $resolution =
            (
                new ResolveOffer()
            )->execute(
                $customerId,
                $subscription->getPlanId(),
                'retention'
            );

        if ($resolution === null) {
            throw new RuntimeException(
                'No existe una oferta de retención disponible.'
            );
        }

        $redemptionId =
            (
                new RedeemOffer()
            )->execute(
                $customerId,
                $resolution,
                $subscriptionId
            );

        do_action(
            'dsm_offer_retention_accepted',
            $redemptionId,
            $subscriptionId,
            $customerId,
            $resolution
        );

        return $redemptionId;
    }
}
