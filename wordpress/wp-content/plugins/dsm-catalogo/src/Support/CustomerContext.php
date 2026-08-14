<?php

declare(strict_types=1);

namespace DSM\Catalogo\Support;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Acceso neutral al contexto de clientes.
 *
 * DSM Catálogo no conoce las clases internas ni las tablas
 * de DSM Clientes. Toda comprobación se realiza mediante
 * su contrato público:
 *
 * dsm_customer_context_by_id
 */
final class CustomerContext
{
    /**
     * Comprueba que el cliente exista y esté activo.
     *
     * @return array<string, mixed>
     */
    public static function requireActive(
        int $customerId
    ): array {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        $context =
            apply_filters(
                'dsm_customer_context_by_id',
                null,
                $customerId
            );

        if (!is_array($context)) {
            throw new RuntimeException(
                'No se encontró el cliente indicado.'
            );
        }

        $resolvedCustomerId =
            max(
                0,
                (int) (
                    $context['id']
                    ?? 0
                )
            );

        if (
            $resolvedCustomerId <= 0
            || $resolvedCustomerId !== $customerId
        ) {
            throw new RuntimeException(
                'El cliente indicado no es válido.'
            );
        }

        $status =
            sanitize_key(
                (string) (
                    $context['status']
                    ?? ''
                )
            );

        if ($status !== 'active') {
            throw new RuntimeException(
                'La cuenta del cliente no está activa.'
            );
        }

        return $context;
    }

    /**
     * Comprueba existencia sin exigir estado activo.
     *
     * Puede resultar útil posteriormente para auditoría
     * histórica de productos.
     *
     * @return array<string, mixed>
     */
    public static function requireExisting(
        int $customerId
    ): array {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        $context =
            apply_filters(
                'dsm_customer_context_by_id',
                null,
                $customerId
            );

        if (!is_array($context)) {
            throw new RuntimeException(
                'No se encontró el cliente indicado.'
            );
        }

        $resolvedCustomerId =
            max(
                0,
                (int) (
                    $context['id']
                    ?? 0
                )
            );

        if (
            $resolvedCustomerId <= 0
            || $resolvedCustomerId !== $customerId
        ) {
            throw new RuntimeException(
                'El cliente indicado no es válido.'
            );
        }

        return $context;
    }

    private function __construct()
    {
    }
}
