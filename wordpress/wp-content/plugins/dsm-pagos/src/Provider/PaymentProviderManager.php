<?php

declare(strict_types=1);

namespace DSM\Pagos\Provider;

if (!defined('ABSPATH')) {
    exit;
}

final class PaymentProviderManager
{
    private static ?PaymentProviderRegistry $registry =
        null;

    public static function registry(): PaymentProviderRegistry
    {
        if (self::$registry === null) {
            self::$registry =
                new PaymentProviderRegistry();

            do_action(
                'dsm_payment_register_providers',
                self::$registry
            );
        }

        return self::$registry;
    }

    public static function reset(): void
    {
        self::$registry =
            null;
    }

    private function __construct()
    {
    }
}
