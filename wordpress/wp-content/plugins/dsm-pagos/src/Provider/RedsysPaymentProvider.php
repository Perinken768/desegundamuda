<?php

declare(strict_types=1);

namespace DSM\Pagos\Provider;

use DSM\Pagos\Payment\Payment;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class RedsysPaymentProvider implements PaymentProvider
{
    public function getCode(): string
    {
        return 'redsys';
    }

    public function getName(): string
    {
        return 'Redsys';
    }

    public function isEnabled(): bool
    {
        return (bool) get_option(
            'dsm_pagos_provider_redsys_enabled',
            false
        );
    }

    public function isConfigured(): bool
    {
        $mode =
            (string) get_option(
                'dsm_pagos_provider_redsys_mode',
                'test'
            );

        $merchantCode =
            trim(
                (string) get_option(
                    'dsm_pagos_provider_redsys_merchant_code',
                    ''
                )
            );

        $terminal =
            trim(
                (string) get_option(
                    'dsm_pagos_provider_redsys_terminal',
                    ''
                )
            );

        $secretKey =
            trim(
                (string) get_option(
                    'dsm_pagos_provider_redsys_secret_key',
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
            && $merchantCode !== ''
            && $terminal !== ''
            && $secretKey !== '';
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
            'Redsys todavía no está conectado con su API.'
        );
    }
}