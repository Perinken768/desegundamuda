<?php

declare(strict_types=1);

namespace DSM\Multitienda\Support;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerContext
{
    /**
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
     * @return array<string, mixed>
     */
    public static function requireCurrentActive(): array
    {
        $context =
            self::current();

        if ($context === null) {
            throw new RuntimeException(
                'Debes iniciar sesión para acceder a Multitienda.'
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
