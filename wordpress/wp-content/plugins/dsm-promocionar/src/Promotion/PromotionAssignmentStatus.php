<?php

declare(strict_types=1);

namespace DSM\Promocionar\Promotion;

if (!defined('ABSPATH')) {
    exit;
}

final class PromotionAssignmentStatus
{
    public const ACTIVE =
        'active';

    public const STOPPED =
        'stopped';

    public const EXHAUSTED =
        'exhausted';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::ACTIVE,
            self::STOPPED,
            self::EXHAUSTED,
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