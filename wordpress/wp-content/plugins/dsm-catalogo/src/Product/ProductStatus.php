<?php

declare(strict_types=1);

namespace DSM\Catalogo\Product;

if (!defined('ABSPATH')) {
    exit;
}

final class ProductStatus
{
    public const DRAFT =
        'draft';

    public const ACTIVE =
        'active';

    public const INACTIVE =
        'inactive';

    public const ARCHIVED =
        'archived';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::DRAFT,
            self::ACTIVE,
            self::INACTIVE,
            self::ARCHIVED,
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

    public static function canBeEdited(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::DRAFT,
                self::ACTIVE,
                self::INACTIVE,
            ],
            true
        );
    }

    public static function canBeActivated(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::DRAFT,
                self::INACTIVE,
            ],
            true
        );
    }

    public static function canBeDeactivated(
        string $status
    ): bool {
        return $status === self::ACTIVE;
    }

    public static function canBeArchived(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::DRAFT,
                self::ACTIVE,
                self::INACTIVE,
            ],
            true
        );
    }

    public static function canGenerateAdvertisements(
        string $status
    ): bool {
        return $status === self::ACTIVE;
    }

    public static function canReceiveStock(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::DRAFT,
                self::ACTIVE,
                self::INACTIVE,
            ],
            true
        );
    }

    public static function isVisibleInCatalog(
        string $status
    ): bool {
        return $status === self::ACTIVE;
    }

    public static function isArchived(
        string $status
    ): bool {
        return $status === self::ARCHIVED;
    }

    private function __construct()
    {
    }
}