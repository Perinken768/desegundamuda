<?php

declare(strict_types=1);

namespace DSM\Promocionar\Promotion;

if (!defined('ABSPATH')) {
    exit;
}

final class PromotionWalletStatus
{
    public const AVAILABLE =
        'available';

    public const IN_USE =
        'in_use';

    public const EXHAUSTED =
        'exhausted';

    public const CANCELLED =
        'cancelled';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::AVAILABLE,
            self::IN_USE,
            self::EXHAUSTED,
            self::CANCELLED,
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