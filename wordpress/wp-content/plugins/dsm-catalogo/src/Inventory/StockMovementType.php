<?php

declare(strict_types=1);

namespace DSM\Catalogo\Inventory;

if (!defined('ABSPATH')) {
    exit;
}

final class StockMovementType
{
    public const INITIAL =
        'initial';

    public const REPLENISHMENT =
        'replenishment';

    public const RESERVATION =
        'reservation';

    public const RESERVATION_RELEASE =
        'reservation_release';

    public const SALE =
        'sale';

    public const ADJUSTMENT =
        'adjustment';

    public const RETURN =
        'return';

    public const CANCELLATION =
        'cancellation';

    public const EXPIRATION =
        'expiration';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::INITIAL,
            self::REPLENISHMENT,
            self::RESERVATION,
            self::RESERVATION_RELEASE,
            self::SALE,
            self::ADJUSTMENT,
            self::RETURN,
            self::CANCELLATION,
            self::EXPIRATION,
        ];
    }

    public static function isValid(
        string $movementType
    ): bool {
        return in_array(
            $movementType,
            self::all(),
            true
        );
    }

    public static function affectsPhysicalStock(
        string $movementType
    ): bool {
        return in_array(
            $movementType,
            [
                self::INITIAL,
                self::REPLENISHMENT,
                self::SALE,
                self::ADJUSTMENT,
                self::RETURN,
            ],
            true
        );
    }

    public static function affectsReservedStock(
        string $movementType
    ): bool {
        return in_array(
            $movementType,
            [
                self::RESERVATION,
                self::RESERVATION_RELEASE,
                self::SALE,
                self::CANCELLATION,
                self::EXPIRATION,
            ],
            true
        );
    }

    public static function isReservationMovement(
        string $movementType
    ): bool {
        return in_array(
            $movementType,
            [
                self::RESERVATION,
                self::RESERVATION_RELEASE,
                self::CANCELLATION,
                self::EXPIRATION,
            ],
            true
        );
    }

    public static function isSale(
        string $movementType
    ): bool {
        return $movementType
            === self::SALE;
    }

    public static function canIncreasePhysicalStock(
        string $movementType
    ): bool {
        return in_array(
            $movementType,
            [
                self::INITIAL,
                self::REPLENISHMENT,
                self::ADJUSTMENT,
                self::RETURN,
            ],
            true
        );
    }

    public static function canDecreasePhysicalStock(
        string $movementType
    ): bool {
        return in_array(
            $movementType,
            [
                self::SALE,
                self::ADJUSTMENT,
            ],
            true
        );
    }

    public static function canIncreaseReservedStock(
        string $movementType
    ): bool {
        return $movementType
            === self::RESERVATION;
    }

    public static function canDecreaseReservedStock(
        string $movementType
    ): bool {
        return in_array(
            $movementType,
            [
                self::RESERVATION_RELEASE,
                self::SALE,
                self::CANCELLATION,
                self::EXPIRATION,
            ],
            true
        );
    }

    private function __construct()
    {
    }
}