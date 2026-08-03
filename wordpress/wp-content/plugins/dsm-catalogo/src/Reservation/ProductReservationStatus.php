<?php

declare(strict_types=1);

namespace DSM\Catalogo\Reservation;

if (!defined('ABSPATH')) {
    exit;
}

final class ProductReservationStatus
{
    public const ACTIVE =
        'active';

    public const RELEASED =
        'released';

    public const COMPLETED =
        'completed';

    public const CANCELLED =
        'cancelled';

    public const EXPIRED =
        'expired';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::ACTIVE,
            self::RELEASED,
            self::COMPLETED,
            self::CANCELLED,
            self::EXPIRED,
        ];
    }

    public static function isValid(
        string $status
    ): bool {
        return in_array(
            $status,
            self::all(),
            true
        );
    }

    public static function isOpen(
        string $status
    ): bool {
        return $status === self::ACTIVE;
    }

    public static function isClosed(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::RELEASED,
                self::COMPLETED,
                self::CANCELLED,
                self::EXPIRED,
            ],
            true
        );
    }

    public static function canBeReleased(
        string $status
    ): bool {
        return $status === self::ACTIVE;
    }

    public static function canBeCompleted(
        string $status
    ): bool {
        return $status === self::ACTIVE;
    }

    public static function canBeCancelled(
        string $status
    ): bool {
        return $status === self::ACTIVE;
    }

    public static function canExpire(
        string $status
    ): bool {
        return $status === self::ACTIVE;
    }

    public static function returnsStock(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::RELEASED,
                self::CANCELLED,
                self::EXPIRED,
            ],
            true
        );
    }

    public static function consumesPhysicalStock(
        string $status
    ): bool {
        return $status === self::COMPLETED;
    }

    private function __construct()
    {
    }
}