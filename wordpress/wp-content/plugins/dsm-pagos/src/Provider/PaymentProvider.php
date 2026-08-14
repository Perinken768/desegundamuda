<?php

declare(strict_types=1);

namespace DSM\Pagos\Provider;

use DSM\Pagos\Payment\Payment;

if (!defined('ABSPATH')) {
    exit;
}

interface PaymentProvider
{
    public function getCode(): string;

    public function getName(): string;

    /**
     * El administrador ha habilitado el proveedor.
     */
    public function isEnabled(): bool;

    /**
     * El proveedor tiene todos los datos mínimos
     * necesarios para funcionar.
     */
    public function isConfigured(): bool;

    /**
     * Solo puede mostrarse en checkout cuando está
     * habilitado y configurado.
     */
    public function isAvailable(): bool;

    public function createCheckoutUrl(
        Payment $payment,
        string $successUrl,
        string $cancelUrl
    ): string;
}