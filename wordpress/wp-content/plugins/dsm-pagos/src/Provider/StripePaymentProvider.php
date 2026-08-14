<?php

declare(strict_types=1);

namespace DSM\Pagos\Provider;

use DSM\Pagos\Payment\Payment;
use RuntimeException;

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
        throw new RuntimeException(
            'Stripe todavía no está conectado con su API.'
        );
    }
}