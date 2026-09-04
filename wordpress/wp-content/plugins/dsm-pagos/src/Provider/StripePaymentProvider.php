<?php

declare(strict_types=1);

namespace DSM\Pagos\Provider;

use DSM\Pagos\Payment\Payment;
use RuntimeException;
use Stripe\StripeClient;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class StripePaymentProvider implements PaymentProvider
{
    private const SUBSCRIPTION_CHECKOUT_FILTER =
        'dsm_payment_subscription_checkout_data';

    public function getCode(): string
    {
        return 'stripe';
    }

    public function getName(): string
    {
        return 'Stripe';
    }

    public function isEnabled(): bool
    {
        return (bool) get_option(
            'dsm_pagos_provider_stripe_enabled',
            false
        );
    }

    public function isConfigured(): bool
    {
        $mode =
            (string) get_option(
                'dsm_pagos_provider_stripe_mode',
                'test'
            );

        $secretKey =
            trim(
                (string) get_option(
                    'dsm_pagos_provider_stripe_secret_key',
                    ''
                )
            );

        $webhookSecret =
            trim(
                (string) get_option(
                    'dsm_pagos_provider_stripe_webhook_secret',
                    ''
                )
            );

        return in_array(
            $mode,
            [
                'test',
                'live',
            ],
            true
        )
            && $secretKey !== ''
            && $webhookSecret !== '';
    }

    public function isAvailable(): bool
    {
        return $this->isEnabled()
            && $this->isConfigured();
    }

    public function createCheckoutUrl(
        Payment $payment,
        string $successUrl,
        string $cancelUrl
    ): string {
        if (!$this->isAvailable()) {
            throw new RuntimeException(
                'Stripe no está disponible.'
            );
        }

        if (!$payment->isPending()) {
            throw new RuntimeException(
                'Solo los pagos pendientes pueden enviarse a Stripe.'
            );
        }

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

        $amountInCents =
            (int) round(
                $payment->getAmount()
                * 100
            );

        if ($amountInCents <= 0) {
            throw new RuntimeException(
                'El importe del pago no es válido para Stripe.'
            );
        }

        $currency =
            strtolower(
                trim(
                    $payment->getCurrency()
                )
            );

        if ($currency === '') {
            throw new RuntimeException(
                'La moneda del pago no es válida.'
            );
        }

        /*
         * Stripe sustituirá literalmente
         * {CHECKOUT_SESSION_ID} al volver al sitio.
         */
        $successUrl =
            add_query_arg(
                [
                    'stripe_session_id' =>
                        '{CHECKOUT_SESSION_ID}',
                ],
                $successUrl
            );

        $metadata =
            $this->buildMetadata(
                $payment
            );

        /*
         * Una suscripción se distingue explícitamente
         * de cualquier otro pago DSM.
         *
         * Promociones, publicidad puntual futura, etc.
         * continúan utilizando mode=payment.
         */
        $isSubscription =
            $this->isSubscriptionPayment(
                $payment
            );

        $sessionData = [
            'mode' =>
                $isSubscription
                    ? 'subscription'
                    : 'payment',

            'success_url' =>
                $successUrl,

            'cancel_url' =>
                $cancelUrl,

            /*
             * Referencia interna DSM.
             */
            'client_reference_id' =>
                'dsm-payment-'
                . $payment->getId(),

            /*
             * Metadata de la Checkout Session.
             *
             * Permite identificar nuestro Payment
             * en checkout.session.completed.
             */
            'metadata' =>
                $metadata,
        ];

        $priceData = [
            'currency' =>
                $currency,

            'unit_amount' =>
                $amountInCents,

            'product_data' => [
                'name' =>
                    $this->getProductName(
                        $payment
                    ),

                'description' =>
                    $this->getProductDescription(
                        $payment
                    ),
            ],
        ];

        if ($isSubscription) {
            $subscriptionData =
                $this->resolveSubscriptionCheckoutData(
                    $payment
                );

            /*
             * En Stripe, el precio debe ser recurrente
             * cuando Checkout trabaja en mode=subscription.
             */
            $priceData['recurring'] = [
                'interval' =>
                    $subscriptionData[
                        'interval'
                    ],

                'interval_count' =>
                    $subscriptionData[
                        'interval_count'
                    ],
            ];

            /*
             * La metadata de subscription_data se copia
             * al objeto Subscription de Stripe.
             *
             * Será esencial para reconocer renovaciones
             * futuras, donde ya no existe la Checkout
             * Session original.
             */
            $sessionData['subscription_data'] = [
                'metadata' => [
                    'dsm_initial_payment_id' =>
                        (string) $payment
                            ->getId(),

                    'dsm_customer_id' =>
                        (string) $payment
                            ->getCustomerId(),

                    'dsm_plan_id' =>
                        (string) $subscriptionData[
                            'plan_id'
                        ],

                    'dsm_plan_code' =>
                        (string) $subscriptionData[
                            'plan_code'
                        ],

                    'dsm_source' =>
                        'desegundamuda',
                ],
            ];
        }

        $sessionData['line_items'] = [
            [
                'quantity' =>
                    1,

                'price_data' =>
                    $priceData,
            ],
        ];

        try {
            $stripe =
                new StripeClient(
                    $secretKey
                );

            $session =
                $stripe
                    ->checkout
                    ->sessions
                    ->create(
                        $sessionData
                    );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    'Stripe no pudo crear la sesión de pago: %s',
                    $exception->getMessage()
                ),
                0,
                $exception
            );
        }

        $checkoutUrl =
            trim(
                (string) (
                    $session->url
                    ?? ''
                )
            );

        if ($checkoutUrl === '') {
            throw new RuntimeException(
                'Stripe no devolvió una URL de checkout.'
            );
        }

        return $checkoutUrl;
    }

    /**
     * @return array<string, string>
     */
    private function buildMetadata(
        Payment $payment
    ): array {
        return [
            'dsm_payment_id' =>
                (string) $payment
                    ->getId(),

            'dsm_customer_id' =>
                (string) $payment
                    ->getCustomerId(),

            'dsm_purpose' =>
                $payment
                    ->getPurpose(),

            'dsm_source_type' =>
                $payment
                    ->getSourceType()
                ?? '',

            'dsm_source_id' =>
                (string) (
                    $payment
                        ->getSourceId()
                    ?? 0
                ),
        ];
    }

    private function isSubscriptionPayment(
        Payment $payment
    ): bool {
        return $payment->getPurpose()
            === 'subscription'
            && $payment->getSourceType()
                === 'subscription_plan';
    }

    /**
     * @return array{
     *     plan_id: int,
     *     plan_code: string,
     *     plan_name: string,
     *     interval: string,
     *     interval_count: int
     * }
     */
    private function resolveSubscriptionCheckoutData(
        Payment $payment
    ): array {
        $data =
            apply_filters(
                self::SUBSCRIPTION_CHECKOUT_FILTER,
                null,
                $payment
            );

        if (!is_array($data)) {
            throw new RuntimeException(
                'No se pudieron obtener los datos recurrentes del plan.'
            );
        }

        $planId =
            isset($data['plan_id'])
                ? (int) $data['plan_id']
                : 0;

        $planCode =
            isset($data['plan_code'])
                ? sanitize_key(
                    (string) $data[
                        'plan_code'
                    ]
                )
                : '';

        $planName =
            isset($data['plan_name'])
                ? trim(
                    (string) $data[
                        'plan_name'
                    ]
                )
                : '';

        $interval =
            isset($data['interval'])
                ? strtolower(
                    trim(
                        (string) $data[
                            'interval'
                        ]
                    )
                )
                : '';

        $intervalCount =
            isset($data['interval_count'])
                ? (int) $data[
                    'interval_count'
                ]
                : 0;

        if ($planId <= 0) {
            throw new RuntimeException(
                'El plan recurrente no contiene un identificador válido.'
            );
        }

        if ($planCode === '') {
            throw new RuntimeException(
                'El plan recurrente no contiene un código válido.'
            );
        }

        if ($planName === '') {
            throw new RuntimeException(
                'El plan recurrente no contiene un nombre válido.'
            );
        }

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
                'El intervalo recurrente no es compatible con Stripe.'
            );
        }

        if ($intervalCount <= 0) {
            throw new RuntimeException(
                'La frecuencia recurrente no es válida.'
            );
        }

        return [
            'plan_id' =>
                $planId,

            'plan_code' =>
                $planCode,

            'plan_name' =>
                $planName,

            'interval' =>
                $interval,

            'interval_count' =>
                $intervalCount,
        ];
    }

    private function getProductName(
        Payment $payment
    ): string {
        $sourceReference =
            trim(
                (string) (
                    $payment
                        ->getSourceReference()
                    ?? ''
                )
            );

        if ($sourceReference !== '') {
            return sprintf(
                'DeSegundaMuda - %s',
                $sourceReference
            );
        }

        return sprintf(
            'DeSegundaMuda - Pago #%d',
            $payment->getId()
        );
    }

    private function getProductDescription(
        Payment $payment
    ): string {
        if (
            $this->isSubscriptionPayment(
                $payment
            )
        ) {
            return sprintf(
                'Suscripción DeSegundaMuda · Pago inicial #%d',
                $payment->getId()
            );
        }

        return sprintf(
            'Pago #%d · %s',
            $payment->getId(),
            $payment->getPurpose()
        );
    }
}
