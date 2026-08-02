<?php

declare(strict_types=1);

namespace DSM\Clientes\Customer;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerStatus
{
    /**
     * Cuenta creada, pero con el correo todavía sin verificar.
     */
    public const PENDING = 'pending';

    /**
     * Cuenta activa y utilizable normalmente.
     */
    public const ACTIVE = 'active';

    /**
     * Cuenta cerrada temporalmente por decisión del cliente.
     */
    public const INACTIVE = 'inactive';

    /**
     * Cuenta suspendida temporalmente por administración.
     */
    public const SUSPENDED = 'suspended';

    /**
     * Cuenta bloqueada por administración.
     */
    public const BLOCKED = 'blocked';

    /**
     * El cliente ha confirmado que quiere eliminar su cuenta,
     * pero todavía se encuentra dentro del periodo de gracia.
     */
    public const DELETION_PENDING = 'deletion_pending';

    public static function isValid(string $status): bool
    {
        return in_array(
            $status,
            self::all(),
            true
        );
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::ACTIVE,
            self::INACTIVE,
            self::SUSPENDED,
            self::BLOCKED,
            self::DELETION_PENDING,
        ];
    }

    /**
     * Indica si una cuenta puede iniciar sesión.
     */
    public static function canAuthenticate(
        string $status
    ): bool {
        return $status === self::ACTIVE
            || $status === self::PENDING;
    }

    /**
     * Indica si el estado ha sido impuesto por administración.
     */
    public static function isAdministrativeRestriction(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::SUSPENDED,
                self::BLOCKED,
            ],
            true
        );
    }

    /**
     * Devuelve una etiqueta legible para administración.
     */
    public static function label(string $status): string
    {
        return match ($status) {
            self::PENDING => 'Pendiente',
            self::ACTIVE => 'Activo',
            self::INACTIVE => 'Inactivo',
            self::SUSPENDED => 'Suspendido',
            self::BLOCKED => 'Bloqueado',
            self::DELETION_PENDING =>
                'Eliminación pendiente',
            default => 'Desconocido',
        };
    }

    private function __construct()
    {
    }
}