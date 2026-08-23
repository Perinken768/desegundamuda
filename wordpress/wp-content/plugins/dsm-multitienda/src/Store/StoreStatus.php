<?php

declare(strict_types=1);

namespace DSM\Multitienda\Store;

if (!defined('ABSPATH')) {
    exit;
}

final class StoreStatus
{
    public const DRAFT =
        'draft';

    public const ACTIVE =
        'active';

    public const HIDDEN =
        'hidden';

    public const SUSPENDED =
        'suspended';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::DRAFT,
            self::ACTIVE,
            self::HIDDEN,
            self::SUSPENDED,
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

    private function __construct()
    {
    }
}
