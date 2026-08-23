<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Subscription;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionStatus
{
    public const ACTIVE =
        'active';

    public const CANCELLED =
        'cancelled';

    public const EXPIRED =
        'expired';

    public const PENDING =
        'pending';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::ACTIVE,
            self::CANCELLED,
            self::EXPIRED,
            self::PENDING,
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
