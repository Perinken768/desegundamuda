<?php

declare(strict_types=1);

namespace DSM\Directos\Application;

use DSM\Suscripciones\Application\CustomerEntitlementService;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectAccessService
{
    private const DIRECTS_FEATURE =
        'directos';

    private const MULTISTORE_FEATURE =
        'multistore';

    public function __construct(
        private readonly CustomerEntitlementService $entitlementService =
            new CustomerEntitlementService()
    ) {
    }

    /**
     * Indica si el cliente puede utilizar DSM Directos.
     */
    public function hasAccess(
        int $customerId
    ): bool {
        if ($customerId <= 0) {
            return false;
        }

        try {
            return $this->entitlementService
                ->hasFeature(
                    $customerId,
                    self::DIRECTS_FEATURE
                );
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Indica si el cliente puede utilizar
     * sus anuncios como artículos del directo.
     *
     * Cualquier cliente con acceso a Directos
     * puede utilizar sus propios anuncios.
     */
    public function canUseAdvertisements(
        int $customerId
    ): bool {
        return $this->hasAccess(
            $customerId
        );
    }

    /**
     * Indica si además puede seleccionar
     * productos y variantes de su inventario.
     */
    public function canUseInventory(
        int $customerId
    ): bool {
        if (!$this->hasAccess($customerId)) {
            return false;
        }

        try {
            return $this->entitlementService
                ->hasFeature(
                    $customerId,
                    self::MULTISTORE_FEATURE
                );
        } catch (Throwable) {
            return false;
        }
    }

    public function assertHasAccess(
        int $customerId
    ): void {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'No se pudo identificar al cliente.'
            );
        }

        if (!$this->hasAccess($customerId)) {
            throw new RuntimeException(
                'Tu suscripción no incluye acceso a DSM Directos.'
            );
        }
    }
}
