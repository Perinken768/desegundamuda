<?php

declare(strict_types=1);

namespace DSM\Anuncios\Advertisement;

if (!defined('ABSPATH')) {
    exit;
}

final class AdvertisementStatus
{
    public const DRAFT =
        'draft';

    public const PENDING =
        'pending';

    public const ACTIVE =
        'active';

    public const RESERVED =
        'reserved';

    public const CLOSED =
        'closed';

    public const REJECTED =
        'rejected';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::DRAFT,
            self::PENDING,
            self::ACTIVE,
            self::RESERVED,
            self::CLOSED,
            self::REJECTED,
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

    public static function canBeEditedByCustomer(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::DRAFT,
                self::REJECTED,
            ],
            true
        );
    }

    public static function canBeSubmitted(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::DRAFT,
                self::REJECTED,
            ],
            true
        );
    }

    public static function canBePublished(
        string $status
    ): bool {
        return $status === self::PENDING;
    }

    public static function canBeRejected(
        string $status
    ): bool {
        return $status === self::PENDING;
    }

    public static function canBeReserved(
        string $status
    ): bool {
        return $status === self::ACTIVE;
    }

    public static function canBeReleased(
        string $status
    ): bool {
        return $status === self::RESERVED;
    }

    public static function canBeClosed(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::ACTIVE,
                self::RESERVED,
            ],
            true
        );
    }

    public static function canBeDeletedByCustomer(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::DRAFT,
                self::REJECTED,
                self::CLOSED,
            ],
            true
        );
    }

    public static function isPublic(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::ACTIVE,
                self::RESERVED,
            ],
            true
        );
    }

    public static function countsTowardsActiveLimit(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::ACTIVE,
                self::RESERVED,
            ],
            true
        );
    }

    private function __construct()
    {
    }
}