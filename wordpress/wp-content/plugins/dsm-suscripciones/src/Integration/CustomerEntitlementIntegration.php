<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Integration;

use DSM\Suscripciones\Application\CustomerEntitlementService;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Expone derechos comerciales de DSM Suscripciones
 * mediante contratos neutrales para otros plugins.
 *
 * De esta forma Publicidad, Multitienda u otros módulos
 * no necesitan conocer cómo se almacenan internamente
 * las suscripciones.
 */
final class CustomerEntitlementIntegration
{
    public static function register(): void
    {
        add_filter(
            'dsm_customer_has_advertising',
            [
                self::class,
                'hasAdvertising',
            ],
            10,
            2
        );
    }

    public static function hasAdvertising(
        mixed $currentValue,
        int $customerId
    ): bool {
        if ($customerId <= 0) {
            return false;
        }

        /*
         * Respetamos un TRUE proporcionado previamente.
         */
        if ($currentValue === true) {
            return true;
        }

        try {
            $service =
                new CustomerEntitlementService();

            return $service->hasAdvertising(
                $customerId
            );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Suscripciones] No se pudo resolver '
                . 'el derecho advertising del cliente '
                . $customerId
                . ': '
                . $exception->getMessage()
            );

            return false;
        }
    }

    private function __construct()
    {
    }
}
