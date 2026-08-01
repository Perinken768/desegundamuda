<?php

declare(strict_types=1);

namespace DSM\Clientes\Authentication;

if (!defined('ABSPATH')) {
    exit;
}

final class EmailVerificationToken
{
    public static function generate(): string
    {
        return bin2hex(
            random_bytes(32)
        );
    }

    public static function hash(string $token): string
    {
        return hash(
            'sha256',
            $token
        );
    }

    public static function isValidFormat(string $token): bool
    {
        return preg_match(
            '/^[a-f0-9]{64}$/',
            $token
        ) === 1;
    }

    private function __construct()
    {
    }
}