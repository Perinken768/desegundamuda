<?php

declare(strict_types=1);

namespace DSM\Clientes\Customer;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerStatus
{
    public const PENDING = 'pending';
    public const ACTIVE = 'active';
    public const SUSPENDED = 'suspended';
    public const BLOCKED = 'blocked';

    public static function isValid(string $status): bool
    {
        return in_array(
            $status,
            [
                self::PENDING,
                self::ACTIVE,
                self::SUSPENDED,
                self::BLOCKED,
            ],
            true
        );
    }

    private function __construct()
    {
    }
}