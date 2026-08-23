<?php

declare(strict_types=1);

namespace DSM\Multitienda\Application;

use DSM\Suscripciones\Application\CustomerEntitlementService;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class MultistoreAccessService
{
    public function __construct(
        private readonly CustomerEntitlementService $entitlementService =
            new CustomerEntitlementService()
    ) {
    }

    public function hasAccess(
        int $customerId
    ): bool {
        if ($customerId <= 0) {
            return false;
        }

        try {
            return $this->entitlementService
                ->hasMultistore(
                    $customerId
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
                'El identificador del cliente no es válido.'
            );
        }

        if (
            !$this->hasAccess(
                $customerId
            )
        ) {
            throw new RuntimeException(
                'El cliente no tiene acceso activo a Multitienda.'
            );
        }
    }
}
