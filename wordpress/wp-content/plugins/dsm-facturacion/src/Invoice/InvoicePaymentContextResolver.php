<?php

declare(strict_types=1);

namespace DSM\Facturacion\Invoice;

use DSM\Pagos\Payment\Payment;
use DSM\Promocionar\Promotion\PromotionPlanRepository;
use DSM\Suscripciones\Subscription\SubscriptionPlanRepository;
use DSM\Suscripciones\Subscription\SubscriptionRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class InvoicePaymentContextResolver
{
    public function __construct()
    {
    }

    /**
     * @return array{
     *     description: string
     * }
     */
    public function resolve(
        Payment $payment
    ): array {
        $context = [
            'description' =>
                $this->resolveDefaultContext(
                    $payment
                ),
        ];

        /*
         * Punto de extensión neutral.
         *
         * Cualquier módulo podrá enriquecer en el futuro
         * el concepto de la factura sin modificar el
         * núcleo de DSM Facturación.
         */
        $filtered =
            apply_filters(
                'dsm_invoice_payment_context',
                $context,
                $payment
            );

        if (!is_array($filtered)) {
            return $context;
        }

        $description =
            trim(
                (string) (
                    $filtered[
                        'description'
                    ]
                    ?? ''
                )
            );

        if ($description === '') {
            $description =
                $context[
                    'description'
                ];
        }

        return [
            'description' =>
                $description,
        ];
    }

    private function resolveDefaultContext(
        Payment $payment
    ): string {
        return match (
            $payment->getPurpose()
        ) {
            'promotion' =>
                $this->resolvePromotion(
                    $payment
                ),

            'subscription' =>
                $this->resolveSubscription(
                    $payment
                ),

            default =>
                $this->fallback(
                    $payment
                ),
        };
    }

    private function resolvePromotion(
        Payment $payment
    ): string {
        $fallback =
            'Servicio de promoción de anuncio';

        if (
            $payment->getSourceType()
            !== 'promotion_plan'
        ) {
            return $fallback;
        }

        $planId =
            (int) (
                $payment->getSourceId()
                ?? 0
            );

        if ($planId <= 0) {
            return $fallback;
        }

        if (
            !class_exists(
                PromotionPlanRepository::class
            )
        ) {
            return $fallback;
        }

        try {
            $plan =
                (
                    new PromotionPlanRepository()
                )->findById(
                    $planId
                );

            if ($plan === null) {
                return $fallback;
            }

            $seconds =
                $plan->getDurationSeconds();

            if (
                $seconds > 0
                && $seconds % DAY_IN_SECONDS === 0
            ) {
                $days =
                    (int) (
                        $seconds
                        / DAY_IN_SECONDS
                    );

                return sprintf(
                    'Promoción de anuncio - %d %s',
                    $days,
                    $days === 1
                        ? 'día'
                        : 'días'
                );
            }

            $name =
                trim(
                    $plan->getName()
                );

            if ($name !== '') {
                return sprintf(
                    'Promoción de anuncio - %s',
                    $name
                );
            }
        } catch (Throwable $exception) {
            error_log(
                '[DSM Facturación] No se pudo resolver el plan de promoción: '
                . $exception->getMessage()
            );
        }

        return $fallback;
    }

    private function resolveSubscription(
        Payment $payment
    ): string {
        if (
            $payment->getSourceType()
            === 'subscription_renewal'
        ) {
            return $this
                ->resolveSubscriptionRenewal(
                    $payment
                );
        }

        return $this
            ->resolveInitialSubscription(
                $payment
            );
    }

    private function resolveInitialSubscription(
        Payment $payment
    ): string {
        $fallback =
            'Suscripción DeSegundaMuda';

        if (
            $payment->getSourceType()
            !== 'subscription_plan'
        ) {
            return $fallback;
        }

        $planId =
            (int) (
                $payment->getSourceId()
                ?? 0
            );

        if ($planId <= 0) {
            return $fallback;
        }

        $planName =
            $this->findSubscriptionPlanName(
                $planId
            );

        if ($planName === null) {
            return $fallback;
        }

        return sprintf(
            'Suscripción %s',
            $planName
        );
    }

    private function resolveSubscriptionRenewal(
        Payment $payment
    ): string {
        $fallback =
            'Renovación de suscripción DeSegundaMuda';

        $subscriptionId =
            (int) (
                $payment->getSourceId()
                ?? 0
            );

        if ($subscriptionId <= 0) {
            return $fallback;
        }

        if (
            !class_exists(
                SubscriptionRepository::class
            )
        ) {
            return $fallback;
        }

        try {
            $subscription =
                (
                    new SubscriptionRepository()
                )->findById(
                    $subscriptionId
                );

            if ($subscription === null) {
                return $fallback;
            }

            $planName =
                $this->findSubscriptionPlanName(
                    $subscription
                        ->getPlanId()
                );

            if ($planName === null) {
                return $fallback;
            }

            return sprintf(
                'Renovación suscripción %s',
                $planName
            );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Facturación] No se pudo resolver la renovación de suscripción: '
                . $exception->getMessage()
            );

            return $fallback;
        }
    }

    private function findSubscriptionPlanName(
        int $planId
    ): ?string {
        if ($planId <= 0) {
            return null;
        }

        if (
            !class_exists(
                SubscriptionPlanRepository::class
            )
        ) {
            return null;
        }

        try {
            $plan =
                (
                    new SubscriptionPlanRepository()
                )->findById(
                    $planId
                );

            if ($plan === null) {
                return null;
            }

            $name =
                trim(
                    $plan->getName()
                );

            return $name !== ''
                ? $name
                : null;
        } catch (Throwable $exception) {
            error_log(
                '[DSM Facturación] No se pudo resolver el plan de suscripción: '
                . $exception->getMessage()
            );

            return null;
        }
    }

    private function fallback(
        Payment $payment
    ): string {
        $purpose =
            trim(
                $payment->getPurpose()
            );

        if ($purpose === '') {
            return 'Servicio DeSegundaMuda';
        }

        return sprintf(
            'Servicio DeSegundaMuda (%s)',
            $purpose
        );
    }
}
