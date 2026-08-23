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
                        [
                            'mode' =>
                                'payment',

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
                             * Estos datos volverán en el
                             * webhook checkout.session.completed.
                             */
                            'metadata' => [
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
                            ],

                            'line_items' => [
                                [
                                    'quantity' =>
                                        1,

                                    'price_data' => [
                                        'currency' =>
                                            $currency,

                                        'unit_amount' =>
                                            $amountInCents,

                                        'product_data' => [
                                            'name' =>
                                                $this
                                                    ->getProductName(
                                                        $payment
                                                    ),

                                            'description' =>
                                                $this
                                                    ->getProductDescription(
                                                        $payment
                                                    ),
                                        ],
                                    ],
                                ],
                            ],
                        ]
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
        return sprintf(
            'Pago #%d · %s',
            $payment->getId(),
            $payment->getPurpose()
        );
    }
}