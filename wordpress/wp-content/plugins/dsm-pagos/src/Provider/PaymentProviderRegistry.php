<?php

declare(strict_types=1);

namespace DSM\Pagos\Provider;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registro central de proveedores de pago.
 *
 * Permite añadir proveedores sin acoplar el checkout
 * a implementaciones concretas.
 */
final class PaymentProviderRegistry
{
    /**
     * @var array<string, PaymentProvider>
     */
    private array $providers = [];

    public function register(
        PaymentProvider $provider
    ): void {
        $code =
            sanitize_key(
                $provider->getCode()
            );

        if ($code === '') {
            throw new RuntimeException(
                'El proveedor de pago no tiene un código válido.'
            );
        }

        $this->providers[$code] =
            $provider;
    }

    public function has(
        string $code
    ): bool {
        $code =
            sanitize_key(
                $code
            );

        return isset(
            $this->providers[$code]
        );
    }

    public function get(
        string $code
    ): PaymentProvider {
        $code =
            sanitize_key(
                $code
            );

        if (
            !isset(
                $this->providers[$code]
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'El proveedor de pago "%s" no está registrado.',
                    $code
                )
            );
        }

        return $this->providers[$code];
    }

    /**
     * @return array<string, PaymentProvider>
     */
    public function all(): array
    {
        return $this->providers;
    }

    /**
     * Devuelve únicamente los proveedores que pueden
     * utilizarse actualmente.
     *
     * @return array<string, PaymentProvider>
     */
    public function available(): array
    {
        return array_filter(
            $this->providers,
            static fn (
                PaymentProvider $provider
            ): bool =>
                $provider->isAvailable()
        );
    }
}
