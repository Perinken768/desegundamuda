<?php

declare(strict_types=1);

namespace DSM\Pagos\Provider;

use RuntimeException;
use Stripe\StripeClient;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class StripeSubscriptionManager
{
    public function scheduleCancellationAtPeriodEnd(
        string $subscriptionId
    ): void {
        $subscriptionId =
            $this->validateSubscriptionId(
                $subscriptionId
            );

        try {
            $stripe =
                new StripeClient(
                    $this->getSecretKey()
                );

            $subscription =
                $stripe
                    ->subscriptions
                    ->update(
                        $subscriptionId,
                        [
                            'cancel_at_period_end' =>
                                true,
                        ]
                    );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo cancelar la renovación en Stripe: %s',
                    $exception->getMessage()
                ),
                0,
                $exception
            );
        }

        if (
            !isset(
                $subscription
                    ->cancel_at_period_end
            )
            || !$subscription
                ->cancel_at_period_end
        ) {
            throw new RuntimeException(
                'Stripe no confirmó la cancelación al final del período.'
            );
        }
    }

    public function reactivateRenewal(
        string $subscriptionId
    ): void {
        $subscriptionId =
            $this->validateSubscriptionId(
                $subscriptionId
            );

        try {
            $stripe =
                new StripeClient(
                    $this->getSecretKey()
                );

            $subscription =
                $stripe
                    ->subscriptions
                    ->update(
                        $subscriptionId,
                        [
                            'cancel_at_period_end' =>
                                false,
                        ]
                    );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo reactivar la renovación en Stripe: %s',
                    $exception->getMessage()
                ),
                0,
                $exception
            );
        }

        if (
            isset(
                $subscription
                    ->cancel_at_period_end
            )
            && $subscription
                ->cancel_at_period_end
        ) {
            throw new RuntimeException(
                'Stripe no confirmó la reactivación de la renovación.'
            );
        }
    }

    private function validateSubscriptionId(
        string $subscriptionId
    ): string {
        $subscriptionId =
            trim(
                $subscriptionId
            );

        if ($subscriptionId === '') {
            throw new RuntimeException(
                'No se indicó una suscripción Stripe válida.'
            );
        }

        if (
            !str_starts_with(
                $subscriptionId,
                'sub_'
            )
        ) {
            throw new RuntimeException(
                'El identificador de suscripción Stripe no es válido.'
            );
        }

        return $subscriptionId;
    }

    private function getSecretKey(): string
    {
        $secretKey =
            trim(
                (string) get_option(
                    'dsm_pagos_provider_stripe_secret_key',
                    ''
                )
            );

        if ($secretKey === '') {
            throw new RuntimeException(
                'No se encontró la clave secreta de Stripe.'
            );
        }

        return $secretKey;
    }
}
