<?php

declare(strict_types=1);

namespace DSM\Pagos\Provider;

use DSM\Pagos\Payment\Payment;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class PayPalPaymentProvider implements PaymentProvider
{
    public function getCode(): string
    {
        return 'paypal';
    }

    public function getName(): string
    {
        return 'PayPal';
    }

    public function isEnabled(): bool
    {
        return (bool) get_option(
            'dsm_pagos_provider_paypal_enabled',
            false
        );
    }

    public function isConfigured(): bool
    {
        $mode =
            (string) get_option(
                'dsm_pagos_provider_paypal_mode',
                'sandbox'
            );

        $clientId =
            trim(
                (string) get_option(
                    'dsm_pagos_provider_paypal_client_id',
                    ''
                )
            );

        $clientSecret =
            trim(
                (string) get_option(
                    'dsm_pagos_provider_paypal_client_secret',
                    ''
                )
            );

        $webhookId =
            trim(
                (string) get_option(
                    'dsm_pagos_provider_paypal_webhook_id',
                    ''
                )
            );

        return in_array(
            $mode,
            [
                'sandbox',
                'live',
            ],
            true
        )
            && $clientId !== ''
            && $clientSecret !== ''
            && $webhookId !== '';
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
            'PayPal todavía no está conectado con su API.'
        );
    }
}