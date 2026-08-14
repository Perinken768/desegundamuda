<?php

declare(strict_types=1);

namespace DSM\Pagos\Payment;

if (!defined('ABSPATH')) {
    exit;
}

final class PaymentStatus
{
    public const PENDING =
        'pending';

    public const PAID =
        'paid';

    public const FAILED =
        'failed';

    public const CANCELLED =
        'cancelled';

    public static function isValid(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::PENDING,
                self::PAID,
                self::FAILED,
                self::CANCELLED,
            ],
            true
        );
    }

    private function __construct()
    {
    }
}
