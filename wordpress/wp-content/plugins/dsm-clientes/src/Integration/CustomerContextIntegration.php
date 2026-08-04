<?php

declare(strict_types=1);

namespace DSM\Clientes\Integration;

use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Profile\CustomerProfileRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Expone el cliente autenticado al resto de módulos DSM.
 *
 * Los demás plugins no necesitan conocer las clases internas
 * de autenticación, sesión, clientes o perfiles.
 */
final class CustomerContextIntegration
{
    public static function register(): void
    {
        add_filter(
            'dsm_current_customer_context',
            [self::class, 'resolve'],
            10,
            1
        );
    }

    /**
     * @param mixed $currentContext
     *
     * @return array<string, mixed>|null
     */
    public static function resolve(
        mixed $currentContext
    ): ?array {
        /*
         * Respeta un contexto ya aportado por otra integración
         * con una prioridad anterior.
         */
        if (is_array($currentContext)) {
            return $currentContext;
        }

        try {
            $authenticatedCustomer =
                new AuthenticatedCustomer(
                    new CustomerSessionRepository(),
                    new CustomerRepository()
                );

            $customer =
                $authenticatedCustomer->resolve();

            if ($customer === null) {
                return null;
            }

            $profileRepository =
                new CustomerProfileRepository();

            $profile =
                $profileRepository
                    ->findByCustomerId(
                        $customer->getId()
                    );

            return [
                'id' =>
                    $customer->getId(),

                'email' =>
                    $customer->getEmail(),

                'status' =>
                    $customer->getStatus(),

                'display_name' =>
                    $profile?->getDisplayName()
                    ?? '',

                'island_id' =>
                    $profile?->getIslandId(),

                'municipality_id' =>
                    $profile?->getMunicipalityId(),

                'avatar_attachment_id' =>
                    $profile?->getAvatarAttachmentId(),
            ];
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] No se pudo construir el contexto del cliente: '
                . $exception->getMessage()
            );

            return null;
        }
    }

    private function __construct()
    {
    }
}