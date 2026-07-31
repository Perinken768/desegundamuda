<?php

declare(strict_types=1);

namespace DSM\Clientes\Authentication;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class SessionToken
{
    private const TOKEN_BYTES = 32;

    public static function generate(): string
    {
        try {
            return bin2hex(
                random_bytes(self::TOKEN_BYTES)
            );
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'No se pudo generar un token de sesión seguro.',
                0,
                $exception
            );
        }
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function isValidFormat(string $token): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }

    private function __construct()
    {
    }
}