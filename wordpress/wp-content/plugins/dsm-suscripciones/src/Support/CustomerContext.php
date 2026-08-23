<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Support;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerContext
{
    /**
     * Devuelve el contexto del cliente autenticado.
     *
     * @return array<string, mixed>|null
     */
    public static function current(): ?array
    {
        $context =
            apply_filters(
                'dsm_current_customer_context',
                null
            );

        if (!is_array($context)) {
            return null;
        }

        $customerId =
            max(
                0,
                (int) (
                    $context['id']
                    ?? 0
                )
            );

        if ($customerId <= 0) {
            return null;
        }

        return $context;
    }

    /**
     * Devuelve el cliente autenticado y exige
     * que su cuenta esté activa.
     *
     * @return array<string, mixed>
     */
    public static function requireCurrentActive(): array
    {
        $context =
            self::current();

        if ($context === null) {
            throw new RuntimeException(
                'Debes iniciar sesión para realizar esta acción.'
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
     * Comprueba un cliente concreto mediante
     * la API pública de DSM Clientes.
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

    private function __construct()
    {
    }
}
