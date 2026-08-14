<?php

declare(strict_types=1);

namespace DSM\Pagos\Integration;

use DSM\Pagos\Provider\PaymentProviderRegistry;
use DSM\Pagos\Provider\PayPalPaymentProvider;
use DSM\Pagos\Provider\RedsysPaymentProvider;
use DSM\Pagos\Provider\StripePaymentProvider;

if (!defined('ABSPATH')) {
    exit;
}

final class PaymentProviderIntegration
{
    public static function register(): void
    {
        add_action(
            'dsm_payment_register_providers',
            [
                self::class,
                'registerProviders',
            ]
        );
    }

    public static function registerProviders(
        PaymentProviderRegistry $registry
    ): void {
        $registry->register(
            new StripePaymentProvider()
        );

        $registry->register(
            new RedsysPaymentProvider()
        );

        $registry->register(
            new PayPalPaymentProvider()
        );
    }

    private function __construct()
    {
    }
}
